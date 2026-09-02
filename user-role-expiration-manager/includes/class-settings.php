<?php
/**
 * Plugin Settings API Handler.
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
	 * Option key in wp_options.
	 */
	public const OPTION_KEY = 'urem_settings';

	/**
	 * Register settings hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Get default settings values.
	 *
	 * @return array Default settings.
	 */
	public static function get_defaults(): array {
		$current_user_id = get_current_user_id();

		return array(
			'enabled'                => '1',
			'default_duration'       => 30,
			'default_unit'           => 'days',
			'default_role'           => 'subscriber',
			'cron_schedule'          => 'daily',
			'logout_user_on_expire'  => '0',
			'send_email_on_expire'   => '1',
			'email_subject'          => __( 'Pemberitahuan: Masa Berlaku Peran Pengguna Anda Telah Berakhir di {site_name}', 'user-role-expiration-manager' ),
			'email_message'          => __( "Halo {user_name},\n\nMasa berlaku peran/role ({old_role}) Anda di {site_name} telah berakhir pada {expiration_date}.\nPeran akun Anda kini telah diubah menjadi ({new_role}).\n\nJika ada pertanyaan, silakan hubungi tim administrator situs.\n\nSalam,\n{site_name}", 'user-role-expiration-manager' ),
			'send_reminder_email'    => '1',
			'reminder_days_before'   => 3,
			'reminder_subject'       => __( 'Penting: Peran Pengguna Anda Akan Expired dalam {days_left} Hari - {site_name}', 'user-role-expiration-manager' ),
			'reminder_message'       => __( "Halo {user_name},\n\nPeran/role ({old_role}) Anda di {site_name} akan berakhir dalam {days_left} hari lagi (pada {expiration_date}).\nSetelah tanggal tersebut, peran Anda akan otomatis berubah menjadi ({new_role}).\n\nSilakan lakukan perpanjangan keanggotaan sebelum batas waktu habis.\n\nSalam,\n{site_name}", 'user-role-expiration-manager' ),
			'enable_logging'         => '1',
			'log_retention_days'     => 90,
			'dry_run_mode'           => '0',
			'primary_admin_id'       => $current_user_id ? $current_user_id : 1,
		);
	}

	/**
	 * Retrieve saved settings with defaults fallback.
	 *
	 * @return array Settings array.
	 */
	public static function get_settings(): array {
		$saved    = get_option( self::OPTION_KEY, array() );
		$defaults = self::get_defaults();

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$settings = wp_parse_args( $saved, $defaults );

		// Auto-set primary admin ID if missing
		if ( empty( $settings['primary_admin_id'] ) && get_current_user_id() ) {
			$settings['primary_admin_id'] = get_current_user_id();
		}

		return $settings;
	}

	/**
	 * Register settings field with WordPress Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'urem_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_defaults(),
			)
		);
	}

	/**
	 * Sanitize submitted settings form values.
	 *
	 * @param array $input Submitted data.
	 * @return array Sanitized data.
	 */
	public static function sanitize_settings( $input ): array {
		$output   = array();
		$defaults = self::get_defaults();

		$output['enabled']               = ! empty( $input['enabled'] ) ? '1' : '0';
		$output['default_duration']      = isset( $input['default_duration'] ) ? max( 1, absint( $input['default_duration'] ) ) : 30;
		$output['default_unit']          = isset( $input['default_unit'] ) && in_array( $input['default_unit'], array( 'days', 'weeks', 'months', 'years' ), true ) ? $input['default_unit'] : 'days';

		$all_roles = urem_get_all_roles();
		$output['default_role']          = isset( $input['default_role'] ) && isset( $all_roles[ $input['default_role'] ] ) ? $input['default_role'] : 'subscriber';

		$output['cron_schedule']         = isset( $input['cron_schedule'] ) && in_array( $input['cron_schedule'], array( 'hourly', 'twicedaily', 'daily' ), true ) ? $input['cron_schedule'] : 'daily';

		$output['logout_user_on_expire'] = ! empty( $input['logout_user_on_expire'] ) ? '1' : '0';
		$output['send_email_on_expire']  = ! empty( $input['send_email_on_expire'] ) ? '1' : '0';

		$output['email_subject']         = isset( $input['email_subject'] ) ? sanitize_text_field( wp_unslash( $input['email_subject'] ) ) : $defaults['email_subject'];
		$output['email_message']         = isset( $input['email_message'] ) ? sanitize_textarea_field( wp_unslash( $input['email_message'] ) ) : $defaults['email_message'];

		$output['send_reminder_email']   = ! empty( $input['send_reminder_email'] ) ? '1' : '0';
		$output['reminder_days_before']  = isset( $input['reminder_days_before'] ) ? max( 1, absint( $input['reminder_days_before'] ) ) : 3;

		$output['reminder_subject']      = isset( $input['reminder_subject'] ) ? sanitize_text_field( wp_unslash( $input['reminder_subject'] ) ) : $defaults['reminder_subject'];
		$output['reminder_message']      = isset( $input['reminder_message'] ) ? sanitize_textarea_field( wp_unslash( $input['reminder_message'] ) ) : $defaults['reminder_message'];

		$output['enable_logging']        = ! empty( $input['enable_logging'] ) ? '1' : '0';
		$output['log_retention_days']    = isset( $input['log_retention_days'] ) ? max( 0, absint( $input['log_retention_days'] ) ) : 90;
		$output['dry_run_mode']          = ! empty( $input['dry_run_mode'] ) ? '1' : '0';

		// Preserve primary admin ID
		$existing                        = get_option( self::OPTION_KEY, array() );
		$output['primary_admin_id']      = ! empty( $existing['primary_admin_id'] ) ? (int) $existing['primary_admin_id'] : get_current_user_id();

		return $output;
	}
}
