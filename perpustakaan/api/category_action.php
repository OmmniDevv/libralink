<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireRole('admin');

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = ['success' => false, 'message' => ''];

try {
    if ($action === 'add') {
        $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if (empty($nama_kategori)) {
            throw new Exception('Nama kategori tidak boleh kosong');
        }

        $query = "INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $nama_kategori, $deskripsi);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Kategori berhasil ditambahkan';
        } else {
            throw new Exception('Gagal menambahkan kategori');
        }

    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if ($id === 0 || empty($nama_kategori)) {
            throw new Exception('Data tidak lengkap');
        }

        $query = "UPDATE categories SET nama_kategori = ?, deskripsi = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssi', $nama_kategori, $deskripsi, $id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Kategori berhasil diperbarui';
        } else {
            throw new Exception('Gagal memperbarui kategori');
        }
    } else {
        throw new Exception('Aksi tidak dikenal');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>