<?php
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" style="width: 280px; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto;">
    <a href="/perpustakaan_online/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
        <span class="fs-5 fw-bold">📚 Perpustakaan</span>
    </a>
    <hr>
    
    <div class="mb-3">
        <small class="text-muted">Login sebagai:</small>
        <p class="fw-bold mb-0"><?php echo sanitize($current_user['nama']); ?></p>
        <small class="text-primary"><?php echo ucfirst($current_user['role']); ?></small>
    </div>
    
    <hr>
    
    <ul class="nav nav-pills flex-column mb-auto">
        <?php if ($current_user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="/perpustakaan_online/admin/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : 'link-dark'; ?>">
                    📊 Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/admin/buku.php" class="nav-link <?php echo $current_page === 'buku.php' ? 'active' : 'link-dark'; ?>">
                    📖 Manajemen Buku
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/admin/kategori.php" class="nav-link <?php echo $current_page === 'kategori.php' ? 'active' : 'link-dark'; ?>">
                    🏷️ Kategori
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/admin/user.php" class="nav-link <?php echo $current_page === 'user.php' ? 'active' : 'link-dark'; ?>">
                    👥 Manajemen User
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/admin/transaksi.php" class="nav-link <?php echo $current_page === 'transaksi.php' ? 'active' : 'link-dark'; ?>">
                    💳 Semua Transaksi
                </a>
            </li>
        <?php elseif ($current_user['role'] === 'petugas'): ?>
            <li class="nav-item">
                <a href="/perpustakaan_online/petugas/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : 'link-dark'; ?>">
                    📊 Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/petugas/peminjaman.php" class="nav-link <?php echo $current_page === 'peminjaman.php' ? 'active' : 'link-dark'; ?>">
                    📤 Kelola Peminjaman
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/petugas/pengembalian.php" class="nav-link <?php echo $current_page === 'pengembalian.php' ? 'active' : 'link-dark'; ?>">
                    📥 Kelola Pengembalian
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/petugas/denda.php" class="nav-link <?php echo $current_page === 'denda.php' ? 'active' : 'link-dark'; ?>">
                    💰 Kelola Denda
                </a>
            </li>
            <li class="nav-item">
                <a href="/perpustakaan_online/petugas/laporan.php" class="nav-link <?php echo $current_page === 'laporan.php' ? 'active' : 'link-dark'; ?>">
                    📋 Laporan
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <hr>
    
    <div class="d-grid">
        <a href="/perpustakaan_online/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
    </div>
</div>