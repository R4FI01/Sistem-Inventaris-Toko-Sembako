<div class="modal animated zoomIn" id="update-modal" tabindex="-1" aria-labelledby="updateProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateProductLabel">Ubah Produk</h5>
            </div>
            <div class="modal-body">
                <form id="update-form">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 p-1">
                                <label class="form-label">Kategori</label>
                                <select class="form-control form-select" id="productCategoryUpdate">
                                    <option value="">Pilih Kategori</option>
                                </select>

                                <label class="form-label mt-2">Nama Produk</label>
                                <input type="text" class="form-control" id="productNameUpdate">

                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Satuan untuk Menghitung Stok</label>
                                        <select class="form-control form-select" id="productBaseUnitUpdate" disabled></select>
                                        <small class="text-muted">Tidak dapat diubah agar riwayat stok dan transaksi tetap konsisten.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stok Saat Ini</label>
                                        <input type="text" class="form-control" id="productCurrentStockUpdate" readonly>
                                        <small class="text-muted">Gunakan menu Persediaan untuk menambah atau menyesuaikan stok.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Harga per Satuan Utama</label>
                                        <input type="number" min="0" step="0.01" class="form-control" id="baseUnitPriceUpdate">
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0">Bentuk Barang untuk Pencatatan Stok</label>
                                        <small class="d-block text-muted">Satuan dasar otomatis tersedia dengan konversi 1. Pilih satu penerimaan utama atau biarkan satuan dasar sebagai default.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm bg-gradient-primary" onclick="addUpdateStockUnitRow()">+ Tambah Bentuk Barang</button>
                                </div>

                                <div class="table-responsive mt-2">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                        <tr>
                                            <th>Jenis Satuan</th>
                                            <th>Nama Bentuk Barang</th>
                                            <th>Isi dalam Satuan Stok</th>
                                            <th class="text-center">Utama</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody id="updateStockUnitRows"></tbody>
                                    </table>
                                </div>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0">Pilihan Jual Tambahan</label>
                                        <small class="d-block text-muted">Satuan dasar tetap tersedia sebagai satuan jual default.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm bg-gradient-primary" onclick="addUpdateSaleUnitRow()">+ Tambah Pilihan Jual</button>
                                </div>

                                <div class="table-responsive mt-2">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                        <tr>
                                            <th>Satuan yang Dijual</th>
                                            <th>Isi dalam Satuan Stok</th>
                                            <th>Harga per Satuan</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody id="updateSaleUnitRows"></tbody>
                                    </table>
                                </div>

                                <hr class="my-3">

                                <img class="w-15" id="oldImg" src="{{ asset('images/default.jpg') }}" alt="Pratinjau gambar produk"/>
                                <br>
                                <label class="form-label mt-2">Gambar Baru, opsional</label>
                                <input type="file" class="form-control" id="productImgUpdate" accept=".jpg,.jpeg,.png,.webp" onchange="previewUpdateImage(this)">

                                <input type="hidden" id="updateID">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="update-modal-close" type="button" class="btn bg-gradient-primary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" onclick="update()" id="update-btn" class="btn bg-gradient-success">Perbarui</button>
            </div>
        </div>
    </div>
</div>

