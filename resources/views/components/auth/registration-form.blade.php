<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-10 center-screen">
            <div class="card animated fadeIn w-100 p-3">
                <div class="card-body">
                    <h4>Daftar Akun</h4>
                    <hr/>
                    <div class="container-fluid m-0 p-0">
                        <div class="row m-0 p-0">
                            <div class="col-md-4 p-2">
                                <label>Alamat Email</label>
                                <input id="email" placeholder="Email Pengguna" class="form-control" type="email"/>
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
                                <input id="mobile" placeholder="Nomor HP" class="form-control" type="mobile"/>
                            </div>
                            <div class="col-md-4 p-2">
                                <label>Password</label>
                                <input id="password" placeholder="Kata Sandi" class="form-control" type="password"/>
                            </div>
                        </div>
                        <div class="row m-0 p-0">
                            <div class="col-md-4 p-2">
                                <button onclick="onRegistration()" class="btn mt-3 w-100  bg-gradient-primary">Daftar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>


  async function onRegistration() {

        let email = document.getElementById('email').value;
        let firstName = document.getElementById('firstName').value;
        let lastName = document.getElementById('lastName').value;
        let mobile = document.getElementById('mobile').value;
        let password = document.getElementById('password').value;

        if(email.length===0){
            errorToast('Email wajib diisi')
        }
        else if(firstName.length===0){
            errorToast('Nama depan wajib diisi')
        }
        else if(lastName.length===0){
            errorToast('Nama belakang wajib diisi')
        }
        else if(mobile.length===0){
            errorToast('Nomor HP wajib diisi')
        }
        else if(password.length===0){
            errorToast('Kata sandi wajib diisi')
        }
        else{
            showLoader();
            let res=await axios.post("/user-registration",{
                email:email,
                firstName:firstName,
                lastName:lastName,
                mobile:mobile,
                password:password
            })
            hideLoader();
            if(res.status===200 && res.data['status']==='success'){
                successToast(res.data['message']);
                setTimeout(function (){
                    window.location.href='/userLogin'
                },2000)
            }
            else{
                errorToast(res.data['message'])
            }
        }
    }
</script>
