<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Helper.php';

startSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header('Location: /perpustakaan_online/admin/dashboard.php');
    } elseif ($user['role'] === 'petugas') {
        header('Location: /perpustakaan_online/petugas/dashboard.php');
    } else {
        header('Location: /perpustakaan_online/user/dashboard.php');
    }
    exit;
}

$page_title = 'Beranda';
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold mb-4">Perpustakaan Online</h1>
                <p class="lead mb-4">Akses ribuan buku digital dengan mudah. Pinjam, baca, dan kembalikan kapan saja dari kenyamanan rumah Anda.</p>
                <div class="d-flex gap-3">
                    <a href="/perpustakaan_online/login.php" class="btn btn-light btn-lg">Login</a>
                    <a href="/perpustakaan_online/register.php" class="btn btn-outline-light btn-lg">Daftar</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div style="font-size: 150px;">📚</div>
            </div>
        </div>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">Fitur Unggulan</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100 text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div style="font-size: 48px; margin-bottom: 15px;">📖</div>
                        <h5 class="card-title">Katalog Lengkap</h5>
                        <p class="card-text text-muted">Ribuan buku dari berbagai kategori tersedia untuk Anda jelajahi.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div style="font-size: 48px; margin-bottom: 15px;">⚡</div>
                        <h5 class="card-title">Cepat & Mudah</h5>
                        <p class="card-text text-muted">Proses peminjaman yang cepat dan mudah dalam hitungan menit.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div style="font-size: 48px; margin-bottom: 15px;">📱</div>
                        <h5 class="card-title">Akses Dimana Saja</h5>
                        <p class="card-text text-muted">Akses dari browser di perangkat apapun kapan saja Anda mau.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 text-center border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div style="font-size: 48px; margin-bottom: 15px;">🔔</div>
                        <h5 class="card-title">Pengingat Otomatis</h5>
                        <p class="card-text text-muted">Dapatkan notifikasi pengingat tanggal pengembalian buku Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold">Cara Menggunakan</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; font-weight: bold;">1</div>
                        <h5 class="card-title">Daftar Akun</h5>
                        <p class="card-text text-muted">Buat akun baru dengan email dan password Anda.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; font-weight: bold;">2</div>
                        <h5 class="card-title">Cari Buku</h5>
                        <p class="card-text text-muted">Cari buku favorit dari katalog yang tersedia.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; font-weight: bold;">3</div>
                        <h5 class="card-title">Pinjam Buku</h5>
                        <p class="card-text text-muted">Pinjam buku dan kembalikan tepat waktu.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Tentang Perpustakaan Kami</h2>
                <p class="lead mb-3">Perpustakaan Online adalah platform digital yang memudahkan Anda mengakses ribuan buku dari berbagai genre dan penulis terkemuka.</p>
                <p class="mb-3">Kami berkomitmen untuk memberikan pengalaman terbaik dalam membaca dengan sistem manajemen yang efisien dan user-friendly.</p>
                <ul class="list-unstyled">
                    <li class="mb-2">✅ Koleksi buku terlengkap</li>
                    <li class="mb-2">✅ Sistem peminjaman mudah</li>
                    <li class="mb-2">✅ Layanan pelanggan 24/7</li>
                    <li class="mb-2">✅ Gratis untuk semua anggota</li>
                </ul>
            </div>
            <div class="col-lg-6 text-center">
                <div style="font-size: 200px; opacity: 0.1;">📚</div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>