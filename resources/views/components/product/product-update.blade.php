<div class="modal animated zoomIn" id="update-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Perbarui Produk Sembako</h5>
            </div>
            <div class="modal-body">
                <form id="update-form">
                    <div class="container">
                        <div class="row">
                            <div class="col-12 p-1">


                                <label class="form-label">Kategori</label>
                                <select type="text" class="form-control form-select" id="productCategoryUpdate">
                                    <option value="">Pilih Kategori</option>
                                </select>

                                <label class="form-label mt-2">Nama Produk</label>
                                <input type="text" class="form-control" id="productNameUpdate">

                                <label class="form-label mt-2">Harga</label>
                                <input type="text" class="form-control" id="productPriceUpdate">

                                <label class="form-label mt-2">Jumlah Stok</label>
                                <input type="text" class="form-control" id="productUnitUpdate">
                                <br/>
                                <img class="w-15" id="oldImg" src="{{asset('images/default.jpg')}}"/>
                                <br/>
                                <label class="form-label mt-2">Gambar</label>
                                <input oninput="oldImg.src=window.URL.createObjectURL(this.files[0])"  type="file" class="form-control" id="productImgUpdate">

                                <input type="text" class="d-none" id="updateID">
                                <input type="text" class="d-none" id="filePath">


                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button id="update-modal-close" class="btn bg-gradient-primary" data-bs-dismiss="modal" aria-label="Close">Tutup</button>
                <button onclick="update()" id="update-btn" class="btn bg-gradient-success" >Perbarui</button>
            </div>

        </div>
    </div>
</div>


<script>



    async function UpdateFillCategoryDropDown(){
        let res = await axios.get("/list-category")
        res.data.forEach(function (item,i) {
            let option=`<option value="${item['id']}">${item['name']}</option>`
            $("#productCategoryUpdate").append(option);
        })
    }


    async function FillUpUpdateForm(id,filePath){

        document.getElementById('updateID').value=id;
        document.getElementById('filePath').value=filePath;
        document.getElementById('oldImg').src=filePath;


        showLoader();
        await UpdateFillCategoryDropDown();

        let res=await axios.post("/product-by-id",{id:id})
        hideLoader();

        document.getElementById('productNameUpdate').value=res.data['name'];
        document.getElementById('productPriceUpdate').value=res.data['price'];
        document.getElementById('productUnitUpdate').value=res.data['unit'];
        document.getElementById('productCategoryUpdate').value=res.data['category_id'];

    }



    async function update() {

        let productCategoryUpdate=document.getElementById('productCategoryUpdate').value;
        let productNameUpdate = document.getElementById('productNameUpdate').value;
        let productPriceUpdate = document.getElementById('productPriceUpdate').value;
        let productUnitUpdate = document.getElementById('productUnitUpdate').value;
        let updateID=document.getElementById('updateID').value;
        let filePath=document.getElementById('filePath').value;
        let productImgUpdate = document.getElementById('productImgUpdate').files[0];


        if (productCategoryUpdate.length === 0) {
            errorToast("Kategori produk wajib dipilih !")
        }
        else if(productNameUpdate.length===0){
            errorToast("Nama produk wajib diisi !")
        }
        else if(productPriceUpdate.length===0){
            errorToast("Harga produk wajib diisi !")
        }
        else if(productUnitUpdate.length===0){
            errorToast("Jumlah stok wajib diisi !")
        }

        else {

            document.getElementById('update-modal-close').click();

            let formData=new FormData();
            if (productImgUpdate) {
                formData.append('img', productImgUpdate);
            }
            formData.append('id',updateID)
            formData.append('name',productNameUpdate)
            formData.append('price',productPriceUpdate)
            formData.append('unit',productUnitUpdate)
            formData.append('category_id',productCategoryUpdate)
            formData.append('file_path',filePath)

            const config = {
                headers: {
                    'content-type': 'multipart/form-data'
                }
            }

            try {
                showLoader();

                let res = await axios.post("/update-product", formData, config);

                hideLoader();

                if (res.status === 200 && res.data === 1) {
                    successToast('Data berhasil diperbarui');
                    document.getElementById("update-form").reset();
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
                    errorToast("Terjadi kesalahan saat memperbarui produk.");
                }
            }
        }
    }
</script>