<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
checkAdmin();

// Statistik untuk dashboard
$total_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$occupied_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status='occupied'")->fetch_assoc()['count'];
$total_tenants = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='tenant'")->fetch_assoc()['count'];
$pending_payments = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status='pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Manajemen Kos</title>
    <link rel="stylesheet" href="../assets/css/d_admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/images/logo.jpg" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
            <span>Kozan</span>
        </div>
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="rooms.php"><i class="fas fa-door-open"></i> Kelola Kamar</a>
        <a href="tenants.php"><i class="fas fa-users"></i> Data Penyewa</a>
        <a href="payments.php"><i class="fas fa-money-bill-wave"></i> Pembayaran</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="header d-flex justify-content-between align-items-center">
            <h2>Dashboard Admin</h2>
            <div class="welcome">Hi <?= $_SESSION['full_name'] ?></div>
        </div>

        <!-- Statistik Cards -->
        <div class="card-dashboard mt-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Total Kamar</h6>
                        <h3><?= $total_rooms ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Kamar Terisi</h6>
                        <h3><?= $occupied_rooms ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Total Penyewa</h6>
                        <h3><?= $total_tenants ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h6>Pembayaran Pending</h6>
                        <h3><?= $pending_payments ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card-dashboard mt-4">
            <h5 class="fw-bold mb-4" style="color: #2a69ac;">Menu Utama</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="rooms.php" class="quick-action">
                        <i class="fas fa-door-open"></i>
                        <div>Kelola Data Kamar</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="tenants.php" class="quick-action">
                        <i class="fas fa-users"></i>
                        <div>Kelola Data Penyewa</div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="payments.php" class="quick-action">
                        <i class="fas fa-money-bill-wave"></i>
                        <div>Kelola Pembayaran</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>