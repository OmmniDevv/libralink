<?php
// Sidebar untuk Admin dan Petugas
require_once dirname(__DIR__) . '/config/functions.php';

$current_file = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<div class="sidebar bg-dark text-white p-3">
    <div class="sidebar-header mb-4 pb-3 border-bottom">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-book"></i> Perpustakaan
        </h5>
        <small class="text-muted d-block mt-1">
            <?php echo ucfirst($role); ?>
        </small>
    </div>

    <ul class="nav flex-column">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'books.php' ? 'active' : ''; ?>" href="books.php">
                    <i class="fas fa-book"></i> Kelola Buku
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                    <i class="fas fa-list"></i> Kategori
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i> Kelola User
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                    <i class="fas fa-exchange-alt"></i> Transaksi
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
            </li>

        <?php elseif ($role === 'petugas'): ?>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'borrowing.php' ? 'active' : ''; ?>" href="borrowing.php">
                    <i class="fas fa-hand-holding-heart"></i> Peminjaman
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'returns.php' ? 'active' : ''; ?>" href="returns.php">
                    <i class="fas fa-undo"></i> Pengembalian
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'fines.php' ? 'active' : ''; ?>" href="fines.php">
                    <i class="fas fa-money-bill-wave"></i> Denda
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo $current_file === 'members.php' ? 'active' : ''; ?>" href="members.php">
                    <i class="fas fa-users"></i> Anggota
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <hr class="my-4 border-secondary">

    <div class="sidebar-footer">
        <div class="user-info mb-3 pb-3 border-bottom border-secondary">
            <small class="text-muted d-block mb-1">Logged in as:</small>
            <small class="text-truncate d-block"><?php echo isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User'; ?></small>
        </div>
        <a href="../api/logout.php" class="btn btn-sm btn-outline-danger w-100">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<style>
    .sidebar {
        min-height: 100vh;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        overflow-y: auto;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        border-radius: 5px;
        transition: all 0.3s ease;
        padding: 10px 12px;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .sidebar .nav-link.active {
        background-color: #0d6efd;
        color: white;
    }

    .main-content {
        margin-left: 250px;
        padding: 20px;
    }
</style>