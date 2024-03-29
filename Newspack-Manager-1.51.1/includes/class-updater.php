<?php
/**
 * Newspack Plugin Updater.
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class for the plugin updater.
 */
final class Updater {
	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin;

	/**
	 * Plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * GitHub repository.
	 *
	 * @var string
	 */
	private $github_repository;

	/**
	 * Constructor.
	 *
	 * @param string $plugin            Plugin.
	 * @param string $plugin_file       Plugin file.
	 * @param string $github_repository GitHub repository.
	 */
	public function __construct( $plugin, $plugin_file, $github_repository ) {
		$this->plugin            = $plugin;
		$this->plugin_file       = $plugin_file;
		$this->github_repository = $github_repository;
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'add_update_data' ] );
	}

	/**
	 * Get GitHub API url.
	 *
	 * @return string
	 */
	private function get_api_url() {
		return 'https://api.github.com/repos/' . $this->github_repository . '/releases/latest';
	}

	/**
	 * Fetch GitHub data on the latest release.
	 *
	 * @return array|bool
	 */
	private function fetch_latest_github_data() {
		$response = wp_safe_remote_get( $this->get_api_url() );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return false;
		}

		$data = json_decode( $body, true );
		if ( empty( $data ) ) {
			return false;
		}

		if ( empty( $data['tag_name'] ) || empty( $data['assets'] ) ) {
			return false;
		}

		return $data;
	}

	/**
	 * Get the available release data.
	 */
	private function get_release_data() {
		$transient_key = sprintf( 'newspack_updater_%s', $this->plugin );
		$release_data  = get_transient( $transient_key );
		if ( false === $release_data ) {
			$github_data = self::fetch_latest_github_data();
			$expiration  = 60 * 60 * 12; // 12 hours.
			if ( $github_data ) {
				$plugin_data  = \get_plugin_data( $this->plugin_file );
				$release_data = (object) [
					'id'            => $this->plugin,
					'slug'          => $plugin_data['TextDomain'],
					'plugin'        => $this->plugin,
					'new_version'   => str_replace( 'v', '', $github_data['tag_name'] ),
					'url'           => $plugin_data['PluginURI'],
					'package'       => $github_data['assets'][0]['browser_download_url'],
					'icons'         => [],
					'banners'       => [],
					'banners_rtl'   => [],
					'tested'        => '',
					'requires_php'  => '',
					'compatibility' => new \stdClass(),
				];
			} else {
				$release_data = [];
				$expiration   = 60; // 1 minute.
			}
			set_transient( $transient_key, $release_data, $expiration );
		}
		return $release_data;
	}

	/**
	 * Add update data.
	 *
	 * @param array $transient Transient data.
	 * @return array
	 */
	public function add_update_data( $transient ) {
		$data = self::get_release_data();
		if ( empty( $data ) ) {
			return $transient;
		}
		$plugin_data = \get_plugin_data( $this->plugin_file );
		if ( version_compare( $plugin_data['Version'], $data->new_version, '<' ) ) {
			$transient->response[ $this->plugin ] = $data;
			unset( $transient->no_update[ $this->plugin ] );
		} else {
			$transient->no_update[ $this->plugin ] = $data;
			unset( $transient->response[ $this->plugin ] );
		}
		return $transient;
	}
}

