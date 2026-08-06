<?php
/**
 * Expiration Logs View Template (Full Width Responsive).
 *
 * @package UserRoleExpirationManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Search & Pagination handling
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

$logs_data = \UserRoleExpirationManager\Logger::get_logs( $search, $paged, 20 );
$logs      = $logs_data['items'];
$total     = $logs_data['total'];
$max_pages = ceil( $total / 20 );

$clear_url  = wp_nonce_url( admin_url( 'admin-post.php?action=urem_clear_logs' ), 'urem_clear_logs_nonce', 'urem_nonce' );
$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=urem_export_logs' ), 'urem_export_logs_nonce', 'urem_nonce' );
?>

<div class="urem-logs-container" style="margin-top: 15px;">
	<div class="urem-card" style="padding: 20px;">
		<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
			<h2 style="margin: 0; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-list-view" style="color: #2271b1;"></span>
				<?php esc_html_e( 'Log Catatan Expiration & Perubahan Role', 'user-role-expiration-manager' ); ?>
			</h2>

			<div style="display: flex; gap: 10px; align-items: center;">
				<!-- Export CSV Button -->
				<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Ekspor CSV', 'user-role-expiration-manager' ); ?>
				</a>

				<!-- Clear Logs Button -->
				<a href="<?php echo esc_url( $clear_url ); ?>" class="button button-link-delete urem-clear-logs-btn">
					<span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Hapus Semua Log', 'user-role-expiration-manager' ); ?>
				</a>
			</div>
		</div>

		<!-- Search Form -->
		<form method="get" style="margin-bottom: 20px;">
			<input type="hidden" name="page" value="user-role-expiration-manager">
			<input type="hidden" name="tab" value="logs">
			<p class="search-box" style="float: none; margin-bottom: 15px; display: flex; gap: 8px;">
				<input type="search" id="urem-log-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Cari email, ID, atau trigger..." style="min-width: 280px;">
				<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Cari Log', 'user-role-expiration-manager' ); ?>">
				<?php if ( ! empty( $search ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'users.php?page=user-role-expiration-manager&tab=logs' ) ); ?>" class="button"><?php esc_html_e( 'Reset Pencarian', 'user-role-expiration-manager' ); ?></a>
				<?php endif; ?>
			</p>
		</form>

		<!-- Logs Table -->
		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th scope="col" style="width: 70px;"><?php esc_html_e( 'ID Log', 'user-role-expiration-manager' ); ?></th>
					<th scope="col" style="width: 80px;"><?php esc_html_e( 'ID User', 'user-role-expiration-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Email Pengguna', 'user-role-expiration-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Role Lama', 'user-role-expiration-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Role Baru', 'user-role-expiration-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Trigger', 'user-role-expiration-manager' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Keterangan', 'user-role-expiration-manager' ); ?></th>
					<th scope="col" style="width: 160px;"><?php esc_html_e( 'Waktu Disimpan', 'user-role-expiration-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr>
						<td colspan="8" style="text-align: center; padding: 20px; color: #646970;">
							<?php esc_html_e( 'Belum ada log catatan yang tersimpan.', 'user-role-expiration-manager' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td>#<?php echo esc_html( (string) $log->id ); ?></td>
							<td><?php echo esc_html( (string) $log->user_id ); ?></td>
							<td><strong><?php echo esc_html( $log->user_email ); ?></strong></td>
							<td><span class="urem-badge urem-badge-gray"><?php echo esc_html( $log->old_role ? $log->old_role : '-' ); ?></span></td>
							<td><span class="urem-badge urem-badge-green"><?php echo esc_html( $log->new_role ? $log->new_role : '-' ); ?></span></td>
							<td><code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?php echo esc_html( $log->trigger_type ); ?></code></td>
							<td><?php echo esc_html( $log->reason ); ?></td>
							<td><?php echo esc_html( urem_format_datetime( $log->created_at ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Pagination Links -->
		<?php if ( $max_pages > 1 ) : ?>
			<div class="tablenav bottom" style="margin-top: 15px;">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php printf( esc_html__( '%d log ditemukan', 'user-role-expiration-manager' ), (int) $total ); ?></span>
					<?php
					echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo; Sebelumnya', 'user-role-expiration-manager' ),
							'next_text' => __( 'Selanjutnya &raquo;', 'user-role-expiration-manager' ),
							'total'     => $max_pages,
							'current'   => $paged,
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>

	</div>
</div>
