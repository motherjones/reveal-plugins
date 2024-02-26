<?php
/**
 * Newspack Manager performance.
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager performance class.
 */
final class Performance {
	/**
	 * The single instance of the class.
	 *
	 * @var Performance
	 */
	protected static $instance = null;

	/**
	 * Hook name for the cron job used to activate the license.
	 */
	const CRON_HOOK = 'newspack_manager_perfmatters_license_check';

	/**
	 * Main Newspack Performance Server Instance.
	 * Ensures only one instance of Newspack Performance Server Instance is loaded or can be loaded.
	 *
	 * @return Performance - Instance.
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
		add_filter( 'default_option_perfmatters_edd_license_key', [ __CLASS__, 'provide_perfmatters_license_key' ] );
		add_filter( 'option_perfmatters_edd_license_key', [ __CLASS__, 'provide_perfmatters_license_key' ] );
		add_action( 'init', [ __CLASS__, 'cron_init' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'activate_perfmatters_license' ] );
		add_action( 'admin_menu', [ __CLASS__, 'restrict_perfmatters_menu_item' ] );
		add_filter( 'plugin_action_links', [ __CLASS__, 'restrict_plugin_action_link' ], 11, 2 );
		add_action( 'current_screen', [ __CLASS__, 'restrict_perfmatters_page' ] );
		add_action( 'admin_bar_menu', [ __CLASS__, 'restrict_perfmatters_admin_bar_menu_item' ], 501 );
	}

	/**
	 * Can Perfmatters settings be accessed?
	 */
	private static function can_access_perfmatters() {
		if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
			return true;
		}

		// If this is the admin user, or the env. flag is provided, don't restrict access.
		if ( defined( 'NEWSPACK_MANAGER_PERFMATTERS_ALLOW_EDITS' ) && NEWSPACK_MANAGER_PERFMATTERS_ALLOW_EDITS ) {
			return true;
		}
		$adminnewspack_user = get_user_by( 'login', NEWSPACK_MANAGER_ADMIN_USERNAME );
		if ( $adminnewspack_user && get_current_user_id() === $adminnewspack_user->ID ) {
			return true;
		}
		return false;
	}

	/**
	 * Restrict access to Perfmatters menu item.
	 */
	public static function restrict_perfmatters_menu_item() {
		if ( self::can_access_perfmatters() ) {
			return;
		}
		remove_submenu_page( 'options-general.php', 'perfmatters' );
	}

	/**
	 * Restrict access to Perfmatters action link on plugins.php.
	 *
	 * @param array  $actions Plugin action links.
	 * @param string $plugin_file Plugin file.
	 */
	public static function restrict_plugin_action_link( $actions, $plugin_file ) {
		if ( 'perfmatters/perfmatters.php' !== $plugin_file ) {
			return $actions;
		}
		if ( self::can_access_perfmatters() ) {
			return $actions;
		}
		unset( $actions['settings'] );
		return $actions;
	}

	/**
	 * Restrict access to Perfmatters page.
	 */
	public static function restrict_perfmatters_page() {
		if ( self::can_access_perfmatters() ) {
			return;
		}
		$screen = get_current_screen();
		if ( 'settings_page_perfmatters' === $screen->id ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'newspack-manager' ) );
		}
	}

	/**
	 * Can Perfmatters license be programmatically activated?
	 */
	private static function can_activate() {
		return false !== self::get_license_key() && ! function_exists( 'perfmatters_check_license' ) || ! function_exists( 'perfmatters_activate_license' );
	}

	/**
	 * Set up the cron job. Will run once daily and remove featured status for all listings whose expiration date has passed.
	 */
	public static function cron_init() {
		if ( self::can_activate() ) {
			return;
		}
		$has_activated = get_option( 'newspack_manager_perfmatters_license_activated', false );
		if ( ! $has_activated && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Activate Perfmatters license.
	 */
	public static function activate_perfmatters_license() {
		if ( self::can_activate() ) {
			return;
		}
		$status = perfmatters_check_license();
		if ( 'site_inactive' === $status->license ) {
			$did_update = perfmatters_activate_license();
			if ( false !== $did_update ) {
				update_option( 'newspack_manager_perfmatters_license_activated', true );
				wp_clear_scheduled_hook( self::CRON_HOOK );
			}
		}
	}

	/**
	 * Get the license key.
	 */
	private static function get_license_key() {
		if ( defined( 'NEWSPACK_PERFMATTERS_LICENSE_KEY' ) ) {
			return NEWSPACK_PERFMATTERS_LICENSE_KEY;
		}
		return false;
	}

	/**
	 * Hide PerfMatters admin bar menu item.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public static function restrict_perfmatters_admin_bar_menu_item( $wp_admin_bar ) {
		if ( self::can_access_perfmatters() ) {
			return;
		}
		$wp_admin_bar->remove_menu( 'perfmatters' );
	}

	/**
	 * Provide the license option for Perfmatters plugin.
	 *
	 * @param string $license_key License key.
	 */
	public static function provide_perfmatters_license_key( $license_key ) {
		if ( empty( $license_key ) && false !== self::get_license_key() ) {
			return self::get_license_key();
		}

		return $license_key;
	}
}
Performance::instance();

