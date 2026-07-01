	<div class="modal animated zoomIn" id="update-modal" tabindex="-1" aria-labelledby="updateProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateProductLabel">Perbarui Produk Sembako</h5>
            </div>
            <div class="modal-body">
                <form id="update-form">
                    <div class="container">
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
                                        <label class="form-label">Satuan Dasar</label>
                                        <input type="text" class="form-control" id="productBaseUnitUpdate">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stok dalam Satuan Dasar</label>
                                        <input type="number" min="0" step="0.001" class="form-control" id="productStockBaseUpdate">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Harga Satuan Dasar</label>
                                        <input type="number" min="0" step="0.01" class="form-control" id="baseUnitPriceUpdate">
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0">Satuan Jual Tambahan</label>
                                        <small class="d-block text-muted">Satuan dasar dikelola otomatis dengan konversi 1.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm bg-gradient-primary" onclick="addUpdateUnitRow()">+ Tambah Satuan</button>
                                </div>

                                <div class="table-responsive mt-2">
                                    <table class="table table-sm">
                                        <thead>
                                        <tr>
                                            <th>Nama Satuan</th>
                                            <th>Konversi ke Satuan Dasar</th>
                                            <th>Harga Jual</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody id="updateUnitRows"></tbody>
                                    </table>
                                </div>

                                <img class="w-15" id="oldImg" src="{{ asset('images/default.jpg') }}" alt="Pratinjau gambar produk"/>
                                <br>
                                <label class="form-label mt-2">Gambar Baru, opsional</label>
                                <input type="file" class="form-control" id="productImgUpdate" accept=".jpg,.jpeg,.png,.webp"
                                       onchange="previewUpdateImage(this)">

                                <input type="hidden" id="updateID">
                                <input type="hidden" id="filePath">
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
    async function UpdateFillCategoryDropDown(selectedCategoryId = '') {
        const res = await axios.get('/list-category');
        const category = $('#productCategoryUpdate');

        category.empty().append('<option value="">Pilih Kategori</option>');
        res.data.forEach(function (item) {
            category.append(`<option value="${item['id']}">${item['name']}</option>`);
        });

        category.val(selectedCategoryId);
    }

    function previewUpdateImage(input) {
        if (input.files && input.files[0]) {
            document.getElementById('oldImg').src = window.URL.createObjectURL(input.files[0]);
        }
    }

    function addUpdateUnitRow(unit = {}) {
        const row = `
            <tr>
                <td><input type="text" class="form-control form-control-sm update-unit-name" value="${unit.unit_name || ''}" placeholder="Contoh: dus"></td>
                <td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm update-unit-factor" value="${unit.conversion_factor || ''}" placeholder="Contoh: 12"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm update-unit-price" value="${unit.selling_price || ''}" placeholder="Contoh: 168000"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove()">Hapus</button></td>
            </tr>`;

        $('#updateUnitRows').append(row);
    }

    function collectUpdateUnits() {
        let units = [];
        let isValid = true;

        $('#updateUnitRows tr').each(function () {
            const unitName = $(this).find('.update-unit-name').val().trim();
            const conversionFactor = parseFloat($(this).find('.update-unit-factor').val());
            const sellingPrice = parseFloat($(this).find('.update-unit-price').val());

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
            errorToast('Setiap satuan tambahan harus memiliki nama, konversi, dan harga jual yang valid.');
            return null;
        }

        return units;
    }

    async function FillUpUpdateForm(id, filePath) {
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
            document.getElementById('filePath').value = filePath;
            document.getElementById('oldImg').src = product['img_url'];
            document.getElementById('productNameUpdate').value = product['name'];
            document.getElementById('productBaseUnitUpdate').value = product['base_unit'] || 'pcs';
            document.getElementById('productStockBaseUpdate').value = product['stock_base'] ?? product['unit'];
            document.getElementById('baseUnitPriceUpdate').value = product['price'];
            document.getElementById('productImgUpdate').value = '';

            $('#updateUnitRows').empty();
            (product['units'] || [])
                .filter(unit => !unit['is_default'])
                .forEach(unit => addUpdateUnitRow(unit));
        } catch (error) {
            errorToast('Data produk gagal dimuat.');
        } finally {
            hideLoader();
        }
    }

    async function update() {
        const productCategory = document.getElementById('productCategoryUpdate').value;
        const productName = document.getElementById('productNameUpdate').value.trim();
        const baseUnit = document.getElementById('productBaseUnitUpdate').value.trim();
        const stockBase = document.getElementById('productStockBaseUpdate').value;
        const baseUnitPrice = document.getElementById('baseUnitPriceUpdate').value;
        const updateId = document.getElementById('updateID').value;
        const productImage = document.getElementById('productImgUpdate').files[0];
        const units = collectUpdateUnits();

        if (units === null) return;
        if (!productCategory) return errorToast('Kategori produk wajib dipilih.');
        if (!productName) return errorToast('Nama produk wajib diisi.');
        if (!baseUnit) return errorToast('Satuan dasar wajib diisi.');
        if (stockBase === '' || Number(stockBase) < 0) return errorToast('Stok dasar wajib diisi dengan angka 0 atau lebih.');
        if (baseUnitPrice === '' || Number(baseUnitPrice) < 0) return errorToast('Harga satuan dasar wajib diisi.');

        const formData = new FormData();
        formData.append('id', updateId);
        formData.append('category_id', productCategory);
        formData.append('name', productName);
        formData.append('base_unit', baseUnit);
        formData.append('stock_base', stockBase);
        formData.append('base_unit_price', baseUnitPrice);
        formData.append('units', JSON.stringify(units));

        if (productImage) {
            formData.append('img', productImage);
        }

        try {
            showLoader();
            const res = await axios.post('/update-product', formData, {
                headers: {'content-type': 'multipart/form-data'}
            });
            hideLoader();

            if (res.status === 200 && res.data.status === 'success') {
                successToast('Data berhasil diperbarui.');
                document.getElementById('update-modal-close').click();
                await getList();
            } else {
                errorToast(res.data.message || 'Proses gagal.');
            }
        } catch (error) {
            hideLoader();
            const errors = error.response?.data?.errors;
            const message = errors ? Object.values(errors)[0][0] : (error.response?.data?.message || 'Terjadi kesalahan saat memperbarui produk.');
            errorToast(message);
        }
    }
</script>
