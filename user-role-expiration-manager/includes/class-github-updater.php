<?php
/**
 * Self-Hosted GitHub Automatic Updater.
 *
 * Supports GitHub Releases API, direct raw main branch header fallback, force check trigger,
 * upgrader_source_selection folder routing, and native row notice injection.
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
		// Inject into both transient getter and setter
		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_popup_info' ), 20, 3 );

		// Render native yellow update row on plugins.php page
		add_action( 'after_plugin_row_' . $this->plugin_basename, array( $this, 'show_update_notification_row' ), 10, 2 );

		// Fix unzipped archive source selection & folder structure
		add_filter( 'upgrader_source_selection', array( $this, 'fix_upgrader_source_selection' ), 10, 4 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install_rename_folder' ), 10, 3 );

		// Force check trigger handler via admin-post
		add_action( 'admin_post_urem_force_check_update', array( $this, 'handle_force_check_update' ) );
	}

	/**
	 * Fetch release data from GitHub API with fallback to main branch header.
	 *
	 * @param bool $force_refresh Skip cache if true.
	 * @return object|null Release info object or null on failure.
	 */
	public function get_github_release_info( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_site_transient( $this->transient_key );
			if ( false !== $cached && is_object( $cached ) ) {
				return $cached;
			}
		}

		// Strategy 1: Check GitHub Releases API
		$url      = 'https://api.github.com/repos/' . $this->repository . '/releases/latest';
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

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );

			if ( ! empty( $data->tag_name ) ) {
				set_site_transient( $this->transient_key, $data, 6 * HOUR_IN_SECONDS );
				return $data;
			}
		}

		// Strategy 2: Fallback to direct raw GitHub main branch header parsing
		$raw_url  = 'https://raw.githubusercontent.com/' . $this->repository . '/main/user-role-expiration-manager/user-role-expiration-manager.php';
		$raw_resp = wp_remote_get( $raw_url, array( 'timeout' => 10 ) );

		if ( ! is_wp_error( $raw_resp ) && 200 === wp_remote_retrieve_response_code( $raw_resp ) ) {
			$content = wp_remote_retrieve_body( $raw_resp );

			if ( preg_match( '/Version:\s*([0-9\.]+)/i', $content, $matches ) ) {
				$remote_version = trim( $matches[1] );
				$fallback_data  = (object) array(
					'tag_name'     => 'v' . $remote_version,
					'zipball_url'  => 'https://github.com/' . $this->repository . '/archive/refs/heads/main.zip',
					'body'         => 'Pembaruan otomatis dari branch main GitHub repository.',
					'is_fallback'  => true,
				);

				set_site_transient( $this->transient_key, $fallback_data, 10 * MINUTE_IN_SECONDS );
				return $fallback_data;
			}
		}

		return null;
	}

	/**
	 * Hook into site_transient_update_plugins to announce updates.
	 *
	 * @param object $transient Transient data object.
	 * @return object Modified transient object.
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
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

			$plugin_data = (object) array(
				'id'          => 'https://github.com/' . $this->repository,
				'slug'        => $slug,
				'plugin'      => $this->plugin_basename,
				'new_version' => $new_version,
				'url'         => 'https://github.com/' . $this->repository,
				'package'     => $download_url,
				'tested'      => get_bloginfo( 'version' ),
				'requires'    => '5.8',
				'requires_php'=> '7.4',
			);

			if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
				$transient->response = array();
			}

			$transient->response[ $this->plugin_basename ] = $plugin_data;
		}

		return $transient;
	}

	/**
	 * Render native yellow update notification row on plugins.php page.
	 *
	 * @param string $file Plugin file name.
	 * @param array  $plugin_data Plugin header data.
	 * @return void
	 */
	public function show_update_notification_row( $file, $plugin_data ) {
		$release = $this->get_github_release_info();
		if ( ! $release ) {
			return;
		}

		$new_version = ltrim( $release->tag_name, 'v' );
		if ( ! version_compare( $this->current_version, $new_version, '<' ) ) {
			return;
		}

		$slug        = basename( dirname( UREM_PLUGIN_FILE ) );
		$details_url = add_query_arg(
			array(
				'tab'       => 'plugin-information',
				'plugin'    => $slug,
				'TB_iframe' => 'true',
				'width'     => '600',
				'height'    => '550',
			),
			self_admin_url( 'plugin-install.php' )
		);

		$update_url = wp_nonce_url(
			self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $file ),
			'upgrade-plugin_' . $file
		);

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		$column_count  = $wp_list_table ? $wp_list_table->get_column_count() : 4;

		add_thickbox();

		echo '<tr class="plugin-update-tr active" id="' . esc_attr( $slug ) . '-update" data-slug="' . esc_attr( $slug ) . '" data-plugin="' . esc_attr( $file ) . '">';
		echo '<td colspan="' . esc_attr( (string) $column_count ) . '" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt">';
		echo '<p>';
		printf(
			/* translators: 1: Plugin name, 2: Version number, 3: View details link, 4: Update link */
			esc_html__( 'Terdapat versi baru dari %1$s (%2$s) tersedia. %3$s atau %4$s.', 'user-role-expiration-manager' ),
			'<strong>User Role Expiration Manager</strong>',
			esc_html( $new_version ),
			sprintf( '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>', esc_url( $details_url ), esc_attr__( 'Lihat rincian versi', 'user-role-expiration-manager' ), sprintf( __( 'Lihat rincian versi %s', 'user-role-expiration-manager' ), esc_html( $new_version ) ) ),
			sprintf( '<a href="%s" class="update-link" aria-label="%s">%s</a>', esc_url( $update_url ), esc_attr__( 'Perbarui Sekarang', 'user-role-expiration-manager' ), esc_html__( 'Perbarui Sekarang', 'user-role-expiration-manager' ) )
		);
		echo '</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Route WordPress upgrader to the correct inner plugin source directory inside downloaded archive.
	 *
	 * @param string $source Unzipped source directory path.
	 * @param string $remote_source Remote source path.
	 * @param object $upgrader WP_Upgrader instance.
	 * @param array  $hook_extra Hook extra details.
	 * @return string Corrected source path.
	 */
	public function fix_upgrader_source_selection( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_basename ) {
			// Check if unzipped folder contains nested user-role-expiration-manager directory
			$nested_dir = untrailingslashit( $source ) . '/user-role-expiration-manager/';
			if ( file_exists( $nested_dir . 'user-role-expiration-manager.php' ) ) {
				return $nested_dir;
			}
		}

		return $source;
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
		$res->requires      = '5.8';
		$res->tested        = get_bloginfo( 'version' );
		$res->requires_php  = '7.4';
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

			// Handle nested plugin folder inside github archive zip if needed
			$inner_plugin_dir = $result['destination'] . '/user-role-expiration-manager';
			if ( $wp_filesystem->is_dir( $inner_plugin_dir ) ) {
				$wp_filesystem->move( $inner_plugin_dir, $correct_folder );
				$wp_filesystem->delete( $result['destination'], true );
			} else {
				$wp_filesystem->move( $result['destination'], $correct_folder );
			}

			$result['destination'] = $correct_folder;
		}

		// Clear update cache after install
		delete_site_transient( $this->transient_key );
		delete_site_transient( 'update_plugins' );

		return $result;
	}

	/**
	 * Handle instant force check update action from admin.
	 *
	 * @return void
	 */
	public function handle_force_check_update() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'Unauthorized user.', 'user-role-expiration-manager' ) );
		}

		check_admin_referer( 'urem_force_check_update_nonce', 'urem_nonce' );

		delete_site_transient( $this->transient_key );
		delete_site_transient( 'update_plugins' );

		// Force fetch fresh info
		$release = $this->get_github_release_info( true );

		// Force update plugins transient save
		if ( $release ) {
			$new_version = ltrim( $release->tag_name, 'v' );
			if ( version_compare( $this->current_version, $new_version, '<' ) ) {
				$transient = get_site_transient( 'update_plugins' );
				if ( ! is_object( $transient ) ) {
					$transient = new \stdClass();
				}
				$slug        = basename( dirname( UREM_PLUGIN_FILE ) );
				$plugin_data = (object) array(
					'id'          => 'https://github.com/' . $this->repository,
					'slug'        => $slug,
					'plugin'      => $this->plugin_basename,
					'new_version' => $new_version,
					'url'         => 'https://github.com/' . $this->repository,
					'package'     => ! empty( $release->zipball_url ) ? $release->zipball_url : '',
					'tested'      => get_bloginfo( 'version' ),
					'requires'    => '5.8',
					'requires_php'=> '7.4',
				);
				if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
					$transient->response = array();
				}
				$transient->response[ $this->plugin_basename ] = $plugin_data;
				set_site_transient( 'update_plugins', $transient );
			}
		}

		$redirect_url = add_query_arg(
			array(
				'urem_update_checked' => 1,
			),
			admin_url( 'plugins.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
