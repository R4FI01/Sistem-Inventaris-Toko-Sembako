<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
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
        $data = $this->validateProductData($request, true);
        $imageUrl = null;

        DB::beginTransaction();

        try {
            $imageUrl = $this->storeImage($request->file('img'), $userId);

            $product = Product::create([
                'user_id' => $userId,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'price' => $data['base_unit_price'],
                // Kolom lama tetap diisi sebagai salinan stok dasar agar data lama tidak rusak.
                'unit' => (string) $data['stock_base'],
                'base_unit' => $data['base_unit'],
                'stock_base' => $data['stock_base'],
                'img_url' => $imageUrl,
            ]);

            $this->syncProductUnits($product, $data['units']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan.',
                'product' => $product->fresh('units'),
            ], 201);
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
        $deleted = $product->delete();

        if ($deleted && $imageUrl && File::exists(public_path($imageUrl))) {
            File::delete(public_path($imageUrl));
        }

        return $deleted;
    }

    public function ProductByID(Request $request)
    {
        $userId = (int) $request->header('id');
        $productId = $request->input('id');

        return Product::where('id', $productId)
            ->where('user_id', $userId)
            ->with('units')
            ->first();
    }

    public function ProductList(Request $request)
    {
        $userId = (int) $request->header('id');

        return Product::where('user_id', $userId)
            ->with('units')
            ->orderByDesc('id')
            ->get();
    }

    public function UpdateProduct(Request $request)
    {
        $userId = (int) $request->header('id');
        $productId = $request->input('id');
        $data = $this->validateProductData($request, false);

        $product = Product::where('id', $productId)
            ->where('user_id', $userId)
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

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
                // Kolom lama tetap sinkron dengan stok dasar.
                'unit' => (string) $data['stock_base'],
                'base_unit' => $data['base_unit'],
                'stock_base' => $data['stock_base'],
                'img_url' => $newImageUrl ?: $oldImageUrl,
            ]);

            $this->syncProductUnits($product, $data['units']);

            DB::commit();

            if ($newImageUrl && $oldImageUrl && File::exists(public_path($oldImageUrl))) {
                File::delete(public_path($oldImageUrl));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil diperbarui.',
                'product' => $product->fresh('units'),
            ]);
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

    private function validateProductData(Request $request, bool $imageRequired): array
    {
        $this->decodeUnits($request);

        $rules = [
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'base_unit' => 'required|string|max:50',
            'stock_base' => 'required|numeric|min:0',
            'base_unit_price' => 'required|numeric|min:0',
            'units' => 'nullable|array',
            'units.*.unit_name' => 'required|string|max:50',
            'units.*.conversion_factor' => 'required|numeric|gt:0',
            'units.*.selling_price' => 'required|numeric|min:0',
        ];

        $rules['img'] = $imageRequired
            ? 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
            : 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';

        $messages = [
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'name.required' => 'Nama produk wajib diisi.',
            'base_unit.required' => 'Satuan dasar wajib diisi.',
            'stock_base.required' => 'Stok dasar wajib diisi.',
            'stock_base.numeric' => 'Stok dasar harus berupa angka.',
            'base_unit_price.required' => 'Harga satuan dasar wajib diisi.',
            'base_unit_price.numeric' => 'Harga satuan dasar harus berupa angka.',
            'units.*.unit_name.required' => 'Nama satuan jual wajib diisi.',
            'units.*.conversion_factor.required' => 'Faktor konversi wajib diisi.',
            'units.*.conversion_factor.gt' => 'Faktor konversi harus lebih besar dari 0.',
            'units.*.selling_price.required' => 'Harga jual satuan wajib diisi.',
            'img.required' => 'Gambar produk wajib diunggah.',
            'img.image' => 'File harus berupa gambar.',
            'img.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'img.max' => 'Ukuran gambar maksimal 2 MB.',
        ];

        $data = $request->validate($rules, $messages);
        $data['base_unit'] = trim($data['base_unit']);
        $data['units'] = array_values($data['units'] ?? []);

        foreach ($data['units'] as &$unit) {
            $unit['unit_name'] = trim($unit['unit_name']);
            $unit['conversion_factor'] = (float) $unit['conversion_factor'];
            $unit['selling_price'] = (float) $unit['selling_price'];
        }
        unset($unit);

        $this->ensureUnitNamesAreValid($data['base_unit'], $data['units']);

        return $data;
    }

    private function decodeUnits(Request $request): void
    {
        $units = $request->input('units', []);

        if ($units === null || $units === '') {
            $request->merge(['units' => []]);
            return;
        }

        if (is_string($units)) {
            $decoded = json_decode($units, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw ValidationException::withMessages([
                    'units' => ['Daftar satuan jual tidak memiliki format yang valid.'],
                ]);
            }

            $request->merge(['units' => $decoded]);
        }
    }

    private function ensureUnitNamesAreValid(string $baseUnit, array $units): void
    {
        $baseUnitKey = strtolower($baseUnit);
        $unitNames = [];

        foreach ($units as $unit) {
            $unitNameKey = strtolower($unit['unit_name']);

            if ($unitNameKey === $baseUnitKey) {
                throw ValidationException::withMessages([
                    'units' => ['Satuan jual tambahan tidak boleh sama dengan satuan dasar.'],
                ]);
            }

            if (in_array($unitNameKey, $unitNames, true)) {
                throw ValidationException::withMessages([
                    'units' => ['Nama satuan jual tidak boleh duplikat.'],
                ]);
            }

            $unitNames[] = $unitNameKey;
        }
    }

    private function syncProductUnits(Product $product, array $units): void
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

        // Riwayat transaksi aman karena invoice_products menyimpan snapshot satuan,
        // faktor konversi, dan harga pada saat transaksi dibuat.
        $product->units()
            ->where('is_default', false)
            ->delete();

        foreach ($units as $unit) {
            ProductUnit::create([
                'product_id' => $product->id,
                'unit_name' => $unit['unit_name'],
                'conversion_factor' => $unit['conversion_factor'],
                'selling_price' => $unit['selling_price'],
                'is_default' => false,
            ]);
        }
    }

    private function storeImage(UploadedFile $image, int $userId): string
    {
        $directory = public_path('uploads');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $fileName = $userId . '-' . time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move($directory, $fileName);

        return 'uploads/' . $fileName;
    }
}
