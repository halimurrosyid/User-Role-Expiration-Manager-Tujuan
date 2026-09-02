# Developer & AI Context Guide - User Role Expiration Manager

Dokumen panduan ini dibuat khusus untuk memberikan konteks lengkap mengenai arsitektur, struktur kode, fitur, sistem pengamanan, dan hook API plugin **User Role Expiration Manager** agar dapat dipahami dan dikembangkan secara instan oleh **AI Coding Assistant** maupun Pengembang (Developer) di masa depan.

---

## 📌 Informasi Dasar Plugin

- **Nama Plugin**: User Role Expiration Manager
- **Namespace PHP**: `\UserRoleExpirationManager`
- **Text Domain**: `user-role-expiration-manager`
- **Author**: [Mujaddid Halimurrosyid](https://it.telkomuniversity.ac.id/)
- **Repository GitHub**: `halimurrosyid/User-Role-Expiration-Manager-Tujuan`
- **Persyaratan Minimum**: WordPress 5.8+, PHP 7.4+ (Diuji hingga WP 6.8+ / 7.0+ & PHP 8.3+)

---

## 🏗️ Struktur Arsitektur & Direktori

Plugin mengadopsi standar **WordPress Coding Standards (WPCS)** dengan struktur Object-Oriented Programming (OOP) dan Autoloader otomatis.

```
user-role-expiration-manager/
├── user-role-expiration-manager.php  # Main bootstrap file, pendefinisian konstanta UREM_VERSION
├── uninstall.php                      # Uninstallation handler (hapus opsi, unschedule cron, drop table)
├── DEVELOPER.md                       # Dokumen panduan untuk AI & Developer (File Ini)
├── README.md                          # Dokumentasi publik & riwayat changelog
├── includes/
│   ├── class-autoloader.php           # PSR-4 Style Autoloader untuk namespace \UserRoleExpirationManager
│   ├── class-plugin.php               # Singleton bootstrap & activation/deactivation lifecycle
│   ├── class-admin.php                # Admin menu, assets enqueue, row meta, action links, admin notices
│   ├── class-expiration.php           # Logic engine: perhitungan expired, role transition, email templates
│   ├── class-settings.php             # Settings API handler (defaults, sanitization, key `urem_settings`)
│   ├── class-logger.php               # Custom DB table (`{$wpdb->prefix}urem_logs`) handler, search, export CSV, auto-prune
│   ├── class-cron.php                 # WP-Cron 100-user chunking batch processor & pre-expiration reminders
│   ├── class-user-meta.php            # Panel Edit Profile (user-edit.php), datetime picker, log history, bulk actions
│   ├── class-user-list-table.php      # Custom columns di users.php, status badges, sortable date, filter SQL
│   ├── class-dashboard-widget.php     # Native WP Dashboard summary widget
│   ├── class-github-updater.php       # Self-hosted GitHub automatic updater (Releases API + main branch fallback + upgrader filter)
│   └── helpers.php                    # Global helper functions (roles getter, duration presets, badges, datetime formatters)
├── admin/
│   ├── css/
│   │   └── admin.css                  # Modern 2-Column Responsive CSS & badge styles
│   ├── js/
│   │   └── admin.js                   # Confirmation modals & Quick Presets auto-fill handler
│   └── views/
│       ├── settings-page.php          # View Settings & Manual Scan (2-column layout dengan sidebar stats)
│       ├── logs-page.php              # View Logs list table (search, pagination, clear logs, export CSV)
│       └── user-profile-fields.php    # View User edit profile metabox & per-user log history
```

---

## 🔒 Proteksi Keamanan & Isolasi Metadata

1. **Perlindungan Akun Admin (Admin Protection Guard)**:
   - Method `Expiration::process_user_expiration()` memblokir akun Administrator yang sedang aktif agar tidak dapat ter-expire secara tidak sengaja.
2. **Isolasi Metadata Kustom**:
   - Seluruh data masa berlaku disimpan dalam meta key terisolasi (`_urem_expiration_enabled`, `_urem_expiration_start`, `_urem_expiration_duration`, `_urem_expiration_unit`, `_urem_expiration_role`, `_urem_expiration_ts`, `_urem_reminder_sent`).
   - Tidak pernah mengubah atau mengganggu data autentikasi login (`wp_users`), password, atau kredensial autentikasi WordPress.
3. **Keamanan Database & Hak Akses**:
   - Seluruh kueri SQL menggunakan `$wpdb->prepare()`.
   - Seluruh handler form/tindakan memeriksa *Capability* (`manage_options` / `edit_users`) serta memverifikasi Nonce Token.

---

## 🚀 Mekanisme GitHub Automatic Updater

- **Repository GitHub**: `halimurrosyid/User-Role-Expiration-Manager-Tujuan`
- **Class Updater**: `\UserRoleExpirationManager\GitHub_Updater`
- **Dual-Strategy Check**:
  - Strategi 1: Memeriksa rilis API GitHub Releases (`/releases/latest`).
  - Strategi 2: Membaca file versi mentah di branch `main` GitHub (`raw.githubusercontent.com/.../main/user-role-expiration-manager.php`).
- **Upgrader Source Selection Filter**: Hook `upgrader_source_selection` secara otomatis mengarahkan ekstraksi file zip GitHub langsung ke subfolder `user-role-expiration-manager/`.
- **Row Injector**: Hook `after_plugin_row_user-role-expiration-manager/user-role-expiration-manager.php` menjamin baris notifikasi update warna kuning beserta tombol 1-klik *Perbarui Sekarang* selalu tampil di `plugins.php`.

---

## 🔌 Hook API untuk Pengembangan Lebih Lanjut

### Custom Filters:
- `apply_filters( 'urem_available_roles', $roles )`: Menambah/mengubah daftar role tujuan.
- `apply_filters( 'urem_duration_presets', $presets )`: Menambah/mengubah preset durasi cepat.
- `apply_filters( 'urem_expiration_units', $units )`: Menambah satuan waktu kustom.
- `apply_filters( 'urem_should_expire_user', $should_expire, $user_id, $old_role, $target_role, $trigger )`: Membatalkan atau mengizinkan proses expiration user secara terprogram.

### Custom Actions:
- `do_action( 'urem_before_user_expired', $user_id, $old_role, $target_role, $trigger )`: Dipanggil tepat sebelum role pengguna diubah.
- `do_action( 'urem_after_user_expired', $user_id, $old_role, $target_role, $trigger )`: Dipanggil tepat setelah role pengguna berhasil diubah.

---

## ⚠️ Aturan Pengembangan Penting bagi AI / Developer Masa Depan

1. **Sinkronisasi Versi**: Selalu perbarui nomor versi di `UREM_VERSION` pada `user-role-expiration-manager.php` dan `README.md` secara bersamaan ketika merilis fitur baru.
2. **Pengemasan File ZIP**: Pada OS Windows, selalu gunakan perintah `tar -a -c -f user-role-expiration-manager.zip user-role-expiration-manager` agar separator direktori di dalam ZIP menggunakan forward slash `/` (kompatibel penuh dengan server Linux).
3. **Kompatibilitas PHP**: Pertahankan kompatibilitas dari PHP 7.4 hingga PHP 8.3+. Hindari penggunaan fungsi yang diperkenalkan di PHP 8.0+ tanpa melengkapinya dengan *fallback/polyfill*.
4. **Standard Sanitasi & Escaping**: Selalu sanitasi seluruh input (`sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`) dan escape seluruh output (`esc_html`, `esc_attr`, `esc_url`).
