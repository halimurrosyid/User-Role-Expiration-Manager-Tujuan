<?php
/**
 * Core Expiration Logic Engine.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Expiration
 */
class Expiration {

	/**
	 * Meta key constants.
	 */
	public const META_ENABLED   = '_urem_expiration_enabled';
	public const META_START     = '_urem_expiration_start';
	public const META_DURATION  = '_urem_expiration_duration';
	public const META_UNIT      = '_urem_expiration_unit';
	public const META_ROLE      = '_urem_expiration_role';
	public const META_TIMESTAMP = '_urem_expiration_ts';

	/**
	 * Get user expiration metadata with fallback to global settings.
	 *
	 * @param int $user_id User ID.
	 * @return array Expiration data array.
	 */
	public static function get_user_expiration_data( int $user_id ): array {
		$enabled  = get_user_meta( $user_id, self::META_ENABLED, true );
		$start    = get_user_meta( $user_id, self::META_START, true );
		$duration = get_user_meta( $user_id, self::META_DURATION, true );
		$unit     = get_user_meta( $user_id, self::META_UNIT, true );
		$role     = get_user_meta( $user_id, self::META_ROLE, true );

		$defaults = Settings::get_defaults();

		// Fallbacks
		if ( '' === $enabled ) {
			$enabled = '0';
		}

		if ( empty( $start ) ) {
			$start = current_time( 'Y-m-d H:i:s' );
		}

		if ( '' === $duration || false === $duration ) {
			$duration = (int) $defaults['default_duration'];
		} else {
			$duration = (int) $duration;
		}

		if ( empty( $unit ) ) {
			$unit = $defaults['default_unit'];
		}

		if ( '' === $role || false === $role ) {
			$role = $defaults['default_role'];
		}

		return array(
			'enabled'  => '1' === (string) $enabled,
			'start'    => $start,
			'duration' => $duration,
			'unit'     => $unit,
			'role'     => $role,
		);
	}

	/**
	 * Calculate exact expiration timestamp for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int|null Timestamp or null if not set.
	 */
	public static function get_expiration_timestamp( int $user_id ): ?int {
		$data = self::get_user_expiration_data( $user_id );

		if ( ! $data['enabled'] || empty( $data['start'] ) || $data['duration'] <= 0 ) {
			return null;
		}

		$start_ts = is_numeric( $data['start'] ) ? (int) $data['start'] : strtotime( $data['start'] );
		if ( ! $start_ts ) {
			return null;
		}

		$modifier = '+' . $data['duration'] . ' ' . $data['unit'];
		return strtotime( $modifier, $start_ts );
	}

	/**
	 * Save/Update indexed timestamp user meta for fast SQL queries.
	 *
	 * @param int $user_id User ID.
	 * @return int|null Calculated timestamp.
	 */
	public static function update_expiration_timestamp_meta( int $user_id ): ?int {
		$ts = self::get_expiration_timestamp( $user_id );
		if ( null !== $ts ) {
			update_user_meta( $user_id, self::META_TIMESTAMP, $ts );
		} else {
			delete_user_meta( $user_id, self::META_TIMESTAMP );
		}
		return $ts;
	}

	/**
	 * Get remaining days until expiration.
	 *
	 * @param int $user_id User ID.
	 * @return int|null Remaining days (negative if expired, null if disabled).
	 */
	public static function get_remaining_days( int $user_id ): ?int {
		$exp_ts = self::get_expiration_timestamp( $user_id );
		if ( null === $exp_ts ) {
			return null;
		}

		$now      = current_time( 'timestamp' );
		$diff_sec = $exp_ts - $now;

		return (int) floor( $diff_sec / DAY_IN_SECONDS );
	}

	/**
	 * Get user expiration status.
	 *
	 * @param int $user_id User ID.
	 * @return string 'active', 'expiring_soon', 'expired', or 'disabled'.
	 */
	public static function get_user_status( int $user_id ): string {
		$data = self::get_user_expiration_data( $user_id );
		if ( ! $data['enabled'] ) {
			return 'disabled';
		}

		$exp_ts = self::get_expiration_timestamp( $user_id );
		if ( null === $exp_ts ) {
			return 'disabled';
		}

		$now = current_time( 'timestamp' );

		if ( $now >= $exp_ts ) {
			return 'expired';
		}

		$days      = self::get_remaining_days( $user_id );
		$threshold = (int) apply_filters( 'urem_expiring_soon_threshold_days', 30 );

		if ( null !== $days && $days <= $threshold ) {
			return 'expiring_soon';
		}

		return 'active';
	}

