<?php
/**
 * Main Plugin Orchestrator Singleton.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Instance variable.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Main Instance Singleton Pattern.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants.
	 *
	 * @return void
	 */
	private function define_constants() {
		if ( ! defined( 'UREM_VERSION' ) ) {
			define( 'UREM_VERSION', '1.0.0' );
		}
		if ( ! defined( 'UREM_GITHUB_REPO' ) ) {
			define( 'UREM_GITHUB_REPO', 'halimurrosyid/User-Role-Expiration-Manager-Tujuan' );
		}
	}

	/**
	 * Load core files and helpers.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once UREM_PLUGIN_DIR . 'includes/helpers.php';
	}

	/**
	 * Initialize components and hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Component Initializations
		Settings::init();
		Admin::init();
		User_Meta::init();
		User_List_Table::init();
		Cron::init();
		Dashboard_Widget::init();

		// GitHub Automatic Updater Initialization
		$updater = new GitHub_Updater( UREM_GITHUB_REPO );
		$updater->init();

		// Load Textdomain
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'user-role-expiration-manager',
			false,
			dirname( plugin_basename( UREM_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Plugin Activation Handler.
	 *
	 * @return void
	 */
	public static function activate() {
		Logger::create_table();
		Cron::schedule_cron();

		// Ensure default options exist
		if ( ! get_option( Settings::OPTION_NAME ) ) {
			update_option( Settings::OPTION_NAME, Settings::get_defaults() );
		}

		// Clear rewrite rules or transients if needed
		delete_transient( 'urem_expiring_users_cache' );
		delete_site_transient( 'urem_github_release_info' );
	}

	/**
	 * Plugin Deactivation Handler.
	 *
	 * @return void
	 */
	public static function deactivate() {
		Cron::unschedule_cron();
		delete_site_transient( 'urem_github_release_info' );
	}
}
