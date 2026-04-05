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
    // Check if book is currently borrowed
    $query_check = "SELECT COUNT(*) as count FROM transaction_details td
                    JOIN transactions t ON td.transaction_id = t.id
                    WHERE td.book_id = ? AND t.status IN ('approved', 'overdue')";
    $stmt = $conn->prepare($query_check);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        throw new Exception('Tidak dapat menghapus buku yang sedang dipinjam');
    }

    // Delete book
    $query = "DELETE FROM books WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Buku berhasil dihapus';
    } else {
        throw new Exception('Gagal menghapus buku');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>