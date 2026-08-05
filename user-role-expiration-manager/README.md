# User Role Expiration Manager WordPress Plugin

Plugin WordPress profesional untuk mengelola masa berlaku role pengguna secara otomatis, aman, dan efisien.

[![GitHub Release](https://img.shields.io/github/v/release/halimurrosyid/User-Role-Expiration-Manager-Tujuan?style=flat-square)](https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan/releases)
[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.8%2B-blue?style=flat-square)](https://wordpress.org)
[![PHP Compatible](https://img.shields.io/badge/PHP-8.1%2B-777bb4?style=flat-square)](https://php.net)

---

## 📌 Informasi Plugin

- **Nama Plugin**: User Role Expiration Manager
- **Versi**: 1.0.0
- **Pengembang (Author)**: [Mujaddid Halimurrosyid](https://it.telkomuniversity.ac.id/)
- **Website Institusi**: [https://it.telkomuniversity.ac.id/](https://it.telkomuniversity.ac.id/)
- **Repository GitHub**: [https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan](https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan)
- **Lisensi**: GPL v2 or later

---

## 🌟 Fitur Utama

- **Automatic Update dari Dashboard WordPress**: Terhubung langsung dengan repository GitHub (`halimurrosyid/User-Role-Expiration-Manager-Tujuan`). Setiap kali ada versi rilis baru di GitHub, WordPress akan mendeteksi dan memperbarui plugin secara otomatis dari Dashboard tanpa perlu instalasi ulang atau plugin tambahan.
- **Tidak Mengubah Data Login Auth**: Menggunakan metadata sendiri (`_urem_*`) sehingga 100% aman dan kompatibel dengan plugin autentikasi seperti **Authorizer**, **WooCommerce**, **User Role Editor**, dan **Members**.
- **Integrasi Native WP Admin**:
  - Submenu "Role Expiration" di bawah menu **Pengguna (Users)**.
  - Metabox "Role Expiration" pada halaman **Edit User** (`user-edit.php`).
  - Kolom tambahan & filter status pada tabel **Semua Pengguna** (`users.php`).
  - Bulk Actions (Reset, Enable, Disable, Expire Sekarang, Ubah Role).
  - Native WP Dashboard Widget.
- **WP-Cron Batch Processing**: Pemrosesan batch efisien (100 user/chunk) yang siap menangani puluhan ribu pengguna (20.000+ users) tanpa memory limit overflow.
- **Manual Scan**: Tombol *Scan Sekarang* untuk menjalankan pemeriksaan peran instan tanpa menunggu jadwal cron.
- **Logging & CSV Export**: Mencatat seluruh riwayat perubahan peran pengguna ke database kustom (`{$wpdb->prefix}urem_logs`), dilengkapi fitur pencarian, filter, hapus log, dan **Export CSV**.
- **Dynamic Role API**: Mengambil seluruh role bawaan maupun custom role dari API WordPress (`wp_roles()`), termasuk opsi `No Role`.
- **Notifikasi Admin Notice**: Peringatan otomatis jika terdapat pengguna yang akan expired dalam 7 hari atau telah expired hari ini.
- **Fitur Tambahan**: Auto-logout sesi pengguna setelah role berubah, notifikasi email ke pengguna, dan Dry Run (mode simulasi).

---

## 📋 Persyaratan Sistem

- **WordPress Version**: Minimal 6.8+
- **PHP Version**: Minimal 8.1+
- **Database**: MySQL 5.7+ / MariaDB 10.3+

---

## 🚀 Cara Instalasi & Automatic Updates

1. Unduh file `.zip` dari [GitHub Releases](https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan/releases/latest) atau salin folder `user-role-expiration-manager` ke direktori plugin WordPress Anda:
   ```
   wp-content/plugins/user-role-expiration-manager/
   ```
2. Buka dashboard WordPress admin -> **Plugin** -> **Plugin Terpasang**.
3. Cari **User Role Expiration Manager** dan klik **Aktifkan**.
4. **Update Otomatis**: Plugin sudah dilengkapi dengan fitur *GitHub Automatic Updater*. Saat ada versi rilis baru di repository GitHub, pemberitahuan update akan otomatis muncul di menu **Dashboard** -> **Pembaruan (Updates)** WordPress dan dapat di-update dengan 1-klik tanpa perlu melakukan upload ulang secara manual!

---

## ⚙️ Panduan Penggunaan

### 1. Pengaturan Global (`Pengguna` -> `Role Expiration` -> `Settings`)
- **Enable Plugin**: Aktifkan atau nonaktifkan pemrosesan otomatis.
- **Default Expiration Duration**: Tentukan durasi default (misalnya `30 Hari`, `2 Bulan`, `1 Tahun`).
- **Default Role Setelah Expired**: Pilih role tujuan (misalnya `Subscriber` atau `No Role`).
- **Jadwal WP-Cron**: Pilih frekuensi pemeriksaan (`Hourly`, `Twice Daily`, `Daily`).
- **Session Logout**: Aktifkan untuk otomatis mengeluarkan pengguna dari login saat role-nya expired.
- **Notifikasi Email**: Kirim email pemberitahuan ke pengguna ketika role berubah.
- **Dry Run Mode**: Mode simulasi untuk menguji pemrosesan role tanpa benar-benar mengubah role user di database.

### 2. Pengaturan Per Pengguna (`Pengguna` -> `Edit User`)
Pada bagian bawah halaman edit profil pengguna terdapat panel **Role Expiration**:
- **Enable Expiration**: Aktifkan/matikan khusus untuk user ini.
- **Tanggal Mulai**: Tentukan tanggal awal perhitungan.
- **Durasi & Satuan**: Set durasi khusus pengguna.
- **Role Setelah Expired**: Pilih role tujuan khusus.
- **Tombol Action**:
  - `Reset Tanggal Mulai`: Mereset tanggal mulai ke waktu saat ini.
  - `Expire Sekarang`: Langsung mengubah role pengguna saat ini juga.

### 3. Kolom & Bulk Actions (`Pengguna` -> `Semua Pengguna`)
Tabel daftar pengguna dilengkapi kolom:
- **Tanggal Mulai**
- **Tanggal Expired**
- **Role Setelah Expired**
- **Status Badge**:
  - 🟩 **Aktif**: Masa berlaku masih aman.
  - 🟨 **Akan Expired**: Sisa kurang dari 30 hari.
  - 🟥 **Expired**: Telah melewati tanggal expired.
  - ⬜ **Disabled**: Fitur expiration dimatikan.
- **Sisa Hari**

---

## 📌 Alur Pembaruan Versi (Version Release Workflow)

Setiap kali Anda membuat pembaruan atau perbaikan fitur pada plugin ini, ikuti alur peningkatan versi berikut agar tercatat dengan rapi dan otomatis terdeteksi oleh WordPress:

1. **Perbarui Nomor Versi**:
   - Di `user-role-expiration-manager.php`: Ubah header `Version: X.Y.Z` dan konstanta `define( 'UREM_VERSION', 'X.Y.Z' );`.
2. **Catat Perubahan pada Changelog**:
   - Tambahkan daftar perubahan baru pada seksi [Changelog](#-changelog) di file `README.md`.
3. **Commit & Push ke GitHub**:
   ```bash
   git add .
   git commit -m "Release version X.Y.Z - Brief description"
   git push origin main
   ```
4. **Buat Release Tag di GitHub**:
   - Buka halaman GitHub Repository -> **Releases** -> **Create a new release**.
   - Masukkan Tag: `vX.Y.Z` (contoh: `v1.0.1`).
   - Masukkan judul & deskripsi rilis.
   - Selesai! Seluruh website WordPress yang memasang plugin ini akan otomatis menerima notifikasi update untuk versi `X.Y.Z`.

---

## 📝 Changelog

### Version 1.0.0 (2026-08-05)
- **Initial Release**: Rilis perdana plugin User Role Expiration Manager oleh Mujaddid Halimurrosyid ([Telkom University IT](https://it.telkomuniversity.ac.id/)).
- Integration dengan menu native WordPress **Pengguna (Users)**.
- Per-user role expiration management di halaman `user-edit.php`.
- Dashboard Widget ringkasan status pengguna.
- Kolom custom & dropdown filter pada `users.php`.
- WP-Cron batching processor (100 users/batch).
- Tombol **Scan Sekarang** untuk pemrosesan manual.
- Database logging system (`{$wpdb->prefix}urem_logs`) dengan fitur **Export CSV** dan clear logs.
- Fitur **GitHub Automatic Updater** terintegrasi secara bawaan.

---

## 🛠️ Hook & Filter untuk Developer

### Action Hooks
- `urem_before_user_expired` ($user_id, $old_role, $target_role, $trigger)
- `urem_after_user_expired` ($user_id, $old_role, $target_role, $trigger)

### Filter Hooks
- `urem_available_roles` ($roles)
- `urem_expiring_soon_threshold_days` ($days)
- `urem_expiration_units` ($units)

---

## 📄 Lisensi

Plugin ini dirilis di bawah lisensi [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
