<?php
/**
 * Dashboard Widget Class.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dashboard_Widget
 */
class Dashboard_Widget {

	/**
	 * Register widget hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widget' ) );
	}

	/**
	 * Add dashboard widget.
	 *
	 * @return void
	 */
	public static function add_dashboard_widget(): void {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'urem_status_dashboard_widget',
			__( 'User Role Expiration Overview', 'user-role-expiration-manager' ),
			array( __CLASS__, 'render_widget' )
		);
	}

	/**
	 * Render dashboard widget content.
	 *
	 * @return void
	 */
	public static function render_widget(): void {
		// Calculate stats efficiently
		$enabled_users = new \WP_User_Query(
			array(
				'fields'     => 'ID',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => Expiration::META_ENABLED,
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);

		$user_ids = $enabled_users->get_results();
		$total   = count( $user_ids );

		$active_count        = 0;
		$expiring_soon_count = 0;
		$expired_count       = 0;

		foreach ( $user_ids as $uid ) {
			$uid    = (int) $uid;
			$status = Expiration::get_user_status( $uid );

			if ( 'active' === $status ) {
				$active_count++;
			} elseif ( 'expiring_soon' === $status ) {
				$expiring_soon_count++;
			} elseif ( 'expired' === $status ) {
				$expired_count++;
			}
		}

		$manage_url = admin_url( 'users.php?page=user-role-expiration-manager' );
		$all_users_url = admin_url( 'users.php' );
		?>
		<div class="urem-dashboard-widget-container">
			<ul class="urem-widget-stats-list">
				<li class="urem-stat-item">
					<span class="urem-stat-number"><?php echo esc_html( (string) $total ); ?></span>
					<span class="urem-stat-label"><?php esc_html_e( 'Total User Dipantau', 'user-role-expiration-manager' ); ?></span>
				</li>
				<li class="urem-stat-item urem-stat-green">
					<span class="urem-stat-number"><?php echo esc_html( (string) $active_count ); ?></span>
					<span class="urem-stat-label"><?php esc_html_e( 'Aktif', 'user-role-expiration-manager' ); ?></span>
				</li>
				<li class="urem-stat-item urem-stat-yellow">
					<span class="urem-stat-number"><?php echo esc_html( (string) $expiring_soon_count ); ?></span>
					<span class="urem-stat-label"><?php esc_html_e( 'Akan Expired (<30 Hari)', 'user-role-expiration-manager' ); ?></span>
				</li>
				<li class="urem-stat-item urem-stat-red">
					<span class="urem-stat-number"><?php echo esc_html( (string) $expired_count ); ?></span>
					<span class="urem-stat-label"><?php esc_html_e( 'Expired', 'user-role-expiration-manager' ); ?></span>
				</li>
			</ul>
			<hr>
			<div class="urem-widget-actions">
				<a href="<?php echo esc_url( $manage_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-admin-generic" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Kelola Role Expiration', 'user-role-expiration-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'urem_status', 'expiring_soon', $all_users_url ) ); ?>" class="button">
					<?php esc_html_e( 'Lihat User Akan Expired', 'user-role-expiration-manager' ); ?>
				</a>
			</div>
		</div>
		<style>
			.urem-widget-stats-list {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 12px;
				margin: 0 0 15px 0;
				padding: 0;
				list-style: none;
			}
			.urem-stat-item {
				background: #f6f7f7;
				border: 1px solid #c3c4c7;
				border-radius: 6px;
				padding: 10px;
				text-align: center;
			}
			.urem-stat-number {
				display: block;
				font-size: 22px;
				font-weight: 700;
				color: #1d2327;
			}
			.urem-stat-label {
				font-size: 12px;
				color: #646970;
			}
			.urem-stat-green .urem-stat-number { color: #008a20; }
			.urem-stat-yellow .urem-stat-number { color: #dba617; }
			.urem-stat-red .urem-stat-number { color: #d63638; }
			.urem-widget-actions {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
			}
		</style>
		<?php
	}
}
