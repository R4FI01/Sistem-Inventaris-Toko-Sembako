@extends('layout.sidenav-layout')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4 col-lg-4 p-2">
                <div class="shadow-sm h-100 bg-white rounded-3 p-3">
                    <div class="row">
                        <div class="col-8">
                            <span class="text-bold text-dark">PELANGGAN</span>
                            <p class="text-xs mx-0 my-1">Nama: <span id="CName"></span></p>
                            <p class="text-xs mx-0 my-1">ID Pelanggan: <span id="CId"></span></p>
                        </div>
                        <div class="col-4">
                            <img class="w-50" src="{{ asset('images/logo.png') }}" alt="Logo">
                            <p class="text-bold mx-0 my-1 text-dark">Transaksi</p>
                            <p class="text-xs mx-0 my-1">Tanggal: {{ date('Y-m-d') }}</p>
                        </div>
                    </div>
                    <hr class="mx-0 my-2 p-0 bg-secondary">
                    <div class="row">
                        <div class="col-12">
                            <table class="table w-100" id="invoiceTable">
                                <thead class="w-100">
                                <tr class="text-xs">
                                    <td>Nama Produk</td>
                                    <td>Jumlah</td>
                                    <td>Total</td>
                                    <td>Hapus</td>
                                </tr>
                                </thead>
                                <tbody class="w-100" id="invoiceList"></tbody>
                            </table>
                        </div>
                    </div>
                    <hr class="mx-0 my-2 p-0 bg-secondary">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-bold text-xs my-1 text-dark">TOTAL: <span id="total"></span></p>
                            <p class="text-bold text-xs my-2 text-dark">TOTAL BAYAR: <span id="payable"></span></p>
                            <p class="text-bold text-xs my-1 text-dark">PPN (5%): <span id="vat"></span></p>
                            <p class="text-bold text-xs my-1 text-dark">Diskon: <span id="discount"></span></p>
                            <span class="text-xxs">Diskon (%):</span>
                            <input value="0" min="0" max="100" type="number" step="0.25" onchange="DiscountChange()" class="form-control w-40" id="discountP">
                            <p>
                                <button onclick="createInvoice()" class="btn my-3 bg-gradient-primary w-40">Simpan Transaksi</button>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-4 p-2">
                <div class="shadow-sm h-100 bg-white rounded-3 p-3">
                    <table class="table w-100" id="productTable">
                        <thead class="w-100">
                        <tr class="text-xs text-bold">
                            <td>Produk</td>
                            <td>Pilih</td>
                        </tr>
                        </thead>
                        <tbody class="w-100" id="productList"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4 col-lg-4 p-2">
                <div class="shadow-sm h-100 bg-white rounded-3 p-3">
                    <table class="table table-sm w-100" id="customerTable">
                        <thead class="w-100">
                        <tr class="text-xs text-bold">
                            <td>Pelanggan</td>
                            <td>Pilih</td>
                        </tr>
                        </thead>
                        <tbody class="w-100" id="customerList"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal animated zoomIn" id="create-modal" tabindex="-1" aria-labelledby="saleUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="saleUnitModalLabel">Tambah Produk ke Transaksi</h6>
                </div>
                <div class="modal-body">
                    <form id="add-form">
                        <div class="container">
                            <div class="row">
                                <div class="col-12 p-1">
                                    <label class="form-label">ID Produk</label>
                                    <input type="text" class="form-control" id="PId" readonly>
                                    <input type="hidden" id="PStockBase">
                                    <input type="hidden" id="PBaseUnit">

                                    <label class="form-label mt-2">Nama Produk</label>
                                    <input type="text" class="form-control" id="PName" readonly>

                                    <label class="form-label mt-2">Satuan Jual</label>
                                    <select class="form-select" id="PUnit" onchange="changeSaleUnit()"></select>

                                    <label class="form-label mt-2">Harga per Satuan Jual</label>
                                    <input type="text" class="form-control" id="PPrice" readonly>

                                    <label class="form-label mt-2">Stok Tersedia</label>
                                    <input type="text" class="form-control" id="PStock" readonly>
                                    <small class="text-muted" id="PStockInfo"></small>

                                    <label class="form-label mt-2">Jumlah Beli</label>
                                    <input type="number" min="0.001" step="0.001" class="form-control" id="PQty">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-gradient-primary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" onclick="add()" id="save-btn" class="btn bg-gradient-success">Tambah</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let InvoiceItemList = [];
        let ProductCatalog = {};
        window.GrandTotals = {total: 0, vat: 0, discount: 0, payable: 0};

        (async () => {
            showLoader();
            await CustomerList();
            await ProductList();
            hideLoader();
        })();

        function displayQty(value) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 3
            });
        }

        function saleBaseUnit(product) {
            const value = product?.['base_unit'];
            if (value && typeof value === 'object') {
                return value['symbol'] || value['name'] || product?.['inventory_unit']?.['symbol'] || 'pcs';
            }
            return value || product?.['inventory_unit']?.['symbol'] || 'pcs';
        }

        function operationalStockText(product) {
            const stockBase = Number(product?.['stock_base'] ?? product?.['unit'] ?? 0);
            const baseUnit = saleBaseUnit(product);
            const stockUnits = product?.['stock_units'] || [];
            const primaryUnit = stockUnits.find(unit => Boolean(unit['is_primary_receipt_unit'])) || stockUnits[0];

            if (!primaryUnit || Number(primaryUnit['conversion_to_base']) <= 1) {
                return `${displayQty(stockBase)} ${baseUnit}`;
            }

            const conversion = Number(primaryUnit['conversion_to_base']);
            const packages = Math.floor((stockBase + 0.000001) / conversion);
            const remainder = Number((stockBase - packages * conversion).toFixed(3));
            const parts = [];

            if (packages > 0) parts.push(`${displayQty(packages)} ${primaryUnit['label']}`);
            if (remainder > 0.000001 || parts.length === 0) parts.push(`${displayQty(Math.max(remainder, 0))} ${baseUnit}`);

            return `Setara ${parts.join(' + ')}`;
        }

        function ShowInvoiceItem() {
            const invoiceList = $('#invoiceList');
            invoiceList.empty();

            InvoiceItemList.forEach(function (item, index) {
                const row = `<tr class="text-xs">
                    <td>${item['product_name']}<br><span class="text-muted">${formatRupiah(item['unit_price'])} / ${item['unit_name']}</span></td>
                    <td>${displayQty(item['qty'])} ${item['unit_name']}</td>
                    <td>${formatRupiah(item['sale_price'])}</td>
                    <td><a data-index="${index}" class="btn remove text-xxs px-2 py-1 btn-sm m-0">Hapus</a></td>
                </tr>`;
                invoiceList.append(row);
            });

            CalculateGrandTotal();

            $('.remove').on('click', function () {
                removeItem($(this).data('index'));
            });
        }

        function removeItem(index) {
            InvoiceItemList.splice(index, 1);
            ShowInvoiceItem();
        }

        function DiscountChange() {
            CalculateGrandTotal();
        }

        function CalculateGrandTotal() {
            const total = InvoiceItemList.reduce((sum, item) => sum + Number(item['sale_price']), 0);
            const discountPercentage = Math.min(Math.max(Number(document.getElementById('discountP').value) || 0, 0), 100);
            const discount = Number((total * discountPercentage / 100).toFixed(2));
            const vat = Number(((total - discount) * 5 / 100).toFixed(2));
            const payable = Number((total - discount + vat).toFixed(2));

            window.GrandTotals = {total, vat, discount, payable};

            document.getElementById('total').innerText = formatRupiah(total);
            document.getElementById('payable').innerText = formatRupiah(payable);
            document.getElementById('vat').innerText = formatRupiah(vat);
            document.getElementById('discount').innerText = formatRupiah(discount);
        }

        function getSelectedProductUnit() {
            const product = ProductCatalog[document.getElementById('PId').value];
            const productUnitId = document.getElementById('PUnit').value;

            return product?.units?.find(unit => String(unit['id']) === String(productUnitId));
        }

        function changeSaleUnit() {
            const productUnit = getSelectedProductUnit();
            const stockBase = Number(document.getElementById('PStockBase').value || 0);
            const baseUnit = document.getElementById('PBaseUnit').value;

            if (!productUnit) return;

            const conversionFactor = Number(productUnit['conversion_factor']);
            const purchasableQty = conversionFactor > 0 ? stockBase / conversionFactor : 0;

            document.getElementById('PPrice').value = productUnit['selling_price'];
            document.getElementById('PStock').value = `${displayQty(stockBase)} ${baseUnit} • ${operationalStockText(ProductCatalog[document.getElementById('PId').value])}`;
            document.getElementById('PStockInfo').innerText = `Maksimal dapat dijual: ${displayQty(purchasableQty)} ${productUnit['unit_name']}.`;
        }

        function add() {
            const productId = document.getElementById('PId').value;
            const productName = document.getElementById('PName').value;
            const productUnit = getSelectedProductUnit();
            const qty = Number(document.getElementById('PQty').value);
            const stockBase = Number(document.getElementById('PStockBase').value || 0);

            if (!productId || !productName) return errorToast('Produk wajib dipilih.');
            if (!productUnit) return errorToast('Satuan jual wajib dipilih.');
            if (!Number.isFinite(qty) || qty <= 0) return errorToast('Jumlah beli harus lebih dari 0.');

            const conversionFactor = Number(productUnit['conversion_factor']);
            const requestedBaseQty = qty * conversionFactor;
            const selectedBaseQty = InvoiceItemList
                .filter(item => String(item['product_id']) === String(productId))
                .reduce((sum, item) => sum + Number(item['qty']) * Number(item['conversion_factor']), 0);
            const remainingBaseQty = stockBase - selectedBaseQty;

            if (requestedBaseQty - remainingBaseQty > 0.000001) {
                return errorToast(`Stok ${productName} tidak mencukupi. Sisa stok: ${displayQty(Math.max(remainingBaseQty, 0))} ${document.getElementById('PBaseUnit').value}.`);
            }

            const unitPrice = Number(productUnit['selling_price']);
            const existingItem = InvoiceItemList.find(item =>
                String(item['product_id']) === String(productId)
                && String(item['product_unit_id']) === String(productUnit['id'])
            );

            if (existingItem) {
                existingItem['qty'] = Number((Number(existingItem['qty']) + qty).toFixed(3));
                existingItem['sale_price'] = Number((Number(existingItem['qty']) * unitPrice).toFixed(2));
            } else {
                InvoiceItemList.push({
                    product_name: productName,
                    product_id: productId,
                    product_unit_id: productUnit['id'],
                    unit_name: productUnit['unit_name'],
                    conversion_factor: conversionFactor,
                    unit_price: unitPrice,
                    qty: qty,
                    sale_price: Number((qty * unitPrice).toFixed(2))
                });
            }

            $('#create-modal').modal('hide');
            ShowInvoiceItem();
        }

        function addModal(productId) {
            const product = ProductCatalog[productId];
            const units = product?.units || [];

            if (!product || units.length === 0) {
                return errorToast('Produk belum memiliki satuan jual. Silakan perbarui produk terlebih dahulu.');
            }

            document.getElementById('PId').value = product['id'];
            document.getElementById('PName').value = product['name'];
            document.getElementById('PStockBase').value = product['stock_base'] ?? product['unit'];
            document.getElementById('PBaseUnit').value = saleBaseUnit(product);
            document.getElementById('PQty').value = '';

            const unitSelect = $('#PUnit');
            unitSelect.empty();
            units.forEach(function (unit) {
                unitSelect.append(`<option value="${unit['id']}">${unit['unit_name']} (${displayQty(unit['conversion_factor'])} ${saleBaseUnit(product)})</option>`);
            });

            changeSaleUnit();
            $('#create-modal').modal('show');
        }

        async function CustomerList() {
            const res = await axios.get('/list-customer');
            const customerList = $('#customerList');
            const customerTable = $('#customerTable');

            if ($.fn.DataTable.isDataTable('#customerTable')) {
                customerTable.DataTable().destroy();
            }

            customerList.empty();

            res.data.forEach(function (item) {
                customerList.append(`<tr class="text-xs">
                    <td><i class="bi bi-person"></i> ${item['name']}</td>
                    <td><a data-name="${item['name']}" data-id="${item['id']}" class="btn btn-outline-dark addCustomer text-xxs px-2 py-1 btn-sm m-0">Pilih</a></td>
                </tr>`);
            });

            $('.addCustomer').on('click', function () {
                $('#CName').text($(this).data('name'));
                $('#CId').text($(this).data('id'));
            });

            new DataTable('#customerTable', {
                order: [[0, 'asc']],
                scrollCollapse: false,
                info: false,
                lengthChange: false
            });
        }

        async function ProductList() {
            const res = await axios.get('/list-product');
            const productList = $('#productList');
            const productTable = $('#productTable');

            if ($.fn.DataTable.isDataTable('#productTable')) {
                productTable.DataTable().destroy();
            }

            productList.empty();
            ProductCatalog = {};

            res.data.forEach(function (item) {
                ProductCatalog[item['id']] = item;
                const baseUnit = saleBaseUnit(item);
                const stockBase = item['stock_base'] ?? item['unit'];
                const unitInfo = (item['units'] || [])
                    .map(unit => `${unit['unit_name']}: ${formatRupiah(unit['selling_price'])}`)
                    .join('<br>');

                productList.append(`<tr class="text-xs">
                    <td>
                        <img class="w-10" src="${item['img_url']}" alt="${item['name']}">
                        ${item['name']}<br>
                        <span class="badge bg-gradient-info">Stok: ${displayQty(stockBase)} ${baseUnit}</span>
                        <div class="mt-1 text-muted">${operationalStockText(item)}</div>
                        <div class="mt-1 text-muted">${unitInfo}</div>
                    </td>
                    <td><a data-id="${item['id']}" class="btn btn-outline-dark text-xxs px-2 py-1 addProduct btn-sm m-0">Pilih</a></td>
                </tr>`);
            });

            $('.addProduct').on('click', function () {
                addModal($(this).data('id'));
            });

            new DataTable('#productTable', {
                order: [[0, 'asc']],
                scrollCollapse: false,
                info: false,
                lengthChange: false
            });
        }

        async function createInvoice() {
            const customerId = document.getElementById('CId').innerText;
            const discountPercentage = Number(document.getElementById('discountP').value) || 0;

            if (!customerId) return errorToast('Pelanggan wajib dipilih.');
            if (InvoiceItemList.length === 0) return errorToast('Produk wajib dipilih.');

            try {
                showLoader();
                const res = await axios.post('/invoice-create', {
                    customer_id: customerId,
                    discount_percentage: discountPercentage,
                    products: InvoiceItemList.map(item => ({
                        product_id: item['product_id'],
                        product_unit_id: item['product_unit_id'],
                        qty: item['qty']
                    }))
                });
                hideLoader();

                if (res.data.status === 'success') {
                    successToast('Transaksi berhasil dibuat.');
                    window.location.href = '/invoicePage';
                } else {
                    errorToast(res.data.message || 'Terjadi kesalahan.');
                }
            } catch (error) {
                hideLoader();
                errorToast(error.response?.data?.message || 'Terjadi kesalahan saat membuat transaksi.');
            }
        }
    </script>
@endsection
