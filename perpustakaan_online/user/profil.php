<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Helper.php';

requireRole('user');
$current_user = getCurrentUser();

$db = new Database();
$conn = $db->connect();

$error = '';
$success = '';

// Get user profile
$query = "SELECT id, nama, email, no_telepon, alamat, status FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$user_profile = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama = sanitize($_POST['nama'] ?? '');
        $no_telepon = sanitize($_POST['no_telepon'] ?? '');
        $alamat = sanitize($_POST['alamat'] ?? '');

        if (empty($nama)) {
            $error = 'Nama harus diisi!';
        } else {
            $query_update = "UPDATE users SET nama = ?, no_telepon = ?, alamat = ? WHERE id = ?";
            $stmt = $conn->prepare($query_update);
            $stmt->bind_param('sssi', $nama, $no_telepon, $alamat, $current_user['id']);

            if ($stmt->execute()) {
                $_SESSION['user_nama'] = $nama;
                $user_profile['nama'] = $nama;
                $user_profile['no_telepon'] = $no_telepon;
                $user_profile['alamat'] = $alamat;
                $success = 'Profil berhasil diperbarui!';
            } else {
                $error = 'Gagal memperbarui profil: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Semua field password harus diisi!';
        } elseif (!validatePassword($new_password)) {
            $error = 'Password baru minimal 6 karakter!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Password baru dan konfirmasi tidak sesuai!';
        } else {
            // Verify current password
            $query_pwd = "SELECT password FROM users WHERE id = ?";
            $stmt_pwd = $conn->prepare($query_pwd);
            $stmt_pwd->bind_param('i', $current_user['id']);
            $stmt_pwd->execute();
            $result_pwd = $stmt_pwd->get_result();
            $user_pwd = $result_pwd->fetch_assoc();
            $stmt_pwd->close();

            if (!password_verify($current_password, $user_pwd['password'])) {
                $error = 'Password saat ini salah!';
            } else {
                $new_hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $query_update_pwd = "UPDATE users SET password = ? WHERE id = ?";
                $stmt_update_pwd = $conn->prepare($query_update_pwd);
                $stmt_update_pwd->bind_param('si', $new_hashed, $current_user['id']);

                if ($stmt_update_pwd->execute()) {
                    $success = 'Password berhasil diubah!';
                } else {
                    $error = 'Gagal mengubah password!';
                }
                $stmt_update_pwd->close();
            }
        }
    }
}

$db->closeConnection();

$page_title = 'Profil Saya';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">Profil Saya</h1>
            <p class="text-muted">Kelola informasi profil dan keamanan akun Anda</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Update Profil -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Informasi Profil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="profil.php" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?php echo sanitize($user_profile['nama']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo sanitize($user_profile['email']); ?>" disabled>
                            <small class="text-muted">Email tidak dapat diubah</small>
                        </div>

                        <div class="mb-3">
                            <label for="no_telepon" class="form-label">No. Telepon</label>
                            <input type="tel" class="form-control" id="no_telepon" name="no_telepon" value="<?php echo sanitize($user_profile['no_telepon'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="4"><?php echo sanitize($user_profile['alamat'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Ubah Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="profil.php" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Ubah Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Account Status -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold">Status Akun</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Status:</strong><br>
                        <?php echo getStatusBadge($user_profile['status']); ?>
                    </p>
                    <p class="mb-2">
                        <strong>Role:</strong><br>
                        <?php echo getRoleBadge('user'); ?>
                    </p>
                    <p class="text-muted small">
                        Akun Anda aktif dan dapat menggunakan semua fitur perpustakaan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>