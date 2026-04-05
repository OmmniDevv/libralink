-- Database Perpustakaan Online
CREATE DATABASE IF NOT EXISTS perpustakaan_online;
USE perpustakaan_online;

-- Tabel Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    no_telepon VARCHAR(15),
    alamat TEXT,
    role ENUM('user', 'admin', 'petugas') NOT NULL DEFAULT 'user',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Books
CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    penulis VARCHAR(100) NOT NULL,
    penerbit VARCHAR(100),
    tahun_terbit YEAR,
    isbn VARCHAR(20),
    kategori_id INT NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    lokasi_rak VARCHAR(50),
    deskripsi TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX (kategori_id),
    INDEX (judul)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Transactions (Peminjaman)
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tanggal_pinjam DATETIME DEFAULT CURRENT_TIMESTAMP,
    tanggal_kembali_direncanakan DATE NOT NULL,
    tanggal_kembali_aktual DATETIME,
    status ENUM('dipinjam', 'dikembalikan', 'hilang', 'dibatalkan') NOT NULL DEFAULT 'dipinjam',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX (user_id),
    INDEX (status),
    INDEX (tanggal_pinjam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Transaction Details (Detail Buku per Peminjaman)
CREATE TABLE IF NOT EXISTS transaction_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    book_id INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT,
    INDEX (transaction_id),
    INDEX (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Denda
CREATE TABLE IF NOT EXISTS penalties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    hari_terlambat INT NOT NULL,
    nominal_denda DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status_pembayaran ENUM('belum_bayar', 'sudah_bayar') DEFAULT 'belum_bayar',
    tanggal_pembayaran DATETIME,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX (transaction_id),
    UNIQUE (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data dummy
INSERT INTO categories (nama_kategori, deskripsi) VALUES
('Fiksi', 'Buku cerita dan novel fiksi'),
('Non-Fiksi', 'Buku pengetahuan umum'),
('Sains', 'Buku tentang sains dan teknologi'),
('Sejarah', 'Buku sejarah dan biografi'),
('Anak-anak', 'Buku untuk anak-anak'),
('Komik', 'Komik dan manga');

INSERT INTO users (nama, email, password, no_telepon, alamat, role, status) VALUES
('Admin Master', 'admin@perpustakaan.com', '$2y$10$YJvWIGTq5W8b2t0N3z5X.e1wF3K8T9U2P5Q7R4S6T9U2P5Q7R4S6', '081234567890', 'Jl. Admin No. 1', 'admin', 'aktif'),
('Petugas Libra', 'petugas@perpustakaan.com', '$2y$10$YJvWIGTq5W8b2t0N3z5X.e1wF3K8T9U2P5Q7R4S6T9U2P5Q7R4S6', '081234567891', 'Jl. Petugas No. 2', 'petugas', 'aktif'),
('Budi Santoso', 'budi@gmail.com', '$2y$10$YJvWIGTq5W8b2t0N3z5X.e1wF3K8T9U2P5Q7R4S6T9U2P5Q7R4S6', '081234567892', 'Jl. Budi No. 3', 'user', 'aktif'),
('Siti Nurhaliza', 'siti@gmail.com', '$2y$10$YJvWIGTq5W8b2t0N3z5X.e1wF3K8T9U2P5Q7R4S6T9U2P5Q7R4S6', '081234567893', 'Jl. Siti No. 4', 'user', 'aktif');

-- Password: admin123 (hash dengan password_hash di PHP)

INSERT INTO books (judul, penulis, penerbit, tahun_terbit, isbn, kategori_id, stok, lokasi_rak, deskripsi) VALUES
('Laskar Pelangi', 'Andrea Hirata', 'Bentang', 2005, '979-989-0061-4', 1, 5, 'A1', 'Cerita inspiratif tentang anak-anak di Belitong'),
('Bumi', 'Tere Liye', 'Gramedia', 2014, '978-602-262-050-1', 1, 3, 'A2', 'Novel petualangan fiksi ilmiah'),
('Sapiens', 'Yuval Noah Harari', 'L.Alvarez', 2011, '978-971-23-6267-3', 2, 2, 'B1', 'Sejarah singkat umat manusia'),
('Educating the Other', 'Pramoedya Ananta Toer', 'Lentera Dipantara', 2003, '979-9029-90-4', 4, 2, 'B2', 'Koleksi esai dan artikel sejarah'),
('Doraemon', 'Fujiko F. Fujio', 'Shogakukan', 1969, '978-4-09-126401-3', 6, 8, 'C1', 'Komik petualangan robot kucing'),
('Naruto', 'Masashi Kishimoto', 'Jump Comics', 1999, '978-4-08-872370-0', 6, 6, 'C2', 'Komik ninja dan persahabatan'),
('Pengantar Fisika Modern', 'Kenneth S. Krane', 'Penerbit UI', 2012, '978-979-4561-98-6', 3, 4, 'D1', 'Buku fisika tingkat lanjut'),
('Sejarah Indonesia Singkat', 'Marwati Djoened Poesponegoro', 'Gramedia', 2010, '978-602-03-0186-9', 4, 3, 'D2', 'Ringkasan sejarah Indonesia');

-- Index tambahan untuk performa
CREATE INDEX idx_books_kategori ON books(kategori_id);
CREATE INDEX idx_transactions_user ON transactions(user_id);
CREATE INDEX idx_transaction_details_trans ON transaction_details(transaction_id);
CREATE INDEX idx_penalties_trans ON penalties(transaction_id);