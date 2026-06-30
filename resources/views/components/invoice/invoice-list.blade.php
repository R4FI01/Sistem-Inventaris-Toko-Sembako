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
                        <a href="{{ url("/salePage") }}" class="float-end btn m-0 bg-gradient-primary">
                            Buat Transaksi
                        </a>
                    </div>
                </div>

                <hr class="bg-dark"/>

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

    async function getList() {
        let listType = $('#listType').val();

        showLoader();

        let url = "/invoice-select";

        if (listType === "customers") {
            url = "/top-customers";
        } else if (listType === "products") {
            url = "/top-products";
        }

        let res = await axios.get(url);

        hideLoader();

        renderTable(listType, res.data);
    }

    function resetTable() {
        let tableList = $("#tableList");
        let tableData = $("#tableData");

        if ($.fn.DataTable.isDataTable('#tableData')) {
            tableData.DataTable().destroy();
        }

        tableList.empty();
    }

    function renderTable(listType, data) {
        resetTable();

        if (listType === "customers") {
            renderTopCustomers(data);
        } else if (listType === "products") {
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
        let tableList = $("#tableList");

        $("#tableHead").html(`
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
            </tr>
        `);

        data.forEach(function (item, index) {
            let invoiceNumber = item['invoice_number'] || '-';
            let customerName = item['customer'] ? item['customer']['name'] : '-';
            let customerMobile = item['customer'] ? item['customer']['mobile'] : '-';
            let customerId = item['customer'] ? item['customer']['id'] : '';
            let createdAt = item['created_at'] ? item['created_at'].substring(0, 10) : '-';

            let row = `<tr>
                    <td>${index + 1}</td>
                    <td><span class="badge bg-gradient-primary">${invoiceNumber}</span></td>
                    <td>${customerName}</td>
                    <td>${customerMobile}</td>
                    <td>${formatRupiah(item['total'])}</td>
                    <td>${formatRupiah(item['vat'])}</td>
                    <td>${formatRupiah(item['discount'])}</td>
                    <td>${formatRupiah(item['payable'])}</td>
                    <td>${createdAt}</td>
                    <td>
                        <button data-id="${item['id']}" data-cus="${customerId}" class="viewBtn btn btn-outline-dark text-sm px-3 py-1 btn-sm m-0">
                            <i class="fa text-sm fa-eye"></i>
                        </button>
                    </td>
                 </tr>`

            tableList.append(row)
        })

        $('.viewBtn').on('click', async function () {
            let id = $(this).data('id');
            let cus = $(this).data('cus');
            await TransaksiDetails(cus, id)
        })
    }

    function renderTopCustomers(data) {
        $("#tableHead").html(`
            <tr class="bg-light">
                <th>Peringkat</th>
                <th>Nama Pelanggan</th>
                <th>Nomor HP</th>
                <th>Jumlah Transaksi</th>
                <th>Total Belanja</th>
            </tr>
        `);

        data.forEach(function (item, index) {
            let row = `<tr>
                    <td><span class="badge bg-gradient-primary">${index + 1}</span></td>
                    <td>${item['name'] || '-'}</td>
                    <td>${item['mobile'] || '-'}</td>
                    <td>${item['total_transactions'] || 0}</td>
                    <td>${formatRupiah(item['total_payable'])}</td>
                 </tr>`

            $("#tableList").append(row)
        })
    }

    function renderTopProducts(data) {
        $("#tableHead").html(`
            <tr class="bg-light">
                <th>Peringkat</th>
                <th>Nama Produk</th>
                <th>Qty Terjual</th>
                <th>Jumlah Transaksi</th>
                <th>Total Penjualan</th>
                <th>Stok Saat Ini</th>
            </tr>
        `);

        data.forEach(function (item, index) {
            let stock = parseInt(item['unit']) || 0;
            let stockBadge = stock <= 5 ? 'bg-gradient-danger' : 'bg-gradient-info';

            let row = `<tr>
                    <td><span class="badge bg-gradient-primary">${index + 1}</span></td>
                    <td>${item['name'] || '-'}</td>
                    <td>${item['total_qty'] || 0}</td>
                    <td>${item['total_transactions'] || 0}</td>
                    <td>${formatRupiah(item['total_sales'])}</td>
                    <td><span class="badge ${stockBadge}">${stock}</span></td>
                 </tr>`

            $("#tableList").append(row)
        })
    }
</script>
