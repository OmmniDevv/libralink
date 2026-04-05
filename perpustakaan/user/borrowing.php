<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('user');

$user_id = getCurrentUserId();
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';

// Get user's borrowing
$query = "SELECT t.id, t.tanggal_pinjam, t.tanggal_kembali_rencana, t.status, COUNT(td.id) as total_buku
          FROM transactions t
          LEFT JOIN transaction_details td ON t.id = td.transaction_id
          WHERE t.user_id = ? AND t.status IN ('approved', 'overdue')
          GROUP BY t.id
          ORDER BY t.tanggal_pinjam DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Saya - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-4">Peminjaman Saya</h2>

        <?php if ($msg === 'success' || $success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success ?: 'Berhasil'; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <a href="books.php" class="btn btn-primary mb-3">
            <i class="fas fa-plus"></i> Pinjam Buku Baru
        </a>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
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
                        while ($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo formatDate($row['tanggal_pinjam']); ?></td>
                                <td><?php echo formatDate($row['tanggal_kembali_rencana']); ?></td>
                                <td><?php echo $row['total_buku']; ?></td>
                                <td><?php echo getStatusBadge($row['status']); ?></td>
                                <td>
                                    <a href="borrowing_detail.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Anda tidak memiliki peminjaman aktif. 
                <a href="books.php" class="alert-link">Mulai pinjam buku</a>
            </div>
        <?php endif; ?>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>