<div class="container">
    <div class="row">
        <div class="col-md-12 col-lg-12">
            <div class="card animated fadeIn w-100 p-3">
                <div class="card-body">
                    <h4>User Profil</h4>
                    <hr/>
                    <div class="container-fluid m-0 p-0">
                        <div class="row m-0 p-0">
                            <div class="col-md-4 p-2">
                                <label>Alamat Email</label>
                                <input readonly id="email" placeholder="User Email" class="form-control" type="email"/>
                            </div>
                            <div class="col-md-4 p-2">
                                <label>Nama Depan</label>
                                <input id="firstName" placeholder="Nama Depan" class="form-control" type="text"/>
                            </div>
                            <div class="col-md-4 p-2">
                                <label>Nama Belakang</label>
                                <input id="lastName" placeholder="Nama Belakang" class="form-control" type="text"/>
                            </div>
                            <div class="col-md-4 p-2">
                                <label>Nomor HP</label>
                                <input id="mobile" placeholder="Mobile" class="form-control" type="mobile"/>
                            </div>
                            <div class="col-md-4 p-2">
                                <label>Kata Sandi</label>
                                <input id="password" placeholder="User Kata Sandi" class="form-control" type="password"/>
                            </div>
                        </div>
                        <div class="row m-0 p-0">
                            <div class="col-md-4 p-2">
                                <button onclick="onPerbarui()" class="btn mt-3 w-100  bg-gradient-primary">Perbarui</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    getProfil();
    async function getProfil(){
        showLoader();
        let res=await axios.get("/user-profile")
        hideLoader();
        if(res.status===200 && res.data['status']==='success'){
            let data=res.data['data'];
            document.getElementById('email').value=data['email'];
            document.getElementById('firstName').value=data['first_name'];
            document.getElementById('lastName').value=data['last_name'];
            document.getElementById('mobile').value=data['mobile'];
            document.getElementById('password').value=data['password'];
        }
        else{
            errorToast(res.data['message'])
        }

    }

    async function onPerbarui() {
        let firstName = document.getElementById('firstName').value;
        let lastName = document.getElementById('lastName').value;
        let mobile = document.getElementById('mobile').value;
        let password = document.getElementById('password').value;

        if(firstName.length===0){
            errorToast('Nama Depan is required')
        }
        else if(lastName.length===0){
            errorToast('Nama Belakang is required')
        }
        else if(mobile.length===0){
            errorToast('Mobile is required')
        }
        else if(password.length===0){
            errorToast('Kata Sandi is required')
        }
        else{
            showLoader();
            let res=await axios.post("/user-update",{
                first_name:firstName,
                last_name:lastName,
                mobile:mobile,
                password:password
            })
            hideLoader();
            if(res.status===200 && res.data['status']==='success'){
                successToast(res.data['message']);
                await getProfil();
            }
            else{
                errorToast(res.data['message'])
            }
        }
    }

</script>