<?php
/**
 * Newspack Manager utilities
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager utilities class.
 */
final class Utils {
	/**
	 * Retrieve Site Kit plugin settings.
	 */
	public static function get_sitekit_settings() {
		$sitekit_ga4_settings = [];
		if ( class_exists( '\Google\Site_Kit\Modules\Analytics_4\Settings' ) ) {
			$sitekit_ga4_settings['ga4'] = get_option( \Google\Site_Kit\Modules\Analytics_4\Settings::OPTION, false );
		}
		if ( class_exists( '\Google\Site_Kit\Modules\Analytics\Settings' ) ) {
			$sitekit_ga4_settings['ua'] = get_option( \Google\Site_Kit\Modules\Analytics\Settings::OPTION, false );
		}
		return $sitekit_ga4_settings;
	}
}

