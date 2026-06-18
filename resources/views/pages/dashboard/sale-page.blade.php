@extends('layout.sidenav-layout')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4 col-lg-4 p-2">
                <div class="shadow-sm h-100 bg-white rounded-3 p-3">
                    <div class="row">
                        <div class="col-8">
                            <span class="text-bold text-dark">PELANGGAN </span>
                            <p class="text-xs mx-0 my-1">Nama: <span id="CName"></span></p>
                            <p class="text-xs mx-0 my-1">ID Pelanggan: <span id="CId"></span></p>
                        </div>
                        <div class="col-4">
                            <img class="w-50" src="{{ asset('images/logo.png') }}">
                            <p class="text-bold mx-0 my-1 text-dark">Transaksi</p>
                            <p class="text-xs mx-0 my-1">Tanggal: {{ date('Y-m-d') }}</p>
                        </div>
                    </div>
                    <hr class="mx-0 my-2 p-0 bg-secondary"/>
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
                                <tbody class="w-100" id="invoiceList">

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <hr class="mx-0 my-2 p-0 bg-secondary"/>
                    <div class="row">
                        <div class="col-12">
                            <p class="text-bold text-xs my-1 text-dark">TOTAL: <span id="total"></span></p>
                            <p class="text-bold text-xs my-2 text-dark">TOTAL BAYAR: <span id="payable"></span></p>
                            <p class="text-bold text-xs my-1 text-dark">PPN(5%): <span id="vat"></span></p>
                            <p class="text-bold text-xs my-1 text-dark">Diskon: <span id="discount"></span></p>
                            <span class="text-xxs">Diskon(%):</span>
                            <input onkeydown="return false" value="0" min="0" type="number" step="0.25" onchange="DiscountChange()" class="form-control w-40" id="discountP"/>
                            <p>
                                <button onclick="createInvoice()" class="btn my-3 bg-gradient-primary w-40">Simpan Transaksi</button>
                            </p>
                        </div>
                        <div class="col-12 p-2">

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
                        <tbody class="w-100" id="productList">

                        </tbody>
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
                        <tbody class="w-100" id="customerList">

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal animated zoomIn" id="create-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLabel">Tambah Produk</h6>
                </div>
                <div class="modal-body">
                    <form id="add-form">
                        <div class="container">
                            <div class="row">
                                <div class="col-12 p-1">
                                    <label class="form-label">ID Produk *</label>
                                    <input type="text" class="form-control" id="PId" readonly>

                                    <label class="form-label mt-2">Nama Produk *</label>
                                    <input type="text" class="form-control" id="PName" readonly>

                                    <label class="form-label mt-2">Harga Produk *</label>
                                    <input type="text" class="form-control" id="PPrice" readonly>

                                    <label class="form-label mt-2">Stok Tersedia *</label>
                                    <input type="text" class="form-control" id="PStock" readonly>

                                    <label class="form-label mt-2">Jumlah Beli *</label>
                                    <input type="number" min="1" class="form-control" id="PQty">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="modal-close" class="btn bg-gradient-primary" data-bs-dismiss="modal" aria-label="Close">Tutup</button>
                    <button onclick="add()" id="save-btn" class="btn bg-gradient-success">Tambah</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (async () => {
            showLoader();
            await CustomerList();
            await ProductList();
            hideLoader();
        })()

        let InvoiceItemList = [];
        window.GrandTotals = {total: 0, vat: 0, discount: 0, payable: 0};

        function ShowInvoiceItem() {
            let invoiceList = $('#invoiceList');
            invoiceList.empty();

            InvoiceItemList.forEach(function (item, index) {
                let row = `<tr class="text-xs">
                        <td>${item['product_name']}</td>
                        <td>${item['qty']}</td>
                        <td>${formatRupiah(item['sale_price'])}</td>
                        <td><a data-index="${index}" class="btn remove text-xxs px-2 py-1 btn-sm m-0">Hapus</a></td>
                     </tr>`
                invoiceList.append(row)
            })

            CalculateGrandTotal();

            $('.remove').on('click', async function () {
                let index = $(this).data('index');
                removeItem(index);
            })
        }

        function removeItem(index) {
            InvoiceItemList.splice(index, 1);
            ShowInvoiceItem()
        }

        function DiscountChange() {
            CalculateGrandTotal();
        }

        function CalculateGrandTotal() {
            let Total = 0;
            let Vat = 0;
            let Payable = 0;
            let Discount = 0;
            let discountPercentage = parseFloat(document.getElementById('discountP').value) || 0;

            InvoiceItemList.forEach((item) => {
                Total = Total + parseFloat(item['sale_price'])
            })

            if (discountPercentage === 0) {
                Vat = ((Total * 5) / 100).toFixed(2);
            } else {
                Discount = ((Total * discountPercentage) / 100).toFixed(2);
                Total = (Total - ((Total * discountPercentage) / 100)).toFixed(2);
                Vat = ((Total * 5) / 100).toFixed(2);
            }

            Payable = (parseFloat(Total) + parseFloat(Vat)).toFixed(2);

            const TotalNum = parseFloat(Total) || 0;
            const VatNum = parseFloat(Vat) || 0;
            const DiscountNum = parseFloat(Discount) || 0;
            const PayableNum = parseFloat(Payable) || 0;

            window.GrandTotals = {total: TotalNum, vat: VatNum, discount: DiscountNum, payable: PayableNum};

            document.getElementById('total').innerText = formatRupiah(TotalNum);
            document.getElementById('payable').innerText = formatRupiah(PayableNum);
            document.getElementById('vat').innerText = formatRupiah(VatNum);
            document.getElementById('discount').innerText = formatRupiah(DiscountNum);
        }

        function add() {
            let PId = document.getElementById('PId').value;
            let PName = document.getElementById('PName').value;
            let PPrice = document.getElementById('PPrice').value;
            let PStock = parseInt(document.getElementById('PStock').value) || 0;
            let PQty = parseInt(document.getElementById('PQty').value) || 0;
            let existingQty = 0;

            InvoiceItemList.forEach(function (item) {
                if (String(item.product_id) === String(PId)) {
                    existingQty = existingQty + parseInt(item.qty);
                }
            })

            let remainingStock = PStock - existingQty;
            let PTotalPrice = (parseFloat(PPrice) * PQty).toFixed(2);

            if (PId.length === 0) {
                errorToast('ID produk wajib diisi');
            } else if (PName.length === 0) {
                errorToast('Nama produk wajib diisi');
            } else if (PPrice.length === 0) {
                errorToast('Harga produk wajib diisi');
            } else if (PQty <= 0) {
                errorToast('Jumlah beli harus lebih dari 0');
            } else if (PQty > remainingStock) {
                errorToast(`Stok ${PName} tidak mencukupi. Stok tersedia: ${PStock}, sudah dipilih: ${existingQty}, sisa stok: ${remainingStock}`);
            } else {
                let item = {
                    product_name: PName,
                    product_id: PId,
                    qty: PQty,
                    sale_price: PTotalPrice
                };

                InvoiceItemList.push(item);
                $('#create-modal').modal('hide')
                ShowInvoiceItem();
            }
        }

        function addModal(id, name, price, stock) {
            document.getElementById('PId').value = id;
            document.getElementById('PName').value = name;
            document.getElementById('PPrice').value = price;
            document.getElementById('PStock').value = stock;
            document.getElementById('PQty').value = '';
            $('#create-modal').modal('show')
        }

        async function CustomerList() {
            let res = await axios.get('/list-customer');
            let customerList = $('#customerList');
            let customerTable = $('#customerTable');

            if ($.fn.DataTable.isDataTable('#customerTable')) {
                customerTable.DataTable().destroy();
            }

            customerList.empty();

            res.data.forEach(function (item) {
                let row = `<tr class="text-xs">
                        <td><i class="bi bi-person"></i> ${item['name']}</td>
                        <td><a data-name="${item['name']}" data-id="${item['id']}" class="btn btn-outline-dark addCustomer text-xxs px-2 py-1 btn-sm m-0">Pilih</a></td>
                     </tr>`
                customerList.append(row)
            })

            $('.addCustomer').on('click', async function () {
                let CName = $(this).data('name');
                let CId = $(this).data('id');

                $('#CName').text(CName)
                $('#CId').text(CId)
            })

            new DataTable('#customerTable', {
                order: [[0, 'desc']],
                scrollCollapse: false,
                info: false,
                lengthChange: false
            });
        }

        async function ProductList() {
            let res = await axios.get('/list-product');
            let productList = $('#productList');
            let productTable = $('#productTable');

            if ($.fn.DataTable.isDataTable('#productTable')) {
                productTable.DataTable().destroy();
            }

            productList.empty();

            res.data.forEach(function (item) {
                let row = `<tr class="text-xs">
                        <td>
                            <img class="w-10" src="${item['img_url']}"/>
                            ${item['name']} (${formatRupiah(item['price'])})
                            <br>
                            <span class="badge bg-gradient-info">Stok: ${item['unit']}</span>
                        </td>
                        <td><a data-name="${item['name']}" data-price="${item['price']}" data-stock="${item['unit']}" data-id="${item['id']}" class="btn btn-outline-dark text-xxs px-2 py-1 addProduct btn-sm m-0">Pilih</a></td>
                     </tr>`
                productList.append(row)
            })

            $('.addProduct').on('click', async function () {
                let PName = $(this).data('name');
                let PPrice = $(this).data('price');
                let PStock = $(this).data('stock');
                let PId = $(this).data('id');
                addModal(PId, PName, PPrice, PStock)
            })

            new DataTable('#productTable', {
                order: [[0, 'desc']],
                scrollCollapse: false,
                info: false,
                lengthChange: false
            });
        }

        async function createInvoice() {
            let total = window.GrandTotals.total;
            let discount = window.GrandTotals.discount;
            let vat = window.GrandTotals.vat;
            let payable = window.GrandTotals.payable;
            let CId = document.getElementById('CId').innerText;

            let Data = {
                'total': total,
                'discount': discount,
                'vat': vat,
                'payable': payable,
                'customer_id': CId,
                'products': InvoiceItemList
            }

            if (CId.length === 0) {
                errorToast('Pelanggan wajib dipilih !')
            } else if (InvoiceItemList.length === 0) {
                errorToast('Produk wajib dipilih !')
            } else {
                showLoader();

                try {
                    let res = await axios.post('/invoice-create', Data)
                    hideLoader();

                    if (res.data.status === 'success' || res.data === 1) {
                        successToast('Transaksi berhasil dibuat');
                        window.location.href = '/invoicePage'
                    } else {
                        errorToast(res.data.message || 'Terjadi kesalahan')
                    }
                } catch (error) {
                    hideLoader();
                    let message = error.response?.data?.message || 'Terjadi kesalahan saat membuat transaksi';
                    errorToast(message)
                }
            }
        }
    </script>
@endsection
