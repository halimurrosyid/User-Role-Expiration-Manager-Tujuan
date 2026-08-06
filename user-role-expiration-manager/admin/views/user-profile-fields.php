<?php
/**
 * User Profile Fields View Template.
 *
 * @package UserRoleExpirationManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reset_url  = wp_nonce_url( admin_url( 'admin-post.php?action=urem_reset_user_start_date&user_id=' . $user->ID ), 'urem_reset_start_date_' . $user->ID, 'urem_nonce' );
$expire_url = wp_nonce_url( admin_url( 'admin-post.php?action=urem_expire_user_now&user_id=' . $user->ID ), 'urem_expire_now_' . $user->ID, 'urem_nonce' );
$presets    = urem_get_duration_presets();

// Format start date for datetime-local input
$formatted_start = '';
if ( ! empty( $data['start'] ) ) {
	$ts = is_numeric( $data['start'] ) ? (int) $data['start'] : strtotime( $data['start'] );
	if ( $ts ) {
		$formatted_start = wp_date( 'Y-m-d\TH:i', $ts );
	}
}
if ( empty( $formatted_start ) ) {
	$formatted_start = wp_date( 'Y-m-d\TH:i', current_time( 'timestamp' ) );
}
?>

<div class="urem-user-profile-section card" style="max-width: 800px; margin-top: 20px; padding: 15px 20px;">
	<h2>
		<span class="dashicons dashicons-clock" style="vertical-align: middle; margin-right: 6px;"></span>
		<?php esc_html_e( 'Role Expiration', 'user-role-expiration-manager' ); ?>
	</h2>

	<?php wp_nonce_field( 'urem_save_user_profile', 'urem_user_profile_nonce' ); ?>

	<table class="form-table" role="presentation">
		<tbody>
			<!-- Enable Expiration -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Expiration', 'user-role-expiration-manager' ); ?></th>
				<td>
					<label for="urem_expiration_enabled">
						<input type="checkbox" id="urem_expiration_enabled" name="urem_expiration_enabled" value="1" <?php checked( $data['enabled'] ); ?>>
						<strong><?php esc_html_e( 'Aktifkan penentuan masa berlaku role untuk pengguna ini', 'user-role-expiration-manager' ); ?></strong>
					</label>
				</td>
			</tr>

			<!-- Tanggal Mulai (Calendar Date Picker) -->
			<tr>
				<th scope="row">
					<label for="urem_expiration_start"><?php esc_html_e( 'Tanggal Mulai', 'user-role-expiration-manager' ); ?></label>
				</th>
				<td>
					<input type="datetime-local" id="urem_expiration_start" name="urem_expiration_start" class="regular-text" value="<?php echo esc_attr( $formatted_start ); ?>">
					<p class="description"><?php esc_html_e( 'Klik icon kalender untuk memilih tanggal & jam awal perhitungan secara visual.', 'user-role-expiration-manager' ); ?></p>
				</td>
			</tr>

			<!-- Durasi & Satuan & Presets -->
			<tr>
				<th scope="row">
					<label for="urem_expiration_duration"><?php esc_html_e( 'Durasi & Satuan', 'user-role-expiration-manager' ); ?></label>
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
					</div>

					<input type="number" id="urem_expiration_duration" name="urem_expiration_duration" class="small-text urem-duration-input" min="1" value="<?php echo esc_attr( (string) $data['duration'] ); ?>">
					<select name="urem_expiration_unit" id="urem_expiration_unit" class="urem-unit-select">
						<?php foreach ( $units as $unit_key => $unit_label ) : ?>
							<option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $data['unit'], $unit_key ); ?>>
								<?php echo esc_html( $unit_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<!-- Role Setelah Expired -->
			<tr>
				<th scope="row">
					<label for="urem_expiration_role"><?php esc_html_e( 'Role Setelah Expired', 'user-role-expiration-manager' ); ?></label>
				</th>
				<td>
					<select name="urem_expiration_role" id="urem_expiration_role">
						<?php foreach ( $all_roles as $role_key => $role_label ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $data['role'], $role_key ); ?>>
								<?php echo esc_html( $role_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Peran baru pengguna setelah masa berlaku habis.', 'user-role-expiration-manager' ); ?></p>
				</td>
			</tr>

			<!-- Status Information -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Status Expiration', 'user-role-expiration-manager' ); ?></th>
				<td>
					<?php echo urem_get_status_badge_html( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>

			<!-- Tanggal Expired -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Tanggal Expired', 'user-role-expiration-manager' ); ?></th>
				<td>
					<strong><?php echo esc_html( urem_format_datetime( $exp_ts ) ); ?></strong>
				</td>
			</tr>

			<!-- Sisa Hari -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Sisa Hari', 'user-role-expiration-manager' ); ?></th>
				<td>
					<?php
					if ( null === $remaining_days ) {
						echo '-';
					} elseif ( $remaining_days < 0 ) {
						printf( '<span style="color: #d63638; font-weight: 600;">%s (%d hari lalu)</span>', esc_html__( 'Expired', 'user-role-expiration-manager' ), abs( $remaining_days ) );
					} else {
						printf( '<strong>%d hari</strong>', (int) $remaining_days );
					}
					?>
				</td>
			</tr>

			<!-- Action Buttons -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Tindakan Manual', 'user-role-expiration-manager' ); ?></th>
				<td>
					<a href="<?php echo esc_url( $reset_url ); ?>" class="button urem-reset-start-date-btn">
						<span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 4px;"></span>
						<?php esc_html_e( 'Reset Tanggal Mulai ke Sekarang', 'user-role-expiration-manager' ); ?>
					</a>

					<a href="<?php echo esc_url( $expire_url ); ?>" class="button button-link-delete urem-expire-now-btn" style="margin-left: 10px;">
						<span class="dashicons dashicons-dismiss" style="vertical-align: middle; margin-right: 4px;"></span>
						<?php esc_html_e( 'Expire Sekarang', 'user-role-expiration-manager' ); ?>
					</a>
				</td>
			</tr>
		</tbody>
	</table>
</div>