<script>
    let UpdateInventoryUnits = [];

    $(document).ready(async function () {
        const res = await axios.get('/list-unit');
        UpdateInventoryUnits = res.data || [];

        $(document).on('change', '.update-stock-primary', function () {
            if (this.checked) {
                $('.update-stock-primary').not(this).prop('checked', false);
            }
        });
    });

    async function UpdateFillCategoryDropDown(selectedCategoryId = '') {
        const res = await axios.get('/list-category');
        const category = $('#productCategoryUpdate');

        category.empty().append('<option value="">Pilih Kategori</option>');
        res.data.forEach(function (item) {
            category.append(`<option value="${item['id']}">${item['name']}</option>`);
        });

        category.val(String(selectedCategoryId));
    }

    function previewUpdateImage(input) {
        if (input.files && input.files[0]) {
            document.getElementById('oldImg').src = window.URL.createObjectURL(input.files[0]);
        }
    }

    function updateStockUnitOptions(selectedId = '') {
        let options = '<option value="">Pilih satuan</option>';
        UpdateInventoryUnits.forEach(function (unit) {
            const selected = String(unit['id']) === String(selectedId) ? 'selected' : '';
            options += `<option value="${unit['id']}" ${selected}>${unit['name']} (${unit['symbol']})</option>`;
        });
        return options;
    }

    function addUpdateStockUnitRow(stockUnit = {}) {
        const row = `
            <tr>
                <td><select class="form-select form-select-sm update-stock-unit">${updateStockUnitOptions(stockUnit.unit_id || '')}</select></td>
                <td><input type="text" class="form-control form-control-sm update-stock-label" value="${stockUnit.label || ''}" placeholder="Contoh: Karung 25 kg"></td>
                <td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm update-stock-factor" value="${stockUnit.conversion_to_base || ''}" placeholder="Contoh: 25"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input update-stock-primary" ${stockUnit.is_primary_receipt_unit ? 'checked' : ''}></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove()">Hapus</button></td>
            </tr>`;

        $('#updateStockUnitRows').append(row);
    }

    function addUpdateSaleUnitRow(unit = {}) {
        const row = `
            <tr>
                <td><input type="text" class="form-control form-control-sm update-sale-unit-name" value="${unit.unit_name || ''}" placeholder="Contoh: dus"></td>
                <td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm update-sale-unit-factor" value="${unit.conversion_factor || ''}" placeholder="Contoh: 12"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm update-sale-unit-price" value="${unit.selling_price || ''}" placeholder="Contoh: 168000"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove()">Hapus</button></td>
            </tr>`;

        $('#updateSaleUnitRows').append(row);
    }

    function collectUpdateStockUnits() {
        const stockUnits = [];
        let isValid = true;

        $('#updateStockUnitRows tr').each(function () {
            const unitId = $(this).find('.update-stock-unit').val();
            const label = $(this).find('.update-stock-label').val().trim();
            const conversion = parseFloat($(this).find('.update-stock-factor').val());
            const primary = $(this).find('.update-stock-primary').is(':checked');

            if (!unitId || !label || !Number.isFinite(conversion) || conversion <= 0) {
                isValid = false;
                return false;
            }

            stockUnits.push({
                unit_id: Number(unitId),
                label: label,
                conversion_to_base: conversion,
                is_primary_receipt_unit: primary
            });
        });

        if (!isValid) {
            errorToast('Setiap satuan stok tambahan harus memiliki master satuan, label, dan konversi yang valid.');
            return null;
        }

        return stockUnits;
    }

    function collectUpdateSaleUnits() {
        const units = [];
        let isValid = true;

        $('#updateSaleUnitRows tr').each(function () {
            const unitName = $(this).find('.update-sale-unit-name').val().trim();
            const conversionFactor = parseFloat($(this).find('.update-sale-unit-factor').val());
            const sellingPrice = parseFloat($(this).find('.update-sale-unit-price').val());

            if (!unitName || !Number.isFinite(conversionFactor) || conversionFactor <= 0 || !Number.isFinite(sellingPrice) || sellingPrice < 0) {
                isValid = false;
                return false;
            }

            units.push({
                unit_name: unitName,
                conversion_factor: conversionFactor,
                selling_price: sellingPrice
            });
        });

        if (!isValid) {
            errorToast('Setiap satuan jual tambahan harus memiliki nama, konversi, dan harga jual yang valid.');
            return null;
        }

        return units;
    }

    async function FillUpUpdateForm(id) {
        showLoader();

        try {
            const res = await axios.post('/product-by-id', {id: id});
            const product = res.data;

            if (!product) {
                errorToast('Produk tidak ditemukan.');
                return;
            }

            await UpdateFillCategoryDropDown(product['category_id']);

            document.getElementById('updateID').value = product['id'];
            document.getElementById('oldImg').src = product['img_url'];
            document.getElementById('productNameUpdate').value = product['name'];
            document.getElementById('baseUnitPriceUpdate').value = product['price'];
            document.getElementById('productImgUpdate').value = '';

            const baseUnit = typeof product['base_unit'] === 'object' ? (product['base_unit']?.['symbol'] || product['inventory_unit']?.['symbol'] || 'pcs') : (product['base_unit'] || product['inventory_unit']?.['symbol'] || 'pcs');
            const stockBase = product['stock_base'] ?? product['unit'];
            document.getElementById('productCurrentStockUpdate').value = `${displayQty(stockBase)} ${baseUnit}`;

            const baseUnitSelect = $('#productBaseUnitUpdate');
            baseUnitSelect.empty();
            UpdateInventoryUnits
                .filter(unit => ['weight', 'volume', 'count'].includes(unit['type']))
                .forEach(function (unit) {
                    baseUnitSelect.append(`<option value="${unit['id']}">${unit['name']} (${unit['symbol']})</option>`);
                });
            baseUnitSelect.val(String(product['base_unit_id']));

            $('#updateStockUnitRows, #updateSaleUnitRows').empty();

            const baseLabel = String(baseUnit).toLowerCase();
            (product['stock_units'] || [])
                .filter(unit => !(Number(unit['display_order']) === 0 && String(unit['label']).toLowerCase() === baseLabel))
                .forEach(unit => addUpdateStockUnitRow(unit));

            (product['units'] || [])
                .filter(unit => !unit['is_default'])
                .forEach(unit => addUpdateSaleUnitRow(unit));
        } catch (error) {
            errorToast(error.response?.data?.message || 'Data produk gagal dimuat.');
        } finally {
            hideLoader();
        }
    }

    async function update() {
        const categoryId = $('#productCategoryUpdate').val();
        const name = $('#productNameUpdate').val().trim();
        const baseUnitId = $('#productBaseUnitUpdate').val();
        const baseUnitPrice = $('#baseUnitPriceUpdate').val();
        const updateId = $('#updateID').val();
        const image = document.getElementById('productImgUpdate').files[0];
        const stockUnits = collectUpdateStockUnits();
        const saleUnits = collectUpdateSaleUnits();

        if (stockUnits === null || saleUnits === null) return;
        if (!categoryId) return errorToast('Kategori produk wajib dipilih.');
        if (!name) return errorToast('Nama produk wajib diisi.');
        if (!baseUnitId) return errorToast('Satuan dasar inventori tidak tersedia.');
        if (baseUnitPrice === '' || Number(baseUnitPrice) < 0) return errorToast('Harga satuan dasar wajib diisi.');

        const formData = new FormData();
        formData.append('id', updateId);
        formData.append('category_id', categoryId);
        formData.append('name', name);
        formData.append('base_unit_id', baseUnitId);
        formData.append('base_unit_price', baseUnitPrice);
        formData.append('stock_units', JSON.stringify(stockUnits));
        formData.append('units', JSON.stringify(saleUnits));

        if (image) {
            formData.append('img', image);
        }

        try {
            showLoader();
            const res = await axios.post('/update-product', formData, {
                headers: {'content-type': 'multipart/form-data'}
            });
            hideLoader();

            if (res.status === 200 && res.data.status === 'success') {
                successToast('Produk berhasil diperbarui.');
                document.getElementById('update-modal-close').click();
                await getList();
            } else {
                errorToast(res.data.message || 'Produk gagal diperbarui.');
            }
        } catch (error) {
            hideLoader();
            const errors = error.response?.data?.errors;
            const message = errors ? Object.values(errors)[0][0] : (error.response?.data?.message || 'Produk gagal diperbarui.');
            errorToast(message);
        }
    }
</script>
