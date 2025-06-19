<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
checkAdmin();

// CREATE - Tambah pembayaran
if (isset($_POST['add_payment'])) {
    $tenant_id = intval($_POST['tenant_id']);
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    $payment_month = $_POST['payment_month'];
    $status = $_POST['status'];
    
    // Get room_id from tenant
    $stmt = $conn->prepare("SELECT id FROM rooms WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    
    if ($room) {
        $room_id = $room['id'];  // Bukan $room['room_id']
        
        $stmt = $conn->prepare("INSERT INTO payments (tenant_id, room_id, amount, payment_date, payment_month, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidsss", $tenant_id, $room_id, $amount, $payment_date, $payment_month, $status);
        
        if ($stmt->execute()) {
            $success = "Pembayaran berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan pembayaran: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Tenant tidak memiliki kamar!";
    }
}

// UPDATE - Update status pembayaran
if (isset($_POST['update_payment'])) {
    $id = intval($_POST['payment_id']);
    $status = $_POST['new_status'];
    $amount = floatval($_POST['new_amount']);
    $payment_date = $_POST['new_payment_date'];
    
    $stmt = $conn->prepare("UPDATE payments SET status=?, amount=?, payment_date=? WHERE id=?");
    $stmt->bind_param("sdsi", $status, $amount, $payment_date, $id);
    
    if ($stmt->execute()) {
        $success = "Pembayaran berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate pembayaran!";
    }
    $stmt->close();
}

// DELETE - Hapus pembayaran
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $stmt = $conn->prepare("DELETE FROM payments WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Pembayaran berhasil dihapus!";
    } else {
        $error = "Gagal menghapus pembayaran!";
    }
    $stmt->close();
}

// READ - Ambil data pembayaran dengan join
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

$where_conditions = ["1=1"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR r.room_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($filter_status)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_month)) {
    $where_conditions[] = "p.payment_month = ?";
    $params[] = $filter_month;
    $types .= 's';
}

$query = "SELECT p.*, u.full_name, r.room_number, r.id as room_id 
          FROM payments p 
          JOIN users u ON p.tenant_id = u.id 
          JOIN rooms r ON p.room_id = r.id 
          WHERE " . implode(' AND ', $where_conditions) . "
          ORDER BY p.payment_date DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $payments = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Ambil data untuk dropdown
$tenants_query = "SELECT u.id, u.full_name, r.room_number, r.price, r.id as room_id 
                  FROM users u 
                  JOIN rooms r ON u.id = r.tenant_id 
                  WHERE u.role = 'tenant' 
                  ORDER BY u.full_name";
$tenants = $conn->query($tenants_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - Admin</title>
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
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="rooms.php"><i class="fas fa-door-open"></i> Kelola Kamar</a>
        <a href="tenants.php"><i class="fas fa-users"></i> Data Penyewa</a>
        <a href="payments.php" class="active"><i class="fas fa-money-bill-wave"></i> Pembayaran</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="header d-flex justify-content-between align-items-center">
            <h2>Kelola Pembayaran</h2>
            <div class="welcome">Hi <?= $_SESSION['full_name'] ?></div>
        </div>

        <div class="mt-3">
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Pembayaran -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Tambah Pembayaran</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="paymentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Penyewa</label>
                                <select class="form-control" name="tenant_id" id="tenant_id" required>
                                    <option value="">Pilih Penyewa</option>
                                    <?php foreach ($tenants as $tenant): ?>
                                        <option value="<?= $tenant['id'] ?>" data-price="<?= $tenant['price'] ?>">
                                            <?= htmlspecialchars($tenant['full_name']) ?> - Kamar <?= $tenant['room_number'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Pilih penyewa</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bulan Pembayaran</label>
                                <input type="month" class="form-control" name="payment_month" required>
                                <div class="invalid-feedback">Pilih bulan pembayaran</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Jumlah (Rp)</label>
                                <input type="number" class="form-control" name="amount" id="amount" min="1" required>
                                <div class="invalid-feedback">Jumlah harus lebih dari 0</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Bayar</label>
                                <input type="date" class="form-control" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                                <div class="invalid-feedback">Pilih tanggal pembayaran</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status" required>
                                    <option value="paid">Lunas</option>
                                    <option value="pending">Pending</option>
                                    <option value="overdue">Terlambat</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="add_payment" class="btn btn-primary">Simpan Pembayaran</button>
                </form>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari nama penyewa atau nomor kamar..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="filter_status">
                            <option value="">Semua Status</option>
                            <option value="paid" <?= $filter_status == 'paid' ? 'selected' : '' ?>>Lunas</option>
                            <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="overdue" <?= $filter_status == 'overdue' ? 'selected' : '' ?>>Terlambat</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="month" class="form-control" name="filter_month" value="<?= $filter_month ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Pembayaran -->
        <div class="card">
            <div class="card-header">
                <h5>Daftar Pembayaran</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Penyewa</th>
                                <th>Kamar</th>
                                <th>Bulan</th>
                                <th>Jumlah</th>
                                <th>Tanggal Bayar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($payment['full_name']) ?></td>
                                <td><?= $payment['room_number'] ?></td>
                                <td><?= date('F Y', strtotime($payment['payment_month'] . '-01')) ?></td>
                                <td>Rp <?= number_format($payment['amount'], 0, ',', '.') ?></td>
                                <td><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <span class="badge <?= $payment['status'] == 'paid' ? 'bg-success' : ($payment['status'] == 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                        <?= $payment['status'] == 'paid' ? 'Lunas' : ($payment['status'] == 'pending' ? 'Pending' : 'Terlambat') ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editPaymentModal<?= $payment['id'] ?>">Edit</button>
                                    <a href="payments.php?delete=<?= $payment['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Yakin ingin menghapus pembayaran ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pembayaran -->
    <?php foreach ($payments as $payment): ?>
    <div class="modal fade" id="editPaymentModal<?= $payment['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Penyewa</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($payment['full_name']) ?> - <?= $payment['room_number'] ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bulan</label>
                            <input type="text" class="form-control" value="<?= date('F Y', strtotime($payment['payment_month'] . '-01')) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah (Rp)</label>
                            <input type="number" class="form-control" name="new_amount" value="<?= $payment['amount'] ?>" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar</label>
                            <input type="date" class="form-control" name="new_payment_date" value="<?= $payment['payment_date'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="new_status" required>
                                <option value="paid" <?= $payment['status'] == 'paid' ? 'selected' : '' ?>>Lunas</option>
                                <option value="pending" <?= $payment['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="overdue" <?= $payment['status'] == 'overdue' ? 'selected' : '' ?>>Terlambat</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_payment" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-fill amount when tenant selected
    document.getElementById('tenant_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        if (price) {
            document.getElementById('amount').value = price;
        }
    });

    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        let isValid = true;
        const inputs = this.querySelectorAll('input[required], select[required]');
        
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        // Additional validation for amount
        const amount = document.getElementById('amount');
        if (amount.value <= 0) {
            amount.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
    </script>
</body>
</html>
