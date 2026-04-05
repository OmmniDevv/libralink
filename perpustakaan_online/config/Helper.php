<?php
// Helper Functions

// Session Management
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

function getCurrentUser() {
    startSession();
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'nama' => $_SESSION['user_nama'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }
    return null;
}

function logout() {
    startSession();
    session_destroy();
    header('Location: /perpustakaan_online/');
    exit;
}

// Role-based Access Control
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /perpustakaan_online/');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    $current_role = $_SESSION['user_role'];
    if (!in_array($current_role, (array)$roles)) {
        header('Location: /perpustakaan_online/');
        exit;
    }
}

// Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // Password minimal 6 karakter
    return strlen($password) >= 6;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Response Functions
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Denda Calculation
function hitungDenda($tanggal_kembali_direncanakan) {
    $denda_per_hari = 5000; // Rp 5000 per hari
    $today = new DateTime();
    $planned_return = new DateTime($tanggal_kembali_direncanakan);
    
    if ($today > $planned_return) {
        $diff = $today->diff($planned_return);
        $hari_terlambat = $diff->days;
        return $hari_terlambat * $denda_per_hari;
    }
    return 0;
}

// Format Rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Format Date
function formatDate($date) {
    $months = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    
    $date_obj = new DateTime($date);
    $formatted = $date_obj->format('d F Y');
    
    foreach ($months as $en => $id) {
        $formatted = str_replace($en, $id, $formatted);
    }
    
    return $formatted;
}

function formatDateTime($datetime) {
    $date_obj = new DateTime($datetime);
    return $date_obj->format('d-m-Y H:i');
}

// Get Status Badge
function getStatusBadge($status) {
    $badges = [
        'dipinjam' => '<span class="badge bg-info">Dipinjam</span>',
        'dikembalikan' => '<span class="badge bg-success">Dikembalikan</span>',
        'hilang' => '<span class="badge bg-danger">Hilang</span>',
        'dibatalkan' => '<span class="badge bg-secondary">Dibatalkan</span>',
        'belum_bayar' => '<span class="badge bg-warning">Belum Bayar</span>',
        'sudah_bayar' => '<span class="badge bg-success">Sudah Bayar</span>',
        'aktif' => '<span class="badge bg-success">Aktif</span>',
        'nonaktif' => '<span class="badge bg-danger">Non-aktif</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

// Get Role Badge
function getRoleBadge($role) {
    $badges = [
        'user' => '<span class="badge bg-primary">User</span>',
        'admin' => '<span class="badge bg-danger">Admin</span>',
        'petugas' => '<span class="badge bg-warning">Petugas</span>'
    ];
    
    return $badges[$role] ?? '<span class="badge bg-secondary">' . $role . '</span>';
}
?>