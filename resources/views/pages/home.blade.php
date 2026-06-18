@extends('layout.app')

@section('content')
    <nav class="navbar sticky-top shadow-sm navbar-expand-lg navbar-light bg-white py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                <img class="img-fluid" src="{{ asset('/images/logo.png') }}" alt="Logo" width="80px">
                <span class="fw-bold text-dark">Inventaris Sembako</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMenu">
                <ul class="navbar-nav ms-auto mt-3 mt-lg-0 mb-3 mb-lg-0 me-4">
                    <li class="nav-item me-4"><a class="nav-link" href="#fitur">Fitur</a></li>
                    <li class="nav-item me-4"><a class="nav-link" href="#manfaat">Manfaat</a></li>
                    <li class="nav-item me-4"><a class="nav-link" href="#alur">Alur Sistem</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                </ul>
                <div>
                    <a class="btn bg-gradient-primary" href="{{ url('/userLogin') }}">Masuk</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="py-5 bg-light">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6 mb-5 mb-lg-0">
                    <span class="badge bg-gradient-primary mb-3">Sistem Inventaris Toko Sembako</span>
                    <h1 class="fw-bold mb-3">Kelola stok, pelanggan, transaksi, dan laporan penjualan dengan lebih mudah.</h1>
                    <p class="lead text-muted mb-4">
                        Aplikasi ini membantu toko sembako mencatat barang masuk, memantau jumlah stok,
                        membuat transaksi penjualan, dan melihat laporan secara praktis dalam satu sistem.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn bg-gradient-primary" href="{{ url('/userLogin') }}">Mulai Transaksi</a>
                        <a class="btn btn-outline-primary" href="{{ url('/userRegistration') }}">Daftar Akun</a>
                    </div>
                </div>

                <div class="col-12 col-lg-5 offset-lg-1 text-center">
                    <img class="img-fluid" src="{{ asset('/images/hero.svg') }}" alt="Ilustrasi sistem inventaris">
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 col-lg-8 mx-auto text-center">
                    <span class="text-muted">Fitur Utama</span>
                    <h2 class="fw-bold">Dirancang untuk kebutuhan toko sembako</h2>
                    <p class="text-muted">Sistem dibuat sederhana agar mudah digunakan dalam kegiatan operasional harian.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-6 col-lg-3 p-3">
                    <div class="card h-100 shadow-sm border-0 p-4">
                        <h5 class="fw-bold">Data Produk</h5>
                        <p class="text-muted mb-0">Mencatat nama produk, kategori, harga, gambar, dan jumlah stok barang.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 p-3">
                    <div class="card h-100 shadow-sm border-0 p-4">
                        <h5 class="fw-bold">Data Pelanggan</h5>
                        <p class="text-muted mb-0">Menyimpan nama dan nomor HP pelanggan agar transaksi lebih mudah dilacak.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 p-3">
                    <div class="card h-100 shadow-sm border-0 p-4">
                        <h5 class="fw-bold">Transaksi Penjualan</h5>
                        <p class="text-muted mb-0">Membuat transaksi, menghitung total bayar, diskon, dan PPN secara otomatis.</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 p-3">
                    <div class="card h-100 shadow-sm border-0 p-4">
                        <h5 class="fw-bold">Laporan Penjualan</h5>
                        <p class="text-muted mb-0">Menampilkan rekap transaksi berdasarkan periode tanggal yang dipilih.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="manfaat" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-5 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3">Manfaat Sistem</h2>
                    <p class="text-muted">
                        Sistem inventaris ini membantu pemilik toko mengurangi pencatatan manual,
                        mempercepat proses transaksi, dan memudahkan pengecekan ketersediaan barang.
                    </p>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="row">
                        <div class="col-12 col-md-6 p-2">
                            <div class="bg-white rounded-3 shadow-sm p-3 h-100">
                                <h6 class="fw-bold">Stok Lebih Terkontrol</h6>
                                <p class="text-muted mb-0">Jumlah stok dapat dipantau setelah transaksi penjualan dilakukan.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 p-2">
                            <div class="bg-white rounded-3 shadow-sm p-3 h-100">
                                <h6 class="fw-bold">Transaksi Lebih Cepat</h6>
                                <p class="text-muted mb-0">Produk dan pelanggan bisa dipilih langsung dari daftar yang tersedia.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 p-2">
                            <div class="bg-white rounded-3 shadow-sm p-3 h-100">
                                <h6 class="fw-bold">Data Lebih Rapi</h6>
                                <p class="text-muted mb-0">Kategori, produk, pelanggan, dan transaksi tersimpan dalam database.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 p-2">
                            <div class="bg-white rounded-3 shadow-sm p-3 h-100">
                                <h6 class="fw-bold">Laporan Mudah Dicetak</h6>
                                <p class="text-muted mb-0">Laporan penjualan dapat dibuat berdasarkan rentang tanggal tertentu.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="alur" class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 col-lg-8 mx-auto text-center">
                    <span class="text-muted">Alur Penggunaan</span>
                    <h2 class="fw-bold">Langkah sederhana menjalankan sistem</h2>
                </div>
            </div>

            <div class="row text-center">
                <div class="col-12 col-md-3 p-3">
                    <div class="border rounded-3 p-4 h-100">
                        <h3 class="fw-bold text-primary">1</h3>
                        <h6 class="fw-bold">Login</h6>
                        <p class="text-muted mb-0">Masuk menggunakan akun pengguna.</p>
                    </div>
                </div>
                <div class="col-12 col-md-3 p-3">
                    <div class="border rounded-3 p-4 h-100">
                        <h3 class="fw-bold text-primary">2</h3>
                        <h6 class="fw-bold">Input Data</h6>
                        <p class="text-muted mb-0">Tambahkan kategori, produk, dan pelanggan.</p>
                    </div>
                </div>
                <div class="col-12 col-md-3 p-3">
                    <div class="border rounded-3 p-4 h-100">
                        <h3 class="fw-bold text-primary">3</h3>
                        <h6 class="fw-bold">Buat Transaksi</h6>
                        <p class="text-muted mb-0">Pilih pelanggan dan produk yang dibeli.</p>
                    </div>
                </div>
                <div class="col-12 col-md-3 p-3">
                    <div class="border rounded-3 p-4 h-100">
                        <h3 class="fw-bold text-primary">4</h3>
                        <h6 class="fw-bold">Cetak Laporan</h6>
                        <p class="text-muted mb-0">Lihat laporan penjualan sesuai periode.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="kontak" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3">Siap digunakan untuk pengelolaan toko sembako</h2>
                    <p class="text-muted mb-0">
                        Masuk ke sistem untuk mulai mengelola data produk, pelanggan, transaksi, dan laporan penjualan.
                    </p>
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <a class="btn bg-gradient-primary" href="{{ url('/userLogin') }}">Masuk ke Sistem</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-4 bg-white">
        <div class="container text-center">
            <img class="img-fluid mb-2" src="{{ asset('/images/logo.png') }}" alt="Logo" width="72px">
            <p class="text-muted mb-0">© {{ date('Y') }} Sistem Inventaris Toko Sembako. All rights reserved.</p>
        </div>
    </footer>
@endsection
