<?php
/**
 * Newspack Plugin Autoupdates
 *
 * Cribbed from https://github.com/a8cteam51/plugin-autoupdate-filter
 *
 * @package Newspack
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Newspack Plugin Autoupdates Class.
 */
final class Newspack_Plugin_Autoupdates {
	const DISABLED_UPDATES_OPTION_PREFIX = 'newspack_manager_autoupdate_disabled_';
	const NONCE_ACTION                   = 'newspack-manager-auto-updates';
	const QUERY_STATE                    = 'newspack-manager-auto-update-disabled';
	const QUERY_PLUGIN_SLUG              = 'newspack-manager-auto-update-slug';

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Plugin_Autoupdates
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Plugin Autoupdates Instance.
	 * Ensures only one instance of Newspack Plugin Autoupdates Instance is loaded or can be loaded.
	 *
	 * @return Newspack Plugin Autoupdates Instance - Main instance.
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
		if ( self::are_auto_updates_by_manager_enabled() ) {
			// Setup plugins to autoupdate _unless_ it's during specific day/time.
			add_filter( 'auto_update_plugin', [ __CLASS__, 'auto_update_specific_times' ], 10, 2 );
			add_filter( 'plugin_auto_update_setting_html', [ __CLASS__, 'plugin_auto_update_setting_html' ], 11, 3 );
			add_action( 'admin_init', [ __CLASS__, 'handle_plugin_auto_update_change' ] );

			// Ensure that plugins required by Newspack plugin can't be deactivated or deleted.
			add_filter( 'plugin_action_links', [ __CLASS__, 'modify_action_links' ], 10, 3 );

			// Ensure compatibility with setting plugin auto-updates with WP-CLI.
			add_action( 'add_option_auto_update_plugins', [ __CLASS__, 'handle_plugin_auto_update_change_using_wp_cli' ], 10, 2 );
			add_action( 'update_option_auto_update_plugins', [ __CLASS__, 'handle_plugin_auto_update_change_using_wp_cli' ], 10, 2 );
			add_action( 'delete_option', [ __CLASS__, 'handle_disabling_last_plugin_auto_update_using_wp_cli' ], 10, 2 );
		}
	}

	/**
	 * Should auto-updates be performed by this plugin?
	 */
	public static function are_auto_updates_by_manager_enabled() {
		if ( defined( 'NEWSPACK_MANAGER_PLUGIN_AUTO_UPDATES_DISABLED' ) && NEWSPACK_MANAGER_PLUGIN_AUTO_UPDATES_DISABLED ) {
			return false;
		}
		return true;
	}

	/**
	 * Are auto-updates disbled for a plugin?
	 *
	 * @param string $plugin_slug A plugin slug.
	 */
	public static function is_disabled_for_plugin( $plugin_slug ) {
		return (bool) get_option( self::DISABLED_UPDATES_OPTION_PREFIX . $plugin_slug, false );
	}

	/**
	 * Handle the plugin auto-update state change.
	 */
	public static function handle_plugin_auto_update_change() {
		if ( isset( $_GET['_wpnonce'], $_GET[ self::QUERY_STATE ], $_GET[ self::QUERY_PLUGIN_SLUG ] ) ) {
			check_admin_referer( self::NONCE_ACTION );
			$is_disabled = intval( $_GET[ self::QUERY_STATE ] );
			$plugin_slug = sanitize_text_field( $_GET[ self::QUERY_PLUGIN_SLUG ] );
			self::set_plugin_auto_update_disabled_state( $plugin_slug, (bool) $is_disabled );
		}
	}

	/**
	 * Handle the plugin auto-update state when using the WP-CLI.
	 *
	 * @param string|string[] $old_auto_updates Old list of plugins with auto-update enabled via WP-CLI.
	 * @param string[]        $auto_updates New plugins list with auto-update enabled via WP-CLI.
	 */
	public static function handle_plugin_auto_update_change_using_wp_cli( $old_auto_updates, $auto_updates ) {
		// add_option_{$name} action pass the name of the option as first parameter,
		// and if it's called it means that we're enabling auto-update for the first time via WP-CLI.
		$old_auto_updates = is_array( $old_auto_updates ) ? $old_auto_updates : [];

		$plugins_with_autoupdates_enabled = array_diff( $auto_updates, $old_auto_updates );
		$plugins_autoupdates_disabled     = array_diff( $old_auto_updates, $auto_updates );

		foreach ( $plugins_with_autoupdates_enabled as $plugin_slug ) {
			self::set_plugin_auto_update_disabled_state( self::get_plugin_slug_from_file( $plugin_slug ), false );
		}

		foreach ( $plugins_autoupdates_disabled as $plugin_slug ) {
			self::set_plugin_auto_update_disabled_state( self::get_plugin_slug_from_file( $plugin_slug ), true );
		}
	}

	/**
	 * Hook on when we delete an option.
	 * Used to disable a plugin auto-updates if it's the last one on the WP-CLI options list.
	 *
	 * @param string $option Option Name to ckeck.
	 */
	public static function handle_disabling_last_plugin_auto_update_using_wp_cli( $option ) {
		// When disabling auto-updates for the last plugin, the option is deleted instead of being updated.
		if ( 'auto_update_plugins' === $option ) {
			$plugins_autoupdates_disabled = get_option( 'auto_update_plugins', [] );

			foreach ( $plugins_autoupdates_disabled as $plugin_slug ) {
				self::set_plugin_auto_update_disabled_state( self::get_plugin_slug_from_file( $plugin_slug ), true );
			}
		}
	}

	/**
	 * Handle the plugin auto-update state change.
	 *
	 * @param string  $plugin_slug Plugin slug.
	 * @param boolean $state Plugin auto-update disabled state.
	 */
	public static function set_plugin_auto_update_disabled_state( $plugin_slug, $state ) {
		update_option( self::DISABLED_UPDATES_OPTION_PREFIX . $plugin_slug, $state );
	}

	/**
	 * Get plugin slug from plugin file path.
	 *
	 * @param string $plugin_path Plugin file path.
	 */
	public static function get_plugin_slug_from_file( $plugin_path ) {
		$result = basename( dirname( $plugin_path ) );
		if ( '.' === $result ) {
			// Handle directory-less plugins (just a PHP file in wp-content/plugins).
			$result = basename( $plugin_path, '.php' );
		}
		return $result;
	}

	/**
	 * Replace default update wording on plugin management page in admin.
	 *
	 * @param string $html The HTML for the plugins table auto-updates column.
	 * @param string $plugin_file Plugin file.
	 * @param array  $plugin_data Plugin data.
	 */
	public static function plugin_auto_update_setting_html( $html, $plugin_file, $plugin_data ) {
		if ( ! isset( $plugin_data['update-supported'] ) || false === $plugin_data['update-supported'] ) {
			return '';
		}
		$slug        = self::get_plugin_slug_from_file( $plugin_data['plugin'] );
		$is_disabled = self::is_disabled_for_plugin( $slug );

		$query_args = [
			self::QUERY_STATE       => $is_disabled ? '0' : '1',
			self::QUERY_PLUGIN_SLUG => $slug,
		];
		$url        = add_query_arg( $query_args, 'plugins.php' );

		$new_html = [];
		if ( $is_disabled ) {
			$new_html[] = 'Auto-updates DISABLED via <strong>Newspack Manager</strong>. ';
		} else {
			$new_html[] = 'Managed by <strong>Newspack Manager</strong>. ';
		}
		$new_html[] = sprintf(
			'<a href="%s">',
			wp_nonce_url( $url, self::NONCE_ACTION )
		);
		if ( $is_disabled ) {
			$new_html[] = 'Enable auto-updates';
		} else {
			$new_html[] = 'Disable auto-updates';
		}
		$new_html[] = '</a>.';

		return implode( '', $new_html );
	}

	/**
	 * Handle auto-updates.
	 * Won't work properly in WP <5.9.
	 *
	 * @param bool   $update Whether the plugin should be updated.
	 * @param object $item The plugin update offer.
	 */
	public static function auto_update_specific_times( $update, $item ) {
		if ( ! is_object( $item ) ) {
			return $update;
		}
		if ( ! property_exists( $item, 'slug' ) ) {
			return $update;
		}
		if ( self::is_disabled_for_plugin( $item->slug ) ) {
			return false;
		}
		$holidays = [
			'christmas' => [
				'start' => gmdate( 'Y' ) . '-12-23 00:00:00',
				'end'   => gmdate( 'Y' ) . '-12-26 00:00:00',
			],
			'new_year'  => [
				'start' => gmdate( 'Y' ) . '-12-31 00:00:00',
				'end'   => gmdate( 'Y' ) . '-12-31 23:59:59',
			],
		];
		$holidays = apply_filters( 'plugin_autoupdate_filter_holidays', $holidays );

		$now = gmdate( 'Y-m-d H:i:s' );

		foreach ( $holidays as $holiday ) {
			$start = $holiday['start'];
			$end   = $holiday['end'];
			if ( $start <= $now && $now <= $end ) {
				return false;
			}
		}

		$hours = [
			'start'      => '10', // 6am Eastern.
			'end'        => '23', // 7pm Eastern.
			'friday_end' => '19', // 3pm Eastern on Fridays.
		];
		$hours = apply_filters( 'plugin_autoupdate_filter_hours', $hours );

		$days_off = [
			'Sat',
			'Sun',
		];
		$days_off = apply_filters( 'plugin_autoupdate_filter_days_off', $days_off );

		$hour = gmdate( 'H' ); // Current hour.
		$day  = gmdate( 'D' );  // Current day of the week.

		// If outside business hours, disable auto-updates.
		if ( $hour < $hours['start'] || $hour > $hours['end'] || in_array( $day, $days_off, true ) || ( 'Fri' === $day && $hour > $hours['friday_end'] ) ) {
			return false;
		}

		// Otherwise, plugins will autoupdate regardless of settings in wp-admin.
		return true;
	}

	/**
	 * Remove 'Deactivate' and 'Delete' links for required plugins.
	 *
	 * @param  array  $actions Array of plugin action links.
	 * @param  string $plugin_file The plugin file.
	 * @param  array  $plugin_data Information about the plugin.
	 * @return array  Modified $actions.
	 */
	public static function modify_action_links( $actions, $plugin_file, $plugin_data ) {
		if ( ! class_exists( '\Newspack\Plugin_Manager' ) ) {
			return $actions;
		}

		$plugin_slug       = isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : $plugin_file;
		$required_plugins  = \Newspack\Plugin_Manager::$required_plugins;
		$installed_plugins = \Newspack\Plugin_Manager::get_installed_plugins();
		$is_installed      = isset( $installed_plugins[ $plugin_slug ] );

		if ( in_array( $plugin_slug, $required_plugins ) && $is_installed ) {
			unset( $actions['deactivate'] );
			unset( $actions['delete'] );
		}

		// Symlinked plugins should never be deleted.
		$plugin_dir_name = preg_replace( '/\/[\w\-]*\.php$/', '', $plugin_slug );
		if ( is_link( WP_CONTENT_DIR . '/plugins/' . $plugin_dir_name ) ) {
			unset( $actions['delete'] );
		}

		return $actions;
	}
}
Newspack_Plugin_Autoupdates::instance();

