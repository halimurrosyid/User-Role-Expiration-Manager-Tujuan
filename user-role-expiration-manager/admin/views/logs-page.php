<?php
/**
 * Logs Page View Template.
 *
 * @package UserRoleExpirationManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use UserRoleExpirationManager\Logger;

$page_num = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 20;
$offset   = ( $page_num - 1 ) * $per_page;
$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$logs        = Logger::get_logs( $per_page, $offset, $search );
$total_logs  = Logger::get_logs_count( $search );
$total_pages = ceil( $total_logs / $per_page );

$clear_url  = wp_nonce_url( admin_url( 'admin-post.php?action=urem_clear_logs' ), 'urem_clear_logs_nonce', 'urem_nonce' );
$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=urem_export_logs' ), 'urem_export_logs_nonce', 'urem_nonce' );
?>

<div class="urem-logs-container">
	<div class="tablenav top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
		<div class="actions alignleft">
			<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 4px;"></span>
				<?php esc_html_e( 'Ekspor CSV', 'user-role-expiration-manager' ); ?>
			</a>

			<a href="<?php echo esc_url( $clear_url ); ?>" class="button button-link-delete urem-clear-logs-btn" style="margin-left: 10px;">
				<span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 4px;"></span>
				<?php esc_html_e( 'Hapus Log', 'user-role-expiration-manager' ); ?>
			</a>
		</div>

		<form method="get" style="display: flex; gap: 6px;">
			<input type="hidden" name="page" value="user-role-expiration-manager">
			<input type="hidden" name="tab" value="logs">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Cari log...', 'user-role-expiration-manager' ); ?>">
			<button type="submit" class="button"><?php esc_html_e( 'Cari', 'user-role-expiration-manager' ); ?></button>
		</form>
	</div>

	<table class="wp-list-table widefat fixed striped table-view-list">
		<thead>
			<tr>
				<th scope="col" style="width: 150px;"><?php esc_html_e( 'Tanggal', 'user-role-expiration-manager' ); ?></th>
				<th scope="col" style="width: 180px;"><?php esc_html_e( 'Pengguna', 'user-role-expiration-manager' ); ?></th>
				<th scope="col" style="width: 110px;"><?php esc_html_e( 'Role Lama', 'user-role-expiration-manager' ); ?></th>
				<th scope="col" style="width: 110px;"><?php esc_html_e( 'Role Baru', 'user-role-expiration-manager' ); ?></th>
				<th scope="col" style="width: 110px;"><?php esc_html_e( 'Pemicu', 'user-role-expiration-manager' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Alasan / Catatan', 'user-role-expiration-manager' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'Belum ada log catatan.', 'user-role-expiration-manager' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( urem_format_datetime( $log->created_at ) ); ?></td>
						<td>
							<strong><?php echo esc_html( $log->user_email ); ?></strong><br>
							<small>ID: <?php echo esc_html( (string) $log->user_id ); ?></small>
						</td>
						<td><code><?php echo esc_html( $log->old_role ); ?></code></td>
						<td><code><?php echo esc_html( $log->new_role ); ?></code></td>
						<td><span class="urem-trigger-tag"><?php echo esc_html( $log->trigger_type ); ?></span></td>
						<td><?php echo esc_html( $log->reason ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php printf( esc_html__( '%d item', 'user-role-expiration-manager' ), (int) $total_logs ); ?></span>
				<span class="pagination-links">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $page_num,
							)
						)
					);
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>
</div>
