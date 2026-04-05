<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?msg=error&error=Invalid request method');
    exit();
}

$nama = sanitize($_POST['nama'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$no_telp = sanitize($_POST['no_telp'] ?? '');
$alamat = sanitize($_POST['alamat'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Validation
if (empty($nama) || empty($email) || empty($no_telp) || empty($alamat) || empty($password)) {
    header('Location: ../index.php?page=register&msg=error&error=Semua field harus diisi');
    exit();
}

if ($password !== $password_confirm) {
    header('Location: ../index.php?page=register&msg=error&error=Password tidak cocok');
    exit();
}

if (strlen($password) < 6) {
    header('Location: ../index.php?page=register&msg=error&error=Password minimal 6 karakter');
    exit();
}

if (!validateEmail($email)) {
    header('Location: ../index.php?page=register&msg=error&error=Email tidak valid');
    exit();
}

// Check if email exists
$query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header('Location: ../index.php?page=register&msg=error&error=Email sudah terdaftar');
    exit();
}

// Hash password
$password_hash = hashPassword($password);

// Generate nomor anggota
$no_anggota = 'MBR' . date('Y') . sprintf('%05d', mt_rand(1, 99999));

// Insert user
$query = "INSERT INTO users (nama, email, password, role, no_anggota, no_telp, alamat, status) 
          VALUES (?, ?, ?, 'user', ?, ?, ?, 'active')";
$stmt = $conn->prepare($query);
$stmt->bind_param('ssssss', $nama, $email, $password_hash, $no_anggota, $no_telp, $alamat);

if ($stmt->execute()) {
    header('Location: ../index.php?msg=register_success&error=Registrasi berhasil, silakan login');
    exit();
} else {
    header('Location: ../index.php?page=register&msg=error&error=Gagal membuat akun');
    exit();
}

$stmt->close();
?>