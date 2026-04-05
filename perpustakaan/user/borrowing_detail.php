<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('user');

$user_id = getCurrentUserId();
$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get transaction
$query_transaction = "SELECT * FROM transactions WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query_transaction);
$stmt->bind_param('ii', $transaction_id, $user_id);
$stmt->execute();
$transaction_result = $stmt->get_result();

if ($transaction_result->num_rows === 0) {
    redirect('borrowing.php?msg=error&error=Transaksi tidak ditemukan');
}

$transaction = $transaction_result->fetch_assoc();

// Get transaction details
$query_details = "SELECT td.*, b.judul, b.penulis FROM transaction_details td
                  JOIN books b ON td.book_id = b.id
                  WHERE td.transaction_id = ?";
$stmt = $conn->prepare($query_details);
$stmt->bind_param('i', $transaction_id);
$stmt->execute();
$details_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <a href="borrowing.php" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <h2 class="fw-bold mb-4">Detail Peminjaman</h2>

        <div class="row">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Informasi Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-bold">ID Transaksi:</td>
                                <td>#<?php echo str_pad($transaction['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Tanggal Pinjam:</td>
                                <td><?php echo formatDate($transaction['tanggal_pinjam']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Tanggal Kembali:</td>
                                <td><?php echo formatDate($transaction['tanggal_kembali_rencana']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Status:</td>
                                <td><?php echo getStatusBadge($transaction['status']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Total Buku:</td>
                                <td><?php echo $transaction['total_buku']; ?></td>
                            </tr>
                            <?php if ($transaction['denda'] > 0): ?>
                                <tr>
                                    <td class="fw-bold">Denda:</td>
                                    <td class="text-danger">Rp<?php echo number_format($transaction['denda'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Status Buku</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($details_result->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($detail = $details_result->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($detail['judul']); ?></h6>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($detail['penulis']); ?></p>
                                        <p class="small mb-0">
                                            Status: 
                                            <?php if ($detail['status_pengembalian'] === 'dikembalikan'): ?>
                                                <span class="badge bg-success">Dikembalikan</span>
                                                <br>
                                                <small class="text-muted">Tanggal: <?php echo formatDate($detail['tanggal_pengembalian']); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Belum Dikembalikan</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endwhile; ?>
                            </div>
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