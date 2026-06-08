<div class="modal animated zoomIn" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <h3 class=" mt-3 text-warning">Hapus Data!</h3>
                <p class="mb-3">Data yang dihapus tidak dapat dikembalikan.</p>
                <input class="d-none" id="deleteID"/>
                <input class="d-none" id="deleteFilePath"/>

            </div>
            <div class="modal-footer justify-content-end">
                <div>
                    <button type="button" id="delete-modal-close" class="btn bg-gradient-success mx-2" data-bs-dismiss="modal">Batal</button>
                    <button onclick="itemDelete()" type="button" id="confirmDelete" class="btn bg-gradient-danger" >Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
     async  function  itemDelete(){
            let id=document.getElementById('deleteID').value;
            let deleteFilePath=document.getElementById('deleteFilePath').value;
            document.getElementById('delete-modal-close').click();
            showLoader();
            let res=await axios.post("/delete-product",{id:id,file_path:deleteFilePath})
            hideLoader();
            if(res.data===1){
                successToast("Data berhasil dihapus")
                await getList();
            }
            else{
                errorToast("Proses gagal!")
            }
     }
</script>
