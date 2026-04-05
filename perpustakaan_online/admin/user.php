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

// Get all users
$count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

$users_query = "
    SELECT id, nama, email, no_telepon, status, created_at 
    FROM users 
    WHERE role = 'user'
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$users_stmt = $conn->prepare($users_query);
$users_stmt->bind_param('ii', $per_page, $offset);
$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$users_stmt->close();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';

        if ($id <= 0 || !in_array($new_status, ['aktif', 'nonaktif'])) {
            $error_message = 'Data tidak valid!';
        } else {
            $update_query = "UPDATE users SET status = ? WHERE id = ? AND role = 'user'";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('si', $new_status, $id);

            if ($update_stmt->execute()) {
                $success_message = 'Status user berhasil diubah!';
                header('Refresh: 1; url=user.php?page=' . $page);
            } else {
                $error_message = 'Gagal mengubah status user!';
            }
            $update_stmt->close();
        }
    }
}

$db->closeConnection();

$page_title = 'Manajemen User - Admin';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-fill" style="margin-left: 280px;">
        <div class="container-fluid py-4 px-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="fw-bold">Manajemen User</h1>
                    <p class="text-muted">Kelola data anggota perpustakaan</p>
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

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th>Terdaftar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                                        <td><?php echo sanitize($user['nama']); ?></td>
                                        <td><?php echo sanitize($user['email']); ?></td>
                                        <td><?php echo sanitize($user['no_telepon'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td><?php echo getStatusBadge($user['status']); ?></td>
                                        <td>
                                            <form method="POST" action="user.php" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'aktif' ? 'nonaktif' : 'aktif'; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $user['status'] === 'aktif' ? 'btn-danger' : 'btn-success'; ?>">
                                                    <?php echo $user['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada user terdaftar</td>
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
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>