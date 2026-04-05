<?php
/**
 * Helper Functions
 */

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check user role
function checkRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../../index.php?msg=login_required');
    }
}

// Require specific role
function requireRole($role) {
    if (!isLoggedIn()) {
        redirect('../../index.php?msg=login_required');
    }
    
    if ($_SESSION['role'] !== $role) {
        redirect('../../index.php?msg=access_denied');
    }
}

// Get current user role
function getCurrentRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize input
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate alert HTML
function showAlert($type, $message) {
    $alertClass = 'alert-' . $type;
    return "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
        $message
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}

// Format date
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

// Get days difference
function getDaysDifference($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

// Calculate fine
function calculateFine($return_date, $due_date, $daily_fine = 5000) {
    $return = new DateTime($return_date);
    $due = new DateTime($due_date);
    
    if ($return <= $due) {
        return 0;
    }
    
    $interval = $return->diff($due);
    $days_late = $interval->days;
    
    return $days_late * $daily_fine;
}

// Get status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Menunggu</span>',
        'approved' => '<span class="badge bg-info">Disetujui</span>',
        'returned' => '<span class="badge bg-success">Dikembalikan</span>',
        'overdue' => '<span class="badge bg-danger">Terlambat</span>',
        'active' => '<span class="badge bg-success">Aktif</span>',
        'inactive' => '<span class="badge bg-secondary">Tidak Aktif</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

// Pagination
function getPagination($total, $per_page) {
    $pages = ceil($total / $per_page);
    return $pages;
}

// Get offset for pagination
function getOffset($page, $per_page) {
    return ($page - 1) * $per_page;
}

// Truncate text
function truncateText($text, $length = 100) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>