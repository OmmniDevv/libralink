<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('user');

$user_id = getCurrentUserId();
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama'] ?? '');
    $no_telp = sanitize($_POST['no_telp'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $errors = [];

    if (empty($nama)) $errors[] = 'Nama tidak boleh kosong';
    if (empty($no_telp)) $errors[] = 'Nomor telepon tidak boleh kosong';
    if (empty($alamat)) $errors[] = 'Alamat tidak boleh kosong';

    if ($password_baru && $password_baru !== $password_confirm) {
        $errors[] = 'Password tidak cocok';
    }

    if (empty($errors)) {
        if ($password_baru) {
            $password_hash = hashPassword($password_baru);
            $query_update = "UPDATE users SET nama = ?, no_telp = ?, alamat = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($query_update);
            $stmt->bind_param('ssssi', $nama, $no_telp, $alamat, $password_hash, $user_id);
        } else {
            $query_update = "UPDATE users SET nama = ?, no_telp = ?, alamat = ? WHERE id = ?";
            $stmt = $conn->prepare($query_update);
            $stmt->bind_param('sssi', $nama, $no_telp, $alamat, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['nama'] = $nama;
            header('Location: profile.php?msg=success&success=Profil berhasil diperbarui');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <a href="dashboard.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Edit Profil</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $_GET['success']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email (tidak bisa diubah)</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nomor Anggota</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['no_anggota']); ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" required
                                       value="<?php echo htmlspecialchars($user['nama']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" name="no_telp" required
                                       value="<?php echo htmlspecialchars($user['no_telp']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="alamat" rows="3" required><?php echo htmlspecialchars($user['alamat']); ?></textarea>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3">
                                <label class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                                <input type="password" class="form-control" name="password_baru">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" name="password_confirm">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>