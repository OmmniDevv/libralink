<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('petugas');

$db = new Database();
$conn = $db->connect();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count
$count_query = "SELECT COUNT(*) as total FROM transactions WHERE status = 'dipinjam'";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get active loans
$loans_query = "
    SELECT t.id, u.nama, u.email, t.tanggal_pinjam, t.tanggal_kembali_direncanakan, 
           GROUP_CONCAT(b.judul SEPARATOR ', ') as buku,
           COUNT(td.id) as jumlah_buku
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN books b ON td.book_id = b.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.status = 'dipinjam'
    GROUP BY t.id
    ORDER BY t.tanggal_pinjam DESC
    LIMIT ? OFFSET ?
";

$loans_stmt = $conn->prepare($loans_query);
$loans_stmt->bind_param('ii', $per_page, $offset);
$loans_stmt->execute();
$loans_result = $loans_stmt->get_result();
$loans = $loans_result->fetch_all(MYSQLI_ASSOC);
$loans_stmt->close();

$db->closeConnection();

$page_title = 'Kelola Peminjaman - Petugas';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-fill" style="margin-left: 280px;">
        <div class="container-fluid py-4 px-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="fw-bold">Kelola Peminjaman</h1>
                    <p class="text-muted">Kelola dan verifikasi peminjaman buku yang masih aktif</p>
                </div>
            </div>

            <!-- Loans Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Buku</th>
                                <th>Jumlah</th>
                                <th>Tgl Pinjam</th>
                                <th>Tgl Rencana</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($loans)): ?>
                                <?php foreach ($loans as $loan): ?>
                                    <?php
                                    $today = new DateTime();
                                    $return_date = new DateTime($loan['tanggal_kembali_direncanakan']);
                                    $is_overdue = $today > $return_date;
                                    ?>
                                    <tr <?php echo $is_overdue ? 'class="table-danger"' : ''; ?>>
                                        <td><strong>#<?php echo $loan['id']; ?></strong></td>
                                        <td>
                                            <small><?php echo sanitize($loan['nama']); ?></small><br>
                                            <small class="text-muted"><?php echo sanitize($loan['email']); ?></small>
                                        </td>
                                        <td><?php echo sanitize(substr($loan['buku'], 0, 40) . (strlen($loan['buku']) > 40 ? '...' : '')); ?></td>
                                        <td><?php echo $loan['jumlah_buku']; ?></td>
                                        <td><?php echo formatDate($loan['tanggal_pinjam']); ?></td>
                                        <td><?php echo formatDate($loan['tanggal_kembali_direncanakan']); ?></td>
                                        <td>
                                            <?php if ($is_overdue): ?>
                                                <span class="badge bg-danger">Terlambat</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/perpustakaan_online/petugas/pengembalian.php?id=<?php echo $loan['id']; ?>" class="btn btn-sm btn-success">
                                                Terima Kembali
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Tidak ada peminjaman aktif</td>
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