<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('admin');

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = getOffset($page, $per_page);

// Filter status
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where = "1=1";
if ($status_filter && in_array($status_filter, ['pending', 'approved', 'returned', 'overdue'])) {
    $where .= " AND t.status = '$status_filter'";
}

// Get total transactions
$query_count = "SELECT COUNT(*) as total FROM transactions t WHERE $where";
$count_result = $conn->query($query_count);
$total = $count_result->fetch_assoc()['total'];
$total_pages = getPagination($total, $per_page);

// Get transactions
$query = "SELECT t.id, u.nama, u.no_anggota, t.tanggal_pinjam, t.tanggal_kembali_rencana, 
                 t.tanggal_kembali_aktual, t.status, t.denda, COUNT(td.id) as total_buku
          FROM transactions t
          JOIN users u ON t.user_id = u.id
          LEFT JOIN transaction_details td ON t.id = td.transaction_id
          WHERE $where
          GROUP BY t.id
          ORDER BY t.tanggal_pinjam DESC
          LIMIT $per_page OFFSET $offset";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php require_once '../includes/sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <h2 class="fw-bold mb-4">Transaksi</h2>

            <!-- Filter -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Filter Status</label>
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                                <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Dikembalikan</option>
                                <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Terlambat</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Cari</button>
                            <a href="transactions.php" class="btn btn-secondary ms-2">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Member</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Dikembalikan</th>
                                        <th>Buku</th>
                                        <th>Denda</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = $offset + 1;
                                    while ($row = $result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
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
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">Belum ada transaksi</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">Pertama</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">Sebelumnya</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">Selanjutnya</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">Terakhir</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>