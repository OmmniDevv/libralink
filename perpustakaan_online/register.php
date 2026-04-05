<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Helper.php';

startSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    header('Location: /perpustakaan_online/user/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');

    // Validasi
    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Nama, email, dan password harus diisi!';
    } elseif (!validateEmail($email)) {
        $error = 'Format email tidak valid!';
    } elseif (!validatePassword($password)) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $password_confirm) {
        $error = 'Password dan konfirmasi password tidak sesuai!';
    } else {
        $db = new Database();
        $conn = $db->connect();

        // Cek apakah email sudah terdaftar
        $query_check = "SELECT id FROM users WHERE email = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param('s', $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert user baru
            $query_insert = "INSERT INTO users (nama, email, password, no_telepon, alamat, role, status) 
                           VALUES (?, ?, ?, ?, ?, 'user', 'aktif')";
            $stmt_insert = $conn->prepare($query_insert);
            $stmt_insert->bind_param('sssss', $nama, $email, $hashed_password, $no_telepon, $alamat);

            if ($stmt_insert->execute()) {
                $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                // Redirect ke login setelah 2 detik
                header('Refresh: 2; url=/perpustakaan_online/login.php');
            } else {
                $error = 'Terjadi kesalahan saat registrasi: ' . $conn->error;
            }

            $stmt_insert->close();
        }

        $stmt_check->close();
        $db->closeConnection();
    }
}

$page_title = 'Registrasi';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
    <div class="card shadow-lg" style="width: 100%; max-width: 500px;">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="display-6">📚</h1>
                <h3 class="card-title">Perpustakaan Online</h3>
                <p class="text-muted">Buat akun baru</p>
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

            <form method="POST" action="register.php" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" required>
                    <div class="invalid-feedback">
                        Nama harus diisi
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback">
                        Masukkan email yang valid
                    </div>
                </div>

                <div class="mb-3">
                    <label for="no_telepon" class="form-label">No. Telepon</label>
                    <input type="tel" class="form-control" id="no_telepon" name="no_telepon">
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <small class="form-text text-muted">Minimal 6 karakter</small>
                    <div class="invalid-feedback">
                        Password harus diisi
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    <div class="invalid-feedback">
                        Konfirmasi password harus diisi
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Daftar</button>
            </form>

            <hr>

            <p class="text-center text-muted mb-0">
                Sudah punya akun? <a href="/perpustakaan_online/login.php">Login di sini</a>
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>