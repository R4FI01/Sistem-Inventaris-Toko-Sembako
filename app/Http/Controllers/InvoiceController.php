<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    function InvoicePage(): View
    {
        return view('pages.dashboard.invoice-page');
    }

    function SalePage(): View
    {
        return view('pages.dashboard.sale-page');
    }

    private function generateInvoiceNumber($invoiceID): string
    {
        return 'INV-' . date('Ymd') . '-' . str_pad($invoiceID, 6, '0', STR_PAD_LEFT);
    }

    function invoiceCreate(Request $request)
    {
        DB::beginTransaction();

        try {
            $user_id = $request->header('id');
            $total = $request->input('total');
            $discount = $request->input('discount');
            $vat = $request->input('vat');
            $payable = $request->input('payable');
            $customer_id = $request->input('customer_id');
            $products = $request->input('products');

            if (!$customer_id) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pelanggan wajib dipilih.'
                ]);
            }

            if (!is_array($products) || count($products) === 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produk wajib dipilih.'
                ]);
            }

            $requestedProducts = [];

            foreach ($products as $EachProduct) {
                $productId = $EachProduct['product_id'] ?? null;
                $qty = (int)($EachProduct['qty'] ?? 0);

                if (!$productId || $qty <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Jumlah beli produk harus lebih dari 0.'
                    ]);
                }

                if (!isset($requestedProducts[$productId])) {
                    $requestedProducts[$productId] = 0;
                }

                $requestedProducts[$productId] += $qty;
            }

            foreach ($requestedProducts as $productId => $totalQty) {
                $product = Product::where('id', $productId)
                    ->where('user_id', $user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Produk tidak ditemukan.'
                    ]);
                }

                $currentStock = (int)$product->unit;

                if ($currentStock < $totalQty) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Stok produk ' . $product->name . ' tidak mencukupi. Stok tersedia: ' . $currentStock . ', jumlah beli: ' . $totalQty . '.'
                    ]);
                }
            }

            $invoice = Invoice::create([
                'invoice_number' => null,
                'total' => $total,
                'discount' => $discount,
                'vat' => $vat,
                'payable' => $payable,
                'user_id' => $user_id,
                'customer_id' => $customer_id,
            ]);

            $invoiceID = $invoice->id;

            $invoice->invoice_number = $this->generateInvoiceNumber($invoiceID);
            $invoice->save();

            foreach ($products as $EachProduct) {
                $qty = (int)$EachProduct['qty'];

                InvoiceProduct::create([
                    'invoice_id' => $invoiceID,
                    'user_id' => $user_id,
                    'product_id' => $EachProduct['product_id'],
                    'qty' => $qty,
                    'sale_price' => $EachProduct['sale_price'],
                ]);

                Product::where('id', $EachProduct['product_id'])
                    ->where('user_id', $user_id)
                    ->decrement('unit', $qty);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dibuat.',
                'invoice_number' => $invoice->invoice_number
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat transaksi.'
            ]);
        }
    }

    function invoiceSelect(Request $request)
    {
        $user_id = $request->header('id');

        return Invoice::where('user_id', $user_id)
            ->with('customer')
            ->orderBy('id', 'desc')
            ->get();
    }

    function topCustomers(Request $request)
    {
        $user_id = $request->header('id');

        return Customer::where('customers.user_id', $user_id)
            ->join('invoices', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.user_id', $user_id)
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

    function topProducts(Request $request)
    {
        $user_id = $request->header('id');

        return Product::where('products.user_id', $user_id)
            ->join('invoice_products', 'products.id', '=', 'invoice_products.product_id')
            ->where('invoice_products.user_id', $user_id)
            ->select(
                'products.id',
                'products.name',
                'products.price',
                'products.unit',
                DB::raw('SUM(invoice_products.qty) as total_qty'),
                DB::raw('COUNT(DISTINCT invoice_products.invoice_id) as total_transactions'),
                DB::raw('COALESCE(SUM(invoice_products.sale_price), 0) as total_sales')
            )
            ->groupBy('products.id', 'products.name', 'products.price', 'products.unit')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_sales')
            ->get();
    }

    function InvoiceDetails(Request $request)
    {
        $user_id = $request->header('id');

        $customerDetails = Customer::where('user_id', $user_id)
            ->where('id', $request->input('cus_id'))
            ->first();

        $invoiceTotal = Invoice::where('user_id', $user_id)
            ->where('id', $request->input('inv_id'))
            ->first();

        $invoiceProduct = InvoiceProduct::where('invoice_id', $request->input('inv_id'))
            ->where('user_id', $user_id)
            ->with('product')
            ->get();

        return array(
            'customer' => $customerDetails,
            'invoice' => $invoiceTotal,
            'product' => $invoiceProduct,
        );
    }
}
