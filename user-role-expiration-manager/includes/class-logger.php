<?php
/**
 * Logger Class for User Role Expiration Manager.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logger
 */
class Logger {

	/**
	 * Get full table name with prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'urem_logs';
	}

	/**
	 * Create DB table schema if it does not exist.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			user_email varchar(100) NOT NULL DEFAULT '',
			old_role varchar(50) NOT NULL DEFAULT '',
			new_role varchar(50) NOT NULL DEFAULT '',
			trigger_type varchar(50) NOT NULL DEFAULT 'cron',
			reason text DEFAULT NULL,
			created_at datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a log record.
	 *
	 * @param int    $user_id User ID.
	 * @param string $user_email User email address.
	 * @param string $old_role Old role string.
	 * @param string $new_role New role string.
	 * @param string $trigger_type Trigger type ('cron', 'manual_scan', 'manual_single', etc.).
	 * @param string $reason Text reason summary.
	 * @return bool Success boolean.
	 */
	public static function add_log( $user_id, $user_email, $old_role, $new_role, $trigger_type, $reason ) {
		$settings = Settings::get_settings();
		if ( empty( $settings['enable_logging'] ) ) {
			return false;
		}

		global $wpdb;

		$table_name = self::get_table_name();

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'      => absint( $user_id ),
				'user_email'   => sanitize_email( $user_email ),
				'old_role'     => sanitize_text_field( $old_role ),
				'new_role'     => sanitize_text_field( $new_role ),
				'trigger_type' => sanitize_text_field( $trigger_type ),
				'reason'       => sanitize_textarea_field( $reason ),
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $inserted;
	}

	/**
	 * Retrieve log entries.
	 *
	 * @param int    $limit Limit per page.
	 * @param int    $offset Offset.
	 * @param string $search Optional search term.
	 * @return array Array of objects.
	 */
	public static function get_logs( $limit = 20, $offset = 0, $search = '' ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$where      = 'WHERE 1=1';
		$params     = array();

		if ( ! empty( $search ) ) {
			$where   .= ' AND (user_email LIKE %s OR reason LIKE %s OR old_role LIKE %s OR new_role LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT * FROM $table_name $where ORDER BY id DESC LIMIT %d OFFSET %d";
		$params[] = absint( $limit );
		$params[] = absint( $offset );

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Count total log entries.
	 *
	 * @param string $search Optional search term.
	 * @return int Total log count.
	 */
	public static function get_logs_count( $search = '' ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$where      = 'WHERE 1=1';
		$params     = array();

		if ( ! empty( $search ) ) {
			$where   .= ' AND (user_email LIKE %s OR reason LIKE %s OR old_role LIKE %s OR new_role LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT COUNT(*) FROM $table_name $where";
		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Clear all logs.
	 *
	 * @return bool
	 */
	public static function clear_logs() {
		global $wpdb;
		$table_name = self::get_table_name();

		return false !== $wpdb->query( "TRUNCATE TABLE $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Export logs as downloadable CSV file.
	 *
	 * @return void
	 */
	public static function export_csv_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'user-role-expiration-manager' ) );
		}

		check_admin_referer( 'urem_export_logs_nonce', 'urem_nonce' );

		global $wpdb;
		$table_name = self::get_table_name();
		$logs       = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$filename = 'urem-logs-' . date( 'Y-m-d_H-i-s' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Header row
		fputcsv( $output, array( 'ID', 'Date', 'User ID', 'User Email', 'Old Role', 'New Role', 'Trigger Type', 'Reason' ) );

		if ( ! empty( $logs ) ) {
			foreach ( $logs as $row ) {
				fputcsv(
					$output,
					array(
						$row['id'],
						$row['created_at'],
						$row['user_id'],
						$row['user_email'],
						$row['old_role'],
						$row['new_role'],
						$row['trigger_type'],
						$row['reason'],
					)
				);
			}
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		exit;
	}
}
