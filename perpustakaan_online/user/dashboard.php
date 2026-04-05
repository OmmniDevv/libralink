<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('user');
$current_user = getCurrentUser();

$db = new Database();
$conn = $db->connect();

// Statistik
$query_stats = "
    SELECT 
        COUNT(*) as total_buku,
        (SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'dipinjam') as buku_dipinjam,
        (SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'dikembalikan') as total_kembali
    FROM books
";

$stmt = $conn->prepare($query_stats);
$stmt->bind_param('ii', $current_user['id'], $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

// Buku terbaru
$query_latest = "
    SELECT b.id, b.judul, b.penulis, b.stok, c.nama_kategori 
    FROM books b
    JOIN categories c ON b.kategori_id = c.id
    ORDER BY b.created_at DESC
    LIMIT 5
";

$result_latest = $conn->query($query_latest);
$latest_books = $result_latest->fetch_all(MYSQLI_ASSOC);

// Peminjaman aktif
$query_active = "
    SELECT t.id, t.tanggal_pinjam, t.tanggal_kembali_direncanakan, 
           GROUP_CONCAT(b.judul SEPARATOR ', ') as buku,
           COUNT(td.id) as jumlah_buku
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN books b ON td.book_id = b.id
    WHERE t.user_id = ? AND t.status = 'dipinjam'
    GROUP BY t.id
    ORDER BY t.tanggal_pinjam DESC
    LIMIT 3
";

$stmt = $conn->prepare($query_active);
$stmt->bind_param('i', $current_user['id']);
$stmt->execute();
$result_active = $stmt->get_result();
$active_loans = $result_active->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->closeConnection();

$page_title = 'Dashboard - User';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Dashboard</h1>
            <p class="text-muted">Selamat datang, <?php echo sanitize($current_user['nama']); ?>!</p>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
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

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Sedang Dipinjam</p>
                            <h3 class="fw-bold"><?php echo $stats['buku_dipinjam']; ?></h3>
                        </div>
                        <div style="font-size: 40px;">📤</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Dikembalikan</p>
                            <h3 class="fw-bold"><?php echo $stats['total_kembali']; ?></h3>
                        </div>
                        <div style="font-size: 40px;">📥</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Status Daftar</p>
                            <h3 class="fw-bold"><span class="badge bg-success">Aktif</span></h3>
                        </div>
                        <div style="font-size: 40px;">✅</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Peminjaman Aktif -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Peminjaman Aktif</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($active_loans)): ?>
                        <?php foreach ($active_loans as $loan): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <p class="fw-bold mb-1"><?php echo sanitize($loan['buku']); ?></p>
                                        <small class="text-muted">Jumlah: <?php echo $loan['jumlah_buku']; ?> buku</small>
                                    </div>
                                </div>
                                <div class="row g-2 text-sm">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Dipinjam:</small>
                                        <small><?php echo formatDate($loan['tanggal_pinjam']); ?></small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Kembali:</small>
                                        <small><?php echo formatDate($loan['tanggal_kembali_direncanakan']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="/perpustakaan_online/user/peminjaman.php" class="btn btn-sm btn-outline-primary w-100">Lihat Semua</a>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">Anda tidak memiliki peminjaman aktif</p>
                        <a href="/perpustakaan_online/user/buku.php" class="btn btn-sm btn-primary w-100">Mulai Pinjam Buku</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Buku Terbaru -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Buku Terbaru</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($latest_books)): ?>
                        <?php foreach ($latest_books as $book): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="fw-bold mb-1"><?php echo sanitize($book['judul']); ?></p>
                                        <small class="text-muted d-block"><?php echo sanitize($book['penulis']); ?></small>
                                        <small class="text-muted d-block"><?php echo sanitize($book['nama_kategori']); ?></small>
                                    </div>
                                    <span class="badge bg-info"><?php echo $book['stok']; ?> stok</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="/perpustakaan_online/user/buku.php" class="btn btn-sm btn-outline-primary w-100">Lihat Semua Buku</a>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">Belum ada buku di katalog</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>