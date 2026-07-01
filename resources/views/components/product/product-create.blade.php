<div class="modal animated zoomIn" id="create-modal" tabindex="-1" aria-labelledby="createProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createProductLabel">Tambah Produk Sembako</h5>
            </div>
            <div class="modal-body">
                <form id="save-form">
                    <div class="container">
                        <div class="row">
                            <div class="col-12 p-1">
                                <label class="form-label">Kategori</label>
                                <select class="form-control form-select" id="productCategory">
                                    <option value="">Pilih Kategori</option>
                                </select>

                                <label class="form-label mt-2">Nama Produk</label>
                                <input type="text" class="form-control" id="productName">

                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Satuan Dasar</label>
                                        <input type="text" class="form-control" id="productBaseUnit" placeholder="Contoh: pcs, botol, kg">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Stok dalam Satuan Dasar</label>
                                        <input type="number" min="0" step="0.001" class="form-control" id="productStockBase" placeholder="Contoh: 120">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Harga Satuan Dasar</label>
                                        <input type="number" min="0" step="0.01" class="form-control" id="baseUnitPrice" placeholder="Contoh: 15000">
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="form-label mb-0">Satuan Jual Tambahan</label>
                                        <small class="d-block text-muted">Satuan dasar akan dibuat otomatis dengan konversi 1.</small>
                                    </div>
                                    <button type="button" class="btn btn-sm bg-gradient-primary" onclick="addCreateUnitRow()">+ Tambah Satuan</button>
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
                                        <tbody id="createUnitRows"></tbody>
                                    </table>
                                </div>

                                <img class="w-15" id="newImg" src="{{ asset('images/default.jpg') }}" alt="Pratinjau gambar produk"/>
                                <br>

                                <label class="form-label mt-2">Gambar</label>
                                <input type="file" class="form-control" id="productImg" accept=".jpg,.jpeg,.png,.webp"
                                       onchange="previewCreateImage(this)">
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
    FillCategoryDropDown();

    async function FillCategoryDropDown() {
        let res = await axios.get('/list-category');
        let category = $('#productCategory');

        category.empty().append('<option value="">Pilih Kategori</option>');

        res.data.forEach(function (item) {
            category.append(`<option value="${item['id']}">${item['name']}</option>`);
        });
    }

    function previewCreateImage(input) {
        if (input.files && input.files[0]) {
            document.getElementById('newImg').src = window.URL.createObjectURL(input.files[0]);
        }
    }

    function addCreateUnitRow(unit = {}) {
        const row = `
            <tr>
                <td><input type="text" class="form-control form-control-sm create-unit-name" value="${unit.unit_name || ''}" placeholder="Contoh: dus"></td>
                <td><input type="number" min="0.001" step="0.001" class="form-control form-control-sm create-unit-factor" value="${unit.conversion_factor || ''}" placeholder="Contoh: 12"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm create-unit-price" value="${unit.selling_price || ''}" placeholder="Contoh: 168000"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove()">Hapus</button></td>
            </tr>`;

        $('#createUnitRows').append(row);
    }

    function collectCreateUnits() {
        let units = [];
        let isValid = true;

        $('#createUnitRows tr').each(function () {
            const unitName = $(this).find('.create-unit-name').val().trim();
            const conversionFactor = parseFloat($(this).find('.create-unit-factor').val());
            const sellingPrice = parseFloat($(this).find('.create-unit-price').val());

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

    async function Save() {
        const productCategory = document.getElementById('productCategory').value;
        const productName = document.getElementById('productName').value.trim();
        const baseUnit = document.getElementById('productBaseUnit').value.trim();
        const stockBase = document.getElementById('productStockBase').value;
        const baseUnitPrice = document.getElementById('baseUnitPrice').value;
        const productImg = document.getElementById('productImg').files[0];
        const units = collectCreateUnits();

        if (units === null) return;
        if (!productCategory) return errorToast('Kategori produk wajib dipilih.');
        if (!productName) return errorToast('Nama produk wajib diisi.');
        if (!baseUnit) return errorToast('Satuan dasar wajib diisi.');
        if (stockBase === '' || Number(stockBase) < 0) return errorToast('Stok dasar wajib diisi dengan angka 0 atau lebih.');
        if (baseUnitPrice === '' || Number(baseUnitPrice) < 0) return errorToast('Harga satuan dasar wajib diisi.');
        if (!productImg) return errorToast('Gambar produk wajib diunggah.');

        const formData = new FormData();
        formData.append('img', productImg);
        formData.append('name', productName);
        formData.append('category_id', productCategory);
        formData.append('base_unit', baseUnit);
        formData.append('stock_base', stockBase);
        formData.append('base_unit_price', baseUnitPrice);
        formData.append('units', JSON.stringify(units));

        try {
            showLoader();
            const res = await axios.post('/create-product', formData, {
                headers: {'content-type': 'multipart/form-data'}
            });
            hideLoader();

            if (res.status === 201 && res.data.status === 'success') {
                successToast('Data berhasil disimpan.');
                document.getElementById('save-form').reset();
                document.getElementById('newImg').src = "{{ asset('images/default.jpg') }}";
                $('#createUnitRows').empty();
                document.getElementById('modal-close').click();
                await getList();
            } else {
                errorToast(res.data.message || 'Proses gagal.');
            }
        } catch (error) {
            hideLoader();
            const errors = error.response?.data?.errors;
            const message = errors ? Object.values(errors)[0][0] : (error.response?.data?.message || 'Terjadi kesalahan saat menyimpan produk.');
            errorToast(message);
        }
    }
</script>
