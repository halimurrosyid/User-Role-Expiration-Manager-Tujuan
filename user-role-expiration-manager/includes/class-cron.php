<?php
/**
 * WP-Cron and Batch Processor Class.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cron
 */
class Cron {

	/**
	 * Cron hook name.
	 */
	public const CRON_HOOK = 'urem_scheduled_expiration_check';

	/**
	 * Initialize cron actions.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( self::CRON_HOOK, array( __CLASS__, 'process_batch_expirations' ) );
		add_action( 'admin_post_urem_manual_scan', array( __CLASS__, 'handle_manual_scan' ) );
	}

	/**
	 * Schedule cron job based on settings.
	 *
	 * @param string|null $schedule Cron recurrence key ('hourly', 'twicedaily', 'daily').
	 * @return void
	 */
	public static function schedule_cron( ?string $schedule = null ): void {
		if ( ! $schedule ) {
			$settings = Settings::get_settings();
			$schedule = $settings['cron_schedule'];
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, $schedule, self::CRON_HOOK );
		}
	}

	/**
	 * Reschedule cron job when recurrence changes.
	 *
	 * @param string $new_schedule New schedule key.
	 * @return void
	 */
	public static function reschedule_cron( string $new_schedule ): void {
		self::unschedule_cron();
		wp_schedule_event( time() + 60, $new_schedule, self::CRON_HOOK );
	}

	/**
	 * Unschedule cron job.
	 *
	 * @return void
	 */
	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Batch process all users whose role expirations are active.
	 *
	 * Designed to be lightweight and scalable for 20,000+ users.
	 *
	 * @param string $trigger Trigger identifier ('cron' or 'manual_scan').
	 * @return int Number of users processed/expired.
	 */
	public static function process_batch_expirations( string $trigger = 'cron' ): int {
		$settings = Settings::get_settings();
		if ( empty( $settings['plugin_enabled'] ) ) {
			return 0;
		}

		$processed_count = 0;
		$page            = 1;
		$number_per_page = 100; // Batch chunk size to keep memory low

		do {
			$args = array(
				'number'     => $number_per_page,
				'paged'      => $page,
				'fields'     => 'ID',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => Expiration::META_ENABLED,
						'value'   => '1',
						'compare' => '=',
					),
				),
			);

			$user_query = new \WP_User_Query( $args );
			$user_ids   = $user_query->get_results();

			if ( empty( $user_ids ) ) {
				break;
			}

			foreach ( $user_ids as $user_id ) {
				$user_id = (int) $user_id;

				if ( Expiration::is_user_expired( $user_id ) ) {
					$success = Expiration::process_user_expiration( $user_id, $trigger );
					if ( $success ) {
						$processed_count++;
					}
				}
			}

			$page++;
			// Stop if page exceeds total query pages
			$total_users = $user_query->get_total();
			$total_pages = ceil( $total_users / $number_per_page );

		} while ( $page <= $total_pages );

		return $processed_count;
	}

	/**
	 * Admin Post action handler for Manual Scan trigger button.
	 *
	 * @return void
	 */
	public static function handle_manual_scan(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'user-role-expiration-manager' ) );
		}

		check_admin_referer( 'urem_manual_scan_nonce', 'urem_nonce' );

		$processed = self::process_batch_expirations( 'manual_scan' );

		$redirect_url = add_query_arg(
			array(
				'page'               => 'user-role-expiration-manager',
				'tab'                => 'manual_scan',
				'urem_scan_complete' => 1,
				'urem_count'         => $processed,
			),
			admin_url( 'users.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
