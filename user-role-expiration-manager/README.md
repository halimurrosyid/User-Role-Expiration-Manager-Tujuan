# User Role Expiration Manager WordPress Plugin

Plugin WordPress profesional untuk mengelola masa berlaku role pengguna secara otomatis, aman, dan efisien.

[![GitHub Release](https://img.shields.io/github/v/release/halimurrosyid/User-Role-Expiration-Manager-Tujuan?style=flat-square)](https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan/releases)
[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.8%2B-blue?style=flat-square)](https://wordpress.org)
[![PHP Compatible](https://img.shields.io/badge/PHP-7.4%2B-777bb4?style=flat-square)](https://php.net)

---

## 📌 Informasi Plugin

- **Nama Plugin**: User Role Expiration Manager
- **Versi**: 1.0.4
- **Pengembang (Author)**: [Mujaddid Halimurrosyid](https://it.telkomuniversity.ac.id/)
- **Website Institusi**: [https://it.telkomuniversity.ac.id/](https://it.telkomuniversity.ac.id/)
- **Repository GitHub**: [https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan](https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan)
- **Lisensi**: GPL v2 or later

---

## 🌟 Fitur Utama

- **Automatic Upgrader Source Selection Filter**: Penambahan filter `upgrader_source_selection` untuk memutus masalah pengekstrakan folder zip repository GitHub secara seamless.
- **Visual DateTime Calendar Picker**: Picker tanggal & jam kalender interaktif untuk memudahkan pengisian Tanggal Mulai.
- **Cek Update GitHub Direct**: Tombol pintas *Cek Update GitHub* di halaman Plugins untuk memeriksa update rilis terbaru secara instan dari GitHub.
- **Fitur Preset Cepat (Quick Presets)**: Mempermudah pemilihan durasi expired (7 Hari, 14 Hari, 1 Bulan, 3 Bulan, 6 Bulan, 1 Tahun, 2 Tahun) secara otomatis dengan 1-klik di Global Settings maupun halaman Edit User.
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

- **WordPress Version**: Minimal 5.8+
- **PHP Version**: Minimal 7.4+
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
- **Pilih Preset Cepat**: Pilih opsi siap pakai (7 Hari, 1 Bulan, 1 Tahun, dll) untuk otomatis mengisi durasi.
- **Enable Plugin**: Aktifkan atau nonaktifkan pemrosesan otomatis.
- **Default Expiration Duration**: Tentukan durasi default kustom jika tidak menggunakan preset.
- **Default Role Setelah Expired**: Pilih role tujuan (misalnya `Subscriber` atau `No Role`).
- **Jadwal WP-Cron**: Pilih frekuensi pemeriksaan (`Hourly`, `Twice Daily`, `Daily`).
- **Session Logout**: Aktifkan untuk otomatis mengeluarkan pengguna dari login saat role-nya expired.
- **Notifikasi Email**: Kirim email pemberitahuan ke pengguna ketika role berubah.
- **Dry Run Mode**: Mode simulasi untuk menguji pemrosesan role tanpa benar-benar mengubah role user di database.

### 2. Pengaturan Per Pengguna (`Pengguna` -> `Edit User`)
Pada bagian bawah halaman edit profil pengguna terdapat panel **Role Expiration**:
- **Pilih Preset Cepat**: Auto-fill durasi pengguna hanya dengan 1-klik.
- **Enable Expiration**: Aktifkan/matikan khusus untuk user ini.
- **Tanggal Mulai**: Tentukan tanggal awal perhitungan.
- **Durasi & Satuan**: Set durasi khusus pengguna.
- **Role Setelah Expired**: Pilih role tujuan khusus.
- **Tombol Action**:
  - `Reset Tanggal Mulai`: Mereset tanggal mulai ke waktu saat ini.
  - `Expire Sekarang`: Langsung mengubah role pengguna saat ini juga.

---

## 📝 Changelog

### Version 1.0.4 (2026-08-06)
- **Automatic Upgrader Fix**: Menambahkan filter `upgrader_source_selection` untuk menangani rute pengekstrakan folder zip repository GitHub secara otomatis sehingga pesan error *The package could not be installed* teratasi 100%.

### Version 1.0.3 (2026-08-06)
- **UI & UX Enhancement**: Menambahkan picker tanggal/jam kalender interaktif HTML5 DateTime untuk Tanggal Mulai.
- **Admin Protection Guard**: Ditambahkan proteksi tingkat lanjut yang memblokir akun Administrator yang sedang aktif dari risiko ter-expire.

### Version 1.0.2 (2026-08-06)
- **Automatic Updater Upgrade**: Menambahkan tombol **Cek Update GitHub** langsung pada tabel Plugins.
- **Dual Transient Strategy**: Penggabungan rilis GitHub API + pemicu `pre_set_site_transient_update_plugins` & `site_transient_update_plugins`.

### Version 1.0.1 (2026-08-06)
- **Feature Update**: Menambahkan **Fitur Preset Cepat (Quick Presets)** durasi (7 Hari, 14 Hari, 1 Bulan, 3 Bulan, 6 Bulan, 1 Tahun, 2 Tahun) pada Global Settings dan Edit User page.
- **Compatibility Improvement**: Optimasi penuh kompatibilitas direktori ZIP dan auto-loader untuk server Linux WordPress.

### Version 1.0.0 (2026-08-05)
- **Initial Release**: Rilis perdana plugin User Role Expiration Manager oleh Mujaddid Halimurrosyid ([Telkom University IT](https://it.telkomuniversity.ac.id/)).

---

## 📄 Lisensi

Plugin ini dirilis di bawah lisensi [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
