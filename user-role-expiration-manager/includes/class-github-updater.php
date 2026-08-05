<?php
/**
 * Self-Hosted GitHub Automatic Updater.
 *
 * @package UserRoleExpirationManager
 */

namespace UserRoleExpirationManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GitHub_Updater
 */
class GitHub_Updater {

	/**
	 * Repository slug (owner/repo).
	 *
	 * @var string
	 */
	private $repository;

	/**
	 * Plugin basename (folder/file.php).
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Transient key for caching API response.
	 *
	 * @var string
	 */
	private $transient_key = 'urem_github_release_info';

	/**
	 * Constructor.
	 *
	 * @param string $repository Repository path e.g. 'halimurrosyid/User-Role-Expiration-Manager-Tujuan'.
	 */
	public function __construct( $repository ) {
		$this->repository      = $repository;
		$this->plugin_basename = plugin_basename( UREM_PLUGIN_FILE );
		$this->current_version = UREM_VERSION;
	}

	/**
	 * Initialize updater hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_popup_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install_rename_folder' ), 10, 3 );
	}

	/**
	 * Fetch release data from GitHub API.
	 *
	 * @return object|null Release info object or null on failure.
	 */
	private function get_github_release_info() {
		$cached = get_site_transient( $this->transient_key );
		if ( false !== $cached && is_object( $cached ) ) {
			return $cached;
		}

		$url = 'https://api.github.com/repos/' . $this->repository . '/releases/latest';

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( empty( $data->tag_name ) ) {
			return null;
		}

		// Cache for 6 hours
		set_site_transient( $this->transient_key, $data, 6 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Hook into pre_set_site_transient_update_plugins to announce updates.
	 *
	 * @param object $transient Transient data object.
	 * @return object Modified transient object.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_github_release_info();
		if ( ! $release ) {
			return $transient;
		}

		$new_version = ltrim( $release->tag_name, 'v' );

		if ( version_compare( $this->current_version, $new_version, '<' ) ) {
			$download_url = ! empty( $release->zipball_url ) ? $release->zipball_url : '';

			// Check asset zip attachment if present
			if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
				foreach ( $release->assets as $asset ) {
					if ( isset( $asset->browser_download_url ) && '.zip' === substr( $asset->name, -4 ) ) {
						$download_url = $asset->browser_download_url;
						break;
					}
				}
			}

			$slug = basename( dirname( UREM_PLUGIN_FILE ) );

			$plugin_data = array(
				'slug'        => $slug,
				'plugin'      => $this->plugin_basename,
				'new_version' => $new_version,
				'url'         => 'https://github.com/' . $this->repository,
				'package'     => $download_url,
			);

			$transient->response[ $this->plugin_basename ] = (object) $plugin_data;
		}

		return $transient;
	}

	/**
	 * Provide plugin details for the "View Details" popup.
	 *
	 * @param object|bool $result Result object.
	 * @param string      $action Action type.
	 * @param object      $args Query args.
	 * @return object|bool
	 */
	public function plugin_popup_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		$slug = basename( dirname( UREM_PLUGIN_FILE ) );
		if ( ! isset( $args->slug ) || $args->slug !== $slug ) {
			return $result;
		}

		$release = $this->get_github_release_info();
		if ( ! $release ) {
			return $result;
		}

		$new_version = ltrim( $release->tag_name, 'v' );

		$res                = new \stdClass();
		$res->name          = 'User Role Expiration Manager';
		$res->slug          = $slug;
		$res->version       = $new_version;
		$res->author        = '<a href="https://it.telkomuniversity.ac.id/" target="_blank">Mujaddid Halimurrosyid</a>';
		$res->homepage      = 'https://github.com/' . $this->repository;
		$res->requires      = '6.8';
		$res->tested        = get_bloginfo( 'version' );
		$res->requires_php  = '8.1';
		$res->download_link = ! empty( $release->zipball_url ) ? $release->zipball_url : '';

		$changelog_content  = ! empty( $release->body ) ? wp_kses_post( nl2br( $release->body ) ) : esc_html__( 'Maintenance update and bug fixes.', 'user-role-expiration-manager' );
		$res->sections      = array(
			'description' => esc_html__( 'Mengelola masa berlaku role pengguna WordPress secara otomatis, aman, dan efisien.', 'user-role-expiration-manager' ),
			'changelog'   => $changelog_content,
		);

		return $res;
	}

	/**
	 * Rename destination folder after unzipping GitHub package.
	 *
	 * @param bool  $response Install response.
	 * @param array $hook_extra Hook extra parameters.
	 * @param array $result Result info.
	 * @return array Modified result info.
	 */
	public function post_install_rename_folder( $response, $hook_extra, $result ) {
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $result;
		}

		$correct_folder = WP_PLUGIN_DIR . '/' . basename( dirname( UREM_PLUGIN_FILE ) );

		if ( isset( $result['destination'] ) && $result['destination'] !== $correct_folder ) {
			global $wp_filesystem;
			$wp_filesystem->move( $result['destination'], $correct_folder );
			$result['destination'] = $correct_folder;
		}

		// Clear update cache after install
		delete_site_transient( $this->transient_key );

		return $result;
	}
}
