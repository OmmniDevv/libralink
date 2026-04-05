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

$query = "SELECT id, judul, penulis, penerbit, tahun_terbit, kategori_id, stok, deskripsi FROM books WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response['success'] = true;
    $response['data'] = $result->fetch_assoc();
} else {
    $response['message'] = 'Buku tidak ditemukan';
}

echo json_encode($response);
?>