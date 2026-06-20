<div class="modal animated zoomIn" id="create-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Tambah Produk Sembako</h5>
                </div>
                <div class="modal-body">
                    <form id="save-form">
                    <div class="container">
                        <div class="row">
                            <div class="col-12 p-1">

                                <label class="form-label">Kategori</label>
                                <select type="text" class="form-control form-select" id="productCategory">
                                    <option value="">Pilih Kategori</option>
                                </select>

                                <label class="form-label mt-2">Nama Produk</label>
                                <input type="text" class="form-control" id="productName">

                                <label class="form-label mt-2">Harga</label>
                                <input type="text" class="form-control" id="productPrice">

                                <label class="form-label mt-2">Jumlah Stok</label>
                                <input type="text" class="form-control" id="productUnit">

                                <br/>
                                <img class="w-15" id="newImg" src="{{asset('images/default.jpg')}}"/>
                                <br/>

                                <label class="form-label">Gambar</label>
                                <input oninput="newImg.src=window.URL.createObjectURL(this.files[0])" type="file" class="form-control" id="productImg">

                            </div>
                        </div>
                    </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="modal-close" class="btn bg-gradient-primary mx-2" data-bs-dismiss="modal" aria-label="Close">Tutup</button>
                    <button onclick="Save()" id="save-btn" class="btn bg-gradient-success" >Simpan</button>
                </div>
            </div>
    </div>
</div>


<script>



    FillCategoryDropDown();

    async function FillCategoryDropDown(){
        let res = await axios.get("/list-category")
        res.data.forEach(function (item,i) {
            let option=`<option value="${item['id']}">${item['name']}</option>`
            $("#productCategory").append(option);
        })
    }


    async function Save() {

        let productCategory=document.getElementById('productCategory').value;
        let productName = document.getElementById('productName').value;
        let productPrice = document.getElementById('productPrice').value;
        let productUnit = document.getElementById('productUnit').value;
        let productImg = document.getElementById('productImg').files[0];

        if (productCategory.length === 0) {
            errorToast("Kategori produk wajib dipilih !")
        }
        else if(productName.length===0){
            errorToast("Nama produk wajib diisi !")
        }
        else if(productPrice.length===0){
            errorToast("Harga produk wajib diisi !")
        }
        else if(productUnit.length===0){
            errorToast("Jumlah stok wajib diisi !")
        }
        else if(!productImg){
            errorToast("Gambar produk wajib diisi !")
        }

        else {

            document.getElementById('modal-close').click();

            let formData=new FormData();
            formData.append('img',productImg)
            formData.append('name',productName)
            formData.append('price',productPrice)
            formData.append('unit',productUnit)
            formData.append('category_id',productCategory)

            const config = {
                headers: {
                    'content-type': 'multipart/form-data'
                }
            }

            try {
            showLoader();

            let res = await axios.post("/create-product", formData, config);

            hideLoader();

            if (res.status === 201) {
                successToast('Data berhasil disimpan');
                document.getElementById("save-form").reset();
                document.getElementById("newImg").src = "{{asset('images/default.jpg')}}";
                await getList();
            } else {
                errorToast("Proses gagal !");
            }

            } catch (error) {
                hideLoader();

                if (error.response && error.response.status === 422) {
                    let errors = error.response.data.errors;
                    let firstError = Object.values(errors)[0][0];
                    errorToast(firstError);
                } else {
                    errorToast("Terjadi kesalahan saat menyimpan produk.");
                }
            }
        }
    }
</script>