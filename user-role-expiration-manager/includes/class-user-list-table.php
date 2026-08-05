<?php
/**
 * All Users List Table Extensions.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class User_List_Table
 */
class User_List_Table {

	/**
	 * Register users table hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'manage_users_columns', array( __CLASS__, 'add_custom_columns' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'render_custom_column_content' ), 10, 3 );
		add_filter( 'manage_users_sortable_columns', array( __CLASS__, 'make_columns_sortable' ) );

		add_action( 'restrict_manage_users', array( __CLASS__, 'render_expiration_filter_dropdown' ) );
		add_action( 'pre_get_users', array( __CLASS__, 'filter_users_by_expiration_status' ) );
	}

	/**
	 * Add custom columns to Users list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public static function add_custom_columns( array $columns ): array {
		$columns['urem_start']       = __( 'Tanggal Mulai', 'user-role-expiration-manager' );
		$columns['urem_expired']     = __( 'Tanggal Expired', 'user-role-expiration-manager' );
		$columns['urem_target_role'] = __( 'Role Setelah Expired', 'user-role-expiration-manager' );
		$columns['urem_status']      = __( 'Status', 'user-role-expiration-manager' );
		$columns['urem_remaining']   = __( 'Sisa Hari', 'user-role-expiration-manager' );

		return $columns;
	}

	/**
	 * Make custom columns sortable.
	 *
	 * @param array $columns Existing sortable columns.
	 * @return array Modified sortable columns.
	 */
	public static function make_columns_sortable( array $columns ): array {
		$columns['urem_expired'] = 'urem_expired_ts';
		return $columns;
	}

	/**
	 * Render column content for custom columns.
	 *
	 * @param string $output Column HTML.
	 * @param string $column_name Column identifier.
	 * @param int    $user_id User ID.
	 * @return string Rendered content.
	 */
	public static function render_custom_column_content( string $output, string $column_name, int $user_id ): string {
		$data   = Expiration::get_user_expiration_data( $user_id );
		$status = Expiration::get_user_status( $user_id );

		switch ( $column_name ) {
			case 'urem_start':
				if ( ! $data['enabled'] ) {
					return '-';
				}
				return esc_html( urem_format_datetime( $data['start'] ) );

			case 'urem_expired':
				if ( ! $data['enabled'] ) {
					return '-';
				}
				$ts = Expiration::get_expiration_timestamp( $user_id );
				return esc_html( urem_format_datetime( $ts ) );

			case 'urem_target_role':
				if ( ! $data['enabled'] ) {
					return '-';
				}
				$all_roles = urem_get_all_roles();
				$role_name = isset( $all_roles[ $data['role'] ] ) ? $all_roles[ $data['role'] ] : $data['role'];
				return esc_html( $role_name );

			case 'urem_status':
				return urem_get_status_badge_html( $status );

			case 'urem_remaining':
				if ( ! $data['enabled'] ) {
					return '-';
				}
				$days = Expiration::get_remaining_days( $user_id );
				if ( null === $days ) {
					return '-';
				}
				if ( $days < 0 ) {
					return '<span style="color: #d63638; font-weight: 600;">' . esc_html__( 'Expired', 'user-role-expiration-manager' ) . '</span>';
				}
				return esc_html( sprintf( _n( '%d hari', '%d hari', $days, 'user-role-expiration-manager' ), $days ) );

			default:
				return $output;
		}
	}

	/**
	 * Render filter dropdown above Users list table.
	 *
	 * @param string $which Table position ('top' or 'bottom').
	 * @return void
	 */
	public static function render_expiration_filter_dropdown( string $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$current_status = isset( $_GET['urem_status'] ) ? sanitize_text_field( wp_unslash( $_GET['urem_status'] ) ) : '';
		?>
		<select name="urem_status" id="urem_status_filter">
			<option value=""><?php esc_html_e( 'Semua Status Expiration', 'user-role-expiration-manager' ); ?></option>
			<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Aktif', 'user-role-expiration-manager' ); ?></option>
			<option value="expiring_soon" <?php selected( $current_status, 'expiring_soon' ); ?>><?php esc_html_e( 'Akan Expired (<30 Hari)', 'user-role-expiration-manager' ); ?></option>
			<option value="expired" <?php selected( $current_status, 'expired' ); ?>><?php esc_html_e( 'Expired', 'user-role-expiration-manager' ); ?></option>
			<option value="disabled" <?php selected( $current_status, 'disabled' ); ?>><?php esc_html_e( 'Expiration Disabled', 'user-role-expiration-manager' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Filter user query based on selected status filter and column sorting.
	 *
	 * @param \WP_User_Query $query Query object.
	 * @return void
	 */
	public static function filter_users_by_expiration_status( \WP_User_Query $query ): void {
		if ( ! is_admin() ) {
			return;
		}

		$status  = isset( $_GET['urem_status'] ) ? sanitize_text_field( wp_unslash( $_GET['urem_status'] ) ) : '';
		$orderby = $query->get( 'orderby' );

		// Handle column sorting by expiration timestamp
		if ( 'urem_expired_ts' === $orderby ) {
			$query->set( 'meta_key', Expiration::META_TIMESTAMP );
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( empty( $status ) ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		$now       = current_time( 'timestamp' );
		$threshold = (int) apply_filters( 'urem_expiring_soon_threshold_days', 30 );
		$soon_ts   = $now + ( $threshold * DAY_IN_SECONDS );

		switch ( $status ) {
			case 'disabled':
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => Expiration::META_ENABLED,
						'value'   => '0',
						'compare' => '=',
					),
					array(
						'key'     => Expiration::META_ENABLED,
						'compare' => 'NOT EXISTS',
					),
				);
				break;

			case 'expired':
				$meta_query[] = array(
					'key'     => Expiration::META_ENABLED,
					'value'   => '1',
					'compare' => '=',
				);
				$meta_query[] = array(
					'key'     => Expiration::META_TIMESTAMP,
					'value'   => $now,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
				break;

			case 'expiring_soon':
				$meta_query[] = array(
					'key'     => Expiration::META_ENABLED,
					'value'   => '1',
					'compare' => '=',
				);
				$meta_query[] = array(
					'key'     => Expiration::META_TIMESTAMP,
					'value'   => $now,
					'compare' => '>',
					'type'    => 'NUMERIC',
				);
				$meta_query[] = array(
					'key'     => Expiration::META_TIMESTAMP,
					'value'   => $soon_ts,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				);
				break;

			case 'active':
				$meta_query[] = array(
					'key'     => Expiration::META_ENABLED,
					'value'   => '1',
					'compare' => '=',
				);
				$meta_query[] = array(
					'key'     => Expiration::META_TIMESTAMP,
					'value'   => $soon_ts,
					'compare' => '>',
					'type'    => 'NUMERIC',
				);
				break;
		}

		$query->set( 'meta_query', $meta_query );
	}
}
