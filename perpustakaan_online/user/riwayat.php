<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('user');
$current_user = getCurrentUser();

$db = new Database();
$conn = $db->connect();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Total records
$count_query = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param('i', $current_user['id']);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Get all transactions
$query = "
    SELECT t.id, t.tanggal_pinjam, t.tanggal_kembali_direncanakan, t.tanggal_kembali_aktual, t.status,
           GROUP_CONCAT(b.judul SEPARATOR ', ') as buku,
           COUNT(td.id) as jumlah_buku,
           (SELECT nominal_denda FROM penalties WHERE transaction_id = t.id) as nominal_denda
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN books b ON td.book_id = b.id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.tanggal_pinjam DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $current_user['id'], $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->closeConnection();

$page_title = 'Riwayat Pinjaman';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Riwayat Pinjaman</h1>
            <p class="text-muted">Lihat semua riwayat peminjaman dan pengembalian Anda</p>
        </div>
    </div>

    <?php if (!empty($transactions)): ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Buku</th>
                            <th>Jumlah</th>
                            <th>Tanggal Pinjam</th>
                            <th>Kembali Rencana</th>
                            <th>Kembali Aktual</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trx): ?>
                            <tr>
                                <td><strong>#<?php echo $trx['id']; ?></strong></td>
                                <td><?php echo sanitize($trx['buku']); ?></td>
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
                                        echo '<span class="badge bg-success">Tidak ada</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
                            <a class="page-link" href="?page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
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
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <h5>Belum Ada Riwayat</h5>
            <p>Anda belum memiliki riwayat peminjaman. Mulai pinjam buku sekarang!</p>
            <a href="/perpustakaan_online/user/buku.php" class="btn btn-primary">Pinjam Buku</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>