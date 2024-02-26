<?php
/**
 * Newspack Podcasts
 *
 * @package Newspack
 */

defined( 'ABSPATH' ) || exit;

require_once NEWSPACK_PODCASTS_FILE . '/vendor/autoload.php';

/**
 * Main Newspack Podcasts Class.
 */
final class Newspack_Podcasts {

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Podcasts
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Podcasts Instance.
	 * Ensures only one instance of Newspack Podcasts Instance is loaded or can be loaded.
	 *
	 * @return Newspack Podcasts Instance - Main instance.
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
		include_once NEWSPACK_PODCASTS_FILE . 'includes/class-newspack-podcasts-cpt.php';
		include_once NEWSPACK_PODCASTS_FILE . 'includes/class-newspack-podcasts-settings.php';
		include_once NEWSPACK_PODCASTS_FILE . 'includes/class-newspack-podcasts-frontend.php';
	}
}
Newspack_Podcasts::instance();

