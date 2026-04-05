<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('admin');

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = getOffset($page, $per_page);

// Get total books
$query_count = "SELECT COUNT(*) as total FROM books";
$count_result = $conn->query($query_count);
$total_books = $count_result->fetch_assoc()['total'];
$total_pages = getPagination($total_books, $per_page);

// Get books
$query_books = "SELECT b.id, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.stok, b.stok_tersedia, 
                       c.nama_kategori
                FROM books b
                LEFT JOIN categories c ON b.kategori_id = c.id
                ORDER BY b.judul ASC
                LIMIT $per_page OFFSET $offset";
$books_result = $conn->query($query_books);

// Get categories
$query_categories = "SELECT * FROM categories ORDER BY nama_kategori ASC";
$categories_result = $conn->query($query_categories);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php require_once '../includes/sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <h2 class="fw-bold mb-4">Kelola Buku</h2>

            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="fas fa-plus"></i> Tambah Buku Baru
            </button>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if ($books_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Judul</th>
                                        <th>Penulis</th>
                                        <th>Kategori</th>
                                        <th>Stok</th>
                                        <th>Tersedia</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = $offset + 1;
                                    while ($book = $books_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($book['judul']); ?></td>
                                            <td><?php echo htmlspecialchars($book['penulis']); ?></td>
                                            <td><?php echo htmlspecialchars($book['nama_kategori']); ?></td>
                                            <td><?php echo $book['stok']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $book['stok_tersedia'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $book['stok_tersedia']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                        onclick="editBook(<?php echo $book['id']; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteBook(<?php echo $book['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">Belum ada data buku</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">Pertama</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Sebelumnya</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Selanjutnya</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>">Terakhir</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Add/Edit Book -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="bookForm" method="POST" action="../api/book_action.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bookModalTitle">Tambah Buku Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="bookId" name="id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Judul *</label>
                            <input type="text" class="form-control" id="judul" name="judul" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Penulis *</label>
                            <input type="text" class="form-control" id="penulis" name="penulis" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Penerbit *</label>
                            <input type="text" class="form-control" id="penerbit" name="penerbit" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tahun Terbit *</label>
                            <input type="number" class="form-control" id="tahun_terbit" name="tahun_terbit" 
                                   min="1900" max="2100" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kategori *</label>
                            <select class="form-select" id="kategori_id" name="kategori_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php 
                                // Reset pointer
                                $categories_result->data_seek(0);
                                while ($cat = $categories_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stok *</label>
                            <input type="number" class="form-control" id="stok" name="stok" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        const addBookModal = new bootstrap.Modal(document.getElementById('addBookModal'));

        function editBook(bookId) {
            fetch(`../api/get_book.php?id=${bookId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const book = data.data;
                        document.getElementById('bookId').value = book.id;
                        document.getElementById('judul').value = book.judul;
                        document.getElementById('penulis').value = book.penulis;
                        document.getElementById('penerbit').value = book.penerbit;
                        document.getElementById('tahun_terbit').value = book.tahun_terbit;
                        document.getElementById('kategori_id').value = book.kategori_id;
                        document.getElementById('stok').value = book.stok;
                        document.getElementById('deskripsi').value = book.deskripsi || '';
                        
                        document.getElementById('bookModalTitle').textContent = 'Edit Buku';
                        document.getElementById('submitBtn').textContent = 'Update';
                        addBookModal.show();
                    } else {
                        alert('Gagal memuat data buku');
                    }
                });
        }

        function deleteBook(bookId) {
            if (confirm('Apakah Anda yakin ingin menghapus buku ini?')) {
                fetch(`../api/delete_book.php?id=${bookId}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Gagal menghapus buku');
                        }
                    });
            }
        }

        // Reset form when modal is hidden
        document.getElementById('addBookModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('bookForm').reset();
            document.getElementById('bookId').value = '';
            document.getElementById('bookModalTitle').textContent = 'Tambah Buku Baru';
            document.getElementById('submitBtn').textContent = 'Simpan';
        });

        document.getElementById('bookForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', document.getElementById('bookId').value ? 'update' : 'add');

            fetch('../api/book_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addBookModal.hide();
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menyimpan data');
                }
            });
        });
    </script>
</body>
</html>