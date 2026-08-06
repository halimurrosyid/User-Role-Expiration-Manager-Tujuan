<?php
/**
 * Custom DB Logger Class.
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
	 * Table name without prefix.
	 */
	public const TABLE_NAME = 'urem_logs';

	/**
	 * Get full table name with WP prefix.
	 *
	 * @return string Full table name.
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create log table on plugin activation.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			user_email varchar(100) NOT NULL,
			old_role varchar(50) NOT NULL,
			new_role varchar(50) NOT NULL,
			trigger_type varchar(50) NOT NULL,
			reason text NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY trigger_type (trigger_type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add a log entry.
	 *
	 * @param int    $user_id User ID.
	 * @param string $user_email User Email.
	 * @param string $old_role Old role.
	 * @param string $new_role New role.
	 * @param string $trigger Trigger type ('cron', 'manual_scan', 'manual_single', 'bulk_action', 'reset', 'reminder_email').
	 * @param string $reason Reason description.
	 * @return bool Insert result.
	 */
	public static function add_log( int $user_id, string $user_email, string $old_role, string $new_role, string $trigger, string $reason ): bool {
		$settings = Settings::get_settings();
		if ( empty( $settings['enable_logging'] ) ) {
			return false;
		}

		global $wpdb;
		$table_name = self::get_table_name();

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'      => $user_id,
				'user_email'   => sanitize_email( $user_email ),
				'old_role'     => sanitize_text_field( $old_role ),
				'new_role'     => sanitize_text_field( $new_role ),
				'trigger_type' => sanitize_text_field( $trigger ),
				'reason'       => sanitize_textarea_field( $reason ),
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Auto-prune logs older than configured retention days.
	 *
	 * @return int Number of deleted rows.
	 */
	public static function auto_prune_old_logs(): int {
		$settings       = Settings::get_settings();
		$retention_days = (int) $settings['log_retention_days'];

		if ( $retention_days <= 0 ) {
			return 0;
		}

		global $wpdb;
		$table_name = self::get_table_name();
		$threshold  = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );

		$result = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table_name} WHERE created_at < %s", $threshold ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return (int) $result;
	}

	/**
	 * Get logs list with pagination and search.
	 *
	 * @param string $search Search query.
	 * @param int    $paged Current page.
	 * @param int    $per_page Items per page.
	 * @return array Array containing 'items' and 'total'.
	 */
	public static function get_logs( string $search = '', int $paged = 1, int $per_page = 20 ): array {
		global $wpdb;
		$table_name = self::get_table_name();
		$offset     = ( $paged - 1 ) * $per_page;

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( ! empty( $search ) ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clauses[] = '(user_email LIKE %s OR trigger_type LIKE %s OR reason LIKE %s OR user_id = %d)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = absint( $search );
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
		if ( ! empty( $params ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$data_sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
		$data_params   = array_merge( $params, array( $per_page, $offset ) );
		$items         = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Clear all logs.
	 *
	 * @return bool Query result.
	 */
	public static function clear_logs(): bool {
		global $wpdb;
		$table_name = self::get_table_name();
		return false !== $wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Export logs to CSV downloadable file.
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
		$logs       = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY id DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$filename = 'urem-expiration-logs-' . date( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// CSV Header
		fputcsv( $output, array( 'ID', 'User ID', 'User Email', 'Old Role', 'New Role', 'Trigger', 'Reason', 'Created At' ) );

		if ( ! empty( $logs ) ) {
			foreach ( $logs as $log ) {
				fputcsv(
					$output,
					array(
						$log->id,
						$log->user_id,
						$log->user_email,
						$log->old_role,
						$log->new_role,
						$log->trigger_type,
						$log->reason,
						$log->created_at,
					)
				);
			}
		}

		fclose( $output );
		exit;
	}
}
