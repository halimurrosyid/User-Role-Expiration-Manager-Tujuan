<?php
/**
 * Admin Controller Class.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 */
class Admin {

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );
		add_action( 'admin_post_urem_clear_logs', array( __CLASS__, 'handle_clear_logs' ) );
		add_action( 'admin_post_urem_export_logs', array( Logger::class, 'export_csv_logs' ) );

		// Plugin Action Links (Settings link under plugin name)
		add_filter( 'plugin_action_links_' . plugin_basename( UREM_PLUGIN_FILE ), array( __CLASS__, 'add_plugin_action_links' ) );

		// Plugin Row Meta (View details link next to Version & Author)
		add_filter( 'plugin_row_meta', array( __CLASS__, 'add_plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Add "Settings" link to plugin action links on plugins.php page.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public static function add_plugin_action_links( $links ) {
		$settings_url = admin_url( 'users.php?page=user-role-expiration-manager' );
		$custom_links = array(
			'settings' => sprintf( '<a href="%s">%s</a>', esc_url( $settings_url ), esc_html__( 'Settings', 'user-role-expiration-manager' ) ),
		);

		return array_merge( $custom_links, $links );
	}

	/**
	 * Add "View details" link to plugin row meta on plugins.php page.
	 *
	 * @param array  $links Existing row meta links.
	 * @param string $file Plugin file name.
	 * @return array Modified row meta links.
	 */
	public static function add_plugin_row_meta( $links, $file ) {
		if ( $file === plugin_basename( UREM_PLUGIN_FILE ) ) {
			add_thickbox();

			$details_url = add_query_arg(
				array(
					'tab'       => 'plugin-information',
					'plugin'    => dirname( $file ),
					'TB_iframe' => 'true',
					'width'     => '600',
					'height'    => '550',
				),
				self_admin_url( 'plugin-install.php' )
			);

			$row_meta = array(
				'view_details' => sprintf(
					'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
					esc_url( $details_url ),
					esc_attr__( 'View details for User Role Expiration Manager', 'user-role-expiration-manager' ),
					esc_attr__( 'User Role Expiration Manager', 'user-role-expiration-manager' ),
					esc_html__( 'View details', 'user-role-expiration-manager' )
				),
			);

			return array_merge( $links, $row_meta );
		}

		return $links;
	}

	/**
	 * Register "Role Expiration" submenu under native "Users" menu.
	 *
	 * @return void
	 */
	public static function register_admin_menu() {
		add_users_page(
			__( 'Role Expiration Manager', 'user-role-expiration-manager' ),
			__( 'Role Expiration', 'user-role-expiration-manager' ),
			'manage_options',
			'user-role-expiration-manager',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin stylesheets and scripts.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		// Load on Users list, User edit, Profile, and Plugin admin page
		$allowed_pages = array( 'users.php', 'user-edit.php', 'profile.php', 'users_page_user-role-expiration-manager' );
		if ( ! in_array( $hook_suffix, $allowed_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'urem-admin-css',
			UREM_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			UREM_VERSION
		);

		wp_enqueue_script(
			'urem-admin-js',
			UREM_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			UREM_VERSION,
			true
		);

		wp_localize_script(
			'urem-admin-js',
			'uremVars',
			array(
				'confirmReset'  => __( 'Apakah Anda yakin ingin mereset tanggal mulai pengguna ini ke hari ini?', 'user-role-expiration-manager' ),
				'confirmExpire' => __( 'Apakah Anda yakin ingin langsung meng-expire role pengguna ini sekarang?', 'user-role-expiration-manager' ),
				'confirmClear'  => __( 'Apakah Anda yakin ingin menghapus semua log catatan?', 'user-role-expiration-manager' ),
			)
		);
	}

	/**
	 * Display Admin Notices for expiring/expired users.
	 *
	 * @return void
	 */
	public static function render_admin_notices() {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		// Only show on relevant admin screens to avoid spamming
		$screen = get_current_screen();
		if ( ! $screen || ! isset( $screen->id ) || ! in_array( $screen->id, array( 'dashboard', 'users', 'users_page_user-role-expiration-manager' ), true ) ) {
			return;
		}

		$expiring_7_days_count = 0;
		$expired_today_count   = 0;

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

		if ( ! empty( $user_ids ) ) {
			foreach ( $user_ids as $uid ) {
				$uid  = (int) $uid;
				$days = Expiration::get_remaining_days( $uid );

				if ( null !== $days ) {
					if ( $days >= 0 && $days <= 7 ) {
						$expiring_7_days_count++;
					} elseif ( -1 === $days || 0 === $days ) {
						$expired_today_count++;
					}
				}
			}
		}

		if ( $expiring_7_days_count > 0 ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>%s:</strong> %s <a href="%s">%s</a></p></div>',
				esc_html__( 'Role Expiration Notice', 'user-role-expiration-manager' ),
				esc_html( sprintf( __( 'Terdapat %d pengguna yang role-nya akan expired dalam 7 hari ke depan.', 'user-role-expiration-manager' ), $expiring_7_days_count ) ),
				esc_url( admin_url( 'users.php?urem_status=expiring_soon' ) ),
				esc_html__( 'Lihat Pengguna', 'user-role-expiration-manager' )
			);
		}

		if ( $expired_today_count > 0 ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p><strong>%s:</strong> %s <a href="%s">%s</a></p></div>',
				esc_html__( 'Role Expiration Notice', 'user-role-expiration-manager' ),
				esc_html( sprintf( __( 'Terdapat %d pengguna yang role-nya telah expired hari ini.', 'user-role-expiration-manager' ), $expired_today_count ) ),
				esc_url( admin_url( 'users.php?urem_status=expired' ) ),
				esc_html__( 'Lihat Pengguna', 'user-role-expiration-manager' )
			);
		}

		// Manual scan success notice
		if ( ! empty( $_GET['urem_scan_complete'] ) && isset( $_GET['urem_count'] ) ) {
			$count = absint( $_GET['urem_count'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sprintf( __( 'Scan manual selesai! Sebanyak %d pengguna telah berhasil diproses/di-expire.', 'user-role-expiration-manager' ), $count ) )
			);
		}

		// Clear logs success notice
		if ( ! empty( $_GET['urem_logs_cleared'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Semua log berhasil dihapus.', 'user-role-expiration-manager' )
			);
		}
	}

	/**
	 * Action handler to clear logs.
	 *
	 * @return void
	 */
	public static function handle_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'user-role-expiration-manager' ) );
		}

		check_admin_referer( 'urem_clear_logs_nonce', 'urem_nonce' );

		Logger::clear_logs();

		$redirect_url = add_query_arg(
			array(
				'page'              => 'user-role-expiration-manager',
				'tab'               => 'logs',
				'urem_logs_cleared' => 1,
			),
			admin_url( 'users.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render main plugin settings & management admin page.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';

		$tabs = array(
			'settings'    => __( 'Settings', 'user-role-expiration-manager' ),
			'manual_scan' => __( 'Manual Scan', 'user-role-expiration-manager' ),
			'logs'        => __( 'Logs', 'user-role-expiration-manager' ),
		);

		?>
		<div class="wrap urem-admin-wrap">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-clock" style="font-size: 30px; width: 30px; height: 30px; margin-right: 8px;"></span>
				<?php esc_html_e( 'User Role Expiration Manager', 'user-role-expiration-manager' ); ?>
			</h1>
			<hr class="wp-header-end">

			<nav class="nav-tab-wrapper wp-clearfix">
				<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
					<?php
					$tab_url = add_query_arg(
						array(
							'page' => 'user-role-expiration-manager',
							'tab'  => $tab_id,
						),
						admin_url( 'users.php' )
					);
					$active_class = ( $current_tab === $tab_id ) ? 'nav-tab-active' : '';
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo esc_attr( $active_class ); ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="urem-tab-content" style="margin-top: 20px;">
				<?php
				switch ( $current_tab ) {
					case 'manual_scan':
						require UREM_PLUGIN_DIR . 'admin/views/settings-page.php'; // renders settings + scan section
						break;
					case 'logs':
						require UREM_PLUGIN_DIR . 'admin/views/logs-page.php';
						break;
					case 'settings':
					default:
						require UREM_PLUGIN_DIR . 'admin/views/settings-page.php';
						break;
				}
				?>
			</div>
		</div>
		<?php
	}
}
