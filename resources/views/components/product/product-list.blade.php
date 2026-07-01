<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 col-sm-12 col-lg-12">
            <div class="card px-5 py-5">
                <div class="row justify-content-between">
                    <div class="align-items-center col">
                        <h4>Produk Sembako</h4>
                    </div>
                    <div class="align-items-center col">
                        <button data-bs-toggle="modal" data-bs-target="#create-modal" class="float-end btn m-0 bg-gradient-primary">Tambah</button>
                    </div>
                </div>
                <hr class="bg-dark">
                <table class="table" id="tableData">
                    <thead>
                    <tr class="bg-light">
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Harga Satuan Dasar</th>
                        <th>Stok Dasar</th>
                        <th>Satuan Jual</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody id="tableList"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    getList();

    function displayQty(value) {
        return Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
    }

    async function getList() {
        showLoader();
        const res = await axios.get('/list-product');
        hideLoader();

        const tableList = $('#tableList');
        const tableData = $('#tableData');

        if ($.fn.DataTable.isDataTable('#tableData')) {
            tableData.DataTable().destroy();
        }

        tableList.empty();

        res.data.forEach(function (item) {
            const stockBase = item['stock_base'] ?? item['unit'];
            const baseUnit = item['base_unit'] || 'pcs';
            const units = item['units'] || [];
            const unitDescription = units.length
                ? units.map(unit => `${unit['unit_name']} = ${displayQty(unit['conversion_factor'])} ${baseUnit}, ${formatRupiah(unit['selling_price'])}`).join('<br>')
                : '-';

            const row = `<tr>
                <td><img class="w-15 h-auto" alt="${item['name']}" src="${item['img_url']}"></td>
                <td>${item['name']}</td>
                <td>${formatRupiah(item['price'])} / ${baseUnit}</td>
                <td>${displayQty(stockBase)} ${baseUnit}</td>
                <td class="text-xs">${unitDescription}</td>
                <td>
                    <button data-path="${item['img_url']}" data-id="${item['id']}" class="btn editBtn btn-sm btn-outline-success">Edit</button>
                    <button data-path="${item['img_url']}" data-id="${item['id']}" class="btn deleteBtn btn-sm btn-outline-danger">Hapus</button>
                </td>
            </tr>`;
            tableList.append(row);
        });

        $('.editBtn').on('click', async function () {
            const id = $(this).data('id');
            const filePath = $(this).data('path');
            await FillUpUpdateForm(id, filePath);
            $('#update-modal').modal('show');
        });

        $('.deleteBtn').on('click', function () {
            $('#delete-modal').modal('show');
            $('#deleteID').val($(this).data('id'));
            $('#deleteFilePath').val($(this).data('path'));
        });

        new DataTable('#tableData', {
            order: [[1, 'asc']],
            lengthMenu: [5, 10, 15, 20, 30]
        });
    }
</script>
