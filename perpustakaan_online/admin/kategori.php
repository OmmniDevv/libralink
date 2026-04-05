<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('admin');

$db = new Database();
$conn = $db->connect();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if (empty($nama_kategori)) {
            $error_message = 'Nama kategori harus diisi!';
        } else {
            $insert_query = "INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param('ss', $nama_kategori, $deskripsi);

            if ($insert_stmt->execute()) {
                $success_message = 'Kategori berhasil ditambahkan!';
            } else {
                $error_message = 'Gagal menambahkan kategori: ' . $conn->error;
            }
            $insert_stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if ($id <= 0 || empty($nama_kategori)) {
            $error_message = 'Data tidak valid!';
        } else {
            $update_query = "UPDATE categories SET nama_kategori = ?, deskripsi = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('ssi', $nama_kategori, $deskripsi, $id);

            if ($update_stmt->execute()) {
                $success_message = 'Kategori berhasil diperbarui!';
            } else {
                $error_message = 'Gagal memperbarui kategori: ' . $conn->error;
            }
            $update_stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $error_message = 'ID kategori tidak valid!';
        } else {
            $delete_query = "DELETE FROM categories WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('i', $id);

            if ($delete_stmt->execute()) {
                $success_message = 'Kategori berhasil dihapus!';
            } else {
                $error_message = 'Gagal menghapus kategori: ' . $conn->error;
            }
            $delete_stmt->close();
        }
    }
}

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY nama_kategori ASC";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$db->closeConnection();

$page_title = 'Manajemen Kategori - Admin';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-fill" style="margin-left: 280px;">
        <div class="container-fluid py-4 px-4">
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="fw-bold">Manajemen Kategori</h1>
                        <p class="text-muted">Kelola kategori buku perpustakaan</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        ➕ Tambah Kategori
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

            <!-- Categories Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><strong>#<?php echo $cat['id']; ?></strong></td>
                                        <td><?php echo sanitize($cat['nama_kategori']); ?></td>
                                        <td><?php echo sanitize($cat['deskripsi']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCategoryModal" onclick="loadCategoryData(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                                Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $cat['id']; ?>)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Tidak ada kategori</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="kategori.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_kategori" class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="kategori.php" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nama_kategori" class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" id="edit_nama_kategori" name="nama_kategori" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="kategori.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus kategori ini?</p>
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
function loadCategoryData(category) {
    document.getElementById('edit_id').value = category.id;
    document.getElementById('edit_nama_kategori').value = category.nama_kategori;
    document.getElementById('edit_deskripsi').value = category.deskripsi || '';
}

function deleteCategory(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>