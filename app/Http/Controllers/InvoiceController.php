<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class InvoiceController extends Controller
{
    public function InvoicePage(): View
    {
        return view('pages.dashboard.invoice-page');
    }

    public function SalePage(): View
    {
        return view('pages.dashboard.sale-page');
    }

    private function generateInvoiceNumber(int $invoiceId): string
    {
        return 'INV-' . date('Ymd') . '-' . str_pad((string) $invoiceId, 6, '0', STR_PAD_LEFT);
    }

    public function invoiceCreate(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|integer',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.product_unit_id' => 'required|integer',
            'products.*.qty' => 'required|numeric|gt:0',
        ], [
            'customer_id.required' => 'Pelanggan wajib dipilih.',
            'products.required' => 'Produk wajib dipilih.',
            'products.min' => 'Produk wajib dipilih.',
            'products.*.product_unit_id.required' => 'Satuan jual wajib dipilih.',
            'products.*.qty.gt' => 'Jumlah beli produk harus lebih dari 0.',
        ]);

        $userId = (int) $request->header('id');
        $customer = Customer::where('id', $data['customer_id'])
            ->where('user_id', $userId)
            ->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pelanggan tidak ditemukan.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $lockedProducts = [];
            $stockRequirements = [];
            $lineItems = [];

            foreach ($data['products'] as $requestedProduct) {
                $productId = (int) $requestedProduct['product_id'];
                $productUnitId = (int) $requestedProduct['product_unit_id'];
                $qty = round((float) $requestedProduct['qty'], 3);

                if (!isset($lockedProducts[$productId])) {
                    $lockedProducts[$productId] = Product::where('id', $productId)
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->first();
                }

                $product = $lockedProducts[$productId];

                if (!$product) {
                    throw new RuntimeException('Produk tidak ditemukan.');
                }

                $productUnit = ProductUnit::where('id', $productUnitId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$productUnit) {
                    throw new RuntimeException('Satuan jual untuk produk ' . $product->name . ' tidak ditemukan.');
                }

                $conversionFactor = (float) $productUnit->conversion_factor;
                $stockReduction = round($qty * $conversionFactor, 3);
                $unitPrice = (float) $productUnit->selling_price;
                $lineTotal = round($qty * $unitPrice, 2);

                if (!isset($stockRequirements[$productId])) {
                    $stockRequirements[$productId] = 0;
                }

                $stockRequirements[$productId] = round(
                    $stockRequirements[$productId] + $stockReduction,
                    3
                );

                $lineItems[] = [
                    'product_id' => $productId,
                    'product_unit_id' => $productUnit->id,
                    'qty' => $qty,
                    'unit_name' => $productUnit->unit_name,
                    'conversion_factor' => $conversionFactor,
                    'unit_price' => $unitPrice,
                    'sale_price' => $lineTotal,
                ];
            }

            foreach ($stockRequirements as $productId => $requiredStock) {
                $product = $lockedProducts[$productId];
                $availableStock = (float) $product->stock_base;

                if ($availableStock + 0.000001 < $requiredStock) {
                    throw new RuntimeException(
                        'Stok produk ' . $product->name . ' tidak mencukupi. '
                        . 'Stok tersedia: ' . $this->formatQty($availableStock) . ' ' . $product->base_unit . '.'
                    );
                }
            }

            $total = round(array_sum(array_column($lineItems, 'sale_price')), 2);
            $discountPercentage = (float) ($data['discount_percentage'] ?? 0);
            $discount = round($total * ($discountPercentage / 100), 2);
            $taxBase = round($total - $discount, 2);
            $vat = round($taxBase * 0.05, 2);
            $payable = round($taxBase + $vat, 2);

            $invoice = Invoice::create([
                'invoice_number' => null,
                'total' => $total,
                'discount' => $discount,
                'vat' => $vat,
                'payable' => $payable,
                'user_id' => $userId,
                'customer_id' => $customer->id,
            ]);

            $invoice->invoice_number = $this->generateInvoiceNumber($invoice->id);
            $invoice->save();

            foreach ($lineItems as $lineItem) {
                InvoiceProduct::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $userId,
                    'product_id' => $lineItem['product_id'],
                    'product_unit_id' => $lineItem['product_unit_id'],
                    'qty' => $lineItem['qty'],
                    'unit_name' => $lineItem['unit_name'],
                    'conversion_factor' => $lineItem['conversion_factor'],
                    'unit_price' => $lineItem['unit_price'],
                    'sale_price' => $lineItem['sale_price'],
                ]);
            }

            foreach ($stockRequirements as $productId => $requiredStock) {
                $product = $lockedProducts[$productId];
                $remainingStock = round((float) $product->stock_base - $requiredStock, 3);

                $product->update([
                    'stock_base' => $remainingStock,
                    // Kolom lama dipertahankan sebagai salinan stok dasar.
                    'unit' => $this->formatQty($remainingStock),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dibuat.',
                'invoice_number' => $invoice->invoice_number,
            ]);
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
                'message' => 'Terjadi kesalahan saat membuat transaksi.',
            ], 500);
        }
    }

    public function invoiceSelect(Request $request)
    {
        $userId = (int) $request->header('id');

        return Invoice::where('user_id', $userId)
            ->with('customer')
            ->orderByDesc('id')
            ->get();
    }

    public function topCustomers(Request $request)
    {
        $userId = (int) $request->header('id');

        return Customer::where('customers.user_id', $userId)
            ->join('invoices', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.user_id', $userId)
            ->select(
                'customers.id',
                'customers.name',
                'customers.mobile',
                DB::raw('COUNT(invoices.id) as total_transactions'),
                DB::raw('COALESCE(SUM(invoices.payable), 0) as total_payable')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.mobile')
            ->orderByDesc('total_transactions')
            ->orderByDesc('total_payable')
            ->get();
    }

    public function topProducts(Request $request)
    {
        $userId = (int) $request->header('id');

        return Product::where('products.user_id', $userId)
            ->join('invoice_products', 'products.id', '=', 'invoice_products.product_id')
            ->where('invoice_products.user_id', $userId)
            ->select(
                'products.id',
                'products.name',
                'products.base_unit',
                'products.stock_base',
                DB::raw('SUM(invoice_products.qty * invoice_products.conversion_factor) as total_base_qty'),
                DB::raw('COUNT(DISTINCT invoice_products.invoice_id) as total_transactions'),
                DB::raw('COALESCE(SUM(invoice_products.sale_price), 0) as total_sales')
            )
            ->groupBy('products.id', 'products.name', 'products.base_unit', 'products.stock_base')
            ->orderByDesc('total_base_qty')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function InvoiceDetails(Request $request)
    {
        $userId = (int) $request->header('id');

        $customerDetails = Customer::where('user_id', $userId)
            ->where('id', $request->input('cus_id'))
            ->first();

        $invoiceTotal = Invoice::where('user_id', $userId)
            ->where('id', $request->input('inv_id'))
            ->first();

        $invoiceProducts = InvoiceProduct::where('invoice_id', $request->input('inv_id'))
            ->where('user_id', $userId)
            ->with(['product', 'productUnit'])
            ->get();

        return response()->json([
            'customer' => $customerDetails,
            'invoice' => $invoiceTotal,
            'product' => $invoiceProducts,
        ]);
    }

    private function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }
}
