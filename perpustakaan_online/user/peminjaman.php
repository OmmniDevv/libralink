<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('user');
$current_user = getCurrentUser();

$db = new Database();
$conn = $db->connect();

// Get user's active loans
$query = "
    SELECT t.id, t.tanggal_pinjam, t.tanggal_kembali_direncanakan, t.status,
           GROUP_CONCAT(b.judul SEPARATOR ', ') as buku,
           COUNT(td.id) as jumlah_buku
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN books b ON td.book_id = b.id
    WHERE t.user_id = ? AND t.status = 'dipinjam'
    GROUP BY t.id
    ORDER BY t.tanggal_pinjam DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$loans = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->closeConnection();

$page_title = 'Status Peminjaman';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Status Peminjaman Saya</h1>
            <p class="text-muted">Kelola peminjaman buku Anda</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($loans)): ?>
        <div class="row g-4">
            <?php foreach ($loans as $loan): ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">Peminjaman #<?php echo $loan['id']; ?></h6>
                                <span class="badge bg-info">Dipinjam</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Buku:</strong><br>
                                <?php echo sanitize($loan['buku']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Jumlah Buku:</strong> <?php echo $loan['jumlah_buku']; ?>
                            </p>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Tanggal Pinjam</small>
                                    <strong><?php echo formatDateTime($loan['tanggal_pinjam']); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Tanggal Kembali</small>
                                    <strong><?php echo formatDate($loan['tanggal_kembali_direncanakan']); ?></strong>
                                </div>
                            </div>

                            <?php
                            $today = new DateTime();
                            $return_date = new DateTime($loan['tanggal_kembali_direncanakan']);
                            $is_overdue = $today > $return_date;
                            $days_left = $today->diff($return_date)->days;
                            ?>

                            <div class="alert <?php echo $is_overdue ? 'alert-danger' : 'alert-warning'; ?>" role="alert">
                                <?php if ($is_overdue): ?>
                                    <strong>⚠️ Terlambat!</strong> Harap kembalikan buku segera. Denda Rp 5.000 per hari.
                                <?php else: ?>
                                    <strong>⏱️ Sisa Waktu:</strong> <?php echo $days_left; ?> hari lagi
                                <?php endif; ?>
                            </div>

                            <a href="/perpustakaan_online/user/kembali.php?id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-success w-100">
                                Kembalikan Buku
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <h5>Tidak Ada Peminjaman Aktif</h5>
            <p>Anda tidak memiliki peminjaman buku yang aktif saat ini.</p>
            <a href="/perpustakaan_online/user/buku.php" class="btn btn-primary">Mulai Pinjam Buku</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>