<?php
// Konfigurasi Database
class Database {
    private $host = 'localhost';
    private $db_name = 'perpustakaan_online';
    private $user = 'root';
    private $pass = '';
    private $conn;

    public function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db_name);

        // Check connection
        if ($this->conn->connect_error) {
            die('Connection Error: ' . $this->conn->connect_error);
        }

        // Set charset
        $this->conn->set_charset('utf8mb4');

        return $this->conn;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>