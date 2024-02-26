<?php
/**
 * Newspack Manager Batcache_Manager
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Batcache Manager Class.
 */
final class Batcache_Manager {
	const CUSTOM_TTL_FOR_SITE_OPTION = '_newspack_batcache_custom_ttl_for_site';
	const CUSTOM_TTL_PER_URL_OPTION  = '_newspack_batcache_custom_ttl_for_per_url';

	/**
	 * The single instance of the class.
	 *
	 * @var Batcache_Manager
	 */
	protected static $instance = null;

	/**
	 * Single class instance.
	 *
	 * @return Batcache_Manager - Instance.
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
		add_action( 'wp_loaded', [ __CLASS__, 'customize_batcache_value' ] );
	}

	/**
	 * Customize Batcache value if needed.
	 */
	public static function customize_batcache_value() {
		/**
		 * Custom cache time for the whole site, in seconds. Use `null` for default values (default 300 sec).
		 */
		$custom_ttl_for_site = get_option( self::CUSTOM_TTL_FOR_SITE_OPTION, null );

		/**
		 * Custom cache times for URLs. To disable caching for a page, use a `0` value.
		 */
		$custom_ttl_per_url = get_option( self::CUSTOM_TTL_PER_URL_OPTION, [] );

		global $batcache;
		if ( ! is_object( $batcache ) && ! is_array( $batcache ) ) {
			return;
		}
		if ( null === $custom_ttl_for_site && empty( $custom_ttl_per_url ) ) {
			return;
		}

		$ttl = null;
		$url = isset( $_SERVER['REQUEST_URI'] ) ? rtrim( $_SERVER['REQUEST_URI'], '/' ) : '/'; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $custom_ttl_per_url[ $url ] ) ) {
			$ttl = $custom_ttl_per_url[ $url ];
		} elseif ( $custom_ttl_for_site ) {
			$ttl = $custom_ttl_for_site;
		}
		if ( null === $ttl ) {
			return;
		}

		if ( is_object( $batcache ) ) {
			// Seconds the cached render of a page will be stored.
			$batcache->max_age = $ttl;
			// The amount of time at least 2 people are required to visit your page for a cached render to be stored.
			$batcache->seconds = $ttl;
		} elseif ( is_array( $batcache ) ) {
			$batcache['max_age'] = $ttl;
			$batcache['seconds'] = $ttl;
		}
	}
}
Batcache_Manager::instance();

