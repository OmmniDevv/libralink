<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

requireLogin();
requireRole('admin');

// Get statistics
// Total users
$query_users = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$users_result = $conn->query($query_users);
$total_users = $users_result->fetch_assoc()['total'];

// Total books
$query_books = "SELECT COUNT(*) as total FROM books";
$books_result = $conn->query($query_books);
$total_books = $books_result->fetch_assoc()['total'];

// Total transactions
$query_transactions = "SELECT COUNT(*) as total FROM transactions";
$transactions_result = $conn->query($query_transactions);
$total_transactions = $transactions_result->fetch_assoc()['total'];

// Total overdue
$query_overdue = "SELECT COUNT(*) as total FROM transactions WHERE status = 'overdue'";
$overdue_result = $conn->query($query_overdue);
$total_overdue = $overdue_result->fetch_assoc()['total'];

// Recent transactions
$query_recent = "SELECT t.id, u.nama, COUNT(td.id) as total_buku, t.tanggal_pinjam, t.status
                 FROM transactions t
                 JOIN users u ON t.user_id = u.id
                 LEFT JOIN transaction_details td ON t.id = td.transaction_id
                 GROUP BY t.id
                 ORDER BY t.created_at DESC
                 LIMIT 5";
$recent_result = $conn->query($query_recent);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan Online</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <?php require_once '../includes/sidebar.php'; ?>

        <div class="main-content flex-grow-1">
            <h2 class="fw-bold mb-4">Dashboard Admin</h2>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-0 small">Total User</p>
                                    <h3 class="fw-bold mb-0"><?php echo $total_users; ?></h3>
                                </div>
                                <div class="display-6 text-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-0 small">Total Buku</p>
                                    <h3 class="fw-bold mb-0"><?php echo $total_books; ?></h3>
                                </div>
                                <div class="display-6 text-success">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-0 small">Total Transaksi</p>
                                    <h3 class="fw-bold mb-0"><?php echo $total_transactions; ?></h3>
                                </div>
                                <div class="display-6 text-info">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-0 small">Terlambat</p>
                                    <h3 class="fw-bold mb-0"><?php echo $total_overdue; ?></h3>
                                </div>
                                <div class="display-6 text-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 fw-bold">Transaksi Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Member</th>
                                                <th>Total Buku</th>
                                                <th>Tanggal Pinjam</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            while ($row = $recent_result->fetch_assoc()): 
                                            ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                                    <td><?php echo $row['total_buku']; ?></td>
                                                    <td><?php echo formatDate($row['tanggal_pinjam']); ?></td>
                                                    <td><?php echo getStatusBadge($row['status']); ?></td>
                                                    <td>
                                                        <a href="transactions.php?id=<?php echo $row['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>