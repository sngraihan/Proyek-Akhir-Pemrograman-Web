# Kozan

Kozan adalah aplikasi berbasis web yang dirancang untuk mempermudah pengelolaan data kos-kosan, termasuk informasi penyewa, kamar, pembayaran, dan laporan. Proyek ini dibuat sebagai bagian dari Proyek Akhir Pemrograman Web, menggunakan teknologi HTML, CSS, JavaScript, PHP, dan MySQL untuk membangun sistem yang efisien dan user-friendly.

## Dosen Pengampu
- M. Iqbal Parabi, S.SI.
- M.T. Rizky Prabowo, M.Kom.
  
## Fitur Utama
- **Manajemen Penyewa**: Tambah, edit, dan hapus data penyewa.
- **Manajemen Kamar**: Kelola informasi kamar seperti status (kosong/terisi), harga, dan fasilitas.
- **Manajemen Pembayaran**: Catat dan lacak pembayaran sewa bulanan penyewa.
- **Laporan Keuangan**: Generate laporan keuangan berdasarkan pembayaran penyewa.
- **Dashboard Admin**: Antarmuka yang intuitif untuk pengelola kos.
- **Autentikasi Pengguna**: Sistem login untuk keamanan data.

## Teknologi yang Digunakan
- **Frontend**:
  - HTML5
  - CSS3 (dengan framework Bootstrap untuk desain responsif)
  - JavaScript
- **Backend**:
  - PHP (versi 7.4 atau lebih tinggi)
  - MySQL (untuk database relasional)
- **Tools dan Dependencies**:
  - XAMPP/Laragon (untuk lingkungan pengembangan lokal)
  - Composer (opsional, untuk manajemen dependensi PHP)
  - Git (untuk version control)

## Prasyarat
Sebelum menjalankan proyek ini, pastikan Anda telah menginstall:
- [XAMPP](https://www.apachefriends.org/) / [Laragon](https://laragon.org/) atau server lokal lainnya (Apache, MySQL).
- [Git](https://git-scm.com/) untuk mengelola repository.
- Browser modern (Google Chrome, Firefox, dll.).
- PHP versi 7.4 atau lebih tinggi.
- MySQL/MariaDB untuk database.

## Instalasi
Ikuti langkah-langkah berikut untuk menjalankan proyek ini di mesin lokal Anda:

1. **Clone Repository**:
   ```bash
   git clone https://github.com/sngraihan/Proyek-Akhir-Pemrograman-Web.git
   ```

2. **Pindah ke Direktori Proyek**:
   ```bash
   cd Proyek-Akhir-Pemrograman-Web
   ```

3. **Konfigurasi Database**:
   - Buka XAMPP, start Apache dan MySQL.
   - Buat database baru di phpMyAdmin, misalnya `db_kos_kosan`.
   - Import file SQL yang ada di folder `/database/db_kos_kosan.sql` ke database yang baru dibuat.

4. **Konfigurasi Koneksi Database**:
   - Buka file `config/database.php` atau file serupa di direktori proyek.
   - Sesuaikan pengaturan koneksi database (hostname, username, password, dan nama database).

5. **Jalankan Aplikasi**:
   - Pindahkan folder proyek ke direktori `htdocs` di XAMPP (contoh: `C:/xampp/htdocs/Proyek-Akhir-Pemrograman-Web`).
   - Akses aplikasi melalui browser dengan URL: `http://localhost/Proyek-Akhir-Pemrograman-Web`.

6. **Login ke Sistem**:
   - Gunakan kredensial default (jika ada) atau buat akun admin melalui halaman registrasi.

## Struktur Direktori
```plaintext
PEMWEB/
├── admin/                          # Folder untuk halaman admin
│   ├── dashboard.php              # Dashboard utama admin dengan statistik
│   ├── rooms.php                  # Kelola data kamar (CRUD)
│   ├── tenants.php                # Kelola data penyewa (CRUD)
│   └── payments.php               # Kelola pembayaran (CRUD)
│
├── tenant/                        # Folder untuk halaman penyewa
│   ├── dashboard.php              # Dashboard penyewa dengan info kamar & pembayaran
│   ├── profile.php                # Edit profil penyewa
│   └── payments.php               # Riwayat pembayaran penyewa
│
├── assets/                        # Folder untuk aset statis
│   ├── css/                       # File CSS
│   │   ├── d_admin.css           # Styling khusus untuk halaman admin
│   │   └── login.css             # Styling untuk halaman login
│   └── images/                    # Gambar dan logo
│       ├── logo.jpg              # Logo aplikasi
│       └── loginimg.jpg          # Background image untuk login
│
├── config/                        # Konfigurasi aplikasi
│   └── database.php              # Koneksi database MySQL
│
├── includes/                      # File PHP yang di-include
│   ├── auth.php                  # Fungsi autentikasi dan session management
│   └── validation.php            # Fungsi validasi input server-side
│
├── scripts/                       # Script database
│   └── kos_management.sql        # Database schema dan sample data
│
├── index.php                      # Halaman utama (redirect berdasarkan role)
├── login.php                      # Halaman login dengan validasi
└── logout.php                     # Script logout dan destroy session
```

## Cara Penggunaan
1. **Login Admin**:
   - Masuk ke sistem menggunakan kredensial admin.
2. **Kelola Data**:
   - Tambah penyewa baru melalui menu "Penyewa".
   - Tambah atau edit kamar melalui menu "Kamar".
   - Catat pembayaran melalui menu "Pembayaran".
3. **Lihat Laporan**:
   - Akses laporan keuangan melalui menu "Laporan".
4. **Logout**:
   - Keluar dari sistem untuk keamanan.

## Kontributor
- [Raihan Andi Saungnaga](https://github.com/sngraihan) 2317051058
- [Dymaz Satya Putra](https://github.com/DYmazeh) 2357051017
- [Adrianne Julian Claresta](https://github.com/Idheid) 2317051002
- [Maurah Hellena](https://github.com/Mauraa16) 2317051105

## Lisensi
Proyek ini dilisensikan di bawah [MIT License](LICENSE). Silakan gunakan, modifikasi, dan distribusikan sesuai kebutuhan.
