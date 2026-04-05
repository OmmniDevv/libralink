<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('user');
$current_user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $book_id = (int)($_GET['id'] ?? 0);
    
    if ($book_id <= 0) {
        header('Location: /perpustakaan_online/user/buku.php');
        exit;
    }

    $db = new Database();
    $conn = $db->connect();

    // Get book details
    $query = "SELECT id, judul, penulis, penerbit, stok FROM books WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: /perpustakaan_online/user/buku.php');
        exit;
    }

    $book = $result->fetch_assoc();
    $stmt->close();

    if ($book['stok'] <= 0) {
        $db->closeConnection();
        header('Location: /perpustakaan_online/user/buku.php');
        exit;
    }

    $db->closeConnection();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 1);
    $durasi = (int)($_POST['durasi'] ?? 7);

    $error = '';

    if ($book_id <= 0 || $jumlah <= 0 || $durasi <= 0) {
        $error = 'Data tidak valid';
    } else {
        $db = new Database();
        $conn = $db->connect();

        // Check stok
        $query_check = "SELECT stok FROM books WHERE id = ?";
        $stmt = $conn->prepare($query_check);
        $stmt->bind_param('i', $book_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0 || $result->fetch_assoc()['stok'] < $jumlah) {
            $error = 'Stok buku tidak cukup';
        } else {
            // Create transaction
            $tanggal_kembali_direncanakan = date('Y-m-d', strtotime("+$durasi days"));
            
            $query_insert = "INSERT INTO transactions (user_id, tanggal_pinjam, tanggal_kembali_direncanakan, status) 
                           VALUES (?, NOW(), ?, 'dipinjam')";
            $stmt = $conn->prepare($query_insert);
            $stmt->bind_param('is', $current_user['id'], $tanggal_kembali_direncanakan);

            if ($stmt->execute()) {
                $transaction_id = $stmt->insert_id;

                // Insert transaction details
                $query_detail = "INSERT INTO transaction_details (transaction_id, book_id, jumlah) VALUES (?, ?, ?)";
                $stmt_detail = $conn->prepare($query_detail);
                $stmt_detail->bind_param('iii', $transaction_id, $book_id, $jumlah);

                if ($stmt_detail->execute()) {
                    // Update stok
                    $query_update = "UPDATE books SET stok = stok - ? WHERE id = ?";
                    $stmt_update = $conn->prepare($query_update);
                    $stmt_update->bind_param('ii', $jumlah, $book_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    $_SESSION['success_message'] = 'Buku berhasil dipinjam! Silakan kembalikan sesuai tanggal yang ditentukan.';
                    header('Location: /perpustakaan_online/user/peminjaman.php');
                    exit;
                } else {
                    $error = 'Gagal menambahkan detail peminjaman';
                }

                $stmt_detail->close();
            } else {
                $error = 'Gagal membuat peminjaman';
            }

            $stmt->close();
        }

        $db->closeConnection();
    }
}

$page_title = 'Pinjam Buku';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Pinjam Buku</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error) && $error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="pinjam.php" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Buku</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($book['judul']); ?>" disabled>
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Penulis</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($book['penulis']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Penerbit</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($book['penerbit']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stok Tersedia</label>
                            <input type="text" class="form-control" value="<?php echo $book['stok']; ?> buku" disabled>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="jumlah" class="form-label">Jumlah Buku</label>
                                <input type="number" class="form-control" id="jumlah" name="jumlah" value="1" min="1" max="<?php echo $book['stok']; ?>" required>
                                <div class="invalid-feedback">
                                    Jumlah tidak valid
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="durasi" class="form-label">Durasi (Hari)</label>
                                <select class="form-select" id="durasi" name="durasi" required>
                                    <option value="7">7 hari</option>
                                    <option value="14">14 hari</option>
                                    <option value="21">21 hari</option>
                                    <option value="30">30 hari</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded">
                            <p class="mb-2"><small><strong>Tanggal Peminjaman:</strong></small></p>
                            <p class="mb-3"><small><?php echo formatDate(date('Y-m-d')); ?></small></p>
                            
                            <p class="mb-2"><small><strong>Tanggal Kembali Direncanakan:</strong></small></p>
                            <p class="mb-0"><small id="planned_return_date"><?php echo formatDate(date('Y-m-d', strtotime('+7 days'))); ?></small></p>
                        </div>

                        <div class="alert alert-info mt-4" role="alert">
                            <small>
                                <strong>⚠️ Penting:</strong> Harap kembalikan buku sesuai dengan tanggal yang ditentukan. 
                                Keterlambatan akan dikenai denda sebesar Rp 5.000 per hari.
                            </small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Konfirmasi Peminjaman</button>
                            <a href="/perpustakaan_online/user/buku.php" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('durasi').addEventListener('change', function() {
    const days = parseInt(this.value);
    const today = new Date();
    const returnDate = new Date(today.getTime() + days * 24 * 60 * 60 * 1000);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('planned_return_date').textContent = returnDate.toLocaleDateString('id-ID', options);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>