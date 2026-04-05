<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('user');

// Pagination
$per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = getOffset($page, $per_page);

// Search dan filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;

// Build query
$where = "WHERE stok_tersedia > 0";
if ($search) {
    $where .= " AND (judul LIKE '%$search%' OR penulis LIKE '%$search%' OR penerbit LIKE '%$search%')";
}
if ($kategori > 0) {
    $where .= " AND kategori_id = $kategori";
}

// Get total books
$query_count = "SELECT COUNT(*) as total FROM books $where";
$count_result = $conn->query($query_count);
$total_books = $count_result->fetch_assoc()['total'];
$total_pages = getPagination($total_books, $per_page);

// Get books
$query_books = "SELECT b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.stok_tersedia, 
                       c.nama_kategori
                FROM books b
                LEFT JOIN categories c ON b.kategori_id = c.id
                $where
                ORDER BY b.judul ASC
                LIMIT $per_page OFFSET $offset";
$books_result = $conn->query($query_books);

// Get categories for filter
$query_categories = "SELECT * FROM categories ORDER BY nama_kategori ASC";
$categories_result = $conn->query($query_categories);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Buku - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-4">Daftar Buku</h2>

        <!-- Search and Filter -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cari Buku</label>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Judul, penulis, atau penerbit..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="kategori">
                                <option value="0">Semua Kategori</option>
                                <?php while ($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $kategori === $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['nama_kategori']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="books.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Books Grid -->
        <div class="row mb-4">
            <?php if ($books_result->num_rows > 0): ?>
                <?php while ($book = $books_result->fetch_assoc()): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-shadow">
                            <div class="card-body d-flex flex-column">
                                <div class="book-icon text-center mb-3">
                                    <i class="fas fa-book" style="font-size: 48px; color: #667eea;"></i>
                                </div>
                                <h6 class="card-title fw-bold"><?php echo truncateText($book['judul'], 50); ?></h6>
                                <p class="card-text small text-muted">
                                    Penulis: <?php echo truncateText($book['penulis'], 30); ?><br>
                                    Penerbit: <?php echo truncateText($book['penerbit'], 30); ?><br>
                                    Tahun: <?php echo $book['tahun_terbit']; ?>
                                </p>
                                <p class="small">
                                    <span class="badge bg-info"><?php echo $book['nama_kategori']; ?></span>
                                </p>
                                <p class="text-success fw-bold mb-3">
                                    Stok: <?php echo $book['stok_tersedia']; ?>
                                </p>
                                <a href="borrow.php?book_id=<?php echo $book['id']; ?>" 
                                   class="btn btn-primary btn-sm mt-auto">
                                    <i class="fas fa-hand-holding-heart"></i> Pinjam
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Buku tidak ditemukan
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori; ?>">
                                Pertama
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori; ?>">
                                Sebelumnya
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori; ?>">
                                Selanjutnya
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo $kategori; ?>">
                                Terakhir
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="text-center mt-4 text-muted">
            <p>Menampilkan <?php echo $books_result->num_rows; ?> dari <?php echo $total_books; ?> buku</p>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
    <style>
        .hover-shadow {
            transition: box-shadow 0.3s ease;
        }
        .hover-shadow:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        }
    </style>
</body>
</html>