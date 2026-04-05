<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Helper.php';

startSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header('Location: /perpustakaan_online/admin/dashboard.php');
    } elseif ($user['role'] === 'petugas') {
        header('Location: /perpustakaan_online/petugas/dashboard.php');
    } else {
        header('Location: /perpustakaan_online/user/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $db = new Database();
        $conn = $db->connect();

        $query = "SELECT id, nama, email, password, role, status FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] === 'nonaktif') {
                $error = 'Akun Anda telah dinonaktifkan!';
            } elseif (password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: /perpustakaan_online/admin/dashboard.php');
                } elseif ($user['role'] === 'petugas') {
                    header('Location: /perpustakaan_online/petugas/dashboard.php');
                } else {
                    header('Location: /perpustakaan_online/user/dashboard.php');
                }
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Email tidak ditemukan!';
        }

        $stmt->close();
        $db->closeConnection();
    }
}

$page_title = 'Login';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-lg" style="width: 100%; max-width: 400px;">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="display-6">📚</h1>
                <h3 class="card-title">Perpustakaan Online</h3>
                <p class="text-muted">Masuk ke akun Anda</p>
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

            <form method="POST" action="login.php" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback">
                        Masukkan email yang valid
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">
                        Password harus diisi
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
            </form>

            <hr>

            <p class="text-center text-muted mb-0">
                Belum punya akun? <a href="/perpustakaan_online/register.php">Daftar di sini</a>
            </p>

            <div class="mt-4 p-3 bg-light rounded">
                <h6 class="fw-bold mb-2">Demo Akun:</h6>
                <small class="d-block"><strong>User:</strong> budi@gmail.com</small>
                <small class="d-block"><strong>Admin:</strong> admin@perpustakaan.com</small>
                <small class="d-block"><strong>Petugas:</strong> petugas@perpustakaan.com</small>
                <small class="text-muted d-block mt-2"><strong>Password:</strong> admin123</small>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>