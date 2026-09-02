<?php
/**
 * Plugin Name: User Role Expiration Manager
 * Plugin URI: https://github.com/halimurrosyid/User-Role-Expiration-Manager-Tujuan
 * Description: Mengelola masa berlaku role pengguna WordPress secara otomatis dan aman dengan integrasi menu native Pengguna, per-user profile panel, WP-Cron batching, dan logger.
 * Version: 1.1.5
 * Author: Mujaddid Halimurrosyid
 * Author URI: https://it.telkomuniversity.ac.id/
 * Text Domain: user-role-expiration-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package UserRoleExpirationManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants
define( 'UREM_VERSION', '1.1.5' );
define( 'UREM_PLUGIN_FILE', __FILE__ );
define( 'UREM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UREM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Autoloader with safety check
$urem_autoloader = UREM_PLUGIN_DIR . 'includes/class-autoloader.php';
if ( file_exists( $urem_autoloader ) ) {
	require_once $urem_autoloader;
	\UserRoleExpirationManager\Autoloader::register();
}

/**
 * Register Activation & Deactivation Hooks.
 */
register_activation_hook( __FILE__, array( '\UserRoleExpirationManager\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\UserRoleExpirationManager\Plugin', 'deactivate' ) );

/**
 * Bootstrap Main Plugin Instance.
 */
function urem_init_plugin() {
	if ( class_exists( '\UserRoleExpirationManager\Plugin' ) ) {
		return \UserRoleExpirationManager\Plugin::get_instance();
	}
	return null;
}

// Fire plugin bootstrap
urem_init_plugin();
