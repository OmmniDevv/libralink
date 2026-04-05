<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireRole('admin');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$response = ['success' => false, 'message' => ''];

if ($id === 0) {
    $response['message'] = 'ID tidak valid';
    echo json_encode($response);
    exit();
}

try {
    // Check if category is used
    $query_check = "SELECT COUNT(*) as count FROM books WHERE kategori_id = ?";
    $stmt = $conn->prepare($query_check);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        throw new Exception('Tidak dapat menghapus kategori yang memiliki buku');
    }

    // Delete category
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Kategori berhasil dihapus';
    } else {
        throw new Exception('Gagal menghapus kategori');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>