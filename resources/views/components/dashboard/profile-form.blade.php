<div class="container">
    <div class="row">
        <div class="col-md-12 col-lg-12">
            <div class="card animated fadeIn w-100 p-3">
                <div class="card-body">
                    <h4>Profil Pengguna</h4>
                    <p class="text-muted text-sm mb-0">Kelola informasi akun. Kosongkan kata sandi baru jika tidak ingin mengubah password.</p>
                    <hr/>
                    <div class="container-fluid m-0 p-0">
                        <div class="row m-0 p-0">
                            <div class="col-md-4 p-2">
                                <label>Alamat Email</label>
                                <input readonly id="email" placeholder="Email Pengguna" class="form-control" type="email"/>
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
                                <input id="mobile" placeholder="Nomor HP" class="form-control" type="text"/>
                            </div>
                            <div class="col-md-4 p-2">
                                <label>Kata Sandi Baru</label>
                                <input id="password" placeholder="Kosongkan jika tidak diubah" class="form-control" type="password"/>
                            </div>
                        </div>
                        <div class="row m-0 p-0">
                            <div class="col-md-4 p-2">
                                <button onclick="onPerbarui()" class="btn mt-3 w-100 bg-gradient-primary">Perbarui</button>
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

    async function getProfil() {
        showLoader();
        let res = await axios.get("/user-profile")
        hideLoader();

        if (res.status === 200 && res.data['status'] === 'success') {
            let data = res.data['data'];
            document.getElementById('email').value = data['email'];
            document.getElementById('firstName').value = data['first_name'];
            document.getElementById('lastName').value = data['last_name'];
            document.getElementById('mobile').value = data['mobile'];
            document.getElementById('password').value = '';
        } else {
            errorToast(res.data['message'])
        }
    }

    async function onPerbarui() {
        let firstName = document.getElementById('firstName').value;
        let lastName = document.getElementById('lastName').value;
        let mobile = document.getElementById('mobile').value;
        let password = document.getElementById('password').value;

        if (firstName.length === 0) {
            errorToast('Nama depan wajib diisi')
        } else if (lastName.length === 0) {
            errorToast('Nama belakang wajib diisi')
        } else if (mobile.length === 0) {
            errorToast('Nomor HP wajib diisi')
        } else if (password.length > 0 && password.length < 6) {
            errorToast('Kata sandi baru minimal 6 karakter')
        } else {
            showLoader();
            let res = await axios.post("/user-update", {
                first_name: firstName,
                last_name: lastName,
                mobile: mobile,
                password: password
            })
            hideLoader();

            if (res.status === 200 && res.data['status'] === 'success') {
                successToast(res.data['message']);
                document.getElementById('password').value = '';
                await getProfil();
            } else {
                errorToast(res.data['message'])
            }
        }
    }
</script>
