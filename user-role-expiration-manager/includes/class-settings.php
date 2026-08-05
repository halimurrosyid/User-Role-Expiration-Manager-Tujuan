<?php
/**
 * Settings Handler Class.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	/**
	 * Option key constant.
	 */
	public const OPTION_NAME = 'urem_settings';

	/**
	 * Register settings hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Get default settings values.
	 *
	 * @return array
	 */
	public static function get_defaults(): array {
		return array(
			'plugin_enabled'         => '1',
			'default_duration'       => 30,
			'default_unit'           => 'days',
			'default_role'           => 'subscriber',
			'cron_schedule'          => 'daily',
			'logout_user_on_expire'  => '0',
			'send_email_on_expire'   => '1',
			'dry_run_mode'           => '0',
			'enable_logging'         => '1',
		);
	}

	/**
	 * Retrieve plugin settings with defaults populated.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		$saved    = get_option( self::OPTION_NAME, array() );
		$defaults = self::get_defaults();

		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	/**
	 * Register WordPress settings API options.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			'urem_settings_group',
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_defaults(),
			)
		);
	}

	/**
	 * Sanitize submitted settings inputs.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized input.
	 */
	public static function sanitize_settings( $input ): array {
		$output   = array();
		$defaults = self::get_defaults();

		$output['plugin_enabled']        = ! empty( $input['plugin_enabled'] ) ? '1' : '0';
		$output['default_duration']      = isset( $input['default_duration'] ) ? max( 1, absint( $input['default_duration'] ) ) : $defaults['default_duration'];
		$output['default_unit']          = isset( $input['default_unit'] ) && in_array( $input['default_unit'], array_keys( urem_get_expiration_units() ), true ) ? $input['default_unit'] : $defaults['default_unit'];
		$output['default_role']          = isset( $input['default_role'] ) && in_array( $input['default_role'], array_keys( urem_get_all_roles() ), true ) ? $input['default_role'] : $defaults['default_role'];
		$output['cron_schedule']         = isset( $input['cron_schedule'] ) && in_array( $input['cron_schedule'], array( 'hourly', 'twicedaily', 'daily' ), true ) ? $input['cron_schedule'] : $defaults['cron_schedule'];
		$output['logout_user_on_expire'] = ! empty( $input['logout_user_on_expire'] ) ? '1' : '0';
		$output['send_email_on_expire']  = ! empty( $input['send_email_on_expire'] ) ? '1' : '0';
		$output['dry_run_mode']          = ! empty( $input['dry_run_mode'] ) ? '1' : '0';
		$output['enable_logging']        = ! empty( $input['enable_logging'] ) ? '1' : '0';

		// Re-schedule cron if schedule setting changed
		$old_settings = self::get_settings();
		if ( $old_settings['cron_schedule'] !== $output['cron_schedule'] ) {
			Cron::reschedule_cron( $output['cron_schedule'] );
		}

		return $output;
	}
}
