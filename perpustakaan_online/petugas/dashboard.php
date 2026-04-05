<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('petugas');

$db = new Database();
$conn = $db->connect();

// Get statistics
$stats_query = "
    SELECT
        (SELECT COUNT(*) FROM transactions WHERE status = 'dipinjam') as peminjaman_aktif,
        (SELECT COUNT(*) FROM transactions WHERE status = 'dikembalikan' AND DATE(tanggal_kembali_aktual) = CURDATE()) as pengembalian_hari_ini,
        (SELECT COUNT(*) FROM penalties WHERE status_pembayaran = 'belum_bayar') as denda_belum_bayar,
        (SELECT COALESCE(SUM(nominal_denda), 0) FROM penalties WHERE status_pembayaran = 'belum_bayar') as total_denda
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get pending loans (menunggu verifikasi)
$pending_query = "
    SELECT t.id, u.nama, u.email, t.tanggal_pinjam, COUNT(td.id) as jumlah_buku
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.status = 'dipinjam'
    GROUP BY t.id
    ORDER BY t.tanggal_pinjam DESC
    LIMIT 10
";

$pending_result = $conn->query($pending_query);
$pending_loans = $pending_result->fetch_all(MYSQLI_ASSOC);

// Get overdue loans
$overdue_query = "
    SELECT t.id, u.nama, t.tanggal_kembali_direncanakan, DATEDIFF(CURDATE(), t.tanggal_kembali_direncanakan) as hari_terlambat
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.status = 'dipinjam' AND t.tanggal_kembali_direncanakan < CURDATE()
    ORDER BY t.tanggal_kembali_direncanakan ASC
    LIMIT 5
";

$overdue_result = $conn->query($overdue_query);
$overdue_loans = $overdue_result->fetch_all(MYSQLI_ASSOC);

$db->closeConnection();

$page_title = 'Dashboard - Petugas';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-fill" style="margin-left: 280px;">
        <div class="container-fluid py-4 px-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="fw-bold">Dashboard Petugas</h1>
                    <p class="text-muted">Kelola peminjaman dan pengembalian buku</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Peminjaman Aktif</p>
                                    <h3 class="fw-bold"><?php echo $stats['peminjaman_aktif']; ?></h3>
                                </div>
                                <div style="font-size: 40px;">📤</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Pengembalian Hari Ini</p>
                                    <h3 class="fw-bold"><?php echo $stats['pengembalian_hari_ini']; ?></h3>
                                </div>
                                <div style="font-size: 40px;">📥</div>
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
                                    <h3 class="fw-bold"><?php echo $stats['denda_belum_bayar']; ?></h3>
                                </div>
                                <div style="font-size: 40px;">💰</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1">Total Denda</p>
                                    <h3 class="fw-bold"><?php echo formatRupiah($stats['total_denda']); ?></h3>
                                </div>
                                <div style="font-size: 40px;">💵</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Pending Loans -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Peminjaman Aktif</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_loans as $loan): ?>
                                        <tr>
                                            <td><?php echo sanitize($loan['nama']); ?></td>
                                            <td><?php echo $loan['jumlah_buku']; ?> buku</td>
                                            <td><?php echo formatDate($loan['tanggal_pinjam']); ?></td>
                                            <td>
                                                <a href="/perpustakaan_online/petugas/peminjaman.php" class="btn btn-sm btn-primary">Detail</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-top">
                            <a href="/perpustakaan_online/petugas/peminjaman.php" class="btn btn-sm btn-outline-primary">Kelola Semua</a>
                        </div>
                    </div>
                </div>

                <!-- Overdue Loans -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 fw-bold">Peminjaman Terlambat</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Tgl Rencana</th>
                                        <th>Hari</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdue_loans as $loan): ?>
                                        <tr class="table-danger">
                                            <td><?php echo sanitize($loan['nama']); ?></td>
                                            <td><?php echo formatDate($loan['tanggal_kembali_direncanakan']); ?></td>
                                            <td><span class="badge bg-danger"><?php echo $loan['hari_terlambat']; ?> hari</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-top">
                            <a href="/perpustakaan_online/petugas/pengembalian.php" class="btn btn-sm btn-outline-danger">Kelola Pengembalian</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>