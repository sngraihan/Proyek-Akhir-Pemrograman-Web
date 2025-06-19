<?php
session_start();
require_once 'config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: tenant/dashboard.php');
    }
    exit();
}

// Proses login
if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek kredensial menggunakan prepared statement
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Redirect berdasarkan role
                if ($user['role'] == 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: tenant/dashboard.php');
                }
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Manajemen Kos</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

</head>

<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container login-container">
        <div class="login-card">
            <!-- Left Side - Branding -->
            <div class="login-left">
                <div class="kos-icon"></div>
                <h2>Sistem Manajemen </h2>
                <p>Kelola Kozkoz-an Anda dengan mudah dan efisien</p>

                <ul class="features">
                    <li>Manajemen Kamar Terintegrasi</li>
                    <li>Kelola Data Penyewa</li>
                    <li>Tracking Pembayaran Real-time</li>
                    <li>Laporan Keuangan Lengkap</li>
                    <li>Dashboard Analytics</li>
                </ul>

                <div class="demo-info">
                    <h6> Demo Account</h6>
                    <small>
                        <strong>Admin:</strong> admin / password<br>
                        <strong>Penyewa:</strong> koron / password
                    </small>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-right">
                <div class="login-header">
                    <h3>Selamat Datang Kembali!</h3>
                    <p>Silakan masuk ke akun Anda untuk melanjutkan</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" id="errorAlert">
                        ⚠️ <?= htmlspecialchars($error) ?>
                        <button type="button" class="alert-close" onclick="closeAlert()">&times;</button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="username" class="form-label">
                            👤 Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Masukkan username Anda"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required>
                        <div class="invalid-feedback">
                            Username minimal 3 karakter
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            🔒 Password
                        </label>
                        <div class="password-container">
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Masukkan password Anda" required>
                            <button type="button" class="password-toggle" id="togglePassword"
                                aria-label="Toggle password visibility">
                                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-eye" viewBox="0 0 24 24"
                                    aria-hidden="true" focusable="false">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Password minimal 6 karakter
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="loginBtn">
                        <span id="loginText"> Masuk Sekarang</span>
                    </button>
                </form>


            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                password.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        });

        // Close alert
        function closeAlert() {
            const alert = document.getElementById('errorAlert');
            if (alert) {
                alert.style.display = 'none';
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            let isValid = true;

            // Reset validation
            [username, password].forEach(field => {
                field.classList.remove('is-invalid', 'is-valid');
            });

            // Validate username
            if (username.value.trim().length < 3) {
                username.classList.add('is-invalid');
                isValid = false;
            } else {
                username.classList.add('is-valid');
            }

            // Validate password
            if (password.value.length < 6) {
                password.classList.add('is-invalid');
                isValid = false;
            } else {
                password.classList.add('is-valid');
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                // Show loading state
                loginBtn.disabled = true;
                loginText.innerHTML = '<span class="loading">⏳</span> Memproses...';
            }
        });

        // Real-time validation
        document.getElementById('username').addEventListener('input', function () {
            this.classList.remove('is-invalid', 'is-valid');
            if (this.value.trim().length >= 3) {
                this.classList.add('is-valid');
            } else if (this.value.length > 0) {
                this.classList.add('is-invalid');
            }
        });

        document.getElementById('password').addEventListener('input', function () {
            this.classList.remove('is-invalid', 'is-valid');
            if (this.value.length >= 6) {
                this.classList.add('is-valid');
            } else if (this.value.length > 0) {
                this.classList.add('is-invalid');
            }
        });

        // Auto-hide alert after 5 seconds
        setTimeout(function () {
            const alert = document.getElementById('errorAlert');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 300);
            }
        }, 5000);

        // Add keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Alt + L to focus username
            if (e.altKey && e.key === 'l') {
                e.preventDefault();
                document.getElementById('username').focus();
            }
            // Escape to close alert
            if (e.key === 'Escape') {
                closeAlert();
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>