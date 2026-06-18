<div class="modal animated zoomIn" id="update-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Perbarui Pelanggan</h5>
            </div>
            <div class="modal-body">
                <form id="update-form">
                    <div class="container">
                        <div class="row">
                            <div class="col-12 p-1">
                                <label class="form-label">Nama Pelanggan *</label>
                                <input type="text" class="form-control" id="customerNameUpdate">

                                <label class="form-label mt-3">Nomor HP Pelanggan *</label>
                                <input type="text" class="form-control" id="customerMobileUpdate">

                                <input type="text" class="d-none" id="updateID">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="update-modal-close" class="btn bg-gradient-primary" data-bs-dismiss="modal" aria-label="Close">Tutup</button>
                <button onclick="Update()" id="update-btn" class="btn bg-gradient-success" >Perbarui</button>
            </div>
        </div>
    </div>
</div>


<script>



    async function FillUpUpdateForm(id){
        document.getElementById('updateID').value=id;
        showLoader();
        let res=await axios.post("/customer-by-id",{id:id})
        hideLoader();
        document.getElementById('customerNameUpdate').value=res.data['name'];
        document.getElementById('customerMobileUpdate').value=res.data['mobile'];
    }


    async function Update() {

        let customerName = document.getElementById('customerNameUpdate').value;
        let customerMobile = document.getElementById('customerMobileUpdate').value;
        let updateID = document.getElementById('updateID').value;


        if (customerName.length === 0) {
            errorToast("Nama pelanggan wajib diisi !")
        }
        else if(customerMobile.length===0){
            errorToast("Nomor HP pelanggan wajib diisi !")
        }
        else {

            document.getElementById('update-modal-close').click();

            showLoader();

            let res = await axios.post("/update-customer",{name:customerName,mobile:customerMobile,id:updateID})

            hideLoader();

            if(res.status===200 && res.data===1){

                successToast('Data berhasil diperbarui');

                document.getElementById("update-form").reset();

                await getList();
            }
            else{
                errorToast("Proses gagal !")
            }
        }
    }

</script>