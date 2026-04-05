<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('admin');

$db = new Database();
$conn = $db->connect();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search
$search = sanitize($_GET['search'] ?? '');
$where_clause = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause .= " AND (b.judul LIKE ? OR b.penulis LIKE ?)";
    $search_term = '%' . $search . '%';
    $params = [$search_term, $search_term];
    $types = 'ss';
}

// Count total
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
    SELECT b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.stok, c.nama_kategori
    FROM books b
    JOIN categories c ON b.kategori_id = c.id
    $where_clause
    ORDER BY b.created_at DESC
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

// Handle actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $judul = sanitize($_POST['judul'] ?? '');
        $penulis = sanitize($_POST['penulis'] ?? '');
        $penerbit = sanitize($_POST['penerbit'] ?? '');
        $tahun_terbit = (int)($_POST['tahun_terbit'] ?? 0);
        $isbn = sanitize($_POST['isbn'] ?? '');
        $kategori_id = (int)($_POST['kategori_id'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);
        $lokasi_rak = sanitize($_POST['lokasi_rak'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if (empty($judul) || empty($penulis) || $kategori_id <= 0) {
            $error_message = 'Judul, penulis, dan kategori harus diisi!';
        } else {
            $insert_query = "INSERT INTO books (judul, penulis, penerbit, tahun_terbit, isbn, kategori_id, stok, lokasi_rak, deskripsi) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param('sssisisis', $judul, $penulis, $penerbit, $tahun_terbit, $isbn, $kategori_id, $stok, $lokasi_rak, $deskripsi);

            if ($insert_stmt->execute()) {
                $success_message = 'Buku berhasil ditambahkan!';
                header('Refresh: 1; url=buku.php');
            } else {
                $error_message = 'Gagal menambahkan buku: ' . $conn->error;
            }
            $insert_stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $judul = sanitize($_POST['judul'] ?? '');
        $penulis = sanitize($_POST['penulis'] ?? '');
        $penerbit = sanitize($_POST['penerbit'] ?? '');
        $tahun_terbit = (int)($_POST['tahun_terbit'] ?? 0);
        $isbn = sanitize($_POST['isbn'] ?? '');
        $kategori_id = (int)($_POST['kategori_id'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);
        $lokasi_rak = sanitize($_POST['lokasi_rak'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if ($id <= 0 || empty($judul) || empty($penulis) || $kategori_id <= 0) {
            $error_message = 'Data tidak valid!';
        } else {
            $update_query = "UPDATE books SET judul = ?, penulis = ?, penerbit = ?, tahun_terbit = ?, isbn = ?, kategori_id = ?, stok = ?, lokasi_rak = ?, deskripsi = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('sssisisisi', $judul, $penulis, $penerbit, $tahun_terbit, $isbn, $kategori_id, $stok, $lokasi_rak, $deskripsi, $id);

            if ($update_stmt->execute()) {
                $success_message = 'Buku berhasil diperbarui!';
                header('Refresh: 1; url=buku.php?page=' . $page);
            } else {
                $error_message = 'Gagal memperbarui buku: ' . $conn->error;
            }
            $update_stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $error_message = 'ID buku tidak valid!';
        } else {
            $delete_query = "DELETE FROM books WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('i', $id);

            if ($delete_stmt->execute()) {
                $success_message = 'Buku berhasil dihapus!';
                header('Refresh: 1; url=buku.php?page=' . max(1, $page - 1));
            } else {
                $error_message = 'Gagal menghapus buku: ' . $conn->error;
            }
            $delete_stmt->close();
        }
    }
}

$db->closeConnection();

$page_title = 'Manajemen Buku - Admin';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-fill" style="margin-left: 280px;">
        <div class="container-fluid py-4 px-4">
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="fw-bold">Manajemen Buku</h1>
                        <p class="text-muted">Kelola katalog buku perpustakaan</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        ➕ Tambah Buku
                    </button>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Search -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="buku.php" class="d-flex gap-2">
                        <input type="text" class="form-control" name="search" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary">Cari</button>
                    </form>
                </div>
            </div>

            <!-- Books Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Penerbit</th>
                                <th>Tahun</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($books)): ?>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><strong>#<?php echo $book['id']; ?></strong></td>
                                        <td><?php echo sanitize($book['judul']); ?></td>
                                        <td><?php echo sanitize($book['penulis']); ?></td>
                                        <td><?php echo sanitize($book['penerbit']); ?></td>
                                        <td><?php echo $book['tahun_terbit']; ?></td>
                                        <td><span class="badge bg-info"><?php echo sanitize($book['nama_kategori']); ?></span></td>
                                        <td>
                                            <?php if ($book['stok'] <= 3): ?>
                                                <span class="badge bg-warning"><?php echo $book['stok']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $book['stok']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBookModal" onclick="loadBookData(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                                Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteBook(<?php echo $book['id']; ?>)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Tidak ada buku ditemukan</td>
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
        </div>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Buku Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="buku.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="judul" class="form-label">Judul Buku *</label>
                        <input type="text" class="form-control" id="judul" name="judul" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="penulis" class="form-label">Penulis *</label>
                            <input type="text" class="form-control" id="penulis" name="penulis" required>
                        </div>
                        <div class="col-md-6">
                            <label for="penerbit" class="form-label">Penerbit</label>
                            <input type="text" class="form-control" id="penerbit" name="penerbit">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tahun_terbit" class="form-label">Tahun Terbit</label>
                            <input type="number" class="form-control" id="tahun_terbit" name="tahun_terbit" min="1900" max="2099">
                        </div>
                        <div class="col-md-6">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" class="form-control" id="isbn" name="isbn">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="kategori_id" class="form-label">Kategori *</label>
                            <select class="form-select" id="kategori_id" name="kategori_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="stok" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="stok" name="stok" value="0" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="lokasi_rak" class="form-label">Lokasi Rak</label>
                        <input type="text" class="form-control" id="lokasi_rak" name="lokasi_rak" placeholder="Cth: A1, B2, dll">
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Buku</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="buku.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_judul" class="form-label">Judul Buku *</label>
                        <input type="text" class="form-control" id="edit_judul" name="judul" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_penulis" class="form-label">Penulis *</label>
                            <input type="text" class="form-control" id="edit_penulis" name="penulis" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_penerbit" class="form-label">Penerbit</label>
                            <input type="text" class="form-control" id="edit_penerbit" name="penerbit">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_tahun_terbit" class="form-label">Tahun Terbit</label>
                            <input type="number" class="form-control" id="edit_tahun_terbit" name="tahun_terbit" min="1900" max="2099">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_isbn" class="form-label">ISBN</label>
                            <input type="text" class="form-control" id="edit_isbn" name="isbn">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_kategori_id" class="form-label">Kategori *</label>
                            <select class="form-select" id="edit_kategori_id" name="kategori_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_stok" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="edit_stok" name="stok" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_lokasi_rak" class="form-label">Lokasi Rak</label>
                        <input type="text" class="form-control" id="edit_lokasi_rak" name="lokasi_rak">
                    </div>

                    <div class="mb-3">
                        <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="buku.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Buku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus buku ini? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadBookData(book) {
    document.getElementById('edit_id').value = book.id;
    document.getElementById('edit_judul').value = book.judul;
    document.getElementById('edit_penulis').value = book.penulis;
    document.getElementById('edit_penerbit').value = book.penerbit;
    document.getElementById('edit_tahun_terbit').value = book.tahun_terbit;
    document.getElementById('edit_isbn').value = book.isbn || '';
    document.getElementById('edit_kategori_id').value = book.kategori_id;
    document.getElementById('edit_stok').value = book.stok;
    document.getElementById('edit_lokasi_rak').value = book.lokasi_rak || '';
    document.getElementById('edit_deskripsi').value = book.deskripsi || '';
}

function deleteBook(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteBookModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>