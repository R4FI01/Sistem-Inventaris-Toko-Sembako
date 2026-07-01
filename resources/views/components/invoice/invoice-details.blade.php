<div class="modal animated zoomIn" id="details-modal" tabindex="-1" aria-labelledby="detailsInvoiceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="detailsInvoiceLabel">Detail Transaksi</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div id="invoice" class="modal-body p-3">
                <div class="container-fluid">
                    <br>
                    <div class="row">
                        <div class="col-8">
                            <span class="text-bold text-dark">PELANGGAN</span>
                            <p class="text-xs mx-0 my-1">Nama: <span id="CNama"></span></p>
                            <p class="text-xs mx-0 my-1">Nomor HP: <span id="CMobile"></span></p>
                            <p class="text-xs mx-0 my-1">ID Pelanggan: <span id="CId"></span></p>
                        </div>
                        <div class="col-4">
                            <img class="w-40" src="{{ asset('images/logo.png') }}" alt="Logo">
                            <p class="text-bold mx-0 my-1 text-dark">Transaksi</p>
                            <p class="text-xs mx-0 my-1">No Transaksi: <span id="InvoiceNumber"></span></p>
                            <p class="text-xs mx-0 my-1">Tanggal: <span id="InvoiceDate"></span></p>
                        </div>
                    </div>

                    <hr class="mx-0 my-2 p-0 bg-secondary">

                    <div class="row">
                        <div class="col-12">
                            <table class="table w-100" id="invoiceTable">
                                <thead class="w-100">
                                <tr class="text-xs text-bold">
                                    <td>Nama Produk</td>
                                    <td>Jumlah</td>
                                    <td>Total</td>
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn bg-gradient-primary" data-bs-dismiss="modal">Tutup</button>
                <button onclick="CetakPage()" class="btn bg-gradient-success">Cetak</button>
            </div>
        </div>
    </div>
</div>

<script>
    function detailQty(value) {
        return Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
    }

    async function TransaksiDetails(cusId, invId) {
        showLoader();
        const res = await axios.post('/invoice-details', {cus_id: cusId, inv_id: invId});
        hideLoader();

        const customer = res.data['customer'];
        const invoice = res.data['invoice'];

        document.getElementById('CNama').innerText = customer ? customer['name'] : '-';
        document.getElementById('CMobile').innerText = customer ? customer['mobile'] : '-';
        document.getElementById('CId').innerText = customer ? customer['id'] : '-';
        document.getElementById('InvoiceNumber').innerText = invoice?.['invoice_number'] || '-';
        document.getElementById('InvoiceDate').innerText = invoice?.['created_at'] ? invoice['created_at'].substring(0, 10) : '-';
        document.getElementById('total').innerText = formatRupiah(invoice?.['total'] || 0);
        document.getElementById('payable').innerText = formatRupiah(invoice?.['payable'] || 0);
        document.getElementById('vat').innerText = formatRupiah(invoice?.['vat'] || 0);
        document.getElementById('discount').innerText = formatRupiah(invoice?.['discount'] || 0);

        const invoiceList = $('#invoiceList');
        invoiceList.empty();

        res.data['product'].forEach(function (item) {
            const productName = item['product']?.['name'] || '-';
            const unitName = item['unit_name'] || item['product_unit']?.['unit_name'] || item['product']?.['base_unit'] || '-';
            invoiceList.append(`<tr class="text-xs">
                <td>${productName}<br><span class="text-muted">${formatRupiah(item['unit_price'] || 0)} / ${unitName}</span></td>
                <td>${detailQty(item['qty'])} ${unitName}</td>
                <td>${formatRupiah(item['sale_price'])}</td>
            </tr>`);
        });

        $('#details-modal').modal('show');
    }

    function CetakPage() {
        const printContents = document.getElementById('invoice').innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;

        setTimeout(function () {
            location.reload();
        }, 1000);
    }
</script>
