<div class="modal animated zoomIn" id="create-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Tambah Pelanggan</h5>
                </div>
                <div class="modal-body">
                    <form id="save-form">
                    <div class="container">
                        <div class="row">
                            <div class="col-12 p-1">
                                <label class="form-label">Nama Pelanggan *</label>
                                <input type="text" class="form-control" id="customerName">
                                <label class="form-label">Email Pelanggan *</label>
                                <input type="text" class="form-control" id="customerEmail">
                                <label class="form-label">Nomor HP Pelanggan *</label>
                                <input type="text" class="form-control" id="customerMobile">
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="modal-close" class="btn bg-gradient-primary" data-bs-dismiss="modal" aria-label="Close">Tutup</button>
                    <button onclick="Save()" id="save-btn" class="btn bg-gradient-success" >Simpan</button>
                </div>
            </div>
    </div>
</div>


<script>

    async function Save() {

        let customerName = document.getElementById('customerName').value;
        let customerEmail = document.getElementById('customerEmail').value;
        let customerMobile = document.getElementById('customerMobile').value;

        if (customerName.length === 0) {
            errorToast("Nama pelanggan wajib diisi !")
        }
        else if(customerEmail.length===0){
            errorToast("Email pelanggan wajib diisi !")
        }
        else if(customerMobile.length===0){
            errorToast("Nomor HP pelanggan wajib diisi !")
        }
        else {

            document.getElementById('modal-close').click();

            showLoader();
            let res = await axios.post("/create-customer",{name:customerName,email:customerEmail,mobile:customerMobile})
            hideLoader();

            if(res.status===201){

                successToast('Data berhasil disimpan');

                document.getElementById("save-form").reset();

                await getList();
            }
            else{
                errorToast("Proses gagal !")
            }
        }
    }

</script>