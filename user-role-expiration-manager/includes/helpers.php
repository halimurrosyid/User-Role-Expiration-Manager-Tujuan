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
	 * Retrieve all registered and editable roles in WordPress.
	 *
	 * Fully compatible with WooCommerce, User Role Editor, Members, and Authorizer.
	 *
	 * @return array Array of role_key => Role Name.
	 */
	function urem_get_all_roles() {
		$roles = array(
			'none' => __( 'No Role (Remove All Roles)', 'user-role-expiration-manager' ),
		);

		$editable_roles = function_exists( 'get_editable_roles' ) ? get_editable_roles() : array();

		if ( ! empty( $editable_roles ) ) {
			foreach ( $editable_roles as $role_key => $role_data ) {
				$roles[ $role_key ] = function_exists( 'translate_user_role' ) ? translate_user_role( $role_data['name'] ) : $role_data['name'];
			}
		} else {
			global $wp_roles;
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
			foreach ( $wp_roles->get_names() as $role_key => $role_name ) {
				$roles[ $role_key ] = function_exists( 'translate_user_role' ) ? translate_user_role( $role_name ) : $role_name;
			}
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
	function urem_get_expiration_units() {
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

if ( ! function_exists( 'urem_get_duration_presets' ) ) {
	/**
	 * Get predefined quick duration presets.
	 *
	 * @return array Preset key => Preset configuration array.
	 */
	function urem_get_duration_presets() {
		$presets = array(
			'7_days'   => array(
				'label'    => __( '7 Hari', 'user-role-expiration-manager' ),
				'duration' => 7,
				'unit'     => 'days',
			),
			'14_days'  => array(
				'label'    => __( '14 Hari', 'user-role-expiration-manager' ),
				'duration' => 14,
				'unit'     => 'days',
			),
			'1_month'  => array(
				'label'    => __( '1 Bulan (30 Hari)', 'user-role-expiration-manager' ),
				'duration' => 1,
				'unit'     => 'months',
			),
			'3_months' => array(
				'label'    => __( '3 Bulan', 'user-role-expiration-manager' ),
				'duration' => 3,
				'unit'     => 'months',
			),
			'6_months' => array(
				'label'    => __( '6 Bulan', 'user-role-expiration-manager' ),
				'duration' => 6,
				'unit'     => 'months',
			),
			'1_year'   => array(
				'label'    => __( '1 Tahun', 'user-role-expiration-manager' ),
				'duration' => 1,
				'unit'     => 'years',
			),
			'2_years'  => array(
				'label'    => __( '2 Tahun', 'user-role-expiration-manager' ),
				'duration' => 2,
				'unit'     => 'years',
			),
		);

		/**
		 * Filter available duration presets.
		 *
		 * @param array $presets Array of preset key => details.
		 */
		return apply_filters( 'urem_duration_presets', $presets );
	}
}

if ( ! function_exists( 'urem_get_status_badge_html' ) ) {
	/**
	 * Generate HTML badge for expiration status.
	 *
	 * @param string $status Status key: 'active', 'expiring_soon', 'expired', or 'disabled'.
	 * @return string HTML output.
	 */
	function urem_get_status_badge_html( $status ) {
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
	function urem_format_datetime( $timestamp ) {
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
