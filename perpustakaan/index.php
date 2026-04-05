<?php
session_start();
require_once 'config/database.php';
require_once 'config/functions.php';

// Jika sudah login, redirect ke dashboard masing-masing
if (isLoggedIn()) {
    if (checkRole('admin')) {
        redirect('admin/dashboard.php');
    } elseif (checkRole('petugas')) {
        redirect('petugas/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'login';
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Online</title>
    <link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            width: 100%;
            max-width: 450px;
        }
        .auth-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-header h2 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background-color: #667eea;
            border: none;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #764ba2;
        }
        .toggle-form {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .toggle-form a {
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .toggle-form a:hover {
            text-decoration: underline;
        }
        .alert {
            margin-bottom: 20px;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ddd;
        }
        .divider span {
            padding: 0 10px;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- Login Form -->
        <div id="loginForm" class="auth-card">
            <div class="auth-header">
                <h2>Perpustakaan Online</h2>
                <p class="text-muted">Masuk ke akun Anda</p>
            </div>

            <?php if ($msg === 'login_required'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    Anda harus login terlebih dahulu
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($msg === 'access_denied'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Akses ditolak. Anda tidak memiliki akses ke halaman ini
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($msg === 'logout_success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Anda berhasil logout
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form id="loginFormElement" method="POST" action="api/auth.php">
                <div class="mb-3">
                    <label for="login_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="login_email" name="email" required>
                    <small class="form-text text-muted">Demo: admin@perpustakaan.com atau petugas@perpustakaan.com</small>
                </div>

                <div class="mb-3">
                    <label for="login_password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="login_password" name="password" required>
                    <small class="form-text text-muted">Default password: password123</small>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>

                <div class="divider">
                    <span>atau</span>
                </div>

                <button type="button" class="btn btn-outline-primary w-100" onclick="toggleForms()">
                    Buat Akun Baru
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="registerForm" class="auth-card d-none">
            <div class="auth-header">
                <h2>Perpustakaan Online</h2>
                <p class="text-muted">Daftar akun baru</p>
            </div>

            <form id="registerFormElement" method="POST" action="api/register.php">
                <div class="mb-3">
                    <label for="register_nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="register_nama" name="nama" required>
                </div>

                <div class="mb-3">
                    <label for="register_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="register_email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="register_no_telp" class="form-label">Nomor Telepon</label>
                    <input type="tel" class="form-control" id="register_no_telp" name="no_telp" required>
                </div>

                <div class="mb-3">
                    <label for="register_alamat" class="form-label">Alamat</label>
                    <textarea class="form-control" id="register_alamat" name="alamat" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="register_password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="register_password" name="password" required minlength="6">
                    <small class="form-text text-muted">Minimal 6 karakter</small>
                </div>

                <div class="mb-3">
                    <label for="register_password_confirm" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="register_password_confirm" name="password_confirm" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary w-100">Daftar</button>

                <div class="divider">
                    <span>atau</span>
                </div>

                <button type="button" class="btn btn-outline-primary w-100" onclick="toggleForms()">
                    Kembali ke Login
                </button>
            </form>
        </div>
    </div>

    <script src="assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForms() {
            document.getElementById('loginForm').classList.toggle('d-none');
            document.getElementById('registerForm').classList.toggle('d-none');
        }

        // Form validation
        document.getElementById('loginFormElement').addEventListener('submit', function(e) {
            const email = document.getElementById('login_email').value.trim();
            const password = document.getElementById('login_password').value.trim();

            if (!email || !password) {
                e.preventDefault();
                alert('Semua field harus diisi');
            }
        });

        document.getElementById('registerFormElement').addEventListener('submit', function(e) {
            const nama = document.getElementById('register_nama').value.trim();
            const email = document.getElementById('register_email').value.trim();
            const no_telp = document.getElementById('register_no_telp').value.trim();
            const alamat = document.getElementById('register_alamat').value.trim();
            const password = document.getElementById('register_password').value;
            const password_confirm = document.getElementById('register_password_confirm').value;

            if (!nama || !email || !no_telp || !alamat || !password || !password_confirm) {
                e.preventDefault();
                alert('Semua field harus diisi');
                return;
            }

            if (password !== password_confirm) {
                e.preventDefault();
                alert('Password tidak cocok');
                return;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter');
                return;
            }
        });
    </script>
</body>
</html>