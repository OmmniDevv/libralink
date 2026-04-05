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

// Get total users
$query_count = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$count_result = $conn->query($query_count);
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = getPagination($total_users, $per_page);

// Get users
$query_users = "SELECT id, nama, email, no_anggota, no_telp, alamat, status, created_at
                FROM users WHERE role = 'user'
                ORDER BY created_at DESC
                LIMIT $per_page OFFSET $offset";
$users_result = $conn->query($query_users);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php require_once '../includes/sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <h2 class="fw-bold mb-4">Kelola User</h2>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if ($users_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>No. Anggota</th>
                                        <th>Telepon</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = $offset + 1;
                                    while ($user = $users_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['no_anggota']); ?></td>
                                            <td><?php echo htmlspecialchars($user['no_telp']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                        onclick="viewUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </button>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="deactivateUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-ban"></i> Nonaktifkan
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                            onclick="activateUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-check"></i> Aktifkan
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">Belum ada data user</p>
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

    <!-- Modal View User -->
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Nama:</td>
                            <td id="modalNama"></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Email:</td>
                            <td id="modalEmail"></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">No. Anggota:</td>
                            <td id="modalNoAnggota"></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Telepon:</td>
                            <td id="modalTelepon"></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Alamat:</td>
                            <td id="modalAlamat"></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Terdaftar:</td>
                            <td id="modalTerdaftar"></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        const viewUserModal = new bootstrap.Modal(document.getElementById('viewUserModal'));

        function viewUser(userId) {
            fetch(`../api/get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.data;
                        document.getElementById('modalNama').textContent = user.nama;
                        document.getElementById('modalEmail').textContent = user.email;
                        document.getElementById('modalNoAnggota').textContent = user.no_anggota;
                        document.getElementById('modalTelepon').textContent = user.no_telp;
                        document.getElementById('modalAlamat').textContent = user.alamat;
                        document.getElementById('modalTerdaftar').textContent = new Date(user.created_at).toLocaleDateString('id-ID');
                        viewUserModal.show();
                    }
                });
        }

        function deactivateUser(userId) {
            if (confirm('Yakin nonaktifkan user ini?')) {
                fetch(`../api/user_action.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=deactivate&id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }

        function activateUser(userId) {
            if (confirm('Yakin aktifkan user ini?')) {
                fetch(`../api/user_action.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=activate&id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>