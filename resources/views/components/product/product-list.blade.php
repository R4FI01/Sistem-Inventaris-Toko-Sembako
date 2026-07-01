<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card px-4 py-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h4 class="mb-1">Daftar Produk</h4>
                        <small class="text-muted">Pantau harga, stok tersedia, dan pengelolaan setiap produk dalam satu tampilan ringkas.</small>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ url('/stockPage') }}" class="btn btn-outline-primary m-0">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Stok Masuk & Riwayat
                        </a>
                        <button data-bs-toggle="modal" data-bs-target="#create-modal" class="btn bg-gradient-primary m-0">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Produk
                        </button>
                    </div>
                </div>

                <hr class="my-3">

                <div class="table-responsive">
                    <table class="table align-middle" id="tableData">
                        <thead>
                        <tr class="bg-light">
                            <th>Produk</th>
                            <th>Harga Jual</th>
                            <th>Stok Tersedia</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody id="tableList"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let ProductDirectory = {};

    getList();

    function displayQty(value) {
        return Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
    }

    function escapeProductText(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function productBaseUnit(product) {
        const value = product?.['base_unit'];

        if (value && typeof value === 'object') {
            return value['symbol'] || value['name'] || product?.['inventory_unit']?.['symbol'] || 'pcs';
        }

        return value || product?.['inventory_unit']?.['symbol'] || 'pcs';
    }

    function productStockBase(product) {
        return Number(product?.['stock_base'] ?? product?.['unit'] ?? 0);
    }

    function primaryStockUnit(product) {
        const stockUnits = product?.['stock_units'] || [];
        return stockUnits.find(unit => Boolean(unit['is_primary_receipt_unit'])) || stockUnits[0] || null;
    }

    function inventoryEquivalent(product) {
        const stockBase = productStockBase(product);
        const baseUnit = productBaseUnit(product);
        const primaryUnit = primaryStockUnit(product);

        if (!primaryUnit || Number(primaryUnit['conversion_to_base']) <= 1) {
            return '';
        }

        const conversion = Number(primaryUnit['conversion_to_base']);
        const fullPackages = Math.floor((stockBase + 0.000001) / conversion);
        const remainder = Number((stockBase - (fullPackages * conversion)).toFixed(3));
        const parts = [];

        if (fullPackages > 0) {
            parts.push(`${displayQty(fullPackages)} ${primaryUnit['label']}`);
        }

        if (remainder > 0.000001 || parts.length === 0) {
            parts.push(`${displayQty(Math.max(remainder, 0))} ${baseUnit}`);
        }

        return `Setara ${parts.join(' + ')}`;
    }

    function preferredSellingUnit(product) {
        const units = product?.['units'] || [];
        return units.find(unit => Boolean(unit['is_default'])) || units[0] || null;
    }

    function sellingPriceText(product) {
        const unit = preferredSellingUnit(product);
        const unitName = unit?.['unit_name'] || productBaseUnit(product);
        const price = unit?.['selling_price'] ?? product?.['price'] ?? 0;

        return `${formatRupiah(price)} / ${escapeProductText(unitName)}`;
    }

    function stockStatus(product) {
        if (productStockBase(product) <= 0) {
            return '<span class="badge bg-gradient-danger">Stok habis</span>';
        }

        return '<span class="badge bg-gradient-success">Tersedia</span>';
    }

    function productImage(product) {
        return product?.['img_url'] || '{{ asset('images/default.jpg') }}';
    }

    function renderProductRows(products) {
        const tableList = $('#tableList');
        tableList.empty();
        ProductDirectory = {};

        products.forEach(function (item) {
            ProductDirectory[item['id']] = item;

            const baseUnit = productBaseUnit(item);
            const stockBase = productStockBase(item);
            const categoryName = item?.['category']?.['name'] || 'Tanpa kategori';
            const equivalent = inventoryEquivalent(item);
            const row = `<tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <img class="rounded border" style="width:46px;height:46px;object-fit:cover" alt="${escapeProductText(item['name'])}" src="${productImage(item)}">
                        <div>
                            <div class="fw-semibold">${escapeProductText(item['name'])}</div>
                            <small class="text-muted">${escapeProductText(categoryName)}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="fw-semibold">${sellingPriceText(item)}</span>
                </td>
                <td>
                    <div class="fw-semibold">${displayQty(stockBase)} ${escapeProductText(baseUnit)}</div>
                    ${equivalent ? `<small class="text-muted">${escapeProductText(equivalent)}</small>` : ''}
                </td>
                <td>${stockStatus(item)}</td>
                <td class="text-end">
                    <button data-id="${item['id']}" class="btn btn-sm btn-outline-primary manageProductBtn">
                        Kelola <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </td>
            </tr>`;

            tableList.append(row);
        });
    }

    async function getList() {
        showLoader();

        try {
            const res = await axios.get('/list-product');
            const tableData = $('#tableData');

            if ($.fn.DataTable.isDataTable('#tableData')) {
                tableData.DataTable().destroy();
            }

            renderProductRows(res.data || []);

            new DataTable('#tableData', {
                order: [[0, 'asc']],
                lengthMenu: [5, 10, 15, 20, 30],
                language: {
                    emptyTable: 'Belum ada produk. Tambahkan produk pertama Anda.',
                    search: 'Cari produk:'
                }
            });
        } catch (error) {
            errorToast(error.response?.data?.message || 'Daftar produk gagal dimuat.');
        } finally {
            hideLoader();
        }
    }

    $(document)
        .off('click', '.manageProductBtn')
        .on('click', '.manageProductBtn', function () {
            openProductDetail($(this).data('id'));
        });
</script>
