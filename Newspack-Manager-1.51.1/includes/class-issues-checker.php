<?php
/**
 * Newspack Manager Issues Checker
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager Issues Checker class.
 */
final class Issues_Checker {
	/**
	 * The single instance of the class.
	 *
	 * @var Issues_Checker
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Issues_Checker Server Instance.
	 * Ensures only one instance of Newspack Issues_Checker Server Instance is loaded or can be loaded.
	 *
	 * @return Issues_Checker - Instance.
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
		// Run the check in WP cron every day.
		add_action( 'newspack_manager_cron_hook_issues_checker', [ __CLASS__, 'check_issues_daily' ] );
		if ( ! wp_next_scheduled( 'newspack_manager_cron_hook_issues_checker' ) ) {
			wp_schedule_event( time(), 'daily', 'newspack_manager_cron_hook_issues_checker' );
		}

		add_action( 'switch_theme', [ __CLASS__, 'check_issues_on_theme_switch' ] );
	}

	/**
	 * Daily issues checking.
	 */
	public static function check_issues_daily() {
		self::check_ga_settings();
		self::check_managed_extensions_symlink();
	}

	/**
	 * Daily checks for GA settings.
	 */
	private static function check_ga_settings() {
		$issues            = [];
		$site_kit_settings = Utils::get_sitekit_settings();
		if ( isset( $site_kit_settings['ga4'], $site_kit_settings['ga4']['useSnippet'] ) && ! $site_kit_settings['ga4']['useSnippet'] ) {
			$issues[] = 'GA4 snippet is not inserted via Site Kit';
		}

		if ( empty( $issues ) ) {
			return;
		}

		$message = implode( ', ', $issues ) . '. ';

		self::send_request( $message );
	}

	/**
	 * Daily check for managed extensions symlink.
	 */
	private static function check_managed_extensions_symlink() {
		$managed_extensions = [
			'themes'  => [
				'newspack-theme',
				'newspack-joseph',
				'newspack-katharine',
				'newspack-nelson',
				'newspack-sacha',
				'newspack-scott',
			],
			'plugins' => [
				'distributor',
				'newspack-ads',
				'newspack-blocks',
				'newspack-listings',
				'newspack-multibranded-site',
				'newspack-newsletters',
				'newspack-plugin',
				'newspack-popups',
				'newspack-manager',
				'newspack-network',
				'newspack-sponsors',
				'woocommerce',
				'woocommerce-gateway-stripe',
				'woocommerce-subscriptions',
				'wp-parsely',
			],
		];

		$expected_symlink_end = '%s/%s/latest';
		$issues               = [];

		foreach ( $managed_extensions as $type => $extensions ) {
			foreach ( $extensions as $extension ) {
				$expected_path = sprintf( $expected_symlink_end, $type, $extension );
				$actual_path   = WP_CONTENT_DIR . "/$type/$extension";
				$issue         = false;

				// Only the directory exists. Skip plugins/themes that aren't installed.
				if ( is_link( $actual_path ) ) {
					$link_dest = readlink( $actual_path );
					if ( ! preg_match( "|$expected_path\/?$|", $link_dest ) ) { // if doesn't ends with expected path.
						$issue = true;
					}
				} elseif ( is_dir( $actual_path ) ) {
					$issue = true;
				}

				if ( $issue ) {
					$issues[] = $extension;
				}
			}
		}

		if ( empty( $issues ) ) {
			return;
		}

		$message = 'Some extensions are not symlinked to the latest version: `' . implode( '`, `', $issues ) . '`.';

		self::send_request( $message, 'private_v2' );
	}

	/**
	 * Alert on slack when there's a theme switch
	 *
	 * @param string $new_name The new theme name.
	 */
	public static function check_issues_on_theme_switch( $new_name ) {
		$message      = 'Theme was switched to `' . $new_name . '`';
		$non_newspack = strpos( $new_name, 'Newspack' ) !== 0;
		if ( $non_newspack ) {
			$message = 'ATTENTION! Theme was switched to non Newspack theme: `' . $new_name . '`';
		}
		self::send_request( $message, 'private_v2' );
	}

	/**
	 * Send a request to the Newspack Manager Server to report an issue.
	 *
	 * @param string $message The message to send.
	 * @param string $a8c_channel_slug The slug of the channel, as defined in newspack-manager-client, to send the message to.
	 * @return void
	 */
	private static function send_request( $message, $a8c_channel_slug = 'default' ) {

		$site_url = site_url();

		/**
		 * The list of domains that should not send issues to the server.
		 *
		 * @param array $domains_ignore_list The list of domains.
		 */
		$domains_ignore_list = apply_filters( 'newspack_manager_issue_checker_domains_ignore_list', [ 'newspackstaging.com' ] );

		foreach ( $domains_ignore_list as $domain ) {
			if ( false !== strpos( $site_url, $domain ) ) {
				return;
			}
		}

		$params = [
			'site_url'         => site_url(),
			'message'          => $message,
			'a8c_channel_slug' => $a8c_channel_slug,
		];
		$url    = \Newspack_Manager::authenticate_manager_client_url(
			'/wp-json/newspack-manager-client/v1/frontend-report',
			$params
		);
		$result = \wp_safe_remote_post( $url );
		if ( is_wp_error( $result ) || 200 !== $result['response']['code'] ) {
			if ( class_exists( '\Newspack\Logger' ) ) {
				\Newspack\Logger::error( 'Failed to post a message about issues to Slack.' );
				if ( is_wp_error( $result ) ) {
					\Newspack\Logger::error( $result->get_error_message() );
				}
			}
		}
	}
}
Issues_Checker::instance();

