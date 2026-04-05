<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('admin');

$db = new Database();
$conn = $db->connect();

// Get statistics
$stats_query = "
    SELECT
        (SELECT COUNT(*) FROM books) as total_buku,
        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_user,
        (SELECT COUNT(*) FROM transactions) as total_transaksi,
        (SELECT COUNT(*) FROM transactions WHERE status = 'dipinjam') as transaksi_aktif,
        (SELECT COUNT(*) FROM categories) as total_kategori,
        (SELECT COALESCE(SUM(nominal_denda), 0) FROM penalties WHERE status_pembayaran = 'belum_bayar') as total_denda_belum_bayar
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent transactions
$recent_query = "
    SELECT t.id, t.user_id, u.nama, t.tanggal_pinjam, t.status, COUNT(td.id) as jumlah_buku
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN users u ON t.user_id = u.id
    GROUP BY t.id
    ORDER BY t.tanggal_pinjam DESC
    LIMIT 10
";

$recent_result = $conn->query($recent_query);
$recent_transactions = $recent_result->fetch_all(MYSQLI_ASSOC);

// Get low stock books
$low_stock_query = "
    SELECT id, judul, stok, kategori_id
    FROM books
    WHERE stok <= 3
    ORDER BY stok ASC
    LIMIT 5
";

$low_stock_result = $conn->query($low_stock_query);
$low_stock_books = $low_stock_result->fetch_all(MYSQLI_ASSOC);

$db->closeConnection();

$page_title = 'Dashboard - Admin';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-fill" style="margin-left: 280px;">
        <div class="container-fluid py-4 px-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="fw-bold">Dashboard Admin</h1>
                    <p class="text-muted">Selamat datang, Admin! Kelola perpustakaan dari sini.</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total Buku</p>
                                    <h3 class="fw-bold"><?php echo number_format($stats['total_buku']); ?></h3>
                                </div>
                                <div style="font-size: 40px;">📖</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total User</p>
                                    <h3 class="fw-bold"><?php echo number_format($stats['total_user']); ?></h3>
                                </div>
                                <div style="font-size: 40px;">👥</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Transaksi Aktif</p>
                                    <h3 class="fw-bold"><?php echo $stats['transaksi_aktif']; ?></h3>
                                </div>
                                <div style="font-size: 40px;">💳</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Denda Belum Bayar</p>
                                    <h3 class="fw-bold"><?php echo formatRupiah($stats['total_denda_belum_bayar']); ?></h3>
                                </div>
                                <div style="font-size: 40px;">💰</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Transactions -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Transaksi Terbaru</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Jumlah Buku</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $trx): ?>
                                        <tr>
                                            <td><strong>#<?php echo $trx['id']; ?></strong></td>
                                            <td><?php echo sanitize($trx['nama']); ?></td>
                                            <td><?php echo $trx['jumlah_buku']; ?></td>
                                            <td><?php echo formatDate($trx['tanggal_pinjam']); ?></td>
                                            <td><?php echo getStatusBadge($trx['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-top">
                            <a href="/perpustakaan_online/admin/transaksi.php" class="btn btn-sm btn-outline-primary">Lihat Semua Transaksi</a>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Books -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Buku Stok Rendah</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($low_stock_books)): ?>
                                <?php foreach ($low_stock_books as $book): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <p class="fw-bold mb-1" style="font-size: 14px;"><?php echo sanitize($book['judul']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Stok: <?php echo $book['stok']; ?></small>
                                            <span class="badge bg-warning">Rendah</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">Semua buku memiliki stok yang cukup</p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-top">
                            <a href="/perpustakaan_online/admin/buku.php" class="btn btn-sm btn-outline-primary">Kelola Buku</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>