<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/validation.php';
checkTenant();

// UPDATE Profile Tenant
if (isset($_POST['update_profile'])) {
    $user_data = [
        'id' => $_SESSION['user_id'],
        'username' => trim($_POST['username']),
        'full_name' => trim($_POST['full_name']),
        'password' => trim($_POST['new_password'])
    ];
    
    // Validasi
    $errors = validateUser($user_data);
    
    // Cek duplikasi username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $user_data['username'], $user_data['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username sudah digunakan!";
    }
    $stmt->close();
    
    if (empty($errors)) {
        if (!empty($user_data['password'])) {
            $password = password_hash($user_data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=? WHERE id=?");
            $stmt->bind_param("sssi", $user_data['username'], $password, $user_data['full_name'], $user_data['id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=? WHERE id=?");
            $stmt->bind_param("ssi", $user_data['username'], $user_data['full_name'], $user_data['id']);
        }
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['full_name'] = $user_data['full_name'];
            $success = "Profile berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate profile!";
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}

// Ambil data tenant
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tenant_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil data kamar tenant
$stmt = $conn->prepare("SELECT * FROM rooms WHERE tenant_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$my_room = $result->fetch_assoc();
$stmt->close();

// Filter pembayaran
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');

// Ambil data pembayaran tenant dengan filter
$where_conditions = ["p.tenant_id = ?"];
$params = [$_SESSION['user_id']];
$types = 'i';

if (!empty($filter_status)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_year)) {
    $where_conditions[] = "YEAR(p.payment_date) = ?";
    $params[] = $filter_year;
    $types .= 's';
}

$query = "SELECT p.* FROM payments p WHERE " . implode(' AND ', $where_conditions) . " ORDER BY p.payment_date DESC";
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
    COALESCE(SUM(p.amount), 0) as total_amount
    FROM payments p 
    WHERE p.tenant_id = ? AND YEAR(p.payment_date) = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("is", $_SESSION['user_id'], $filter_year);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penyewa - Manajemen Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .room-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(20px, -20px);
        }
        
        .quick-action-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .quick-action-card:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-3px);
        }
        
        .payment-summary-card {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: white;
            border-radius: 15px;
        }
        
        .btn-modern {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary-modern {
            background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .btn-success-modern {
            background: linear-gradient(45deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .btn-info-modern {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .payment-item-mini {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .payment-item-mini.paid {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        
        .payment-item-mini.pending {
            border-left-color: #ffc107;
            background: rgba(255, 193, 7, 0.1);
        }
        
        .payment-item-mini.overdue {
            border-left-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }
        
        .payment-item-mini:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-weight: 600;
        }
        
        .navbar-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 1rem;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-card:nth-child(2) { animation-delay: 0.1s; }
        .animate-card:nth-child(3) { animation-delay: 0.2s; }
        .animate-card:nth-child(4) { animation-delay: 0.3s; }
        
        @media (max-width: 768px) {
            .welcome-section {
                padding: 1.5rem;
                text-align: center;
            }
            
            .dashboard-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-modern">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-house-heart"></i> Panel Penyewa
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="payments.php">
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
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show dashboard-card">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show dashboard-card">
                <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section animate-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">
                        <i class="bi bi-sun"></i> Selamat Datang, <?= htmlspecialchars($_SESSION['full_name']) ?>!
                    </h2>
                    <p class="mb-0 fs-5">Kelola informasi kamar dan pembayaran Anda dengan mudah</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="card-icon">
                        <i class="bi bi-house-heart"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($my_room): ?>
            <!-- Info Cards Row -->
            <div class="row mb-4">
                <!-- Room Info Card -->
                <div class="col-md-6 mb-3">
                    <div class="card room-card h-100 animate-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-3">
                                        <i class="bi bi-house-door"></i> Informasi Kamar
                                    </h5>
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-2"><strong>Nomor:</strong></p>
                                            <p class="mb-2"><strong>Tipe:</strong></p>
                                            <p class="mb-2"><strong>Harga:</strong></p>
                                            <p class="mb-0"><strong>Status:</strong></p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-2"><?= htmlspecialchars($my_room['room_number']) ?></p>
                                            <p class="mb-2"><?= htmlspecialchars($my_room['room_type']) ?></p>
                                            <p class="mb-2">Rp <?= number_format($my_room['price'], 0, ',', '.') ?></p>
                                            <p class="mb-0">
                                                <span class="badge bg-light text-dark">Aktif</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                            </div>
                            <?php if ($my_room['description']): ?>
                                <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                                <p class="mb-0"><small><?= htmlspecialchars($my_room['description']) ?></small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary Card -->
                <div class="col-md-6 mb-3">
                    <div class="card payment-summary-card h-100 animate-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-3">
                                        <i class="bi bi-wallet2"></i> Ringkasan Pembayaran <?= $filter_year ?>
                                    </h5>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <h3><?= $stats['paid_count'] ?></h3>
                                            <p class="mb-0 small">Lunas</p>
                                        </div>
                                        <div class="col-4">
                                            <h3><?= $stats['pending_count'] ?></h3>
                                            <p class="mb-0 small">Pending</p>
                                        </div>
                                        <div class="col-4">
                                            <h3><?= $stats['overdue_count'] ?></h3>
                                            <p class="mb-0 small">Terlambat</p>
                                        </div>
                                    </div>
                                    <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                                    <div class="text-center">
                                        <p class="mb-1"><strong>Total Pembayaran:</strong></p>
                                        <h4>Rp <?= number_format($stats['total_amount'], 0, ',', '.') ?></h4>
                                    </div>
                                </div>
                                <div class="card-icon">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card quick-action-card animate-card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="bi bi-lightning"></i> Menu Cepat
                            </h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <button class="btn btn-modern btn-primary-modern w-100" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                        <i class="bi bi-person-gear"></i> Edit Profile
                                    </button>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="payments.php" class="btn btn-modern btn-info-modern w-100">
                                        <i class="bi bi-receipt"></i> Lihat Semua Pembayaran
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <button class="btn btn-modern btn-success-modern w-100" onclick="window.print()">
                                        <i class="bi bi-printer"></i> Cetak Dashboard
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payment History -->
            <div class="card dashboard-card animate-card">
                <div class="card-header bg-transparent">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> Riwayat Pembayaran Terbaru
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <form method="GET" class="d-inline">
                                <select class="form-select form-select-sm d-inline w-auto" name="filter_year" onchange="this.form.submit()">
                                    <?php for($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                        <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($payments)): ?>
                        <div class="row">
                            <?php 
                            $recent_payments = array_slice($payments, 0, 6); // Show only 6 recent payments
                            foreach ($recent_payments as $payment): 
                            ?>
                                <div class="col-md-6 mb-3">
                                    <div class="payment-item-mini <?= $payment['status'] ?> p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?= date('F Y', strtotime($payment['payment_month'] . '-01')) ?>
                                                </h6>
                                                <p class="mb-1 fw-bold">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></p>
                                                <small class="text-muted"><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></small>
                                            </div>
                                            <div>
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
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($payments) > 6): ?>
                            <div class="text-center mt-3">
                                <a href="payments.php" class="btn btn-modern btn-primary-modern">
                                    <i class="bi bi-eye"></i> Lihat Semua Pembayaran (<?= count($payments) ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                            <h5 class="mt-3 text-muted">Belum Ada Data Pembayaran</h5>
                            <p class="text-muted">Belum ada data pembayaran untuk tahun <?= $filter_year ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Jika belum memiliki kamar -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card dashboard-card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-house-x" style="font-size: 4rem; color: #6c757d;"></i>
                            <h4 class="mt-3">Anda belum memiliki kamar!</h4>
                            <p class="text-muted">Silakan hubungi admin untuk mendapatkan kamar.</p>
                            <button class="btn btn-modern btn-primary-modern">
                                <i class="bi bi-telephone"></i> Hubungi Admin
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Edit Profile -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title"><i class="bi bi-person-gear"></i> Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editProfileForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?= htmlspecialchars($tenant_data['full_name']) ?>" 
                                   minlength="3" 
                                   pattern="[a-zA-Z\s]+" 
                                   required style="border-radius: 10px;">
                            <div class="invalid-feedback">
                                Nama minimal 3 karakter, hanya huruf dan spasi
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" 
                                   value="<?= htmlspecialchars($tenant_data['username']) ?>" 
                                   minlength="3" 
                                   pattern="[a-zA-Z0-9_]+" 
                                   required style="border-radius: 10px;">
                            <div class="invalid-feedback">
                                Username minimal 3 karakter, hanya huruf, angka, dan underscore
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" name="new_password" minlength="6" style="border-radius: 10px;">
                            <div class="invalid-feedback">
                                Password minimal 6 karakter
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border: none;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Batal</button>
                        <button type="submit" name="update_profile" class="btn btn-modern btn-primary-modern">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
        let isValid = true;
        const inputs = this.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        const password = this.querySelector('input[name="new_password"]');
        if (password.value && password.value.length < 6) {
            password.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // Real-time validation
    document.querySelectorAll('#editProfileForm input').forEach(input => {
        input.addEventListener('input', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Add loading animation to cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.dashboard-card, .room-card, .payment-summary-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    </script>
</body>
</html>
