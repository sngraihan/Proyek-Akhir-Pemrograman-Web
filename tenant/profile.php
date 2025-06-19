<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/validation.php';
checkTenant();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $user_data = [
        'id' => $_SESSION['user_id'],
        'username' => trim($_POST['username']),
        'full_name' => trim($_POST['full_name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'emergency_contact' => trim($_POST['emergency_contact']),
        'password' => trim($_POST['new_password'])
    ];
    
    // Basic validation
    $errors = [];
    if (strlen($user_data['full_name']) < 3) {
        $errors[] = "Nama lengkap minimal 3 karakter";
    }
    
    if (!empty($user_data['email']) && !filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    if (!empty($user_data['phone']) && !preg_match('/^[0-9]{10,13}$/', $user_data['phone'])) {
        $errors[] = "Nomor telepon harus 10-13 digit";
    }
    
    // Check username duplication
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $user_data['username'], $user_data['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username sudah digunakan!";
    }
    $stmt->close();
    
    if (empty($errors)) {
        // First, check if additional_info exists
        $check_stmt = $conn->prepare("SELECT id FROM tenant_additional_info WHERE user_id = ?");
        $check_stmt->bind_param("i", $_SESSION['user_id']);
        $check_stmt->execute();
        $info_exists = $check_stmt->get_result()->num_rows > 0;
        $check_stmt->close();
        
        // Update users table
        if (!empty($user_data['password'])) {
            $password = password_hash($user_data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=? WHERE id=?");
            $stmt->bind_param("sssi", $user_data['username'], $password, $user_data['full_name'], $user_data['id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=? WHERE id=?");
            $stmt->bind_param("ssi", $user_data['username'], $user_data['full_name'], $user_data['id']);
        }
        
        if ($stmt->execute()) {
            // Update or insert additional info
            if ($info_exists) {
                $stmt2 = $conn->prepare("UPDATE tenant_additional_info SET email=?, phone=?, emergency_contact=? WHERE user_id=?");
                $stmt2->bind_param("sssi", $user_data['email'], $user_data['phone'], $user_data['emergency_contact'], $_SESSION['user_id']);
            } else {
                $stmt2 = $conn->prepare("INSERT INTO tenant_additional_info (user_id, email, phone, emergency_contact) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("isss", $_SESSION['user_id'], $user_data['email'], $user_data['phone'], $user_data['emergency_contact']);
            }
            $stmt2->execute();
            $stmt2->close();
            
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

// Get tenant data with additional info
$query = "SELECT u.*, tai.email, tai.phone, tai.emergency_contact, r.room_number, r.room_type, r.price 
          FROM users u 
          LEFT JOIN tenant_additional_info tai ON u.id = tai.user_id 
          LEFT JOIN rooms r ON u.id = r.tenant_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tenant_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get payment summary
$payment_summary = $conn->prepare("
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        MIN(payment_date) as first_payment,
        MAX(payment_date) as last_payment
    FROM payments 
    WHERE tenant_id = ?
");
$payment_summary->bind_param("i", $_SESSION['user_id']);
$payment_summary->execute();
$payment_stats = $payment_summary->get_result()->fetch_assoc();
$payment_summary->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Manajemen Kos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            overflow: hidden;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-3px);
        }
        
        .stats-mini {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .form-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
        }
        
        .form-control-modern {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control-modern:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
            background: white;
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
        
        .btn-secondary-modern {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
        }
        
        .navbar-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .badge-modern {
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
        }
        
        .info-item {
            padding: 1rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateX(5px);
        }
        
        .section-title {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 2px;
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
            .profile-header {
                text-align: center;
                padding: 2rem 1rem;
            }
            
            .form-modern {
                padding: 1.5rem;
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="profile.php">
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

    <div class="container mt-4">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show profile-card">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show profile-card">
                <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-4">
                <div class="card profile-card mb-4 animate-card">
                    <div class="profile-header text-center p-4">
                        <div class="profile-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h4 class="mb-2"><?= htmlspecialchars($tenant_data['full_name']) ?></h4>
                        <p class="mb-3 opacity-75">@<?= htmlspecialchars($tenant_data['username']) ?></p>
                        <?php if ($tenant_data['room_number']): ?>
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <span class="badge badge-modern bg-light text-dark">Kamar <?= $tenant_data['room_number'] ?></span>
                                <span class="badge badge-modern bg-light text-dark"><?= $tenant_data['room_type'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <i class="bi bi-calendar text-primary me-2"></i>
                            <strong>Bergabung:</strong><br>
                            <small><?= date('d M Y', strtotime($tenant_data['created_at'])) ?></small>
                        </div>
                        
                        <?php if ($payment_stats['total_payments'] > 0): ?>
                            <div class="stats-mini">
                                <div class="row">
                                    <div class="col-6">
                                        <h5><?= $payment_stats['paid_count'] ?></h5>
                                        <small>Pembayaran Lunas</small>
                                    </div>
                                    <div class="col-6">
                                        <h5><?= $payment_stats['total_payments'] ?></h5>
                                        <small>Total Pembayaran</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tenant_data['room_number']): ?>
                            <div class="info-item">
                                <i class="bi bi-house-door text-success me-2"></i>
                                <strong>Sewa Bulanan:</strong><br>
                                <span class="text-success fw-bold">Rp <?= number_format($tenant_data['price'], 0, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col-md-8">
                <div class="form-modern animate-card">
                    <h3 class="section-title">
                        <i class="bi bi-person-gear"></i> Edit Profile
                    </h3>
                    
                    <form method="POST" id="profileForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-person"></i> Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-modern" name="full_name" 
                                           value="<?= htmlspecialchars($tenant_data['full_name']) ?>" 
                                           minlength="3" required>
                                    <div class="invalid-feedback">Nama lengkap minimal 3 karakter</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-at"></i> Username <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-modern" name="username" 
                                           value="<?= htmlspecialchars($tenant_data['username']) ?>" 
                                           minlength="3" 
                                           pattern="[a-zA-Z0-9_]+" required>
                                    <div class="invalid-feedback">Username minimal 3 karakter, hanya huruf, angka, dan underscore</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                    <input type="email" class="form-control form-control-modern" name="email" 
                                           value="<?= htmlspecialchars($tenant_data['email'] ?? '') ?>"
                                           placeholder="contoh@email.com">
                                    <div class="invalid-feedback">Format email tidak valid</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-telephone"></i> No. Telepon
                                    </label>
                                    <input type="tel" class="form-control form-control-modern" name="phone" 
                                           value="<?= htmlspecialchars($tenant_data['phone'] ?? '') ?>"
                                           pattern="[0-9]{10,13}"
                                           placeholder="08123456789">
                                    <div class="invalid-feedback">Nomor telepon harus 10-13 digit</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-person-plus"></i> Kontak Darurat
                            </label>
                            <input type="text" class="form-control form-control-modern" name="emergency_contact" 
                                   value="<?= htmlspecialchars($tenant_data['emergency_contact'] ?? '') ?>"
                                   placeholder="Nama dan nomor kontak darurat">
                            <small class="text-muted">Contoh: Ibu Siti - 08123456789</small>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-lock"></i> Password Baru
                            </label>
                            <input type="password" class="form-control form-control-modern" name="new_password" minlength="6"
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                            <small class="text-muted">Minimal 6 karakter</small>
                            <div class="invalid-feedback">Password minimal 6 karakter</div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-modern btn-secondary-modern me-md-2">
                                <i class="bi bi-x-circle"></i> Reset
                            </button>
                            <button type="submit" name="update_profile" class="btn btn-modern btn-primary-modern">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const inputs = this.querySelectorAll('input');
        let isValid = true;
        
        inputs.forEach(input => {
            // Remove previous validation states
            input.classList.remove('is-invalid', 'is-valid');
            
            // Check validity
            if (input.hasAttribute('required') && !input.value) {
                input.classList.add('is-invalid');
                isValid = false;
            } else if (input.value && !input.checkValidity()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else if (input.value) {
                input.classList.add('is-valid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // Real-time validation
    document.querySelectorAll('#profileForm input').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
            if (this.value && this.checkValidity()) {
                this.classList.add('is-valid');
            } else if (this.value) {
                this.classList.add('is-invalid');
            }
        });
    });
    
    // Add loading animation to cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.profile-card, .form-modern');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 200);
        });
    });
    </script>
</body>
</html>
