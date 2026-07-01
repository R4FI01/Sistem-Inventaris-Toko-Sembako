<div class="modal animated zoomIn" id="product-detail-modal" tabindex="-1" aria-labelledby="productDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productDetailLabel">Kelola Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="detailProductId">
                <div class="d-flex gap-3 align-items-center mb-4">
                    <img id="detailProductImage" class="rounded border" style="width:72px;height:72px;object-fit:cover" src="{{ asset('images/default.jpg') }}" alt="Gambar produk">
                    <div>
                        <h5 class="mb-1" id="detailProductName">-</h5>
                        <span class="text-muted" id="detailProductCategory">-</span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <small class="text-muted d-block mb-1">Stok tersedia</small>
                            <div class="h5 mb-1" id="detailStockBase">-</div>
                            <small class="text-muted" id="detailStockEquivalent"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <small class="text-muted d-block mb-1">Harga jual utama</small>
                            <div class="h5 mb-1" id="detailSellingPrice">-</div>
                            <small class="text-muted">Harga dapat diatur kembali pada menu ubah produk.</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h6 class="mb-1">Pencatatan persediaan</h6>
                    <p class="text-muted text-sm mb-2" id="detailBaseUnitDescription"></p>
                    <div class="border rounded" id="detailStockUnits"></div>
                </div>

                <div class="mt-4">
                    <h6 class="mb-1">Pilihan penjualan</h6>
                    <p class="text-muted text-sm mb-2">Kasir dapat memilih salah satu satuan berikut ketika membuat transaksi.</p>
                    <div class="border rounded" id="detailSaleUnits"></div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-outline-danger" onclick="requestDeleteFromDetail()">Hapus Produk</button>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="openStockFromDetail()">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Atur Stok
                    </button>
                    <button type="button" class="btn bg-gradient-success" onclick="editFromDetail()">
                        <i class="bi bi-pencil-square me-1"></i> Ubah Produk
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function configurationRow(title, description, badgeText = '') {
        return `<div class="d-flex justify-content-between align-items-start gap-3 px-3 py-2 border-bottom">
            <div>
                <div class="fw-semibold">${escapeProductText(title)}</div>
                <small class="text-muted">${escapeProductText(description)}</small>
            </div>
            ${badgeText ? `<span class="badge bg-gradient-info text-nowrap">${escapeProductText(badgeText)}</span>` : ''}
        </div>`;
    }

    function openProductDetail(productId) {
        const product = ProductDirectory[productId];

        if (!product) {
            errorToast('Data produk tidak ditemukan. Silakan muat ulang halaman.');
            return;
        }

        const baseUnit = productBaseUnit(product);
        const stockBase = productStockBase(product);
        const preferredUnit = preferredSellingUnit(product);
        const categoryName = product?.['category']?.['name'] || 'Tanpa kategori';
        const stockUnits = product['stock_units'] || [];
        const saleUnits = product['units'] || [];

        $('#detailProductId').val(product['id']);
        $('#detailProductImage').attr('src', productImage(product));
        $('#detailProductName').text(product['name']);
        $('#detailProductCategory').text(categoryName);
        $('#detailStockBase').text(`${displayQty(stockBase)} ${baseUnit}`);
        $('#detailStockEquivalent').text(inventoryEquivalent(product) || `Stok dicatat dalam ${baseUnit}.`);
        $('#detailSellingPrice').text(`${formatRupiah(preferredUnit?.['selling_price'] ?? product['price'] ?? 0)} / ${preferredUnit?.['unit_name'] || baseUnit}`);
        $('#detailBaseUnitDescription').text(`Stok produk ini dihitung dalam ${baseUnit}. Bentuk kemasan di bawah membantu pencatatan stok masuk dan penyesuaian.`);

        const stockUnitList = $('#detailStockUnits');
        stockUnitList.empty();
        stockUnits.forEach(function (stockUnit) {
            const description = Number(stockUnit['conversion_to_base']) === 1
                ? `Digunakan sebagai satuan hitung stok.`
                : `1 ${stockUnit['label']} setara ${displayQty(stockUnit['conversion_to_base'])} ${baseUnit}.`;
            stockUnitList.append(configurationRow(
                stockUnit['label'],
                description,
                stockUnit['is_primary_receipt_unit'] ? 'Utama untuk stok masuk' : ''
            ));
        });
        if (!stockUnits.length) {
            stockUnitList.append(configurationRow(baseUnit, `Gunakan ${baseUnit} untuk mencatat persediaan.`));
        }

        const saleUnitList = $('#detailSaleUnits');
        saleUnitList.empty();
        saleUnits.forEach(function (saleUnit) {
            const description = `${formatRupiah(saleUnit['selling_price'])} per ${saleUnit['unit_name']} • setara ${displayQty(saleUnit['conversion_factor'])} ${baseUnit}.`;
            saleUnitList.append(configurationRow(
                saleUnit['unit_name'],
                description,
                saleUnit['is_default'] ? 'Utama' : ''
            ));
        });
        if (!saleUnits.length) {
            saleUnitList.append(configurationRow(baseUnit, `${formatRupiah(product['price'])} per ${baseUnit}.`, 'Utama'));
        }

        $('#product-detail-modal').modal('show');
    }

    function openStockFromDetail() {
        const id = $('#detailProductId').val();
        if (id) window.location.href = `{{ url('/stockPage') }}?product_id=${id}`;
    }

    async function editFromDetail() {
        const id = $('#detailProductId').val();
        if (!id) return;

        $('#product-detail-modal').modal('hide');
        await FillUpUpdateForm(id);
        $('#update-modal').modal('show');
    }

    function requestDeleteFromDetail() {
        const id = $('#detailProductId').val();
        const product = ProductDirectory[id];
        if (!id || !product) return;

        $('#product-detail-modal').modal('hide');
        $('#deleteID').val(id);
        $('#deleteFilePath').val(product['img_url'] || '');
        setTimeout(function () {
            $('#delete-modal').modal('show');
        }, 250);
    }
</script>
