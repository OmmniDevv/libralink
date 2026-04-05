<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('user');

$db = new Database();
$conn = $db->connect();

// Ambil parameter search dan filter
$search = sanitize($_GET['search'] ?? '');
$kategori = sanitize($_GET['kategori'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Query builder
$where_clause = "WHERE b.stok > 0";
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause .= " AND (b.judul LIKE ? OR b.penulis LIKE ?)";
    $search_term = '%' . $search . '%';
    $params = [$search_term, $search_term];
    $types = 'ss';
}

if (!empty($kategori)) {
    $where_clause .= " AND b.kategori_id = ?";
    $params[] = (int)$kategori;
    $types .= 'i';
}

// Total records
$count_query = "SELECT COUNT(*) as total FROM books b $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Get books
$query = "
    SELECT b.id, b.judul, b.penulis, b.penerbit, b.stok, c.nama_kategori, b.cover_image
    FROM books b
    JOIN categories c ON b.kategori_id = c.id
    $where_clause
    ORDER BY b.judul ASC
    LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get categories
$categories_query = "SELECT id, nama_kategori FROM categories ORDER BY nama_kategori ASC";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$db->closeConnection();

$page_title = 'Daftar Buku';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Daftar Buku</h1>
            <p class="text-muted">Jelajahi koleksi buku dari perpustakaan kami</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="buku.php" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $kategori == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize($cat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Cari</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Books Grid -->
    <?php if (!empty($books)): ?>
        <div class="row g-4">
            <?php foreach ($books as $book): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm book-card">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px; font-size: 100px;">
                            📖
                        </div>
                        <div class="card-body">
                            <h6 class="card-title fw-bold"><?php echo sanitize($book['judul']); ?></h6>
                            <p class="card-text text-muted small mb-2"><?php echo sanitize($book['penulis']); ?></p>
                            <p class="card-text text-muted small mb-2"><?php echo sanitize($book['penerbit']); ?></p>
                            <p class="mb-2">
                                <span class="badge bg-info"><?php echo $book['nama_kategori']; ?></span>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Stok: <?php echo $book['stok']; ?></small>
                                <a href="/perpustakaan_online/user/pinjam.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">Pinjam</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori); ?>">First</a>
                    </li>
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori); ?>">Previous</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori); ?>">Next</a>
                    </li>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&kategori=<?php echo urlencode($kategori); ?>">Last</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <h5>Buku Tidak Ditemukan</h5>
            <p>Coba gunakan kata kunci yang berbeda atau pilih kategori lain.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>