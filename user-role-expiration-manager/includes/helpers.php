<?php
/**
 * Global helper functions for User Role Expiration Manager.
 *
 * @package UserRoleExpirationManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'urem_get_all_roles' ) ) {
	/**
	 * Retrieve all registered roles in WordPress.
	 *
	 * Includes an option for 'No Role' (empty string/none).
	 *
	 * @return array Array of role_key => Role Name.
	 */
	function urem_get_all_roles(): array {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$roles = array(
			'none' => __( 'No Role (Remove All Roles)', 'user-role-expiration-manager' ),
		);

		foreach ( $wp_roles->get_names() as $role_key => $role_name ) {
			$roles[ $role_key ] = translate_user_role( $role_name );
		}

		/**
		 * Filter available roles for expiration target.
		 *
		 * @param array $roles Array of role key => role display name.
		 */
		return apply_filters( 'urem_available_roles', $roles );
	}
}

if ( ! function_exists( 'urem_get_expiration_units' ) ) {
	/**
	 * Get expiration duration units.
	 *
	 * @return array
	 */
	function urem_get_expiration_units(): array {
		$units = array(
			'days'   => __( 'Hari', 'user-role-expiration-manager' ),
			'weeks'  => __( 'Minggu', 'user-role-expiration-manager' ),
			'months' => __( 'Bulan', 'user-role-expiration-manager' ),
			'years'  => __( 'Tahun', 'user-role-expiration-manager' ),
		);

		/**
		 * Filter available expiration units.
		 *
		 * @param array $units Array of unit key => unit label.
		 */
		return apply_filters( 'urem_expiration_units', $units );
	}
}

if ( ! function_exists( 'urem_get_status_badge_html' ) ) {
	/**
	 * Generate HTML badge for expiration status.
	 *
	 * @param string $status Status key: 'active', 'expiring_soon', 'expired', or 'disabled'.
	 * @return string HTML output.
	 */
	function urem_get_status_badge_html( string $status ): string {
		switch ( $status ) {
			case 'active':
				$class = 'urem-badge urem-badge-green';
				$label = __( 'Aktif', 'user-role-expiration-manager' );
				break;
			case 'expiring_soon':
				$class = 'urem-badge urem-badge-yellow';
				$label = __( 'Akan Expired (<30 hari)', 'user-role-expiration-manager' );
				break;
			case 'expired':
				$class = 'urem-badge urem-badge-red';
				$label = __( 'Expired', 'user-role-expiration-manager' );
				break;
			case 'disabled':
			default:
				$class = 'urem-badge urem-badge-gray';
				$label = __( 'Disabled', 'user-role-expiration-manager' );
				break;
		}

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( $label ) );
	}
}

if ( ! function_exists( 'urem_format_datetime' ) ) {
	/**
	 * Format timestamp to WP date format.
	 *
	 * @param int|string|null $timestamp Unix timestamp or string date.
	 * @return string Formatted date string or '-'.
	 */
	function urem_format_datetime( $timestamp ): string {
		if ( empty( $timestamp ) ) {
			return '-';
		}

		if ( ! is_numeric( $timestamp ) ) {
			$timestamp = strtotime( $timestamp );
		}

		if ( ! $timestamp ) {
			return '-';
		}

		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		return wp_date( $date_format, $timestamp );
	}
}
