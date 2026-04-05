<?php
$current_user = getCurrentUser();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/perpustakaan_online/">
            📚 Perpustakaan Online
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/perpustakaan_online/user/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/perpustakaan_online/user/buku.php">Daftar Buku</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/perpustakaan_online/user/peminjaman.php">Peminjaman Saya</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            👤 <?php echo sanitize($current_user['nama']); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="/perpustakaan_online/user/profil.php">Profil Saya</a></li>
                            <li><a class="dropdown-item" href="/perpustakaan_online/user/riwayat.php">Riwayat Pinjaman</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/perpustakaan_online/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/perpustakaan_online/">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/perpustakaan_online/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/perpustakaan_online/register.php">Registrasi</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>