<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireRole('admin');

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$response = ['success' => false, 'message' => ''];

if ($id === 0) {
    $response['message'] = 'ID tidak valid';
    echo json_encode($response);
    exit();
}

try {
    if ($action === 'activate') {
        $status = 'active';
    } elseif ($action === 'deactivate') {
        $status = 'inactive';
    } else {
        throw new Exception('Aksi tidak dikenal');
    }

    $query = "UPDATE users SET status = ? WHERE id = ? AND role = 'user'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $status, $id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Status user berhasil diubah';
    } else {
        throw new Exception('Gagal mengubah status user');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>