<html>
<head>
    <style>
        .customers {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            font-size: 12px !important;
        }

        .customers td, #customers th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .customers tr:nth-child(even){background-color: #f2f2f2;}

        .customers tr:hover {background-color: #ddd;}

        .customers th {
            padding-top: 12px;
            padding-bottom: 12px;
            padding-left: 6px;
            text-align: left;
            background-color: #04AA6D;
            color: white;
        }
    </style>
</head>
<body>

<h3>Ringkasan</h3>

<table class="customers" >
    <thead>
    <tr>
        <th>Laporan</th>
        <th>Tanggal</th>
        <th>Total</th>
        <th>Diskon</th>
        <th>PPN</th>
        <th>Total Bayar</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Sales Laporan</td>
        <td>{{$FormTanggal}} to {{$ToTanggal}}</td>
        <td>Rp {{ number_format($total,2,',','.') }}</td>
        <td>Rp {{ number_format($discount,2,',','.') }}</td>
        <td>Rp {{ number_format($vat,2,',','.') }}</td>
        <td>Rp {{ number_format($payable,2,',','.') }} </td>
    </tr>
    </tbody>
</table>


<h3>Rincian</h3>
<table class="customers" >
    <thead>
    <tr>
        <th>Pelanggan</th>
        <th>Nomor HP</th>
        <th>Email</th>
        <th>Total</th>
        <th>Diskon</th>
        <th>PPN</th>
        <th>Total Bayar</th>
        <th>Tanggal</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($list as $item)
        <tr>
            <td>{{$item->customer->name}}</td>
            <td>{{$item->customer->mobile}}</td>
            <td>{{$item->customer->email}}</td>
            <td>Rp {{ number_format($item->total,2,',','.') }}</td>
            <td>Rp {{ number_format($item->discount,2,',','.') }}</td>
            <td>Rp {{ number_format($item->vat,2,',','.') }}</td>
            <td>Rp {{ number_format($item->payable,2,',','.') }}</td>
            <td>{{$item->created_at }}</td>
        </tr>
    @endforeach

    </tbody>
</table>
</body>
</html>