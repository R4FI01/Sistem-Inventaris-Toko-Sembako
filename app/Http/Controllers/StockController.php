<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStockUnit;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class StockController extends Controller
{
    public function StockPage(): View
    {
        return view('pages.dashboard.stock-page');
    }

    public function ProductStockUnits(Request $request)
    {
        $userId = (int) $request->header('id');
        $productId = (int) $request->query('product_id');

        $product = Product::where('id', $productId)
            ->where('user_id', $userId)
            ->with(['inventoryUnit', 'stockUnits.unit'])
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'product' => $product,
            'stock_units' => $product->stockUnits->where('is_active', true)->values(),
        ]);
    }

    public function StockMovementList(Request $request)
    {
        $userId = (int) $request->header('id');
        $productId = $request->query('product_id');

        $query = StockMovement::where('user_id', $userId)
            ->with([
                'product:id,name,base_unit,base_unit_id',
                'product.inventoryUnit:id,name,symbol',
                'productStockUnit:id,label,unit_id',
                'productUnit:id,unit_name',
            ])
            ->orderByDesc('id');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->limit(200)->get();
    }

    public function AddStockMovement(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'product_stock_unit_id' => 'required|integer',
            'movement_type' => 'required|in:receipt,adjustment_in,adjustment_out',
            'quantity' => 'required|numeric|gt:0',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ], [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_stock_unit_id.required' => 'Satuan stok wajib dipilih.',
            'movement_type.required' => 'Jenis pergerakan stok wajib dipilih.',
            'quantity.required' => 'Jumlah stok wajib diisi.',
            'quantity.gt' => 'Jumlah stok harus lebih besar dari 0.',
        ]);

        $userId = (int) $request->header('id');
        $quantity = round((float) $data['quantity'], 3);

        DB::beginTransaction();

        try {
            $product = Product::where('id', $data['product_id'])
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }

            $stockUnit = ProductStockUnit::where('id', $data['product_stock_unit_id'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->with('unit')
                ->lockForUpdate()
                ->first();

            if (!$stockUnit) {
                throw new RuntimeException('Satuan stok tidak tersedia untuk produk ini.');
            }

            $this->ensureWholeQuantityWhenRequired($quantity, $stockUnit->unit->allows_decimal, $stockUnit->label);

            $conversion = (float) $stockUnit->conversion_to_base;
            $baseQuantity = round($quantity * $conversion, 3);
            $isOutgoing = $data['movement_type'] === 'adjustment_out';
            $signedBaseQuantity = $isOutgoing ? -$baseQuantity : $baseQuantity;
            $stockBefore = round((float) $product->stock_base, 3);
            $stockAfter = round($stockBefore + $signedBaseQuantity, 3);

            if ($stockAfter < -0.000001) {
                throw new RuntimeException(
                    'Stok tidak mencukupi. Stok tersedia: '
                    . $this->formatQty($stockBefore)
                    . ' '
                    . $product->base_unit
                    . '.'
                );
            }

            $stockAfter = max($stockAfter, 0);

            $product->update([
                'stock_base' => $stockAfter,
                // Kolom lama dipertahankan sementara agar modul lama tetap konsisten.
                'unit' => $this->formatQty($stockAfter),
                'stock_configuration_status' => 'configured',
            ]);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'product_stock_unit_id' => $stockUnit->id,
                'user_id' => $userId,
                'movement_type' => $data['movement_type'],
                'unit_label' => $stockUnit->label,
                'quantity_input' => $quantity,
                'conversion_to_base' => $conversion,
                'quantity_base' => $signedBaseQuantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference_number' => $data['reference_number'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pergerakan stok berhasil disimpan.',
                'movement' => $movement->load(['product.inventoryUnit', 'productStockUnit.unit']),
            ], 201);
        } catch (RuntimeException $exception) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Pergerakan stok gagal disimpan.',
            ], 500);
        }
    }

    private function ensureWholeQuantityWhenRequired(float $quantity, bool $allowsDecimal, string $unitLabel): void
    {
        if (!$allowsDecimal && abs($quantity - round($quantity)) > 0.000001) {
            throw new RuntimeException('Satuan ' . $unitLabel . ' hanya dapat diisi dalam jumlah bulat.');
        }
    }

    private function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }
}
