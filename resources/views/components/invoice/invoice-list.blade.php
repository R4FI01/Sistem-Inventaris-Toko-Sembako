<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 col-sm-12 col-lg-12">
            <div class="card px-5 py-5">
                <div class="row justify-content-between">
                    <div class="align-items-center col-md-4 col-sm-12">
                        <h5>Daftar</h5>
                    </div>
                    <div class="align-items-center col-md-4 col-sm-12">
                        <select id="listType" class="form-select">
                            <option value="transactions">Daftar Transaksi</option>
                            <option value="customers">Pelanggan Paling Sering Beli</option>
                            <option value="products">Produk Paling Laku</option>
                        </select>
                    </div>
                    <div class="align-items-center col-md-4 col-sm-12">
                        <a href="{{ url('/salePage') }}" class="float-end btn m-0 bg-gradient-primary">Buat Transaksi</a>
                    </div>
                </div>

                <hr class="bg-dark">

                <table class="table" id="tableData">
                    <thead id="tableHead"></thead>
                    <tbody id="tableList"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    getList();

    $('#listType').on('change', async function () {
        await getList();
    });

    function listQty(value) {
        return Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
    }

    async function getList() {
        const listType = $('#listType').val();
        let url = '/invoice-select';

        if (listType === 'customers') {
            url = '/top-customers';
        } else if (listType === 'products') {
            url = '/top-products';
        }

        showLoader();
        const res = await axios.get(url);
        hideLoader();

        renderTable(listType, res.data);
    }

    function resetTable() {
        const tableData = $('#tableData');

        if ($.fn.DataTable.isDataTable('#tableData')) {
            tableData.DataTable().destroy();
        }

        $('#tableList').empty();
    }

    function renderTable(listType, data) {
        resetTable();

        if (listType === 'customers') {
            renderTopCustomers(data);
        } else if (listType === 'products') {
            renderTopProducts(data);
        } else {
            renderTransactions(data);
        }

        new DataTable('#tableData', {
            order: [[0, 'asc']],
            lengthMenu: [5, 10, 15, 20, 30]
        });
    }

    function renderTransactions(data) {
        $('#tableHead').html(`
            <tr class="bg-light">
                <th>No</th>
                <th>No Transaksi</th>
                <th>Nama Pelanggan</th>
                <th>Nomor HP</th>
                <th>Total</th>
                <th>PPN</th>
                <th>Diskon</th>
                <th>Total Bayar</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>`);

        data.forEach(function (item, index) {
            const invoiceNumber = item['invoice_number'] || '-';
            const customerName = item['customer'] ? item['customer']['name'] : '-';
            const customerMobile = item['customer'] ? item['customer']['mobile'] : '-';
            const customerId = item['customer'] ? item['customer']['id'] : '';
            const createdAt = item['created_at'] ? item['created_at'].substring(0, 10) : '-';

            $('#tableList').append(`<tr>
                <td>${index + 1}</td>
                <td><span class="badge bg-gradient-primary">${invoiceNumber}</span></td>
                <td>${customerName}</td>
                <td>${customerMobile}</td>
                <td>${formatRupiah(item['total'])}</td>
                <td>${formatRupiah(item['vat'])}</td>
                <td>${formatRupiah(item['discount'])}</td>
                <td>${formatRupiah(item['payable'])}</td>
                <td>${createdAt}</td>
                <td><button data-id="${item['id']}" data-cus="${customerId}" class="viewBtn btn btn-outline-dark text-sm px-3 py-1 btn-sm m-0"><i class="fa text-sm fa-eye"></i></button></td>
            </tr>`);
        });

        $('.viewBtn').on('click', async function () {
            await TransaksiDetails($(this).data('cus'), $(this).data('id'));
        });
    }

    function renderTopCustomers(data) {
        $('#tableHead').html(`
            <tr class="bg-light">
                <th>Peringkat</th>
                <th>Nama Pelanggan</th>
                <th>Nomor HP</th>
                <th>Jumlah Transaksi</th>
                <th>Total Belanja</th>
            </tr>`);

        data.forEach(function (item, index) {
            $('#tableList').append(`<tr>
                <td><span class="badge bg-gradient-primary">${index + 1}</span></td>
                <td>${item['name'] || '-'}</td>
                <td>${item['mobile'] || '-'}</td>
                <td>${item['total_transactions'] || 0}</td>
                <td>${formatRupiah(item['total_payable'])}</td>
            </tr>`);
        });
    }

    function renderTopProducts(data) {
        $('#tableHead').html(`
            <tr class="bg-light">
                <th>Peringkat</th>
                <th>Nama Produk</th>
                <th>Qty Terjual</th>
                <th>Jumlah Transaksi</th>
                <th>Total Penjualan</th>
                <th>Stok Saat Ini</th>
            </tr>`);

        data.forEach(function (item, index) {
            const stock = Number(item['stock_base']) || 0;
            const baseUnit = item['base_unit'] || 'pcs';
            const stockBadge = stock <= 5 ? 'bg-gradient-danger' : 'bg-gradient-info';

            $('#tableList').append(`<tr>
                <td><span class="badge bg-gradient-primary">${index + 1}</span></td>
                <td>${item['name'] || '-'}</td>
                <td>${listQty(item['total_base_qty'])} ${baseUnit}</td>
                <td>${item['total_transactions'] || 0}</td>
                <td>${formatRupiah(item['total_sales'])}</td>
                <td><span class="badge ${stockBadge}">${listQty(stock)} ${baseUnit}</span></td>
            </tr>`);
        });
    }
</script>
