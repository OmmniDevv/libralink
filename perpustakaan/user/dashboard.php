<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('user');

// Get user statistics
$user_id = getCurrentUserId();

// Total peminjaman
$query_borrowing = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ?";
$stmt = $conn->prepare($query_borrowing);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$borrowing_result = $stmt->get_result()->fetch_assoc();
$total_borrowing = $borrowing_result['total'];

// Peminjaman aktif
$query_active = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ? AND status IN ('approved', 'overdue')";
$stmt = $conn->prepare($query_active);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$active_result = $stmt->get_result()->fetch_assoc();
$active_borrowing = $active_result['total'];

// Total denda
$query_fine = "SELECT COALESCE(SUM(jumlah_denda), 0) as total FROM fines WHERE user_id = ? AND status_pembayaran = 'unpaid'";
$stmt = $conn->prepare($query_fine);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$fine_result = $stmt->get_result()->fetch_assoc();
$total_fine = $fine_result['total'];

// Recent borrowing
$query_recent = "SELECT t.id, t.tanggal_pinjam, t.tanggal_kembali_rencana, t.status, COUNT(td.id) as total_buku
                 FROM transactions t
                 LEFT JOIN transaction_details td ON t.id = td.transaction_id
                 WHERE t.user_id = ?
                 GROUP BY t.id
                 ORDER BY t.created_at DESC
                 LIMIT 5";
$stmt = $conn->prepare($query_recent);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recent_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold mb-1">Selamat Datang, <?php echo $_SESSION['nama']; ?>!</h2>
                <p class="text-muted">Kelola peminjaman buku Anda dengan mudah</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0 small">Total Peminjaman</p>
                                <h3 class="fw-bold mb-0"><?php echo $total_borrowing; ?></h3>
                            </div>
                            <div class="display-6 text-primary">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0 small">Peminjaman Aktif</p>
                                <h3 class="fw-bold mb-0"><?php echo $active_borrowing; ?></h3>
                            </div>
                            <div class="display-6 text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-0 small">Total Denda</p>
                                <h3 class="fw-bold mb-0">Rp<?php echo number_format($total_fine, 0, ',', '.'); ?></h3>
                            </div>
                            <div class="display-6 text-danger">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <a href="books.php" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Cari Buku
                </a>
                <a href="borrowing.php" class="btn btn-info me-2">
                    <i class="fas fa-hand-holding-heart"></i> Peminjaman
                </a>
                <a href="history.php" class="btn btn-secondary">
                    <i class="fas fa-history"></i> Riwayat
                </a>
            </div>
        </div>

        <!-- Recent Borrowing -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Peminjaman Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal Pinjam</th>
                                            <th>Tanggal Kembali</th>
                                            <th>Total Buku</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        while ($row = $recent_result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo formatDate($row['tanggal_pinjam']); ?></td>
                                                <td><?php echo formatDate($row['tanggal_kembali_rencana']); ?></td>
                                                <td><?php echo $row['total_buku']; ?> buku</td>
                                                <td><?php echo getStatusBadge($row['status']); ?></td>
                                                <td>
                                                    <a href="borrowing_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">Anda belum melakukan peminjaman</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>