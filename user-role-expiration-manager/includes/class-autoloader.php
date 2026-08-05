<?php
/**
 * Autoloader for User Role Expiration Manager.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Autoloader
 */
class Autoloader {

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload classes based on namespace.
	 *
	 * @param string $class_name Class name to load.
	 * @return void
	 */
	public static function autoload( string $class_name ): void {
		if ( 0 !== strpos( $class_name, __NAMESPACE__ . '\\' ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( __NAMESPACE__ . '\\' ) );
		$parts          = explode( '\\', $relative_class );
		$final_class    = array_pop( $parts );

		// Convert ClassName to class-classname.php
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $final_class ) ) . '.php';

		$sub_path = '';
		if ( ! empty( $parts ) ) {
			$sub_path = implode( '/', array_map( 'strtolower', $parts ) ) . '/';
		}

		$file = UREM_PLUGIN_DIR . 'includes/' . $sub_path . $file_name;

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
