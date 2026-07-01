<div class="modal animated zoomIn" id="create-modal" tabindex="-1" aria-labelledby="createProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createProductLabel">Tambah Produk</h5>
            </div>
            <div class="modal-body">
                <form id="save-form">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 p-1">
                                <label class="form-label">Kategori</label>
                                <select class="form-control form-select" id="productCategory">
                                    <option value="">Pilih Kategori</option>
                                </select>

                                <label class="form-label mt-2">Nama Produk</label>
                                <input type="text" class="form-control" id="productName" placeholder="Contoh: Beras Premium 5 kg">

                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Satuan untuk Menghitung Stok</label>
                                        <select class="form-control form-select" id="productBaseUnit">
                                            <option value="">Pilih satuan dasar</option>
                                        </select>
                                        <small class="text-muted">Semua stok akan dihitung dalam satuan ini, misalnya kg, liter, atau pcs.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Harga per Satuan Utama</label>
                                        <input type="number" min="0" step="0.01" class="form-control" id="baseUnitPrice" placeholder="Contoh: 15000">
                                        <small class="text-muted">Harga untuk satu satuan stok.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stok Saat Produk Dibuat</label>
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.001" class="form-control" id="initialStockQuantity" value="0">
                                            <select class="form-select" id="initialStockUnit"></select>
                                        </div>
                                        <small class="text-muted">Pilih bentuk barang yang akan dicatat sebagai stok awal.</small>
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0">Bentuk Barang untuk Pencatatan Stok</label>
                                        <small class="d-block text-muted">Contoh: 1 karung berisi 25 kg atau 1 dus berisi 40 bungkus. Satuan hitung stok otomatis tersedia.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm bg-gradient-primary" onclick="addCreateStockUnitRow()">+ Tambah Bentuk Barang</button>
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
                                        <tbody id="createStockUnitRows"></tbody>
                                    </table>
                                </div>
                                <small class="text-muted">Tandai satu bentuk barang sebagai pilihan utama saat mencatat stok masuk.</small>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0">Pilihan Jual Tambahan</label>
                                        <small class="d-block text-muted">Satuan hitung stok otomatis menjadi pilihan jual utama.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm bg-gradient-primary" onclick="addCreateSaleUnitRow()">+ Tambah Pilihan Jual</button>
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
                                        <tbody id="createSaleUnitRows"></tbody>
                                    </table>
                                </div>

                                <hr class="my-3">

                                <img class="w-15" id="newImg" src="{{ asset('images/default.jpg') }}" alt="Pratinjau gambar produk"/>
                                <br>
                                <label class="form-label mt-2">Gambar Produk</label>
                                <input type="file" class="form-control" id="productImg" accept=".jpg,.jpeg,.png,.webp" onchange="previewCreateImage(this)">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="modal-close" type="button" class="btn bg-gradient-primary mx-2" data-bs-dismiss="modal">Tutup</button>
                <button type="button" onclick="Save()" id="save-btn" class="btn bg-gradient-success">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
    let CreateInventoryUnits = [];

    $(document).ready(async function () {
        await Promise.all([FillCategoryDropDown(), loadCreateInventoryUnits()]);

        $('#productBaseUnit').on('change', function () {
            refreshCreateInitialUnitOptions();
        });

        $(document).on('input change', '.create-stock-label, .create-stock-unit, .create-stock-factor', function () {
            refreshCreateInitialUnitOptions();
        });

        $(document).on('change', '.create-stock-primary', function () {
            if (this.checked) {
                $('.create-stock-primary').not(this).prop('checked', false);
            }
        });
    });

    async function FillCategoryDropDown() {
        const res = await axios.get('/list-category');
        const category = $('#productCategory');

        category.empty().append('<option value="">Pilih Kategori</option>');
        res.data.forEach(function (item) {
            category.append(`<option value="${item['id']}">${item['name']}</option>`);
        });
    }

    async function loadCreateInventoryUnits() {
        const res = await axios.get('/list-unit');
        CreateInventoryUnits = res.data || [];
        renderCreateBaseUnitOptions();
        refreshCreateInitialUnitOptions();
    }

    function renderCreateBaseUnitOptions(selectedId = '') {
        const select = $('#productBaseUnit');
        select.empty().append('<option value="">Pilih satuan dasar</option>');

        CreateInventoryUnits
            .filter(unit => ['weight', 'volume', 'count'].includes(unit['type']))
            .forEach(function (unit) {
                select.append(`<option value="${unit['id']}">${unit['name']} (${unit['symbol']})</option>`);
            });

        select.val(String(selectedId));
    }

    function createStockUnitOptions(selectedId = '') {
        let options = '<option value="">Pilih satuan</option>';
        CreateInventoryUnits.forEach(function (unit) {
            const selected = String(unit['id']) === String(selectedId) ? 'selected' : '';
            options += `<option value="${unit['id']}" ${selected}>${unit['name']} (${unit['symbol']})</option>`;
        });
        return options;
    }

    function previewCreateImage(input) {
        if (input.files && input.files[0]) {
            document.getElementById('newImg').src = window.URL.createObjectURL(input.files[0]);
        }
    }

    function addCreateStockUnitRow(stockUnit = {}) {
        const row = `
            <tr>
                <td><select class="form-select form-select-sm create-stock-unit">${createStockUnitOptions(stockUnit.unit_id || '')}</select></td>
                <td><input type="text" class="form-control form-control-sm create-stock-label" value="${stockUnit.label || ''}" placeholder="Contoh: Karung 25 kg"></td>
                <td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm create-stock-factor" value="${stockUnit.conversion_to_base || ''}" placeholder="Contoh: 25"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input create-stock-primary" ${stockUnit.is_primary_receipt_unit ? 'checked' : ''}></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove(); refreshCreateInitialUnitOptions();">Hapus</button></td>
            </tr>`;

        $('#createStockUnitRows').append(row);
        refreshCreateInitialUnitOptions();
    }

    function addCreateSaleUnitRow(unit = {}) {
        const row = `
            <tr>
                <td><input type="text" class="form-control form-control-sm create-sale-unit-name" value="${unit.unit_name || ''}" placeholder="Contoh: dus"></td>
                <td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm create-sale-unit-factor" value="${unit.conversion_factor || ''}" placeholder="Contoh: 12"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm create-sale-unit-price" value="${unit.selling_price || ''}" placeholder="Contoh: 168000"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove()">Hapus</button></td>
            </tr>`;

        $('#createSaleUnitRows').append(row);
    }

    function selectedCreateBaseUnit() {
        const id = $('#productBaseUnit').val();
        return CreateInventoryUnits.find(unit => String(unit['id']) === String(id));
    }

    function refreshCreateInitialUnitOptions(selectedLabel = '') {
        const currentValue = selectedLabel || $('#initialStockUnit').val();
        const baseUnit = selectedCreateBaseUnit();
        const select = $('#initialStockUnit');

        select.empty();

        if (!baseUnit) {
            select.append('<option value="">Pilih satuan dasar terlebih dahulu</option>');
            return;
        }

        const validOptions = [{label: baseUnit['symbol'], text: `${baseUnit['symbol']} (satuan dasar)`}];

        $('#createStockUnitRows tr').each(function () {
            const label = $(this).find('.create-stock-label').val().trim();
            const factor = Number($(this).find('.create-stock-factor').val());
            if (label && Number.isFinite(factor) && factor > 0) {
                validOptions.push({label: label, text: label});
            }
        });

        validOptions.forEach(function (option) {
            select.append(`<option value="${option.label}">${option.text}</option>`);
        });

        const exists = validOptions.some(option => option.label === currentValue);
        select.val(exists ? currentValue : baseUnit['symbol']);
    }

    function collectCreateStockUnits() {
        const stockUnits = [];
        let isValid = true;

        $('#createStockUnitRows tr').each(function () {
            const unitId = $(this).find('.create-stock-unit').val();
            const label = $(this).find('.create-stock-label').val().trim();
            const conversion = parseFloat($(this).find('.create-stock-factor').val());
            const primary = $(this).find('.create-stock-primary').is(':checked');

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

    function collectCreateSaleUnits() {
        const units = [];
        let isValid = true;

        $('#createSaleUnitRows tr').each(function () {
            const unitName = $(this).find('.create-sale-unit-name').val().trim();
            const conversionFactor = parseFloat($(this).find('.create-sale-unit-factor').val());
            const sellingPrice = parseFloat($(this).find('.create-sale-unit-price').val());

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

    async function Save() {
        const categoryId = $('#productCategory').val();
        const name = $('#productName').val().trim();
        const baseUnitId = $('#productBaseUnit').val();
        const baseUnitPrice = $('#baseUnitPrice').val();
        const initialStockQuantity = $('#initialStockQuantity').val();
        const initialStockUnitLabel = $('#initialStockUnit').val();
        const image = document.getElementById('productImg').files[0];
        const stockUnits = collectCreateStockUnits();
        const saleUnits = collectCreateSaleUnits();

        if (stockUnits === null || saleUnits === null) return;
        if (!categoryId) return errorToast('Kategori produk wajib dipilih.');
        if (!name) return errorToast('Nama produk wajib diisi.');
        if (!baseUnitId) return errorToast('Satuan dasar inventori wajib dipilih.');
        if (baseUnitPrice === '' || Number(baseUnitPrice) < 0) return errorToast('Harga satuan dasar wajib diisi.');
        if (initialStockQuantity === '' || Number(initialStockQuantity) < 0) return errorToast('Stok awal wajib diisi dengan angka 0 atau lebih.');
        if (!initialStockUnitLabel) return errorToast('Satuan stok awal wajib dipilih.');
        if (!image) return errorToast('Gambar produk wajib diunggah.');

        const formData = new FormData();
        formData.append('category_id', categoryId);
        formData.append('name', name);
        formData.append('base_unit_id', baseUnitId);
        formData.append('base_unit_price', baseUnitPrice);
        formData.append('initial_stock_quantity', initialStockQuantity);
        formData.append('initial_stock_unit_label', initialStockUnitLabel);
        formData.append('stock_units', JSON.stringify(stockUnits));
        formData.append('units', JSON.stringify(saleUnits));
        formData.append('img', image);

        try {
            showLoader();
            const res = await axios.post('/create-product', formData, {
                headers: {'content-type': 'multipart/form-data'}
            });
            hideLoader();

            if (res.status === 201 && res.data.status === 'success') {
                successToast('Produk berhasil ditambahkan.');
                document.getElementById('modal-close').click();
                document.getElementById('save-form').reset();
                $('#createStockUnitRows, #createSaleUnitRows').empty();
                $('#newImg').attr('src', '{{ asset('images/default.jpg') }}');
                refreshCreateInitialUnitOptions();
                await getList();
            } else {
                errorToast(res.data.message || 'Produk gagal disimpan.');
            }
        } catch (error) {
            hideLoader();
            const errors = error.response?.data?.errors;
            const message = errors ? Object.values(errors)[0][0] : (error.response?.data?.message || 'Produk gagal disimpan.');
            errorToast(message);
        }
    }
</script>
