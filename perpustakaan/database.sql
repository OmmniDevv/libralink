-- Database Perpustakaan Online
-- Created for Laragon MySQL

-- Create Database
CREATE DATABASE IF NOT EXISTS perpustakaan_db;
USE perpustakaan_db;

-- Table: Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: Books
CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(200) NOT NULL,
    penulis VARCHAR(100) NOT NULL,
    penerbit VARCHAR(100) NOT NULL,
    tahun_terbit YEAR,
    kategori_id INT NOT NULL,
    stok INT DEFAULT 0,
    stok_tersedia INT DEFAULT 0,
    deskripsi TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_kategori (kategori_id),
    INDEX idx_judul (judul)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: Users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'petugas') DEFAULT 'user',
    no_anggota VARCHAR(20) UNIQUE,
    no_telp VARCHAR(15),
    alamat TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: Transactions
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali_rencana DATE NOT NULL,
    tanggal_kembali_aktual DATE,
    status ENUM('pending', 'approved', 'returned', 'overdue') DEFAULT 'pending',
    total_buku INT DEFAULT 0,
    denda DECIMAL(10, 2) DEFAULT 0,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_tanggal (tanggal_pinjam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: Transaction Details
CREATE TABLE IF NOT EXISTS transaction_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    book_id INT NOT NULL,
    jumlah INT DEFAULT 1,
    status_pengembalian ENUM('belum_dikembalikan', 'dikembalikan') DEFAULT 'belum_dikembalikan',
    tanggal_pengembalian DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_book (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: Fines (optional)
CREATE TABLE IF NOT EXISTS fines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    user_id INT NOT NULL,
    jumlah_denda DECIMAL(10, 2) NOT NULL,
    status_pembayaran ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    tanggal_denda DATE NOT NULL,
    tanggal_pembayaran DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status_pembayaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Categories
INSERT INTO categories (nama_kategori, deskripsi) VALUES
('Fiksi', 'Buku cerita fiksi'),
('Non-Fiksi', 'Buku non-fiksi atau ilmu pengetahuan'),
('Sejarah', 'Buku tentang sejarah'),
('Biografi', 'Buku biografi tokoh'),
('Referensi', 'Buku referensi dan kamus'),
('Anak-anak', 'Buku untuk anak-anak'),
('Teknologi', 'Buku tentang teknologi dan programming'),
('Bisnis', 'Buku tentang bisnis dan ekonomi')
ON DUPLICATE KEY UPDATE nama_kategori = VALUES(nama_kategori);

-- Insert Default Admin Account
INSERT INTO users (nama, email, password, role, no_anggota, status) VALUES
('Admin Perpustakaan', 'admin@perpustakaan.com', '$2y$10$9HQJhHDf4S7S7H8JJQ8wdOOLnRa9YsQJQJ5Q5Q5Q5Q5Q5Q5Q5Q5Q', 'admin', 'ADM001', 'active'),
('Petugas Perpustakaan', 'petugas@perpustakaan.com', '$2y$10$9HQJhHDf4S7S7H8JJQ8wdOOLnRa9YsQJQJ5Q5Q5Q5Q5Q5Q5Q5Q', 'petugas', 'PTG001', 'active')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

-- Insert Sample Books
INSERT INTO books (judul, penulis, penerbit, tahun_terbit, kategori_id, stok, stok_tersedia, deskripsi) VALUES
('Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 1, 5, 5, 'Novel tentang kehidupan anak-anak di sebuah sekolah di kepulauan'),
('Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia Pustaka Utama', 2009, 1, 4, 4, 'Novel tentang persahabatan dan mimpi di pesantren'),
('Sapiens', 'Yuval Noah Harari', 'Harvill Secker', 2014, 2, 3, 3, 'Sejarah singkat umat manusia'),
('Steve Jobs', 'Walter Isaacson', 'Simon & Schuster', 2011, 4, 2, 2, 'Biografi Steve Jobs, pendiri Apple'),
('Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 7, 4, 4, 'Panduan menulis kode yang bersih dan profesional'),
('Thinking, Fast and Slow', 'Daniel Kahneman', 'Farrar, Straus and Giroux', 2011, 2, 3, 3, 'Tentang bagaimana manusia berpikir'),
('The Hobbit', 'J.R.R. Tolkien', 'Allen & Unwin', 1937, 1, 2, 2, 'Petualangan Bilbo Baggins di Tengah Bumi'),
('Refactoring', 'Martin Fowler', 'Addison-Wesley', 2018, 7, 2, 2, 'Teknik-teknik refactoring untuk kode yang lebih baik')
ON DUPLICATE KEY UPDATE stok = VALUES(stok);

-- Create Views for easier queries

-- View: User with transaction count
CREATE OR REPLACE VIEW user_summary AS
SELECT 
    u.id,
    u.nama,
    u.email,
    u.role,
    COUNT(t.id) as total_peminjaman
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id, u.nama, u.email, u.role;

-- View: Book statistics
CREATE OR REPLACE VIEW book_statistics AS
SELECT 
    b.id,
    b.judul,
    b.stok,
    b.stok_tersedia,
    (b.stok - b.stok_tersedia) as sedang_dipinjam,
    COUNT(t.id) as total_peminjaman
FROM books b
LEFT JOIN transaction_details td ON b.id = td.book_id
LEFT JOIN transactions t ON td.transaction_id = t.id
GROUP BY b.id, b.judul, b.stok, b.stok_tersedia;

COMMIT;