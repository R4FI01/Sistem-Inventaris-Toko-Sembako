<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStockUnit;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ProductController extends Controller
{
    public function ProductPage(): View
    {
        return view('pages.dashboard.product-page');
    }

    public function CreateProduct(Request $request)
    {
        $userId = (int) $request->header('id');
        $data = $this->validateProductData($request, true, true);
        $imageUrl = null;

        DB::beginTransaction();

        try {
            $inventoryUnit = Unit::where('id', $data['base_unit_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $imageUrl = $this->storeImage($request->file('img'), $userId);

            $product = Product::create([
                'user_id' => $userId,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'price' => $data['base_unit_price'],
                // Kolom lama tetap disinkronkan agar modul lama tidak membaca angka stok yang berbeda.
                'unit' => '0',
                'base_unit' => $inventoryUnit->symbol,
                'base_unit_id' => $inventoryUnit->id,
                'stock_base' => 0,
                'stock_configuration_status' => 'configured',
                'img_url' => $imageUrl,
            ]);

            $stockUnits = $this->syncProductStockUnits($product, $inventoryUnit, $data['stock_units']);
            $this->syncProductSalesUnits($product, $data['units']);
            $this->createInitialStockMovement($product, $stockUnits, $data, $userId);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan.',
                'product' => $product->fresh(['category:id,name', 'inventoryUnit', 'units', 'stockUnits.unit']),
            ], 201);
        } catch (ValidationException $exception) {
            DB::rollBack();

            if ($imageUrl && File::exists(public_path($imageUrl))) {
                File::delete(public_path($imageUrl));
            }

            throw $exception;
        } catch (Throwable $exception) {
            DB::rollBack();

            if ($imageUrl && File::exists(public_path($imageUrl))) {
                File::delete(public_path($imageUrl));
            }

            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Produk gagal ditambahkan.',
            ], 500);
        }
    }

    public function DeleteProduct(Request $request)
    {
        $userId = (int) $request->header('id');
        $productId = $request->input('id');

        $product = Product::where('id', $productId)
            ->where('user_id', $userId)
            ->first();

        if (!$product) {
            return 0;
        }

        $imageUrl = $product->img_url;

        try {
            $deleted = $product->delete();

            if ($deleted && $imageUrl && File::exists(public_path($imageUrl))) {
                File::delete(public_path($imageUrl));
            }

            return $deleted;
        } catch (Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi atau pergerakan stok.',
            ], 422);
        }
    }

    public function ProductByID(Request $request)
    {
        $userId = (int) $request->header('id');
        $productId = $request->input('id');

        return Product::where('id', $productId)
            ->where('user_id', $userId)
            ->with(['category:id,name', 'inventoryUnit', 'units', 'stockUnits.unit'])
            ->first();
    }

    public function ProductList(Request $request)
    {
        $userId = (int) $request->header('id');

        return Product::where('user_id', $userId)
            ->with(['category:id,name', 'inventoryUnit', 'units', 'stockUnits.unit'])
            ->orderByDesc('id')
            ->get();
    }

    public function UpdateProduct(Request $request)
    {
        $userId = (int) $request->header('id');
        $productId = (int) $request->input('id');
        $data = $this->validateProductData($request, false, false);

        $product = Product::where('id', $productId)
            ->where('user_id', $userId)
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        if ((int) $product->base_unit_id !== (int) $data['base_unit_id']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Satuan dasar inventori tidak dapat diubah setelah produk dibuat. Buat produk baru apabila satuan dasarnya memang berbeda.',
            ], 422);
        }

        $inventoryUnit = $product->inventoryUnit;
        $oldImageUrl = $product->img_url;
        $newImageUrl = null;

        DB::beginTransaction();

        try {
            if ($request->hasFile('img')) {
                $newImageUrl = $this->storeImage($request->file('img'), $userId);
            }

            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'price' => $data['base_unit_price'],
                'base_unit' => $inventoryUnit->symbol,
                'unit' => $this->formatQty((float) $product->stock_base),
                'stock_configuration_status' => 'configured',
                'img_url' => $newImageUrl ?: $oldImageUrl,
            ]);

            $this->syncProductStockUnits($product, $inventoryUnit, $data['stock_units']);
            $this->syncProductSalesUnits($product, $data['units']);

            DB::commit();

            if ($newImageUrl && $oldImageUrl && File::exists(public_path($oldImageUrl))) {
                File::delete(public_path($oldImageUrl));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil diperbarui.',
                'product' => $product->fresh(['category:id,name', 'inventoryUnit', 'units', 'stockUnits.unit']),
            ]);
        } catch (ValidationException $exception) {
            DB::rollBack();

            if ($newImageUrl && File::exists(public_path($newImageUrl))) {
                File::delete(public_path($newImageUrl));
            }

            throw $exception;
        } catch (Throwable $exception) {
            DB::rollBack();

            if ($newImageUrl && File::exists(public_path($newImageUrl))) {
                File::delete(public_path($newImageUrl));
            }

            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Produk gagal diperbarui.',
            ], 500);
        }
    }

    private function validateProductData(Request $request, bool $imageRequired, bool $isCreate): array
    {
        $this->decodeJsonArray($request, 'units', 'Daftar satuan jual tidak memiliki format yang valid.');
        $this->decodeJsonArray($request, 'stock_units', 'Daftar satuan stok tidak memiliki format yang valid.');

        $rules = [
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:100',
            'base_unit_id' => 'required|integer|exists:units,id',
            'base_unit_price' => 'required|numeric|min:0',
            'stock_units' => 'nullable|array',
            'stock_units.*.unit_id' => 'required|integer|exists:units,id',
            'stock_units.*.label' => 'required|string|max:100',
            'stock_units.*.conversion_to_base' => 'required|numeric|gt:0',
            'stock_units.*.is_primary_receipt_unit' => 'nullable|boolean',
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required|string|max:50',
            'units.*.conversion_factor' => 'required|numeric|gt:0',
            'units.*.selling_price' => 'required|numeric|min:0',
        ];

        if ($isCreate) {
            $rules['initial_stock_quantity'] = 'required|numeric|min:0';
            $rules['initial_stock_unit_label'] = 'required|string|max:100';
        }

        $rules['img'] = $imageRequired
            ? 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
            : 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';

        $messages = [
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'name.required' => 'Nama produk wajib diisi.',
            'base_unit_id.required' => 'Satuan dasar inventori wajib dipilih.',
            'base_unit_price.required' => 'Harga satuan dasar wajib diisi.',
            'initial_stock_quantity.required' => 'Stok awal wajib diisi.',
            'initial_stock_quantity.numeric' => 'Stok awal harus berupa angka.',
            'initial_stock_unit_label.required' => 'Satuan stok awal wajib dipilih.',
            'stock_units.*.label.required' => 'Label satuan stok wajib diisi.',
            'stock_units.*.conversion_to_base.gt' => 'Faktor konversi satuan stok harus lebih besar dari 0.',
            'units.*.unit_name.required' => 'Nama satuan jual wajib diisi.',
            'units.*.conversion_factor.gt' => 'Faktor konversi satuan jual harus lebih besar dari 0.',
            'units.*.selling_price.required' => 'Harga jual satuan wajib diisi.',
            'img.required' => 'Gambar produk wajib diunggah.',
            'img.image' => 'File harus berupa gambar.',
            'img.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'img.max' => 'Ukuran gambar maksimal 2 MB.',
        ];

        $data = $request->validate($rules, $messages);
        $data['name'] = trim($data['name']);
        $data['stock_units'] = array_values($data['stock_units'] ?? []);
        $data['units'] = array_values($data['units'] ?? []);

        foreach ($data['stock_units'] as &$stockUnit) {
            $stockUnit['unit_id'] = (int) $stockUnit['unit_id'];
            $stockUnit['label'] = trim($stockUnit['label']);
            $stockUnit['conversion_to_base'] = round((float) $stockUnit['conversion_to_base'], 3);
            $stockUnit['is_primary_receipt_unit'] = filter_var(
                $stockUnit['is_primary_receipt_unit'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );
        }
        unset($stockUnit);

        foreach ($data['units'] as &$saleUnit) {
            $saleUnit['unit_name'] = trim($saleUnit['unit_name']);
            $saleUnit['conversion_factor'] = round((float) $saleUnit['conversion_factor'], 3);
            $saleUnit['selling_price'] = (float) $saleUnit['selling_price'];
        }
        unset($saleUnit);

        $inventoryUnit = Unit::where('id', $data['base_unit_id'])
            ->where('is_active', true)
            ->first();

        if (!$inventoryUnit) {
            throw ValidationException::withMessages([
                'base_unit_id' => ['Satuan dasar inventori tidak tersedia.'],
            ]);
        }

        if (!in_array($inventoryUnit->type, ['weight', 'volume', 'count'], true)) {
            throw ValidationException::withMessages([
                'base_unit_id' => ['Satuan dasar harus berupa satuan berat, volume, atau hitungan; bukan satuan kemasan.'],
            ]);
        }

        $this->ensureStockUnitsAreValid($inventoryUnit, $data['stock_units']);
        $this->ensureSalesUnitsAreValid($inventoryUnit->symbol, $data['units']);

        if ($isCreate) {
            $data['initial_stock_quantity'] = round((float) $data['initial_stock_quantity'], 3);
            $data['initial_stock_unit_label'] = trim($data['initial_stock_unit_label']);
        }

        return $data;
    }

    private function decodeJsonArray(Request $request, string $field, string $message): void
    {
        $value = $request->input($field, []);

        if ($value === null || $value === '') {
            $request->merge([$field => []]);
            return;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw ValidationException::withMessages([
                    $field => [$message],
                ]);
            }

            $request->merge([$field => $decoded]);
        }
    }

    private function ensureStockUnitsAreValid(Unit $inventoryUnit, array $stockUnits): void
    {
        $labels = [mb_strtolower($inventoryUnit->symbol)];
        $primaryCount = 0;
        $units = Unit::whereIn('id', collect($stockUnits)->pluck('unit_id')->unique()->values())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        foreach ($stockUnits as $stockUnit) {
            $labelKey = mb_strtolower($stockUnit['label']);

            if ($stockUnit['label'] === '') {
                throw ValidationException::withMessages([
                    'stock_units' => ['Label satuan stok tidak boleh kosong.'],
                ]);
            }

            if (in_array($labelKey, $labels, true)) {
                throw ValidationException::withMessages([
                    'stock_units' => ['Label satuan stok tidak boleh sama atau duplikat.'],
                ]);
            }

            $selectedUnit = $units->get($stockUnit['unit_id']);

            if (!$selectedUnit) {
                throw ValidationException::withMessages([
                    'stock_units' => ['Salah satu master satuan stok tidak tersedia.'],
                ]);
            }

            if (!$this->isUnitCompatible($inventoryUnit, $selectedUnit)) {
                throw ValidationException::withMessages([
                    'stock_units' => ['Satuan stok ' . $selectedUnit->name . ' tidak sesuai dengan satuan dasar ' . $inventoryUnit->name . '.'],
                ]);
            }

            if ($stockUnit['is_primary_receipt_unit']) {
                $primaryCount++;
            }

            $labels[] = $labelKey;
        }

        if ($primaryCount > 1) {
            throw ValidationException::withMessages([
                'stock_units' => ['Hanya satu satuan stok tambahan yang boleh menjadi satuan penerimaan utama.'],
            ]);
        }
    }

    private function ensureSalesUnitsAreValid(string $inventoryUnitSymbol, array $saleUnits): void
    {
        $unitNames = [mb_strtolower($inventoryUnitSymbol)];

        foreach ($saleUnits as $saleUnit) {
            $nameKey = mb_strtolower($saleUnit['unit_name']);

            if ($saleUnit['unit_name'] === '') {
                throw ValidationException::withMessages([
                    'units' => ['Nama satuan jual tidak boleh kosong.'],
                ]);
            }

            if (in_array($nameKey, $unitNames, true)) {
                throw ValidationException::withMessages([
                    'units' => ['Nama satuan jual tidak boleh sama atau duplikat.'],
                ]);
            }

            $unitNames[] = $nameKey;
        }
    }

    private function isUnitCompatible(Unit $inventoryUnit, Unit $selectedUnit): bool
    {
        return $selectedUnit->type === 'package' || $selectedUnit->type === $inventoryUnit->type;
    }

    private function syncProductStockUnits(Product $product, Unit $inventoryUnit, array $stockUnits)
    {
        /*
         * Jangan menghapus seluruh konfigurasi satuan saat produk diperbarui.
         * Riwayat stock_movements dapat menunjuk ke product_stock_units; satuan
         * yang tidak lagi dipakai cukup dinonaktifkan agar jejak audit tetap utuh.
         */
        $existingUnits = $product->stockUnits()
            ->get()
            ->keyBy(function (ProductStockUnit $stockUnit) {
                return mb_strtolower($stockUnit->label);
            });

        $primaryAdditionalIndex = null;

        foreach ($stockUnits as $index => $stockUnit) {
            if ($stockUnit['is_primary_receipt_unit']) {
                $primaryAdditionalIndex = $index;
                break;
            }
        }

        $savedIds = [];
        $baseLabel = mb_strtolower($inventoryUnit->symbol);
        $baseStockUnit = $existingUnits->get($baseLabel);

        if (!$baseStockUnit || (int) $baseStockUnit->unit_id !== (int) $inventoryUnit->id) {
            $baseStockUnit = ProductStockUnit::create([
                'product_id' => $product->id,
                'unit_id' => $inventoryUnit->id,
                'label' => $inventoryUnit->symbol,
                'conversion_to_base' => 1,
                'is_primary_receipt_unit' => $primaryAdditionalIndex === null,
                'is_active' => true,
                'display_order' => 0,
            ]);
        } else {
            $baseStockUnit->update([
                'unit_id' => $inventoryUnit->id,
                'label' => $inventoryUnit->symbol,
                'conversion_to_base' => 1,
                'is_primary_receipt_unit' => $primaryAdditionalIndex === null,
                'is_active' => true,
                'display_order' => 0,
            ]);
        }

        $savedIds[] = $baseStockUnit->id;
        $savedStockUnits = collect([$baseStockUnit]);

        foreach ($stockUnits as $index => $stockUnit) {
            $labelKey = mb_strtolower($stockUnit['label']);
            $currentStockUnit = $existingUnits->get($labelKey);
            $attributes = [
                'unit_id' => $stockUnit['unit_id'],
                'label' => $stockUnit['label'],
                'conversion_to_base' => $stockUnit['conversion_to_base'],
                'is_primary_receipt_unit' => $primaryAdditionalIndex === $index,
                'is_active' => true,
                'display_order' => $index + 1,
            ];

            if ($currentStockUnit) {
                $currentStockUnit->update($attributes);
            } else {
                $currentStockUnit = ProductStockUnit::create(array_merge([
                    'product_id' => $product->id,
                ], $attributes));
            }

            $savedIds[] = $currentStockUnit->id;
            $savedStockUnits->push($currentStockUnit);
        }

        // Satuan lama tetap dipertahankan demi riwayat, tetapi tidak muncul lagi
        // sebagai opsi input stok apabila sudah dihapus dari konfigurasi produk.
        $product->stockUnits()
            ->whereNotIn('id', $savedIds)
            ->update([
                'is_active' => false,
                'is_primary_receipt_unit' => false,
            ]);

        return $savedStockUnits;
    }

    private function syncProductSalesUnits(Product $product, array $saleUnits): void
    {
        ProductUnit::updateOrCreate(
            [
                'product_id' => $product->id,
                'is_default' => true,
            ],
            [
                'unit_name' => $product->base_unit,
                'conversion_factor' => 1,
                'selling_price' => $product->price,
            ]
        );

        // invoice_products menyimpan snapshot satuan, harga, dan konversi saat transaksi dibuat.
        $product->units()
            ->where('is_default', false)
            ->delete();

        foreach ($saleUnits as $saleUnit) {
            ProductUnit::create([
                'product_id' => $product->id,
                'unit_name' => $saleUnit['unit_name'],
                'conversion_factor' => $saleUnit['conversion_factor'],
                'selling_price' => $saleUnit['selling_price'],
                'is_default' => false,
            ]);
        }
    }

    private function createInitialStockMovement(Product $product, $stockUnits, array $data, int $userId): void
    {
        $initialStockUnit = $stockUnits->first(function (ProductStockUnit $stockUnit) use ($data) {
            return mb_strtolower($stockUnit->label) === mb_strtolower($data['initial_stock_unit_label']);
        });

        if (!$initialStockUnit) {
            throw ValidationException::withMessages([
                'initial_stock_unit_label' => ['Satuan stok awal harus dipilih dari daftar satuan stok yang telah dikonfigurasi.'],
            ]);
        }

        $initialQuantity = (float) $data['initial_stock_quantity'];
        $initialStockUnit->loadMissing('unit');

        if (!$initialStockUnit->unit->allows_decimal && abs($initialQuantity - round($initialQuantity)) > 0.000001) {
            throw ValidationException::withMessages([
                'initial_stock_quantity' => ['Satuan ' . $initialStockUnit->label . ' hanya dapat diisi dalam jumlah bulat.'],
            ]);
        }

        $baseQuantity = round($initialQuantity * (float) $initialStockUnit->conversion_to_base, 3);

        $product->update([
            'stock_base' => $baseQuantity,
            'unit' => $this->formatQty($baseQuantity),
        ]);

        if ($baseQuantity <= 0) {
            return;
        }

        StockMovement::create([
            'product_id' => $product->id,
            'product_stock_unit_id' => $initialStockUnit->id,
            'user_id' => $userId,
            'movement_type' => 'initial',
            'unit_label' => $initialStockUnit->label,
            'quantity_input' => $initialQuantity,
            'conversion_to_base' => $initialStockUnit->conversion_to_base,
            'quantity_base' => $baseQuantity,
            'stock_before' => 0,
            'stock_after' => $baseQuantity,
            'reference_number' => null,
            'note' => 'Stok awal saat produk dibuat.',
        ]);
    }

    private function storeImage($image, int $userId): string
    {
        if (!$image) {
            throw new RuntimeException('Gambar produk wajib diunggah.');
        }

        $extension = strtolower($image->getClientOriginalExtension());
        $fileName = 'product_' . $userId . '_' . uniqid() . '.' . $extension;
        $directory = public_path('uploads/products');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $image->move($directory, $fileName);

        return '/uploads/products/' . $fileName;
    }

    private function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }
}
