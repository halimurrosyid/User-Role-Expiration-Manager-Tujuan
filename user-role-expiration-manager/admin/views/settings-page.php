<?php
/**
 * Settings & Manual Scan View Template (2-Column Layout).
 *
 * @package UserRoleExpirationManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = \UserRoleExpirationManager\Settings::get_settings();
$roles    = urem_get_all_roles();
$units    = urem_get_expiration_units();
$presets  = urem_get_duration_presets();

// Calculate sidebar summary stats
$enabled_users = new \WP_User_Query(
	array(
		'fields'     => 'ID',
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => \UserRoleExpirationManager\Expiration::META_ENABLED,
				'value'   => '1',
				'compare' => '=',
			),
		),
	)
);

$user_ids       = $enabled_users->get_results();
$stat_total     = count( $user_ids );
$stat_active    = 0;
$stat_soon      = 0;
$stat_expired   = 0;

if ( ! empty( $user_ids ) ) {
	foreach ( $user_ids as $uid ) {
		$status = \UserRoleExpirationManager\Expiration::get_user_status( (int) $uid );
		if ( 'active' === $status ) {
			$stat_active++;
		} elseif ( 'expiring_soon' === $status ) {
			$stat_soon++;
		} elseif ( 'expired' === $status ) {
			$stat_expired++;
		}
	}
}

$check_update_url = wp_nonce_url( admin_url( 'admin-post.php?action=urem_force_check_update' ), 'urem_force_check_update_nonce', 'urem_nonce' );
?>

<div class="urem-admin-layout">
	<!-- MAIN COLUMN (Left - 70%) -->
	<div class="urem-main-content">

		<!-- Quick User Guide Banner -->
		<div class="urem-card urem-guide-card">
			<h2 style="display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-lightbulb" style="color: #2271b1;"></span>
				<?php esc_html_e( 'Panduan Ringkas Penggunaan Plugin', 'user-role-expiration-manager' ); ?>
			</h2>
			<p style="font-size: 13px; line-height: 1.6; color: #1d2327; margin-bottom: 10px;">
				Plugin ini mengelola masa berlaku role (peran) pengguna secara otomatis. Setelah durasi waktu habis sejak tanggal mulai, role pengguna akan otomatis berpindah ke role lain yang Anda tentukan (misalnya dari <em>Editor/Author</em> menjadi <em>Subscriber</em> atau <em>No Role</em>).
			</p>
			<ol style="margin-left: 20px; line-height: 1.8; color: #2c3338; margin-bottom: 0; font-size: 13px;">
				<li><strong>Langkah 1:</strong> Atur durasi default & role tujuan pada form di bawah ini lalu klik <em>Simpan Perubahan Pengaturan</em>.</li>
				<li><strong>Langkah 2:</strong> Pengaturan khusus per-pengguna dapat Anda sesuaikan kapan saja melalui menu <strong>Pengguna &rarr; Edit User</strong>.</li>
				<li><strong>Langkah 3:</strong> Pantau status pengguna (Aktif, Akan Expired, Expired) melalui tabel <strong>Pengguna &rarr; Semua Pengguna</strong> atau widget di <strong>Dashboard</strong>.</li>
			</ol>
		</div>

		<!-- Manual Scan Section -->
		<div class="urem-card">
			<h2>
				<span class="dashicons dashicons-search" style="vertical-align: middle; margin-right: 6px; color: #2271b1;"></span>
				<?php esc_html_e( 'Pemeriksaan Manual (Scan Sekarang)', 'user-role-expiration-manager' ); ?>
			</h2>
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e( 'Klik tombol di bawah untuk langsung memeriksa dan memproses role pengguna yang telah melewati tanggal expired saat ini juga tanpa menunggu jadwal otomatis WP-Cron.', 'user-role-expiration-manager' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'urem_manual_scan_nonce', 'urem_nonce' ); ?>
				<input type="hidden" name="action" value="urem_manual_scan">
				<button type="submit" class="button button-primary button-hero">
					<span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 6px;"></span>
					<?php esc_html_e( 'Scan Sekarang', 'user-role-expiration-manager' ); ?>
				</button>
			</form>
		</div>

		<!-- Global Settings Form -->
		<form method="post" action="options.php">
			<?php settings_fields( 'urem_settings_group' ); ?>

			<div class="urem-card">
				<h2>
					<span class="dashicons dashicons-admin-generic" style="vertical-align: middle; margin-right: 6px; color: #2271b1;"></span>
					<?php esc_html_e( 'Pengaturan Global Role Expiration', 'user-role-expiration-manager' ); ?>
				</h2>

				<table class="form-table" role="presentation">
					<tbody>
						<!-- Enable / Disable -->
						<tr>
							<th scope="row">
								<label for="urem_plugin_enabled"><?php esc_html_e( 'Status Fitur Expiration', 'user-role-expiration-manager' ); ?></label>
							</th>
							<td>
								<label for="urem_plugin_enabled">
									<input type="checkbox" id="urem_plugin_enabled" name="urem_settings[enabled]" value="1" <?php checked( $settings['enabled'], '1' ); ?>>
									<strong><?php esc_html_e( 'Aktifkan pemrosesan otomatis masa berlaku role pengguna.', 'user-role-expiration-manager' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'Jika di-uncheck, sistem tidak akan mengubah role pengguna secara otomatis.', 'user-role-expiration-manager' ); ?></p>
							</td>
						</tr>

						<!-- Default Duration & Unit & Presets -->
						<tr>
							<th scope="row">
								<label for="urem_default_duration"><?php esc_html_e( 'Default Durasi Masa Berlaku', 'user-role-expiration-manager' ); ?></label>
							</th>
							<td>
								<div style="margin-bottom: 8px;">
									<select class="urem-preset-selector" style="font-weight: 600; color: #2271b1;">
										<option value=""><?php esc_html_e( '⚡ Pilih Preset Cepat...', 'user-role-expiration-manager' ); ?></option>
										<?php foreach ( $presets as $preset_key => $preset_info ) : ?>
											<option value="<?php echo esc_attr( $preset_key ); ?>" data-duration="<?php echo esc_attr( (string) $preset_info['duration'] ); ?>" data-unit="<?php echo esc_attr( $preset_info['unit'] ); ?>">
												<?php echo esc_html( $preset_info['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<span class="description"><?php esc_html_e( '(Klik preset di atas untuk mengisi angka durasi secara otomatis)', 'user-role-expiration-manager' ); ?></span>
								</div>

								<input type="number" id="urem_default_duration" name="urem_settings[default_duration]" class="small-text urem-duration-input" min="1" value="<?php echo esc_attr( (string) $settings['default_duration'] ); ?>">
								<select name="urem_settings[default_unit]" id="urem_default_unit" class="urem-unit-select">
									<?php foreach ( $units as $unit_key => $unit_label ) : ?>
										<option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $settings['default_unit'], $unit_key ); ?>>
											<?php echo esc_html( $unit_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Durasi standar yang digunakan untuk setiap pengguna baru.', 'user-role-expiration-manager' ); ?></p>
							</td>
						</tr>

						<!-- Default Target Role -->
						<tr>
							<th scope="row">
								<label for="urem_default_role"><?php esc_html_e( 'Default Role Tujuan (Setelah Expired)', 'user-role-expiration-manager' ); ?></label>
							</th>
							<td>
								<select name="urem_settings[default_role]" id="urem_default_role">
									<?php foreach ( $roles as $role_key => $role_label ) : ?>
										<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $settings['default_role'], $role_key ); ?>>
											<?php echo esc_html( $role_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Peran baru yang akan diberikan kepada pengguna setelah masa berlakunya habis (misal Subscriber atau No Role).', 'user-role-expiration-manager' ); ?></p>
							</td>
						</tr>

						<!-- Cron Schedule -->
						<tr>
							<th scope="row">
								<label for="urem_cron_schedule"><?php esc_html_e( 'Jadwal Pemeriksaan Otomatis', 'user-role-expiration-manager' ); ?></label>
							</th>
							<td>
								<select name="urem_settings[cron_schedule]" id="urem_cron_schedule">
									<option value="hourly" <?php selected( $settings['cron_schedule'], 'hourly' ); ?>><?php esc_html_e( 'Hourly (Setiap Jam)', 'user-role-expiration-manager' ); ?></option>
									<option value="twicedaily" <?php selected( $settings['cron_schedule'], 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily (2x Sehari)', 'user-role-expiration-manager' ); ?></option>
									<option value="daily" <?php selected( $settings['cron_schedule'], 'daily' ); ?>><?php esc_html_e( 'Daily (1x Sehari - Direkomendasikan)', 'user-role-expiration-manager' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Frekuensi pemeriksaan otomatis oleh sistem di latar belakang.', 'user-role-expiration-manager' ); ?></p>
							</td>
						</tr>

						<!-- Logout Session -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Opsi Logout Otomatis', 'user-role-expiration-manager' ); ?></th>
							<td>
								<label for="urem_logout_user_on_expire">
									<input type="checkbox" id="urem_logout_user_on_expire" name="urem_settings[logout_user_on_expire]" value="1" <?php checked( $settings['logout_user_on_expire'], '1' ); ?>>
									<?php esc_html_e( 'Otomatis keluarkan (logout) sesi login pengguna ketika role-nya berubah.', 'user-role-expiration-manager' ); ?>
								</label>
							</td>
						</tr>

						<!-- Dry Run Mode -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Mode Simulasi (Dry Run)', 'user-role-expiration-manager' ); ?></th>
							<td>
								<label for="urem_dry_run_mode">
									<input type="checkbox" id="urem_dry_run_mode" name="urem_settings[dry_run_mode]" value="1" <?php checked( $settings['dry_run_mode'], '1' ); ?>>
									<?php esc_html_e( 'Aktifkan Mode Simulasi: Catat aktivitas di log tanpa benar-benar mengubah role pengguna.', 'user-role-expiration-manager' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Email Notification Templates Card -->
			<div class="urem-card">
				<h2>
					<span class="dashicons dashicons-email-alt" style="vertical-align: middle; margin-right: 6px; color: #2271b1;"></span>
					<?php esc_html_e( 'Template Email Notifikasi & Pengingat', 'user-role-expiration-manager' ); ?>
				</h2>
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'Gunakan placeholder berikut untuk mengkustomisasi pesan email: {user_name}, {old_role}, {new_role}, {site_name}, {expiration_date}, {days_left}.', 'user-role-expiration-manager' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<!-- Send Email on Expire -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Notifikasi Email Setelah Expired', 'user-role-expiration-manager' ); ?></th>
							<td>
								<label for="urem_send_email_on_expire">
									<input type="checkbox" id="urem_send_email_on_expire" name="urem_settings[send_email_on_expire]" value="1" <?php checked( $settings['send_email_on_expire'], '1' ); ?>>
									<strong><?php esc_html_e( 'Kirim email pemberitahuan ke pengguna ketika role-nya berhasil diubah/expired.', 'user-role-expiration-manager' ); ?></strong>
								</label>
							</td>
						</tr>

						<!-- Email Subject -->
						<tr>
							<th scope="row"><label for="urem_email_subject"><?php esc_html_e( 'Subjek Email Expired', 'user-role-expiration-manager' ); ?></label></th>
							<td>
								<input type="text" id="urem_email_subject" name="urem_settings[email_subject]" class="large-text" value="<?php echo esc_attr( $settings['email_subject'] ); ?>">
							</td>
						</tr>

						<!-- Email Message Body -->
						<tr>
							<th scope="row"><label for="urem_email_message"><?php esc_html_e( 'Isi Pesan Email Expired', 'user-role-expiration-manager' ); ?></label></th>
							<td>
								<textarea id="urem_email_message" name="urem_settings[email_message]" rows="5" class="large-text code"><?php echo esc_textarea( $settings['email_message'] ); ?></textarea>
							</td>
						</tr>

						<!-- Send Reminder Email -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Pengingat Sebelum Expired', 'user-role-expiration-manager' ); ?></th>
							<td>
								<label for="urem_send_reminder_email">
									<input type="checkbox" id="urem_send_reminder_email" name="urem_settings[send_reminder_email]" value="1" <?php checked( $settings['send_reminder_email'], '1' ); ?>>
									<strong><?php esc_html_e( 'Kirim email peringatan otomatis SEBELUM masa berlaku pengguna habis.', 'user-role-expiration-manager' ); ?></strong>
								</label>
								<div style="margin-top: 10px;">
									<label for="urem_reminder_days_before">
										<?php esc_html_e( 'Kirim email pengingat ', 'user-role-expiration-manager' ); ?>
										<input type="number" id="urem_reminder_days_before" name="urem_settings[reminder_days_before]" class="small-text" min="1" max="30" value="<?php echo esc_attr( (string) $settings['reminder_days_before'] ); ?>">
										<?php esc_html_e( ' hari sebelum tanggal expired.', 'user-role-expiration-manager' ); ?>
									</label>
								</div>
							</td>
						</tr>

						<!-- Reminder Subject -->
						<tr>
							<th scope="row"><label for="urem_reminder_subject"><?php esc_html_e( 'Subjek Email Pengingat', 'user-role-expiration-manager' ); ?></label></th>
							<td>
								<input type="text" id="urem_reminder_subject" name="urem_settings[reminder_subject]" class="large-text" value="<?php echo esc_attr( $settings['reminder_subject'] ); ?>">
							</td>
						</tr>

						<!-- Reminder Message Body -->
						<tr>
							<th scope="row"><label for="urem_reminder_message"><?php esc_html_e( 'Isi Pesan Email Pengingat', 'user-role-expiration-manager' ); ?></label></th>
							<td>
								<textarea id="urem_reminder_message" name="urem_settings[reminder_message]" rows="5" class="large-text code"><?php echo esc_textarea( $settings['reminder_message'] ); ?></textarea>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Logging & Auto-Prune Card -->
			<div class="urem-card">
				<h2>
					<span class="dashicons dashicons-database" style="vertical-align: middle; margin-right: 6px; color: #2271b1;"></span>
					<?php esc_html_e( 'Pencatatan Log & Pembersihan Otomatis', 'user-role-expiration-manager' ); ?>
				</h2>

				<table class="form-table" role="presentation">
					<tbody>
						<!-- Logging -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Catatan Log Database', 'user-role-expiration-manager' ); ?></th>
							<td>
								<label for="urem_enable_logging">
									<input type="checkbox" id="urem_enable_logging" name="urem_settings[enable_logging]" value="1" <?php checked( $settings['enable_logging'], '1' ); ?>>
									<?php esc_html_e( 'Simpan semua riwayat perubahan role pengguna ke dalam log database.', 'user-role-expiration-manager' ); ?>
								</label>
							</td>
						</tr>

						<!-- Auto-Prune Logs Retention Days -->
						<tr>
							<th scope="row">
								<label for="urem_log_retention_days"><?php esc_html_e( 'Retensi Log (Pembersihan Otomatis)', 'user-role-expiration-manager' ); ?></label>
							</th>
							<td>
								<input type="number" id="urem_log_retention_days" name="urem_settings[log_retention_days]" class="small-text" min="0" max="365" value="<?php echo esc_attr( (string) $settings['log_retention_days'] ); ?>">
								<?php esc_html_e( 'Hari', 'user-role-expiration-manager' ); ?>
								<p class="description"><?php esc_html_e( 'Hapus log catatan secara otomatis yang lebih lama dari jumlah hari ini (Isikan 90 hari atau 0 jika tidak ingin menghapus otomatis).', 'user-role-expiration-manager' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Simpan Perubahan Pengaturan', 'user-role-expiration-manager' ) ); ?>
			</div>
		</form>

	</div>

	<!-- SIDEBAR COLUMN (Right - 30% / 320px) -->
	<div class="urem-sidebar-content">

		<!-- Plugin Info Card -->
		<div class="urem-sidebar-card">
			<h3>
				<span class="dashicons dashicons-info" style="color: #2271b1;"></span>
				<?php esc_html_e( 'Informasi Plugin', 'user-role-expiration-manager' ); ?>
			</h3>
			<ul class="urem-info-list">
				<li>
					<span class="label"><?php esc_html_e( 'Nama Plugin', 'user-role-expiration-manager' ); ?>:</span>
					<span class="value">User Role Expiration</span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Versi Terpasang', 'user-role-expiration-manager' ); ?>:</span>
					<span class="value" style="color: #2271b1; font-weight: 700;">v<?php echo esc_html( UREM_VERSION ); ?></span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Author', 'user-role-expiration-manager' ); ?>:</span>
					<span class="value"><a href="https://it.telkomuniversity.ac.id/" target="_blank">Mujaddid Halimurrosyid</a></span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Lisensi', 'user-role-expiration-manager' ); ?>:</span>
					<span class="value">GPL v2 or later</span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'GitHub Repo', 'user-role-expiration-manager' ); ?>:</span>
					<span class="value"><a href="https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan" target="_blank">Repository &rarr;</a></span>
				</li>
			</ul>
		</div>

		<!-- Overview Quick Stats Card -->
		<div class="urem-sidebar-card">
			<h3>
				<span class="dashicons dashicons-chart-pie" style="color: #2271b1;"></span>
				<?php esc_html_e( 'Ringkasan Expiration', 'user-role-expiration-manager' ); ?>
			</h3>
			<ul class="urem-info-list">
				<li>
					<span class="label"><?php esc_html_e( 'Total Pengguna Dikelola', 'user-role-expiration-manager' ); ?>:</span>
					<span class="value"><strong><?php echo esc_html( (string) $stat_total ); ?></strong></span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Status Aktif', 'user-role-expiration-manager' ); ?>:</span>
					<span class="urem-badge urem-badge-green"><?php echo esc_html( (string) $stat_active ); ?> User</span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Akan Expired (<30 Hari)', 'user-role-expiration-manager' ); ?>:</span>
					<span class="urem-badge urem-badge-yellow"><?php echo esc_html( (string) $stat_soon ); ?> User</span>
				</li>
				<li>
					<span class="label"><?php esc_html_e( 'Status Expired', 'user-role-expiration-manager' ); ?>:</span>
					<span class="urem-badge urem-badge-red"><?php echo esc_html( (string) $stat_expired ); ?> User</span>
				</li>
			</ul>
		</div>

		<!-- Quick Actions & Shortcuts Card -->
		<div class="urem-sidebar-card">
			<h3>
				<span class="dashicons dashicons-admin-links" style="color: #2271b1;"></span>
				<?php esc_html_e( 'Akses Pintas', 'user-role-expiration-manager' ); ?>
			</h3>
			<div class="urem-quick-actions">
				<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" class="button">
					<span class="dashicons dashicons-admin-users"></span>
					<?php esc_html_e( 'Kelola Semua Pengguna', 'user-role-expiration-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'users.php?page=user-role-expiration-manager&tab=logs' ) ); ?>" class="button">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Lihat Log Catatan', 'user-role-expiration-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( $check_update_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Cek Update GitHub', 'user-role-expiration-manager' ); ?>
				</a>
			</div>
		</div>

	</div>
</div>
