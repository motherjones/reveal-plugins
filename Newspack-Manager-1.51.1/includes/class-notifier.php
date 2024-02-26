<?php
/**
 * Newspack Manager Notifier
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager Notifier Class.
 */
final class Notifier {
	/**
	 * The single instance of the class.
	 *
	 * @var Notifier
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Notifier Server Instance.
	 * Ensures only one instance of Newspack Notifier Server Instance is loaded or can be loaded.
	 *
	 * @return Notifier - Instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action(
			'activated_plugin',
			function( $plugin ) {
				self::handle_plugin_state_change( 'activated', $plugin );
			}
		);
		add_action(
			'deactivated_plugin',
			function( $plugin ) {
				self::handle_plugin_state_change( 'deactivated', $plugin );
			}
		);
	}

	/**
	 * Notify on Slack when a plugin is installed or de/activated.
	 *
	 * @param string $state The state of the plugin (activated, deactivated).
	 * @param string $plugin The plugin path (relative).
	 */
	private static function handle_plugin_state_change( $state, $plugin ) {
		$username = wp_get_current_user()->user_login;
		if ( empty( $username ) || NEWSPACK_MANAGER_ADMIN_USERNAME === $username ) {
			return false;
		}

		$plugin_name = '';
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
		if ( ! empty( $plugin_data['Name'] ) ) {
			$plugin_name = $plugin_data['Name'];
		}

		$message = sprintf(
			/* translators: 1: plugin name, 2: plugin filename, 3: plugin state (activated, deactivated), 4: username */
			__( 'Plugin *%1$s* (`%2$s`) was *%3$s* by *%4$s*.', 'newspack' ),
			$plugin_name,
			$plugin,
			$state,
			$username
		);
		if ( method_exists( '\Newspack\Plugin_Manager', 'get_approved_plugins_slugs' ) ) {
			$approved_plugins = \Newspack\Plugin_Manager::get_approved_plugins_slugs();
			if ( ! isset( $approved_plugins[ $plugin_name ] ) ) {
				$message .= ' ' . __( 'It appears that this plugin isn\'t included on our approved list, please use <https://docs.google.com/forms/d/e/1FAIpQLSdoGk3xiUl8YvuJbbi8DxwqKV-x_H6alAvynw3MVlxYQEWQew/viewform|our form> to submit it for review before implementing on the site.', 'newspack-manager' );
			}
		}
		$params = [
			'site_url' => site_url(),
			'message'  => $message,
		];
		$url    = \Newspack_Manager::authenticate_manager_client_url(
			'/wp-json/newspack-manager-client/v1/newspack-slack-alert',
			$params
		);
		$result = \wp_safe_remote_post( $url );
		if ( ! is_wp_error( $result ) && 200 === $result['response']['code'] ) {
			return true;
		} else {
			return new \WP_Error( 'newspack_manager_sw_message_error', __( 'Error sending message to Newspack Manager', 'newspack' ) );
		}
	}
}
Notifier::instance();

