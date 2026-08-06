<?php
/**
 * WP-Cron Scheduler & Chunk Batch Processor.
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
	 * Cron hook event name.
	 */
	public const HOOK_NAME = 'urem_daily_expiration_event';

	/**
	 * Register cron hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( self::HOOK_NAME, array( __CLASS__, 'process_all_user_expirations' ) );
		add_action( 'urem_manual_scan_action', array( __CLASS__, 'process_all_user_expirations' ) );
		add_action( 'admin_post_urem_manual_scan', array( __CLASS__, 'handle_admin_manual_scan' ) );
	}

	/**
	 * Schedule cron event if not already scheduled.
	 *
	 * @param string $recurrence 'hourly', 'twicedaily', or 'daily'.
	 * @return void
	 */
	public static function schedule( string $recurrence = 'daily' ) {
		if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {
			wp_schedule_event( time(), $recurrence, self::HOOK_NAME );
		}
	}

	/**
	 * Unschedule cron event.
	 *
	 * @return void
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::HOOK_NAME );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK_NAME );
		}
	}

	/**
	 * Reschedule cron event with new recurrence.
	 *
	 * @param string $recurrence Recurrence interval.
	 * @return void
	 */
	public static function reschedule( string $recurrence ) {
		self::unschedule();
		self::schedule( $recurrence );
	}

	/**
	 * Process all user expirations using memory-safe 100-user chunking.
	 *
	 * @param string $trigger Trigger source ('cron' or 'manual_scan').
	 * @return int Total users expired/processed.
	 */
	public static function process_all_user_expirations( string $trigger = 'cron' ): int {
		$settings = Settings::get_settings();
		if ( empty( $settings['plugin_enabled'] ) ) {
			return 0;
		}

		$processed_count = 0;
		$chunk_size      = (int) apply_filters( 'urem_cron_chunk_size', 100 );
		$page            = 1;

		do {
			$user_query = new \WP_User_Query(
				array(
					'fields'     => 'ID',
					'number'     => $chunk_size,
					'paged'      => $page,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => Expiration::META_ENABLED,
							'value'   => '1',
							'compare' => '=',
						),
					),
				)
			);

			$user_ids = $user_query->get_results();

			if ( empty( $user_ids ) ) {
				break;
			}

			foreach ( $user_ids as $user_id ) {
				$user_id = (int) $user_id;

				// Check if user is already expired
				if ( Expiration::is_user_expired( $user_id ) ) {
					if ( Expiration::process_user_expiration( $user_id, $trigger ) ) {
						$processed_count++;
					}
				} else {
					// Check and send pre-expiration email reminder if due
					Expiration::check_and_send_pre_expiration_reminder( $user_id );
				}

				// Always update indexed meta timestamp for clean queries
				Expiration::update_expiration_timestamp_meta( $user_id );
			}

			$page++;

			// Prevent infinite loop if total pages reached
			if ( $page > $user_query->get_total_pages() ) {
				break;
			}

		} while ( ! empty( $user_ids ) );

		// Auto-prune old database logs based on retention setting
		Logger::auto_prune_old_logs();

		return $processed_count;
	}

	/**
	 * Admin action handler for manual scan trigger button.
	 *
	 * @return void
	 */
	public static function handle_admin_manual_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'user-role-expiration-manager' ) );
		}

		check_admin_referer( 'urem_manual_scan_nonce', 'urem_nonce' );

		$count = self::process_all_user_expirations( 'manual_scan' );

		$redirect_url = add_query_arg(
			array(
				'page'               => 'user-role-expiration-manager',
				'tab'                => 'manual_scan',
				'urem_scan_complete' => 1,
				'urem_count'         => $count,
			),
			admin_url( 'users.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
