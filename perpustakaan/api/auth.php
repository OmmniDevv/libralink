<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

$action = isset($_POST['action']) ? $_POST['action'] : 'login';
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response['message'] = 'Email dan password harus diisi';
            header('Location: ../index.php?msg=error&error=' . urlencode($response['message']));
            exit();
        }

        // Check user exists
        $query = "SELECT id, nama, email, password, role, status FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header('Location: ../index.php?msg=error&error=Email tidak ditemukan');
            exit();
        }

        $user = $result->fetch_assoc();

        // Check status
        if ($user['status'] !== 'active') {
            header('Location: ../index.php?msg=error&error=Akun Anda tidak aktif');
            exit();
        }

        // Verify password
        if (!verifyPassword($password, $user['password'])) {
            header('Location: ../index.php?msg=error&error=Password salah');
            exit();
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Redirect ke dashboard sesuai role
        if ($user['role'] === 'admin') {
            redirect('../admin/dashboard.php');
        } elseif ($user['role'] === 'petugas') {
            redirect('../petugas/dashboard.php');
        } else {
            redirect('../user/dashboard.php');
        }

        $stmt->close();
    }
} else {
    header('Location: ../index.php?msg=error&error=Invalid request method');
    exit();
}
?>