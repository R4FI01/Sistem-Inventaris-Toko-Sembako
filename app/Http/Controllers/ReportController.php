<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    function ReportPage()
    {
        return view('pages.dashboard.report-page');
    }

    function SalesReport(Request $request)
    {
        $user_id = $request->header('id');

        $FormDate = date('Y-m-d', strtotime($request->FormDate));
        $ToDate = date('Y-m-d', strtotime($request->ToDate));

        $baseQuery = Invoice::where('user_id', $user_id)
            ->whereDate('created_at', '>=', $FormDate)
            ->whereDate('created_at', '<=', $ToDate);

        $total = (clone $baseQuery)->sum('total');
        $vat = (clone $baseQuery)->sum('vat');
        $payable = (clone $baseQuery)->sum('payable');
        $discount = (clone $baseQuery)->sum('discount');

        $list = (clone $baseQuery)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [
            'payable' => $payable,
            'discount' => $discount,
            'total' => $total,
            'vat' => $vat,
            'list' => $list,
            'jumlahTransaksi' => $list->count(),
            'FormTanggal' => $request->FormDate,
            'ToTanggal' => $request->ToDate,
            'TanggalCetak' => date('d/m/Y H:i:s'),
        ];

        $pdf = Pdf::loadView('report.SalesReport', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-penjualan-' . $FormDate . '-sampai-' . $ToDate . '.pdf');
    }
}