<!-- Modal -->
<div class="modal animated zoomIn" id="details-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Transaksi</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div id="invoice" class="modal-body p-3">
                    <div class="container-fluid">
                        <br/>
                        <div class="row">
                            <div class="col-8">
                                <span class="text-bold text-dark">PELANGGAN </span>
                                <p class="text-xs mx-0 my-1">Nama:  <span id="CNama"></span> </p>
                                <p class="text-xs mx-0 my-1">Email:  <span id="CEmail"></span></p>
                                <p class="text-xs mx-0 my-1">ID Pelanggan:  <span id="CId"></span> </p>
                            </div>
                            <div class="col-4">
                                <img class="w-40" src="{{"images/logo.png"}}">
                                <p class="text-bold mx-0 my-1 text-dark">Transaksi  </p>
                                <p class="text-xs mx-0 my-1">Tanggal: {{ date('Y-m-d') }} </p>
                            </div>
                        </div>
                        <hr class="mx-0 my-2 p-0 bg-secondary"/>
                        <div class="row">
                            <div class="col-12">
                                <table class="table w-100" id="invoiceTable">
                                    <thead class="w-100">
                                    <tr class="text-xs text-bold">
                                        <td>Nama</td>
                                        <td>Jumlah</td>
                                        <td>Total</td>
                                    </tr>
                                    </thead>
                                    <tbody  class="w-100" id="invoiceList">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <hr class="mx-0 my-2 p-0 bg-secondary"/>
                        <div class="row">
                            <div class="col-12">
                                <p class="text-bold text-xs my-1 text-dark"> TOTAL: Rp <span id="total"></span></p>
                                <p class="text-bold text-xs my-2 text-dark"> TOTAL BAYAR: Rp  <span id="payable"></span></p>
                                <p class="text-bold text-xs my-1 text-dark"> PPN(5%): Rp  <span id="vat"></span></p>
                                <p class="text-bold text-xs my-1 text-dark"> Diskon: Rp  <span id="discount"></span></p>
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


    async function TransaksiDetails(cus_id,inv_id) {

        showLoader()
        let res=await axios.post("/invoice-details",{cus_id:cus_id,inv_id:inv_id})
        hideLoader();

        function formatRupiahRoundToThousands(value){
            let num = Number(value) || 0;
            let rounded = Math.round(num/1000)*1000;
            return rounded.toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:0});
        }

        document.getElementById('CNama').innerText=res.data['customer']['name']
        document.getElementById('CId').innerText=res.data['customer']['user_id']
        document.getElementById('CEmail').innerText=res.data['customer']['email']
        document.getElementById('total').innerText=formatRupiahRoundToThousands(res.data['invoice']['total'])
        document.getElementById('payable').innerText=formatRupiahRoundToThousands(res.data['invoice']['payable'])
        document.getElementById('vat').innerText=formatRupiahRoundToThousands(res.data['invoice']['vat'])
        document.getElementById('discount').innerText=formatRupiahRoundToThousands(res.data['invoice']['discount'])


        let invoiceList=$('#invoiceList');

        invoiceList.empty();

        res.data['product'].forEach(function (item,index) {
            let row=`<tr class="text-xs">
                        <td>${item['product']['name']}</td>
                        <td>${item['qty']}</td>
                        <td>${formatRupiahRoundToThousands(item['sale_price'])}</td>
                     </tr>`
            invoiceList.append(row)
        });



        $("#details-modal").modal('show')
    }

    function CetakPage() {
        let printContents = document.getElementById('invoice').innerHTML;
        let originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        setTimeout(function() {
            location.reload();
        }, 1000);
    }
</script>
