<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
checkTenant();

// Filter pembayaran
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// Ambil data kamar tenant
$stmt = $conn->prepare("SELECT * FROM rooms WHERE tenant_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$my_room = $result->fetch_assoc();
$stmt->close();

// Ambil data pembayaran tenant dengan filter
$query = "SELECT p.*, r.room_number, r.room_type, r.price as room_price 
          FROM payments p 
          JOIN rooms r ON p.room_id = r.id 
          WHERE p.tenant_id = ?";

// Tambahkan kondisi filter lainnya
if (!empty($filter_status)) {
    $query .= " AND p.status = ?";
}

if (!empty($filter_year)) {
    $query .= " AND YEAR(p.payment_date) = ?";
}

if (!empty($filter_month)) {
    $query .= " AND MONTH(p.payment_date) = ?";
}

$query .= " ORDER BY p.payment_date DESC, p.payment_month DESC";

$stmt = $conn->prepare($query);
$params = [$_SESSION['user_id']];
$types = 'i';

if (!empty($filter_status)) {
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_year)) {
    $params[] = $filter_year;
    $types .= 's';
}

if (!empty($filter_month)) {
    $params[] = $filter_month;
    $types .= 's';
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Statistik pembayaran
$stats_query = "SELECT 
    COUNT(*) as total_payments,
    SUM(CASE WHEN p.status = 'paid' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN p.status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
    COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) as total_paid,
    COALESCE(SUM(p.amount), 0) as total_amount
    FROM payments p 
    WHERE p.tenant_id = ? AND YEAR(p.payment_date) = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("is", $_SESSION['user_id'], $filter_year);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Cek pembayaran yang akan jatuh tempo (bulan depan)
$next_month = date('Y-m', strtotime('+1 month'));
$check_next_payment = $conn->prepare("SELECT * FROM payments WHERE tenant_id = ? AND payment_month = ?");
$check_next_payment->bind_param("is", $_SESSION['user_id'], $next_month);
$check_next_payment->execute();
$next_payment_exists = $check_next_payment->get_result()->num_rows > 0;
$check_next_payment->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran - Manajemen Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .payment-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .payment-card:hover {
            transform: translateY(-5px);
        }
        
        .status-badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        
        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .payment-item {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .payment-item.paid {
            border-left-color: #28a745;
        }
        
        .payment-item.pending {
            border-left-color: #ffc107;
        }
        
        .payment-item.overdue {
            border-left-color: #dc3545;
        }
        
        .payment-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .alert-reminder {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            border: none;
            border-radius: 15px;
        }

        .payment-card.bg-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
        }

        .payment-card.bg-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        }

        .payment-card.bg-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
            border: none;
            color: #333;
        }

        .navbar-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-modern">
        <div class="container">
            <a class="navbar-brand" href="#">Panel Penyewa</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link active" href="payments.php">
                        <i class="bi bi-wallet2"></i> Pembayaran
                    </a>
                    <span class="navbar-text mx-3 text-white">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['full_name']) ?>
                    </span>
                    <a class="nav-link" href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">
                    <i class="bi bi-wallet2"></i> Riwayat Pembayaran
                </h2>
            </div>
        </div>

        <?php if ($my_room): ?>
            <!-- Room Info -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card payment-card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title">
                                        <i class="bi bi-house-door"></i> 
                                        Kamar <?= htmlspecialchars($my_room['room_number']) ?>
                                    </h5>
                                    <p class="card-text">
                                        <span class="badge bg-primary"><?= htmlspecialchars($my_room['room_type']) ?></span>
                                        <span class="ms-2">Rp <?= number_format($my_room['price'], 0, ',', '.') ?>/bulan</span>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <?php if (!$next_payment_exists): ?>
                                        <div class="alert alert-reminder mb-0">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <strong>Reminder:</strong> Pembayaran bulan <?= date('F Y', strtotime('+1 month')) ?> belum ada
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card text-white">
                        <div class="card-body text-center">
                            <h3><?= $stats['total_payments'] ?></h3>
                            <p class="mb-0">Total Pembayaran</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card payment-card bg-success text-white">
                        <div class="card-body text-center">
                            <h3><?= $stats['paid_count'] ?></h3>
                            <p class="mb-0">Lunas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card payment-card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3><?= $stats['pending_count'] ?></h3>
                            <p class="mb-0">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card payment-card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3><?= $stats['overdue_count'] ?></h3>
                            <p class="mb-0">Terlambat</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card filter-card">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="filter_status" onchange="this.form.submit()">
                                        <option value="">Semua Status</option>
                                        <option value="paid" <?= $filter_status == 'paid' ? 'selected' : '' ?>>Lunas</option>
                                        <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="overdue" <?= $filter_status == 'overdue' ? 'selected' : '' ?>>Terlambat</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tahun</label>
                                    <select class="form-select" name="filter_year" onchange="this.form.submit()">
                                        <?php for($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                            <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bulan</label>
                                    <select class="form-select" name="filter_month" onchange="this.form.submit()">
                                        <option value="">Semua Bulan</option>
                                        <?php for($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= $filter_month == $m ? 'selected' : '' ?>>
                                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <a href="payments.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-clockwise"></i> Reset Filter
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card payment-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> 
                                Riwayat Pembayaran <?= $filter_year ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($payments)): ?>
                                <div class="row">
                                    <?php foreach ($payments as $payment): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card payment-item <?= $payment['status'] ?> h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title">
                                                            <?= date('F Y', strtotime($payment['payment_month'] . '-01')) ?>
                                                        </h6>
                                                        <span class="status-badge 
                                                            <?= $payment['status'] == 'paid' ? 'bg-success' : 
                                                                ($payment['status'] == 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                                            <?php if ($payment['status'] == 'paid'): ?>
                                                                <i class="bi bi-check-circle"></i> Lunas
                                                            <?php elseif ($payment['status'] == 'pending'): ?>
                                                                <i class="bi bi-clock"></i> Pending
                                                            <?php else: ?>
                                                                <i class="bi bi-exclamation-circle"></i> Terlambat
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <small class="text-muted">Jumlah:</small>
                                                            <p class="mb-1 fw-bold">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></p>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">Tanggal Bayar:</small>
                                                            <p class="mb-1"><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <small class="text-muted">Kamar:</small>
                                                            <p class="mb-0"><?= $payment['room_number'] ?> - <?= $payment['room_type'] ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Summary -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6>Ringkasan Tahun <?= $filter_year ?></h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1">
                                                            <strong>Total Dibayar:</strong> 
                                                            <span class="text-success">Rp <?= number_format($stats['total_paid'], 0, ',', '.') ?></span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1">
                                                            <strong>Total Tagihan:</strong> 
                                                            <span class="text-primary">Rp <?= number_format($stats['total_amount'], 0, ',', '.') ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <h5 class="mt-3 text-muted">Belum Ada Data Pembayaran</h5>
                                    <p class="text-muted">Tidak ada data pembayaran untuk filter yang dipilih</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Jika belum memiliki kamar -->
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> Anda belum memiliki kamar!</h5>
                        <p>Silakan hubungi admin untuk mendapatkan kamar sebelum melihat riwayat pembayaran.</p>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.payment-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Add click effect to payment items
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });
        });
        
        // Print functionality
        function printPayments() {
            window.print();
        }
        
        // Export functionality (placeholder)
        function exportPayments() {
            alert('Fitur export akan segera tersedia!');
        }
    </script>
</body>
</html>
