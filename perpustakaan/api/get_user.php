<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireRole('admin');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$response = ['success' => false, 'data' => null, 'message' => ''];

if ($id === 0) {
    $response['message'] = 'ID tidak valid';
    echo json_encode($response);
    exit();
}

$query = "SELECT id, nama, email, no_anggota, no_telp, alamat, status, created_at FROM users WHERE id = ? AND role = 'user'";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response['success'] = true;
    $response['data'] = $result->fetch_assoc();
} else {
    $response['message'] = 'User tidak ditemukan';
}

echo json_encode($response);
?>