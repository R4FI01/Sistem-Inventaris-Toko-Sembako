@extends('layout.sidenav-layout')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4 col-md-12 p-2">
                <div class="card p-4 h-100">
                    <h5 class="mb-1">Catat Perubahan Stok</h5>
                    <p class="text-sm text-muted mb-0">Gunakan halaman ini saat barang datang, ketika melakukan penyesuaian, atau saat stok fisik berkurang.</p>
                    <hr>

                    <form id="stockMovementForm">
                        <label class="form-label">Pilih Produk</label>
                        <select class="form-select" id="stockProductId">
                            <option value="">Pilih produk</option>
                        </select>

                        <div class="mt-3 p-3 rounded border bg-light d-none" id="stockProductInfo">
                            <div class="fw-semibold" id="stockProductName"></div>
                            <div class="text-sm mt-1">Stok tersedia: <span id="stockBaseInfo"></span></div>
                            <div class="text-sm text-muted" id="stockEquivalentInfo"></div>
                        </div>

                        <label class="form-label mt-3">Kegiatan</label>
                        <select class="form-select" id="stockMovementType">
                            <option value="receipt">Barang datang dari pemasok</option>
                            <option value="adjustment_in">Tambahkan stok</option>
                            <option value="adjustment_out">Kurangi stok</option>
                        </select>

                        <label class="form-label mt-3">Bentuk barang</label>
                        <select class="form-select" id="stockProductUnitId">
                            <option value="">Pilih produk terlebih dahulu</option>
                        </select>
                        <small class="text-muted">Pilih bentuk barang yang dicatat, misalnya karung, dus, rak, atau satuan eceran.</small>

                        <label class="form-label mt-3">Jumlah barang</label>
                        <input type="number" min="0.001" step="0.001" class="form-control" id="stockQuantity" placeholder="Contoh: 5">
                        <small class="text-muted d-block mt-1" id="stockConversionInfo"></small>

                        <label class="form-label mt-3">Nomor referensi, opsional</label>
                        <input type="text" maxlength="100" class="form-control" id="stockReferenceNumber" placeholder="Contoh: INV-SUP-001">

                        <label class="form-label mt-3">Catatan, opsional</label>
                        <textarea class="form-control" maxlength="1000" rows="3" id="stockNote" placeholder="Contoh: Penerimaan dari distributor A"></textarea>

                        <button type="button" class="btn bg-gradient-success w-100 mt-3" onclick="saveStockMovement()">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Stok
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8 col-md-12 p-2">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <h5 class="mb-1">Riwayat Stok</h5>
                            <small class="text-muted">Menampilkan maksimal 200 perubahan stok terbaru.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadStockMovements()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang
                        </button>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table align-middle" id="stockMovementTable">
                            <thead>
                            <tr class="bg-light">
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Kegiatan</th>
                                <th>Barang Dicatat</th>
                                <th>Perubahan</th>
                                <th>Stok Setelahnya</th>
                                <th>Catatan</th>
                            </tr>
                            </thead>
                            <tbody id="stockMovementList"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let StockProducts = {};
        let SelectedStockProduct = null;
        let SelectedStockUnits = [];

        $(document).ready(async function () {
            await loadStockProducts();
            await loadStockMovements();

            const preselectedProductId = new URLSearchParams(window.location.search).get('product_id');
            if (preselectedProductId && StockProducts[preselectedProductId]) {
                $('#stockProductId').val(preselectedProductId);
                await loadSelectedProductStockUnits();
            }

            $('#stockProductId').on('change', loadSelectedProductStockUnits);
            $('#stockProductUnitId, #stockQuantity, #stockMovementType').on('change input', refreshStockConversionInfo);
        });

        function stockQty(value) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 3
            });
        }

        function escapeStockText(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function stockBaseUnit(product) {
            const value = product?.['base_unit'];
            if (value && typeof value === 'object') {
                return value['symbol'] || value['name'] || product?.['inventory_unit']?.['symbol'] || 'pcs';
            }
            return value || product?.['inventory_unit']?.['symbol'] || 'pcs';
        }

        function stockEquivalent(product) {
            const stockBase = Number(product?.['stock_base'] ?? product?.['unit'] ?? 0);
            const baseUnit = stockBaseUnit(product);
            const primary = (product?.['stock_units'] || []).find(unit => Boolean(unit['is_primary_receipt_unit'])) || (product?.['stock_units'] || [])[0];

            if (!primary || Number(primary['conversion_to_base']) <= 1) {
                return '';
            }

            const conversion = Number(primary['conversion_to_base']);
            const packages = Math.floor((stockBase + 0.000001) / conversion);
            const remainder = Number((stockBase - packages * conversion).toFixed(3));
            const parts = [];

            if (packages > 0) parts.push(`${stockQty(packages)} ${primary['label']}`);
            if (remainder > 0.000001 || parts.length === 0) parts.push(`${stockQty(Math.max(remainder, 0))} ${baseUnit}`);

            return `Setara ${parts.join(' + ')}`;
        }

        function movementTypeLabel(type) {
            const labels = {
                initial: 'Stok awal produk',
                migration_opening: 'Saldo stok awal',
                receipt: 'Barang datang dari pemasok',
                adjustment_in: 'Penambahan stok',
                adjustment_out: 'Pengurangan stok',
                sale: 'Penjualan'
            };
            return labels[type] || type;
        }

        function formatMovementDate(value) {
            if (!value) return '-';
            return new Date(value).toLocaleString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        async function loadStockProducts() {
            try {
                const res = await axios.get('/list-product');
                const select = $('#stockProductId');
                StockProducts = {};
                select.empty().append('<option value="">Pilih produk</option>');

                (res.data || []).forEach(function (product) {
                    StockProducts[product['id']] = product;
                    select.append(`<option value="${product['id']}">${escapeStockText(product['name'])}</option>`);
                });
            } catch (error) {
                errorToast('Daftar produk gagal dimuat.');
            }
        }

        async function loadSelectedProductStockUnits() {
            const productId = $('#stockProductId').val();
            const unitSelect = $('#stockProductUnitId');
            unitSelect.empty();
            SelectedStockProduct = null;
            SelectedStockUnits = [];
            $('#stockProductInfo').addClass('d-none');
            $('#stockConversionInfo').text('');

            if (!productId) {
                unitSelect.append('<option value="">Pilih produk terlebih dahulu</option>');
                return;
            }

            try {
                showLoader();
                const res = await axios.get('/product-stock-units', {params: {product_id: productId}});
                const product = res.data.product;
                const stockUnits = res.data.stock_units || [];
                const baseUnit = stockBaseUnit(product);

                SelectedStockProduct = product;
                SelectedStockUnits = stockUnits;
                StockProducts[product['id']] = product;

                $('#stockProductName').text(product['name']);
                $('#stockBaseInfo').text(`${stockQty(product['stock_base'])} ${baseUnit}`);
                $('#stockEquivalentInfo').text(stockEquivalent(product));
                $('#stockProductInfo').removeClass('d-none');

                unitSelect.append('<option value="">Pilih bentuk barang</option>');
                stockUnits.forEach(function (stockUnit) {
                    const primary = stockUnit['is_primary_receipt_unit'] ? ' • utama' : '';
                    unitSelect.append(`<option value="${stockUnit['id']}">${escapeStockText(stockUnit['label'])} • setara ${stockQty(stockUnit['conversion_to_base'])} ${escapeStockText(baseUnit)}${primary}</option>`);
                });

                const primaryUnit = stockUnits.find(unit => Boolean(unit['is_primary_receipt_unit'])) || stockUnits[0];
                if (primaryUnit) unitSelect.val(String(primaryUnit['id']));
                refreshStockConversionInfo();
            } catch (error) {
                errorToast(error.response?.data?.message || 'Bentuk stok produk gagal dimuat.');
            } finally {
                hideLoader();
            }
        }

        function selectedStockUnit() {
            const unitId = $('#stockProductUnitId').val();
            return SelectedStockUnits.find(unit => String(unit['id']) === String(unitId));
        }

        function refreshStockConversionInfo() {
            const stockUnit = selectedStockUnit();
            const quantity = Number($('#stockQuantity').val());

            if (!stockUnit || !SelectedStockProduct) {
                $('#stockConversionInfo').text('');
                return;
            }

            const baseUnit = stockBaseUnit(SelectedStockProduct);
            const conversion = Number(stockUnit['conversion_to_base']);
            const allowsDecimal = stockUnit?.['unit']?.['allows_decimal'] !== false;
            $('#stockQuantity').attr('step', allowsDecimal ? '0.001' : '1');
            $('#stockQuantity').attr('min', allowsDecimal ? '0.001' : '1');

            let message = `1 ${stockUnit['label']} setara ${stockQty(conversion)} ${baseUnit}.`;

            if (Number.isFinite(quantity) && quantity > 0) {
                const direction = $('#stockMovementType').val() === 'adjustment_out' ? 'mengurangi' : 'menambahkan';
                message += ` Sistem akan ${direction} ${stockQty(quantity * conversion)} ${baseUnit}.`;
            }

            $('#stockConversionInfo').text(message);
        }

        async function saveStockMovement() {
            const productId = $('#stockProductId').val();
            const stockUnitId = $('#stockProductUnitId').val();
            const movementType = $('#stockMovementType').val();
            const quantity = $('#stockQuantity').val();
            const referenceNumber = $('#stockReferenceNumber').val().trim();
            const note = $('#stockNote').val().trim();

            if (!productId) return errorToast('Produk wajib dipilih.');
            if (!stockUnitId) return errorToast('Bentuk barang wajib dipilih.');
            if (quantity === '' || Number(quantity) <= 0) return errorToast('Jumlah barang harus lebih besar dari 0.');

            try {
                showLoader();
                const res = await axios.post('/stock-movements', {
                    product_id: productId,
                    product_stock_unit_id: stockUnitId,
                    movement_type: movementType,
                    quantity: quantity,
                    reference_number: referenceNumber || null,
                    note: note || null
                });

                if (res.data.status === 'success') {
                    successToast('Perubahan stok berhasil disimpan.');
                    $('#stockQuantity, #stockReferenceNumber, #stockNote').val('');
                    await loadStockProducts();
                    $('#stockProductId').val(productId);
                    await loadSelectedProductStockUnits();
                    await loadStockMovements();
                } else {
                    errorToast(res.data.message || 'Perubahan stok gagal disimpan.');
                }
            } catch (error) {
                const errors = error.response?.data?.errors;
                const message = errors ? Object.values(errors)[0][0] : (error.response?.data?.message || 'Perubahan stok gagal disimpan.');
                errorToast(message);
            } finally {
                hideLoader();
            }
        }

        async function loadStockMovements() {
            try {
                const res = await axios.get('/stock-movements');
                const table = $('#stockMovementTable');
                const list = $('#stockMovementList');

                if ($.fn.DataTable.isDataTable('#stockMovementTable')) {
                    table.DataTable().destroy();
                }

                list.empty();

                (res.data || []).forEach(function (movement) {
                    const product = movement['product'] || {};
                    const baseUnit = stockBaseUnit(product);
                    const delta = Number(movement['quantity_base']);
                    const sign = delta > 0 ? '+' : '';
                    const note = movement['note'] ? `<br><span class="text-muted">${escapeStockText(movement['note'])}</span>` : '';
                    const reference = movement['reference_number'] ? `<span class="fw-semibold">${escapeStockText(movement['reference_number'])}</span>` : '<span class="text-muted">-</span>';
                    const row = `<tr class="text-xs">
                        <td>${formatMovementDate(movement['created_at'])}</td>
                        <td>${escapeStockText(product['name'] || '-')}</td>
                        <td>${escapeStockText(movementTypeLabel(movement['movement_type']))}</td>
                        <td>${stockQty(movement['quantity_input'])} ${escapeStockText(movement['unit_label'])}</td>
                        <td>${sign}${stockQty(delta)} ${escapeStockText(baseUnit)}</td>
                        <td>${stockQty(movement['stock_after'])} ${escapeStockText(baseUnit)}</td>
                        <td>${reference}${note}</td>
                    </tr>`;
                    list.append(row);
                });

                new DataTable('#stockMovementTable', {
                    order: [[0, 'desc']],
                    lengthMenu: [10, 20, 50, 100],
                    language: {
                        emptyTable: 'Belum ada riwayat perubahan stok.',
                        search: 'Cari riwayat:'
                    }
                });
            } catch (error) {
                errorToast('Riwayat stok gagal dimuat.');
            }
        }
    </script>
@endsection
