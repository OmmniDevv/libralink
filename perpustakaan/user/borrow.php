<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('user');

$user_id = getCurrentUserId();
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// Get book details
$query_book = "SELECT id, judul, penulis, penerbit, stok_tersedia FROM books WHERE id = ?";
$stmt = $conn->prepare($query_book);
$stmt->bind_param('i', $book_id);
$stmt->execute();
$book_result = $stmt->get_result();

if ($book_result->num_rows === 0) {
    redirect('books.php?msg=error&error=Buku tidak ditemukan');
}

$book = $book_result->fetch_assoc();

// Check if user already borrowed this book
$query_check = "SELECT t.id FROM transactions t
                JOIN transaction_details td ON t.id = td.transaction_id
                WHERE t.user_id = ? AND td.book_id = ? AND t.status IN ('approved', 'overdue')";
$stmt = $conn->prepare($query_check);
$stmt->bind_param('ii', $user_id, $book_id);
$stmt->execute();
$check_result = $stmt->get_result();

$already_borrowed = $check_result->num_rows > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($already_borrowed) {
        redirect('books.php?msg=error&error=Anda sudah meminjam buku ini');
    }

    if ($book['stok_tersedia'] <= 0) {
        redirect('books.php?msg=error&error=Buku tidak tersedia');
    }

    // Create transaction
    $tanggal_pinjam = date('Y-m-d');
    $tanggal_kembali = date('Y-m-d', strtotime('+7 days'));

    $query_insert = "INSERT INTO transactions (user_id, tanggal_pinjam, tanggal_kembali_rencana, status) 
                     VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query_insert);
    $stmt->bind_param('iss', $user_id, $tanggal_pinjam, $tanggal_kembali);

    if ($stmt->execute()) {
        $transaction_id = $stmt->insert_id;

        // Insert transaction details
        $query_details = "INSERT INTO transaction_details (transaction_id, book_id, jumlah) 
                          VALUES (?, ?, 1)";
        $stmt = $conn->prepare($query_details);
        $stmt->bind_param('ii', $transaction_id, $book_id);
        $stmt->execute();

        // Update book stock
        $new_stok = $book['stok_tersedia'] - 1;
        $query_update = "UPDATE books SET stok_tersedia = ? WHERE id = ?";
        $stmt = $conn->prepare($query_update);
        $stmt->bind_param('ii', $new_stok, $book_id);
        $stmt->execute();

        redirect('borrowing.php?msg=success&success=Permohonan peminjaman berhasil dibuat. Menunggu persetujuan petugas.');
    } else {
        $error = "Gagal membuat permohonan peminjaman";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <a href="books.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Konfirmasi Peminjaman Buku</h5>
                    </div>
                    <div class="card-body">
                        <div class="book-details mb-4">
                            <div class="text-center mb-3">
                                <i class="fas fa-book" style="font-size: 64px; color: #667eea;"></i>
                            </div>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Judul:</td>
                                    <td><?php echo htmlspecialchars($book['judul']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Penulis:</td>
                                    <td><?php echo htmlspecialchars($book['penulis']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Penerbit:</td>
                                    <td><?php echo htmlspecialchars($book['penerbit']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Stok Tersedia:</td>
                                    <td>
                                        <?php if ($book['stok_tersedia'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $book['stok_tersedia']; ?> tersedia</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Tidak tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php if ($already_borrowed): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Anda sudah meminjam buku ini. Kembalikan terlebih dahulu sebelum meminjam lagi.
                            </div>
                            <a href="borrowing.php" class="btn btn-secondary w-100">Lihat Peminjaman Saya</a>
                        <?php elseif ($book['stok_tersedia'] <= 0): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle"></i> Buku tidak tersedia saat ini.
                            </div>
                            <a href="books.php" class="btn btn-secondary w-100">Cari Buku Lain</a>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Durasi peminjaman: <strong>7 hari</strong>
                            </div>

                            <form method="POST">
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-check"></i> Konfirmasi Peminjaman
                                </button>
                                <a href="books.php" class="btn btn-secondary w-100">Batal</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>