<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 animated fadeIn col-lg-6 center-screen">
            <div class="card w-90 p-4">
                <div class="card-body">
                    <h4>MASUK</h4>
                    <br/>

                    <input id="email" placeholder="Email Pengguna" class="form-control" type="email"/>
                    <br/>

                    <input id="password" placeholder="Kata Sandi" class="form-control" type="password"/>
                    <br/>

                    <button onclick="SubmitLogin()" class="btn w-100 bg-gradient-primary">
                        Masuk
                    </button>

                    <hr/>

                    <div class="float-end mt-3">
                        <span>
                            <a class="text-center ms-3 h6" href="{{ url('/userRegistration') }}">Daftar</a>
                            <span class="ms-1">|</span>
                            <a class="text-center ms-3 h6" href="{{ url('/sendOtp') }}">Lupa Password</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function SubmitLogin() {
        let email = document.getElementById('email').value;
        let password = document.getElementById('password').value;

        if (email.length === 0) {
            errorToast("Email wajib diisi");
        } else if (password.length === 0) {
            errorToast("Kata sandi wajib diisi");
        } else {
            showLoader();

            try {
                let res = await axios.post("/user-login", {
                    email: email,
                    password: password
                });

                hideLoader();

                if (res.status === 200 && res.data['status'] === 'success') {
                    window.location.href = "/dashboard";
                } else {
                    errorToast(res.data['message'] || 'Login gagal');
                }
            } catch (error) {
                hideLoader();

                let message = error.response?.data?.message || 'Terjadi kesalahan saat login';
                errorToast(message);
            }
        }
    }

    document.getElementById('password').addEventListener('keyup', function (event) {
        if (event.key === 'Enter') {
            SubmitLogin();
        }
    });
</script>