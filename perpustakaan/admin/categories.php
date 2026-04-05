<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('admin');

// Get categories
$query_categories = "SELECT id, nama_kategori, deskripsi FROM categories ORDER BY nama_kategori ASC";
$categories_result = $conn->query($query_categories);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php require_once '../includes/sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <h2 class="fw-bold mb-4">Kelola Kategori</h2>

            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus"></i> Tambah Kategori Baru
            </button>

            <div class="row">
                <?php while ($cat = $categories_result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($cat['nama_kategori']); ?></h5>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($cat['deskripsi']); ?></p>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                            onclick="editCategory(<?php echo $cat['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="deleteCategory(<?php echo $cat['id']; ?>)">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit Category -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="categoryForm" method="POST" action="../api/category_action.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="categoryModalTitle">Tambah Kategori Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="categoryId" name="id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori *</label>
                            <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
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
        const addCategoryModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));

        function editCategory(categoryId) {
            fetch(`../api/get_category.php?id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cat = data.data;
                        document.getElementById('categoryId').value = cat.id;
                        document.getElementById('nama_kategori').value = cat.nama_kategori;
                        document.getElementById('deskripsi').value = cat.deskripsi || '';
                        
                        document.getElementById('categoryModalTitle').textContent = 'Edit Kategori';
                        document.getElementById('submitBtn').textContent = 'Update';
                        addCategoryModal.show();
                    } else {
                        alert('Gagal memuat data kategori');
                    }
                });
        }

        function deleteCategory(categoryId) {
            if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                fetch(`../api/delete_category.php?id=${categoryId}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Gagal menghapus kategori');
                        }
                    });
            }
        }

        // Reset form when modal is hidden
        document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryModalTitle').textContent = 'Tambah Kategori Baru';
            document.getElementById('submitBtn').textContent = 'Simpan';
        });

        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', document.getElementById('categoryId').value ? 'update' : 'add');

            fetch('../api/category_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addCategoryModal.hide();
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menyimpan data');
                }
            });
        });
    </script>
</body>
</html>