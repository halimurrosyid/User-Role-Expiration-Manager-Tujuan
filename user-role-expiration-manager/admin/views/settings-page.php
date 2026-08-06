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
	<div class="card" style="max-width: 1000px; padding: 20px; margin-bottom: 25px;">
		<h2><?php esc_html_e( 'Manual Role Expiration Scan', 'user-role-expiration-manager' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Jalankan pemeriksaan dan pemrosesan role pengguna yang telah melewati tanggal expired secara langsung tanpa harus menunggu jadwal WP-Cron.', 'user-role-expiration-manager' ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'urem_manual_scan_nonce', 'urem_nonce' ); ?>
			<input type="hidden" name="action" value="urem_manual_scan">
			<p>
				<button type="submit" class="button button-primary button-hero">
					<span class="dashicons dashicons-search" style="vertical-align: middle; margin-right: 6px;"></span>
					<?php esc_html_e( 'Scan Sekarang', 'user-role-expiration-manager' ); ?>
				</button>
			</p>
		</form>
	</div>

	<form method="post" action="options.php" style="max-width: 1000px;">
		<?php
		settings_fields( 'urem_settings_group' );
		?>

		<div class="card" style="padding: 20px;">
			<h2><?php esc_html_e( 'Pengaturan Global Role Expiration', 'user-role-expiration-manager' ); ?></h2>

			<table class="form-table" role="presentation">
				<tbody>
					<!-- Enable / Disable -->
					<tr>
						<th scope="row">
							<label for="urem_plugin_enabled"><?php esc_html_e( 'Enable Plugin', 'user-role-expiration-manager' ); ?></label>
						</th>
						<td>
							<label for="urem_plugin_enabled">
								<input type="checkbox" id="urem_plugin_enabled" name="urem_settings[plugin_enabled]" value="1" <?php checked( $settings['plugin_enabled'], '1' ); ?>>
								<?php esc_html_e( 'Aktifkan pemrosesan otomatis masa berlaku role pengguna.', 'user-role-expiration-manager' ); ?>
							</label>
						</td>
					</tr>

					<!-- Default Duration & Unit & Presets -->
					<tr>
						<th scope="row">
							<label for="urem_default_duration"><?php esc_html_e( 'Default Expiration Duration', 'user-role-expiration-manager' ); ?></label>
						</th>
						<td>
							<div style="margin-bottom: 8px;">
								<select class="urem-preset-selector">
									<option value=""><?php esc_html_e( '⚡ Pilih Preset Cepat...', 'user-role-expiration-manager' ); ?></option>
									<?php foreach ( $presets as $preset_key => $preset_info ) : ?>
										<option value="<?php echo esc_attr( $preset_key ); ?>" data-duration="<?php echo esc_attr( (string) $preset_info['duration'] ); ?>" data-unit="<?php echo esc_attr( $preset_info['unit'] ); ?>">
											<?php echo esc_html( $preset_info['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<span class="description"><?php esc_html_e( '(Klik preset untuk mengisi durasi secara otomatis)', 'user-role-expiration-manager' ); ?></span>
							</div>

							<input type="number" id="urem_default_duration" name="urem_settings[default_duration]" class="small-text urem-duration-input" min="1" value="<?php echo esc_attr( (string) $settings['default_duration'] ); ?>">
							<select name="urem_settings[default_unit]" id="urem_default_unit" class="urem-unit-select">
								<?php foreach ( $units as $unit_key => $unit_label ) : ?>
									<option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $settings['default_unit'], $unit_key ); ?>>
										<?php echo esc_html( $unit_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Durasi default yang akan digunakan jika pengguna belum memiliki durasi kustom.', 'user-role-expiration-manager' ); ?></p>
						</td>
					</tr>

					<!-- Default Target Role -->
					<tr>
						<th scope="row">
							<label for="urem_default_role"><?php esc_html_e( 'Default Role Setelah Expired', 'user-role-expiration-manager' ); ?></label>
						</th>
						<td>
							<select name="urem_settings[default_role]" id="urem_default_role">
								<?php foreach ( $roles as $role_key => $role_label ) : ?>
									<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $settings['default_role'], $role_key ); ?>>
										<?php echo esc_html( $role_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Role tujuan setelah masa berlaku pengguna habis. Mengambil seluruh role bawaan dan custom role dari WordPress API.', 'user-role-expiration-manager' ); ?></p>
						</td>
					</tr>

					<!-- Cron Schedule -->
					<tr>
						<th scope="row">
							<label for="urem_cron_schedule"><?php esc_html_e( 'Jadwal WP-Cron', 'user-role-expiration-manager' ); ?></label>
						</th>
						<td>
							<select name="urem_settings[cron_schedule]" id="urem_cron_schedule">
								<option value="hourly" <?php selected( $settings['cron_schedule'], 'hourly' ); ?>><?php esc_html_e( 'Hourly (Setiap Jam)', 'user-role-expiration-manager' ); ?></option>
								<option value="twicedaily" <?php selected( $settings['cron_schedule'], 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily (2x Sehari)', 'user-role-expiration-manager' ); ?></option>
								<option value="daily" <?php selected( $settings['cron_schedule'], 'daily' ); ?>><?php esc_html_e( 'Daily (1x Sehari)', 'user-role-expiration-manager' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Frekuensi eksekusi pemrosesan latar belakang oleh WP-Cron.', 'user-role-expiration-manager' ); ?></p>
						</td>
					</tr>

					<!-- Logout Session -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Session Logout', 'user-role-expiration-manager' ); ?></th>
						<td>
							<label for="urem_logout_user_on_expire">
								<input type="checkbox" id="urem_logout_user_on_expire" name="urem_settings[logout_user_on_expire]" value="1" <?php checked( $settings['logout_user_on_expire'], '1' ); ?>>
								<?php esc_html_e( 'Otomatis logout sesi login pengguna setelah role-nya berubah.', 'user-role-expiration-manager' ); ?>
							</label>
						</td>
					</tr>

					<!-- Send Email -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Notifikasi Email', 'user-role-expiration-manager' ); ?></th>
						<td>
							<label for="urem_send_email_on_expire">
								<input type="checkbox" id="urem_send_email_on_expire" name="urem_settings[send_email_on_expire]" value="1" <?php checked( $settings['send_email_on_expire'], '1' ); ?>>
								<?php esc_html_e( 'Kirim email pemberitahuan ke pengguna setelah role berubah.', 'user-role-expiration-manager' ); ?>
							</label>
						</td>
					</tr>

					<!-- Dry Run Mode -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Dry Run Mode', 'user-role-expiration-manager' ); ?></th>
						<td>
							<label for="urem_dry_run_mode">
								<input type="checkbox" id="urem_dry_run_mode" name="urem_settings[dry_run_mode]" value="1" <?php checked( $settings['dry_run_mode'], '1' ); ?>>
								<?php esc_html_e( 'Mode Simulasi (Dry Run): Catat log perubahan role tanpa benar-benar mengubah role pengguna.', 'user-role-expiration-manager' ); ?>
							</label>
						</td>
					</tr>

					<!-- Logging -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Pencatatan Log', 'user-role-expiration-manager' ); ?></th>
						<td>
							<label for="urem_enable_logging">
								<input type="checkbox" id="urem_enable_logging" name="urem_settings[enable_logging]" value="1" <?php checked( $settings['enable_logging'], '1' ); ?>>
								<?php esc_html_e( 'Simpan semua aktivitas perubahan role dan reset dalam log database.', 'user-role-expiration-manager' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Simpan Perubahan', 'user-role-expiration-manager' ) ); ?>
		</div>
	</form>
</div>
