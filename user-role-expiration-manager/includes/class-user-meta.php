<?php
/**
 * User Profile Meta & Bulk Actions Handler.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class User_Meta
 */
class User_Meta {

	/**
	 * Register hooks for user profile & bulk actions.
	 *
	 * @return void
	 */
	public static function init(): void {
		// User Edit Page Profile Fields
		add_action( 'show_user_profile', array( __CLASS__, 'render_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_user_profile_fields' ) );

		add_action( 'personal_options_update', array( __CLASS__, 'save_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_user_profile_fields' ) );

		// Single actions via admin-post
		add_action( 'admin_post_urem_reset_user_start_date', array( __CLASS__, 'handle_reset_start_date' ) );
		add_action( 'admin_post_urem_expire_user_now', array( __CLASS__, 'handle_expire_now' ) );

		// Bulk actions registration and execution
		add_filter( 'bulk_actions-users', array( __CLASS__, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-users', array( __CLASS__, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'render_bulk_action_notices' ) );
	}

	/**
	 * Render profile fields on user edit page.
	 *
	 * @param \WP_User $user User object.
	 * @return void
	 */
	public static function render_user_profile_fields( \WP_User $user ): void {
		// Strictly restrict to users with edit_users capability
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		$data           = Expiration::get_user_expiration_data( $user->ID );
		$status         = Expiration::get_user_status( $user->ID );
		$remaining_days = Expiration::get_remaining_days( $user->ID );
		$exp_ts         = Expiration::get_expiration_timestamp( $user->ID );

		$all_roles = urem_get_all_roles();
		$units     = urem_get_expiration_units();

		require UREM_PLUGIN_DIR . 'admin/views/user-profile-fields.php';
	}

	/**
	 * Save user profile meta fields.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function save_user_profile_fields( int $user_id ): void {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		if ( ! isset( $_POST['urem_user_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['urem_user_profile_nonce'] ) ), 'urem_save_user_profile' ) ) {
			return;
		}

		$enabled  = ! empty( $_POST['urem_expiration_enabled'] ) ? '1' : '0';
		$start    = isset( $_POST['urem_expiration_start'] ) ? sanitize_text_field( wp_unslash( $_POST['urem_expiration_start'] ) ) : current_time( 'Y-m-d H:i:s' );
		$duration = isset( $_POST['urem_expiration_duration'] ) ? max( 1, absint( $_POST['urem_expiration_duration'] ) ) : 30;
		$unit     = isset( $_POST['urem_expiration_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['urem_expiration_unit'] ) ) : 'days';
		$role     = isset( $_POST['urem_expiration_role'] ) ? sanitize_text_field( wp_unslash( $_POST['urem_expiration_role'] ) ) : 'subscriber';

		update_user_meta( $user_id, Expiration::META_ENABLED, $enabled );
		update_user_meta( $user_id, Expiration::META_START, $start );
		update_user_meta( $user_id, Expiration::META_DURATION, $duration );
		update_user_meta( $user_id, Expiration::META_UNIT, $unit );
		update_user_meta( $user_id, Expiration::META_ROLE, $role );

		Expiration::update_expiration_timestamp_meta( $user_id );
	}

	/**
	 * Handle Reset Start Date single action.
	 *
	 * @return void
	 */
	public static function handle_reset_start_date(): void {
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'user-role-expiration-manager' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		check_admin_referer( 'urem_reset_start_date_' . $user_id, 'urem_nonce' );

		if ( $user_id ) {
			$now = current_time( 'Y-m-d H:i:s' );
			update_user_meta( $user_id, Expiration::META_START, $now );
			Expiration::update_expiration_timestamp_meta( $user_id );

			$user = get_userdata( $user_id );
			$email = $user ? $user->user_email : '';
			Logger::add_log( $user_id, $email, '', '', 'reset', __( 'Expiration start date reset to current time.', 'user-role-expiration-manager' ) );
		}

		$redirect = add_query_arg( 'urem_action_performed', 'reset_success', get_edit_user_link( $user_id ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle Expire Now single action.
	 *
	 * @return void
	 */
	public static function handle_expire_now(): void {
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'user-role-expiration-manager' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		check_admin_referer( 'urem_expire_now_' . $user_id, 'urem_nonce' );

		if ( $user_id ) {
			Expiration::process_user_expiration( $user_id, 'manual_single' );
			Expiration::update_expiration_timestamp_meta( $user_id );
		}

		$redirect = add_query_arg( 'urem_action_performed', 'expire_success', get_edit_user_link( $user_id ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Register custom Bulk Actions on users.php.
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array Modified actions.
	 */
	public static function register_bulk_actions( array $actions ): array {
		if ( ! current_user_can( 'edit_users' ) ) {
			return $actions;
		}

		$actions['urem_bulk_enable']  = __( 'Enable Expiration', 'user-role-expiration-manager' );
		$actions['urem_bulk_disable'] = __( 'Disable Expiration', 'user-role-expiration-manager' );
		$actions['urem_bulk_reset']   = __( 'Reset Tanggal Mulai', 'user-role-expiration-manager' );
		$actions['urem_bulk_expire']  = __( 'Expire Sekarang', 'user-role-expiration-manager' );

		return $actions;
	}

	/**
	 * Handle Bulk Action executions.
	 *
	 * @param string $sendback Sendback URL.
	 * @param string $action Bulk action key.
	 * @param array  $user_ids Array of target user IDs.
	 * @return string Redirect URL.
	 */
	public static function handle_bulk_actions( string $sendback, string $action, array $user_ids ): string {
		if ( ! current_user_can( 'edit_users' ) || empty( $user_ids ) ) {
			return $sendback;
		}

		$processed = 0;

		switch ( $action ) {
			case 'urem_bulk_enable':
				foreach ( $user_ids as $id ) {
					$id = (int) $id;
					update_user_meta( $id, Expiration::META_ENABLED, '1' );
					Expiration::update_expiration_timestamp_meta( $id );
					$processed++;
				}
				break;

			case 'urem_bulk_disable':
				foreach ( $user_ids as $id ) {
					$id = (int) $id;
					update_user_meta( $id, Expiration::META_ENABLED, '0' );
					Expiration::update_expiration_timestamp_meta( $id );
					$processed++;
				}
				break;

			case 'urem_bulk_reset':
				$now = current_time( 'Y-m-d H:i:s' );
				foreach ( $user_ids as $id ) {
					$id = (int) $id;
					update_user_meta( $id, Expiration::META_START, $now );
					Expiration::update_expiration_timestamp_meta( $id );
					$processed++;
				}
				break;

			case 'urem_bulk_expire':
				foreach ( $user_ids as $id ) {
					$id = (int) $id;
					if ( Expiration::process_user_expiration( $id, 'bulk_action' ) ) {
						Expiration::update_expiration_timestamp_meta( $id );
						$processed++;
					}
				}
				break;

			default:
				return $sendback;
		}

		return add_query_arg(
			array(
				'urem_bulk_performed' => $action,
				'urem_bulk_count'     => $processed,
			),
			$sendback
		);
	}

	/**
	 * Render admin notices after single/bulk action execution.
	 *
	 * @return void
	 */
	public static function render_bulk_action_notices(): void {
		if ( ! empty( $_GET['urem_action_performed'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_GET['urem_action_performed'] ) );
			if ( 'reset_success' === $action ) {
				printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Tanggal mulai expiration berhasil di-reset ke waktu saat ini.', 'user-role-expiration-manager' ) );
			} elseif ( 'expire_success' === $action ) {
				printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Role pengguna berhasil di-expire sekarang.', 'user-role-expiration-manager' ) );
			}
		}

		if ( empty( $_GET['urem_bulk_performed'] ) || ! isset( $_GET['urem_bulk_count'] ) ) {
			return;
		}

		$count  = absint( $_GET['urem_bulk_count'] );
		$action = sanitize_text_field( wp_unslash( $_GET['urem_bulk_performed'] ) );

		$message = '';
		switch ( $action ) {
			case 'urem_bulk_enable':
				$message = sprintf( __( 'Expiration enabled for %d users.', 'user-role-expiration-manager' ), $count );
				break;
			case 'urem_bulk_disable':
				$message = sprintf( __( 'Expiration disabled for %d users.', 'user-role-expiration-manager' ), $count );
				break;
			case 'urem_bulk_reset':
				$message = sprintf( __( 'Start date reset for %d users.', 'user-role-expiration-manager' ), $count );
				break;
			case 'urem_bulk_expire':
				$message = sprintf( __( 'Role expired for %d users.', 'user-role-expiration-manager' ), $count );
				break;
		}

		if ( ! empty( $message ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}
	}
}
