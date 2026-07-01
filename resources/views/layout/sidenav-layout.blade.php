<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Sistem Inventaris Toko Sembako</title>

    <link rel="icon" type="image/x-icon" href="{{asset('/favicon.ico')}}" />
    <link href="{{asset('css/bootstrap.css')}}" rel="stylesheet" />
    <link href="{{asset('css/animate.min.css')}}" rel="stylesheet" />
    <link href="{{asset('css/fontawesome.css')}}" rel="stylesheet" />
    <link href="{{asset('css/style.css')}}" rel="stylesheet" />
    <link href="{{asset('css/toastify.min.css')}}" rel="stylesheet" />


    <link href="{{asset('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css')}}" rel="stylesheet" />

    <link href="{{asset('css/jquery.dataTables.min.css')}}" rel="stylesheet" />
    <script src="{{asset('js/jquery-3.7.0.min.js')}}"></script>
    <script src="{{asset('js/jquery.dataTables.min.js')}}"></script>


    <script src="{{asset('js/toastify-js.js')}}"></script>
    {{-- <script src="{{asset('js/axios.min.js')}}"></script> --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.3/axios.min.js"></script>
    <script src="{{asset('js/config.js')}}"></script>
    <script src="{{asset('js/bootstrap.bundle.js')}}"></script>

    <script>
        function formatRupiah(value) {
        let number = Number(value) || 0;

        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number);
        }
    </script>


</head>

<body>

<div id="loader" class="LoadingOverlay d-none">
    <div class="Line-Progress">
        <div class="indeterminate"></div>
    </div>
</div>

<nav class="navbar fixed-top px-0 shadow-sm bg-white">
    <div class="container-fluid">

        <a class="navbar-brand" href="#">
            <span class="icon-nav m-0 h5" onclick="MenuBarClickHandler()">
                <img class="nav-logo-sm mx-2"  src="{{asset('images/menu.svg')}}" alt="logo"/>
            </span>
            <img class="nav-logo  mx-2"  src="{{asset('images/logo.png')}}" alt="logo"/>
        </a>

        <div class="float-right h-auto d-flex">
            <div class="user-dropdown">
                <img class="icon-nav-img" src="{{asset('images/user.webp')}}" alt=""/>
                <div class="user-dropdown-content ">
                    <div class="mt-4 text-center">
                        <img class="icon-nav-img" src="{{asset('images/user.webp')}}" alt=""/>
                        <h6>Pengguna</h6>
                        <hr class="user-dropdown-divider  p-0"/>
                    </div>
                    <a href="{{url('/userProfile')}}" class="side-bar-item">
                        <span class="side-bar-item-caption">Profil</span>
                    </a>
                    <a href="{{url("/user-logout")}}" class="side-bar-item">
                        <span class="side-bar-item-caption">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>


<div id="sideNavRef" class="side-nav-open">

    <a href="{{url("/dashboard")}}" class="side-bar-item">
        <i class="bi bi-graph-up"></i>
        <span class="side-bar-item-caption">Dashboard</span>
    </a>

    <a href="{{url("/customerPage")}}" class="side-bar-item">
        <i class="bi bi-people"></i>
        <span class="side-bar-item-caption">Pelanggan</span>
    </a>

    <a href="{{url("/categoryPage")}}" class="side-bar-item">
        <i class="bi bi-list-nested"></i>
        <span class="side-bar-item-caption">Kategori</span>
    </a>

    <a href="{{url("/productPage")}}" class="side-bar-item">
        <i class="bi bi-bag"></i>
        <span class="side-bar-item-caption">Produk</span>
    </a>

    <a href="{{url("/stockPage")}}" class="side-bar-item">
        <i class="bi bi-box-seam"></i>
        <span class="side-bar-item-caption">Stok & Riwayat</span>
    </a>

    <a href="{{url('/salePage')}}" class="side-bar-item">
        <i class="bi bi-cart-check"></i>
        <span class="side-bar-item-caption">Buat Transaksi</span>
    </a>

    <a href="{{url('/invoicePage')}}" class="side-bar-item">
        <i class="bi bi-receipt"></i>
        <span class="side-bar-item-caption">Daftar Transaksi</span>
    </a>

    <a href="{{url('/reportPage')}}" class="side-bar-item">
        <i class="bi bi-file-earmark-bar-graph"></i>
        <span class="side-bar-item-caption">Laporan</span>
    </a>


</div>


<div id="contentRef" class="content">
    @yield('content')
</div>



<script>
    function MenuBarClickHandler() {
        let sideNav = document.getElementById('sideNavRef');
        let content = document.getElementById('contentRef');
        if (sideNav.classList.contains("side-nav-open")) {
            sideNav.classList.add("side-nav-close");
            sideNav.classList.remove("side-nav-open");
            content.classList.add("content-expand");
            content.classList.remove("content");
        } else {
            sideNav.classList.remove("side-nav-close");
            sideNav.classList.add("side-nav-open");
            content.classList.remove("content-expand");
            content.classList.add("content");
        }
    }
</script>

</body>
</html>
