<?php
/**
 * Settings API Handler Class.
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
	 * Option name in wp_options table.
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
	 * @return array Default settings array.
	 */
	public static function get_defaults(): array {
		$site_name = get_bloginfo( 'name' );

		return array(
			'plugin_enabled'        => '1',
			'default_duration'      => 30,
			'default_unit'          => 'days',
			'default_role'          => 'subscriber',
			'cron_schedule'         => 'daily',
			'logout_user_on_expire' => '0',
			'send_email_on_expire'  => '1',
			'email_subject'         => sprintf( '[%s] Masa Berlaku Role Anda Telah Habis', $site_name ),
			'email_message'         => "Halo {user_name},\r\n\r\nMasa berlaku role Anda di {site_name} telah habis pada {expiration_date}.\r\nRole Anda telah diubah dari {old_role} menjadi {new_role}.\r\n\r\nJika Anda memerlukan bantuan atau ingin memperbarui akses, silakan hubungi administrator.\r\n\r\nHormat kami,\r\n{site_name}",
			'send_reminder_email'   => '1',
			'reminder_days_before'  => 3,
			'reminder_subject'      => sprintf( '[%s] Peringatan: Role Anda Akan Expired dalam {days_left} Hari', $site_name ),
			'reminder_message'      => "Halo {user_name},\r\n\r\nPemberitahuan: Masa berlaku role ({old_role}) Anda di {site_name} akan expired dalam {days_left} hari pada tanggal {expiration_date}.\r\n\r\nSilakan perbarui keanggotaan Anda untuk mempertahankan akses.\r\n\r\nHormat kami,\r\n{site_name}",
			'dry_run_mode'          => '0',
			'enable_logging'        => '1',
			'log_retention_days'    => 90,
		);
	}

	/**
	 * Get stored settings merged with defaults.
	 *
	 * @return array Stored settings array.
	 */
	public static function get_settings(): array {
		$defaults = self::get_defaults();
		$stored   = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, $defaults );
	}

	/**
	 * Register Settings API fields with WordPress.
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
	 * Sanitize submitted settings inputs.
	 *
	 * @param array $input Raw input array.
	 * @return array Sanitized settings array.
	 */
	public static function sanitize_settings( $input ): array {
		$defaults  = self::get_defaults();
		$sanitized = array();

		$sanitized['plugin_enabled']        = ! empty( $input['plugin_enabled'] ) ? '1' : '0';
		$sanitized['default_duration']      = isset( $input['default_duration'] ) ? max( 1, absint( $input['default_duration'] ) ) : $defaults['default_duration'];
		$sanitized['default_unit']          = isset( $input['default_unit'] ) ? sanitize_text_field( $input['default_unit'] ) : $defaults['default_unit'];
		$sanitized['default_role']          = isset( $input['default_role'] ) ? sanitize_text_field( $input['default_role'] ) : $defaults['default_role'];
		$sanitized['cron_schedule']         = isset( $input['cron_schedule'] ) ? sanitize_text_field( $input['cron_schedule'] ) : $defaults['cron_schedule'];
		$sanitized['logout_user_on_expire'] = ! empty( $input['logout_user_on_expire'] ) ? '1' : '0';

		// Email Expiration
		$sanitized['send_email_on_expire']  = ! empty( $input['send_email_on_expire'] ) ? '1' : '0';
		$sanitized['email_subject']         = isset( $input['email_subject'] ) ? sanitize_text_field( $input['email_subject'] ) : $defaults['email_subject'];
		$sanitized['email_message']         = isset( $input['email_message'] ) ? sanitize_textarea_field( $input['email_message'] ) : $defaults['email_message'];

		// Reminder Email
		$sanitized['send_reminder_email']   = ! empty( $input['send_reminder_email'] ) ? '1' : '0';
		$sanitized['reminder_days_before']  = isset( $input['reminder_days_before'] ) ? max( 1, absint( $input['reminder_days_before'] ) ) : $defaults['reminder_days_before'];
		$sanitized['reminder_subject']      = isset( $input['reminder_subject'] ) ? sanitize_text_field( $input['reminder_subject'] ) : $defaults['reminder_subject'];
		$sanitized['reminder_message']      = isset( $input['reminder_message'] ) ? sanitize_textarea_field( $input['reminder_message'] ) : $defaults['reminder_message'];

		$sanitized['dry_run_mode']          = ! empty( $input['dry_run_mode'] ) ? '1' : '0';
		$sanitized['enable_logging']        = ! empty( $input['enable_logging'] ) ? '1' : '0';
		$sanitized['log_retention_days']    = isset( $input['log_retention_days'] ) ? absint( $input['log_retention_days'] ) : $defaults['log_retention_days'];

		// Re-schedule cron if schedule setting changed
		$old_settings = self::get_settings();
		if ( $old_settings['cron_schedule'] !== $sanitized['cron_schedule'] ) {
			Cron::reschedule( $sanitized['cron_schedule'] );
		}

		return $sanitized;
	}
}
