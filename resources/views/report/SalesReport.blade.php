<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
        }

        .header p {
            margin: 4px 0 0;
            font-size: 12px;
        }

        .section-title {
            margin-top: 18px;
            margin-bottom: 8px;
            font-size: 15px;
            font-weight: bold;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
        }

        .table th {
            background-color: #198754;
            color: white;
            border: 1px solid #198754;
            padding: 7px;
            text-align: left;
        }

        .table td {
            border: 1px solid #ddd;
            padding: 7px;
            vertical-align: top;
        }

        .table tr:nth-child(even) {
            background-color: #f7f7f7;
        }

        .text-right {
            text-align: right;
        }

        .summary-box {
            margin-bottom: 14px;
        }

        .footer {
            margin-top: 22px;
            font-size: 10px;
            text-align: right;
            color: #555;
        }
    </style>
</head>

<body>

<div class="header">
    <h2>Laporan Penjualan</h2>
    <p>Sistem Inventaris Toko Sembako</p>
    <p>Periode: {{ $FormTanggal }} sampai {{ $ToTanggal }}</p>
</div>

<div class="section-title">Ringkasan Laporan</div>

<table class="table summary-box">
    <thead>
    <tr>
        <th>Jumlah Transaksi</th>
        <th>Total Penjualan</th>
        <th>Diskon</th>
        <th>PPN</th>
        <th>Total Bayar</th>
        <th>Tanggal Cetak</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $jumlahTransaksi }}</td>
        <td class="text-right">Rp {{ number_format($total, 0, ',', '.') }}</td>
        <td class="text-right">Rp {{ number_format($discount, 0, ',', '.') }}</td>
        <td class="text-right">Rp {{ number_format($vat, 0, ',', '.') }}</td>
        <td class="text-right">Rp {{ number_format($payable, 0, ',', '.') }}</td>
        <td>{{ $TanggalCetak }}</td>
    </tr>
    </tbody>
</table>

<div class="section-title">Rincian Transaksi</div>

<table class="table">
    <thead>
    <tr>
        <th>No</th>
        <th>No Transaksi</th>
        <th>Pelanggan</th>
        <th>Nomor HP</th>
        <th>Total</th>
        <th>Diskon</th>
        <th>PPN</th>
        <th>Total Bayar</th>
        <th>Tanggal</th>
    </tr>
    </thead>

    <tbody>
    @forelse ($list as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->invoice_number ?? '-' }}</td>
            <td>{{ $item->customer->name ?? '-' }}</td>
            <td>{{ $item->customer->mobile ?? '-' }}</td>
            <td class="text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($item->discount, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($item->vat, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($item->payable, 0, ',', '.') }}</td>
            <td>{{ $item->created_at ? date('d/m/Y H:i', strtotime($item->created_at)) : '-' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="9" style="text-align: center;">Tidak ada transaksi pada periode ini.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    Dicetak otomatis oleh Sistem Inventaris Toko Sembako.
</div>

</body>
</html>