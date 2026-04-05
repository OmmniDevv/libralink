<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('admin');

$db = new Database();
$conn = $db->connect();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;
$status_filter = sanitize($_GET['status'] ?? '');

// Build query
$where_clause = "WHERE 1=1";
if (!empty($status_filter)) {
    $where_clause .= " AND t.status = '$status_filter'";
}

// Count total
$count_query = "SELECT COUNT(*) as total FROM transactions t $where_clause";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get transactions
$transactions_query = "
    SELECT t.id, u.nama, u.email, t.tanggal_pinjam, t.tanggal_kembali_direncanakan, 
           t.tanggal_kembali_aktual, t.status, COUNT(td.id) as jumlah_buku,
           COALESCE(p.nominal_denda, 0) as nominal_denda
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN penalties p ON t.id = p.transaction_id
    $where_clause
    GROUP BY t.id
    ORDER BY t.tanggal_pinjam DESC
    LIMIT ? OFFSET ?
";

$transactions_stmt = $conn->prepare($transactions_query);
$transactions_stmt->bind_param('ii', $per_page, $offset);
$transactions_stmt->execute();
$transactions_result = $transactions_stmt->get_result();
$transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
$transactions_stmt->close();

$db->closeConnection();

$page_title = 'Semua Transaksi - Admin';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-fill" style="margin-left: 280px;">
        <div class="container-fluid py-4 px-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="fw-bold">Semua Transaksi</h1>
                    <p class="text-muted">Laporan lengkap semua transaksi peminjaman dan pengembalian</p>
                </div>
            </div>

            <!-- Filter -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="transaksi.php" class="d-flex gap-2">
                        <select class="form-select" name="status" style="max-width: 200px;">
                            <option value="">Semua Status</option>
                            <option value="dipinjam" <?php echo $status_filter === 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                            <option value="dikembalikan" <?php echo $status_filter === 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                            <option value="hilang" <?php echo $status_filter === 'hilang' ? 'selected' : ''; ?>>Hilang</option>
                            <option value="dibatalkan" <?php echo $status_filter === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="transaksi.php" class="btn btn-outline-secondary">Reset</a>
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Jumlah Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Tgl Rencana</th>
                                <th>Tgl Kembali</th>
                                <th>Status</th>
                                <th>Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $trx): ?>
                                    <tr>
                                        <td><strong>#<?php echo $trx['id']; ?></strong></td>
                                        <td>
                                            <small><?php echo sanitize($trx['nama']); ?></small><br>
                                            <small class="text-muted"><?php echo sanitize($trx['email']); ?></small>
                                        </td>
                                        <td><?php echo $trx['jumlah_buku']; ?></td>
                                        <td><?php echo formatDate($trx['tanggal_pinjam']); ?></td>
                                        <td><?php echo formatDate($trx['tanggal_kembali_direncanakan']); ?></td>
                                        <td>
                                            <?php 
                                            echo $trx['tanggal_kembali_aktual'] 
                                                ? formatDate($trx['tanggal_kembali_aktual']) 
                                                : '-'; 
                                            ?>
                                        </td>
                                        <td><?php echo getStatusBadge($trx['status']); ?></td>
                                        <td>
                                            <?php 
                                            if ($trx['nominal_denda'] > 0) {
                                                echo '<span class="badge bg-danger">' . formatRupiah($trx['nominal_denda']) . '</span>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Tidak ada transaksi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=1">First</a>
                        </li>
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">Previous</a>
                        </li>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>">Next</a>
                        </li>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>">Last</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>