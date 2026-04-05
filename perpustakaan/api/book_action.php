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
        $judul = sanitize($_POST['judul'] ?? '');
        $penulis = sanitize($_POST['penulis'] ?? '');
        $penerbit = sanitize($_POST['penerbit'] ?? '');
        $tahun_terbit = intval($_POST['tahun_terbit'] ?? 0);
        $kategori_id = intval($_POST['kategori_id'] ?? 0);
        $stok = intval($_POST['stok'] ?? 0);
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        // Validation
        if (empty($judul) || empty($penulis) || empty($penerbit) || $kategori_id === 0) {
            throw new Exception('Data tidak lengkap');
        }

        $query = "INSERT INTO books (judul, penulis, penerbit, tahun_terbit, kategori_id, stok, stok_tersedia, deskripsi)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssiiiss', $judul, $penulis, $penerbit, $tahun_terbit, $kategori_id, $stok, $stok, $deskripsi);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Buku berhasil ditambahkan';
        } else {
            throw new Exception('Gagal menambahkan buku');
        }

    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $judul = sanitize($_POST['judul'] ?? '');
        $penulis = sanitize($_POST['penulis'] ?? '');
        $penerbit = sanitize($_POST['penerbit'] ?? '');
        $tahun_terbit = intval($_POST['tahun_terbit'] ?? 0);
        $kategori_id = intval($_POST['kategori_id'] ?? 0);
        $stok = intval($_POST['stok'] ?? 0);
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if ($id === 0 || empty($judul) || empty($penulis) || empty($penerbit) || $kategori_id === 0) {
            throw new Exception('Data tidak lengkap');
        }

        // Get current stok_tersedia
        $query_get = "SELECT stok_tersedia FROM books WHERE id = ?";
        $stmt = $conn->prepare($query_get);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            throw new Exception('Buku tidak ditemukan');
        }

        $current_stok_tersedia = $result['stok_tersedia'];
        $sedang_dipinjam = $result['stok'] - $current_stok_tersedia;
        $new_stok_tersedia = $stok - $sedang_dipinjam;

        $query = "UPDATE books SET judul = ?, penulis = ?, penerbit = ?, tahun_terbit = ?, 
                  kategori_id = ?, stok = ?, stok_tersedia = ?, deskripsi = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssiiissi', $judul, $penulis, $penerbit, $tahun_terbit, 
                         $kategori_id, $stok, $new_stok_tersedia, $deskripsi, $id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Buku berhasil diperbarui';
        } else {
            throw new Exception('Gagal memperbarui buku');
        }
    } else {
        throw new Exception('Aksi tidak dikenal');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>