	/**
	 * Check if a user is expired.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if expired.
	 */
	public static function is_user_expired( int $user_id ): bool {
		return 'expired' === self::get_user_status( $user_id );
	}

	/**
	 * Execute role expiration transition for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $trigger Trigger source ('cron', 'manual_scan', 'manual_single', 'bulk_action').
	 * @return bool Success status.
	 */
	public static function process_user_expiration( int $user_id, string $trigger = 'cron' ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$data = self::get_user_expiration_data( $user_id );

		// Retrieve primary old role
		$old_roles  = (array) $user->roles;
		$old_role   = ! empty( $old_roles ) ? reset( $old_roles ) : 'none';
		$target_role = $data['role'];

		// Do not process if target role is identical to current role
		if ( $old_role === $target_role ) {
			return false;
		}

		$settings = Settings::get_settings();
		$dry_run  = ! empty( $settings['dry_run_mode'] );

		/**
		 * Action before user role is updated by expiration manager.
		 *
		 * @param int    $user_id User ID.
		 * @param string $old_role Previous primary role.
		 * @param string $target_role Target primary role.
		 * @param string $trigger Trigger type.
		 */
		do_action( 'urem_before_user_expired', $user_id, $old_role, $target_role, $trigger );

		if ( ! $dry_run ) {
			if ( 'none' === $target_role || '' === $target_role ) {
				$user->set_role( '' );
			} else {
				$user->set_role( $target_role );
			}

			// Logout user session if enabled
			if ( ! empty( $settings['logout_user_on_expire'] ) ) {
				$sessions = \WP_Session_Tokens::get_instance( $user_id );
				$sessions->destroy_all();
			}

			// Send notification email if enabled
			if ( ! empty( $settings['send_email_on_expire'] ) ) {
				self::send_expiration_email( $user, $old_role, $target_role );
			}
		}

		// Record Log
		$reason = sprintf(
			/* translators: 1: Old role, 2: New role, 3: Trigger name, 4: Dry run tag */
			__( 'Role changed from %1$s to %2$s via %3$s%4$s.', 'user-role-expiration-manager' ),
			$old_role,
			$target_role,
			$trigger,
			$dry_run ? ' [DRY RUN]' : ''
		);

		Logger::add_log( $user_id, $user->user_email, $old_role, $target_role, $trigger, $reason );

		/**
		 * Action after user role is updated by expiration manager.
		 *
		 * @param int    $user_id User ID.
		 * @param string $old_role Previous primary role.
		 * @param string $target_role Target primary role.
		 * @param string $trigger Trigger type.
		 */
		do_action( 'urem_after_user_expired', $user_id, $old_role, $target_role, $trigger );

		return true;
	}

	/**
	 * Send email notification to user upon role expiration.
	 *
	 * @param \WP_User $user WP_User object.
	 * @param string   $old_role Previous role.
	 * @param string   $new_role New role.
	 * @return bool Mail send result.
	 */
	private static function send_expiration_email( \WP_User $user, string $old_role, string $new_role ): bool {
		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf( __( '[%s] Notice: Your User Role Has Expired', 'user-role-expiration-manager' ), $site_name );

		$message  = sprintf( __( 'Hello %s,', 'user-role-expiration-manager' ), $user->display_name ) . "\r\n\r\n";
		$message .= sprintf(
			__( 'Your role on %1$s has reached its expiration date and has been changed from %2$s to %3$s.', 'user-role-expiration-manager' ),
			$site_name,
			$old_role,
			$new_role
		) . "\r\n\r\n";
		$message .= __( 'If you believe this is an error or require assistance, please contact the site administrator.', 'user-role-expiration-manager' ) . "\r\n\r\n";
		$message .= sprintf( __( 'Regards,', 'user-role-expiration-manager' ) ) . "\r\n" . $site_name;

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $user->user_email, $subject, $message, $headers );
	}
}
