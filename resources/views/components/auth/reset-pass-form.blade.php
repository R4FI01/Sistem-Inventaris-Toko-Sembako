<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6 center-screen">
            <div class="card animated fadeIn w-90 p-4">
                <div class="card-body">
                    <h4>ATUR KATA SANDI BARU</h4>
                    <br/>
                    <label>Kata Sandi Baru</label>
                    <input id="password" placeholder="Kata Sandi Baru" class="form-control" type="password"/>
                    <br/>
                    <label>Konfirmasi Kata Sandi</label>
                    <input id="cpassword" placeholder="Konfirmasi Kata Sandi" class="form-control" type="password"/>
                    <br/>
                    <button onclick="ResetPass()" class="btn w-100 bg-gradient-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  async function ResetPass() {
        let password = document.getElementById('password').value;
        let cpassword = document.getElementById('cpassword').value;

        if(password.length===0){
            errorToast('Kata sandi wajib diisi')
        }
        else if(cpassword.length===0){
            errorToast('Konfirmasi kata sandi wajib diisi')
        }
        else if(password!==cpassword){
            errorToast('Kata sandi dan konfirmasi harus sama')
        }
        else{
          showLoader()
          let res=await axios.post("/reset-password",{password:password});
          hideLoader();
          if(res.status===200 && res.data['status']==='success'){
              successToast(res.data['message']);
              debugger
              setTimeout(function () {
                  window.location.href="/userLogin";
              },1000);
          }
          else{
            errorToast(res.data['message'])
          }
        }

    }
</script>
