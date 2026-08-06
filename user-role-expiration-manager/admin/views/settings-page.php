<?php
/**
 * Settings & Manual Scan View Template.
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
?>

<div class="urem-settings-container">
	<!-- Help & Guide Banner -->
	<div class="card" style="max-width: 1000px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #2271b1; background: #f0f6fc;">
		<h2 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
			<span class="dashicons dashicons-lightbulb" style="color: #2271b1;"></span>
			<?php esc_html_e( 'Panduan Ringkas Penggunaan Plugin', 'user-role-expiration-manager' ); ?>
		</h2>
		<p style="font-size: 14px; line-height: 1.6; color: #1d2327;">
			Plugin ini mengelola masa berlaku role (peran) pengguna secara otomatis. Setelah durasi waktu habis sejak tanggal mulai, role pengguna akan otomatis berpindah ke role lain yang Anda tentukan (misalnya dari <em>Administrator/Editor</em> menjadi <em>Subscriber</em> atau <em>No Role</em>).
		</p>
		<ol style="margin-left: 20px; line-height: 1.8; color: #2c3338;">
			<li><strong>Langkah 1:</strong> Atur durasi default & role tujuan pada form di bawah ini lalu klik <em>Simpan Perubahan</em>.</li>
			<li><strong>Langkah 2:</strong> Pengaturan khusus per-pengguna dapat Anda sesuaikan kapan saja melalui menu <strong>Pengguna &rarr; Edit User</strong>.</li>
			<li><strong>Langkah 3:</strong> Pantau status pengguna (Aktif, Akan Expired, Expired) melalui tabel <strong>Pengguna &rarr; Semua Pengguna</strong> atau widget di <strong>Dashboard</strong>.</li>
		</ol>
	</div>

	<!-- Manual Scan Section -->
	<div class="card" style="max-width: 1000px; padding: 20px; margin-bottom: 25px;">
		<h2>
			<span class="dashicons dashicons-search" style="vertical-align: middle; margin-right: 6px;"></span>
			<?php esc_html_e( 'Pemeriksaan Manual (Scan Sekarang)', 'user-role-expiration-manager' ); ?>
		</h2>
		<p class="description">
			<?php esc_html_e( 'Klik tombol di bawah untuk langsung memeriksa dan memproses role pengguna yang telah melewati tanggal expired saat ini juga tanpa menunggu jadwal otomatis WP-Cron.', 'user-role-expiration-manager' ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'urem_manual_scan_nonce', 'urem_nonce' ); ?>
			<input type="hidden" name="action" value="urem_manual_scan">
			<p>
				<button type="submit" class="button button-primary button-hero">
					<span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 6px;"></span>
					<?php esc_html_e( 'Scan Sekarang', 'user-role-expiration-manager' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Global Settings Form -->
	<form method="post" action="options.php" style="max-width: 1000px;">
		<?php
		settings_fields( 'urem_settings_group' );
		?>

		<div class="card" style="padding: 20px;">
			<h2>
				<span class="dashicons dashicons-admin-generic" style="vertical-align: middle; margin-right: 6px;"></span>
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
								<input type="checkbox" id="urem_plugin_enabled" name="urem_settings[plugin_enabled]" value="1" <?php checked( $settings['plugin_enabled'], '1' ); ?>>
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

					<!-- Send Email -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Notifikasi Email', 'user-role-expiration-manager' ); ?></th>
						<td>
							<label for="urem_send_email_on_expire">
								<input type="checkbox" id="urem_send_email_on_expire" name="urem_settings[send_email_on_expire]" value="1" <?php checked( $settings['send_email_on_expire'], '1' ); ?>>
								<?php esc_html_e( 'Kirim email pemberitahuan ke pengguna ketika role-nya berhasil diubah.', 'user-role-expiration-manager' ); ?>
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
				</tbody>
			</table>

			<?php submit_button( __( 'Simpan Perubahan Pengaturan', 'user-role-expiration-manager' ) ); ?>
		</div>
	</form>
</div>
