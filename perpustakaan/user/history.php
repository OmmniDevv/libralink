<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('user');

$user_id = getCurrentUserId();

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = getOffset($page, $per_page);

// Get total history
$query_count = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ?";
$stmt = $conn->prepare($query_count);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = getPagination($total, $per_page);

// Get history
$query = "SELECT t.id, t.tanggal_pinjam, t.tanggal_kembali_rencana, t.tanggal_kembali_aktual, t.status, t.denda, COUNT(td.id) as total_buku
          FROM transactions t
          LEFT JOIN transaction_details td ON t.id = td.transaction_id
          WHERE t.user_id = ?
          GROUP BY t.id
          ORDER BY t.tanggal_pinjam DESC
          LIMIT $per_page OFFSET $offset";
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
    <title>Riwayat Peminjaman - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-4">Riwayat Peminjaman</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Tanggal Dikembalikan</th>
                            <th>Total Buku</th>
                            <th>Denda</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        while ($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo formatDate($row['tanggal_pinjam']); ?></td>
                                <td><?php echo formatDate($row['tanggal_kembali_rencana']); ?></td>
                                <td>
                                    <?php 
                                    if ($row['tanggal_kembali_aktual']) {
                                        echo formatDate($row['tanggal_kembali_aktual']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['total_buku']; ?></td>
                                <td>
                                    <?php if ($row['denda'] > 0): ?>
                                        <span class="text-danger fw-bold">Rp<?php echo number_format($row['denda'], 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">Pertama</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Sebelumnya</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Selanjutnya</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>">Terakhir</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Anda belum memiliki riwayat peminjaman
            </div>
        <?php endif; ?>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>