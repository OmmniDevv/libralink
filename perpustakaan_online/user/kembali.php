<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('user');
$current_user = getCurrentUser();

$transaction_id = (int)($_GET['id'] ?? 0);

if ($transaction_id <= 0) {
    header('Location: /perpustakaan_online/user/peminjaman.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get transaction details
$query = "
    SELECT t.id, t.tanggal_pinjam, t.tanggal_kembali_direncanakan, t.status,
           GROUP_CONCAT(b.judul SEPARATOR ', ') as buku,
           GROUP_CONCAT(b.id SEPARATOR ',') as book_ids,
           GROUP_CONCAT(td.jumlah SEPARATOR ',') as jumlah_list,
           COUNT(td.id) as jumlah_buku
    FROM transactions t
    LEFT JOIN transaction_details td ON t.id = td.transaction_id
    LEFT JOIN books b ON td.book_id = b.id
    WHERE t.id = ? AND t.user_id = ?
    GROUP BY t.id
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $transaction_id, $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: /perpustakaan_online/user/peminjaman.php');
    exit;
}

$transaction = $result->fetch_assoc();
$stmt->close();

if ($transaction['status'] !== 'dipinjam') {
    header('Location: /perpustakaan_online/user/peminjaman.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update transaction status
    $query_update = "UPDATE transactions SET status = 'dikembalikan', tanggal_kembali_aktual = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query_update);
    $stmt->bind_param('i', $transaction_id);

    if ($stmt->execute()) {
        // Restore stok
        $book_ids = explode(',', $transaction['book_ids']);
        $jumlah_list = explode(',', $transaction['jumlah_list']);

        for ($i = 0; $i < count($book_ids); $i++) {
            $query_restore = "UPDATE books SET stok = stok + ? WHERE id = ?";
            $stmt_restore = $conn->prepare($query_restore);
            $stmt_restore->bind_param('ii', $jumlah_list[$i], $book_ids[$i]);
            $stmt_restore->execute();
            $stmt_restore->close();
        }

        // Check for penalty
        $today = new DateTime();
        $return_date = new DateTime($transaction['tanggal_kembali_direncanakan']);
        
        if ($today > $return_date) {
            $hari_terlambat = $today->diff($return_date)->days;
            $nominal_denda = $hari_terlambat * 5000;

            $query_penalty = "INSERT INTO penalties (transaction_id, hari_terlambat, nominal_denda) 
                            VALUES (?, ?, ?)";
            $stmt_penalty = $conn->prepare($query_penalty);
            $stmt_penalty->bind_param('iii', $transaction_id, $hari_terlambat, $nominal_denda);
            $stmt_penalty->execute();
            $stmt_penalty->close();
        }

        $_SESSION['success_message'] = 'Buku berhasil dikembalikan!';
        header('Location: /perpustakaan_online/user/peminjaman.php');
        exit;
    } else {
        $error = 'Gagal mengembalikan buku';
    }

    $stmt->close();
}

$db->closeConnection();

$page_title = 'Pengembalian Buku';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Kembalikan Buku</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Buku yang Dipinjam</label>
                        <p class="form-control-plaintext fw-bold">
                            <?php echo sanitize($transaction['buku']); ?>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah Buku</label>
                        <p class="form-control-plaintext">
                            <?php echo $transaction['jumlah_buku']; ?> buku
                        </p>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pinjam</label>
                            <p class="form-control-plaintext">
                                <?php echo formatDateTime($transaction['tanggal_pinjam']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Kembali Direncanakan</label>
                            <p class="form-control-plaintext">
                                <?php echo formatDate($transaction['tanggal_kembali_direncanakan']); ?>
                            </p>
                        </div>
                    </div>

                    <?php
                    $today = new DateTime();
                    $return_date = new DateTime($transaction['tanggal_kembali_direncanakan']);
                    $is_overdue = $today > $return_date;
                    $hari_terlambat = $is_overdue ? $today->diff($return_date)->days : 0;
                    $denda = $hari_terlambat * 5000;
                    ?>

                    <?php if ($is_overdue): ?>
                        <div class="alert alert-danger mb-3" role="alert">
                            <strong>⚠️ Keterlambatan Terdeteksi</strong><br>
                            Hari Terlambat: <?php echo $hari_terlambat; ?> hari<br>
                            <strong>Total Denda: <?php echo formatRupiah($denda); ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-3" role="alert">
                            <strong>✅ Tepat Waktu</strong><br>
                            Buku dikembalikan sebelum tanggal yang ditentukan.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="kembali.php?id=<?php echo $transaction_id; ?>">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Konfirmasi Pengembalian</button>
                            <a href="/perpustakaan_online/user/peminjaman.php" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>