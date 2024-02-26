<?php
/**
 * Newspack Manager Gravity Forms Integration.
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager Gravity Forms Class.
 */
final class Gravity_Forms {
	/**
	 * The single instance of the class.
	 *
	 * @var Gravity_Forms
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Gravity_Forms Server Instance.
	 * Ensures only one instance of Newspack Gravity_Forms Server Instance is loaded or can be loaded.
	 *
	 * @return Gravity_Forms - Instance.
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
		add_action( 'admin_init', [ __CLASS__, 'process_license_key' ] );
		add_action( 'admin_head', [ __CLASS__, 'hide_license_key' ] );
	}

	/**
	 * Get the enviroment stored license key.
	 *
	 * @return string
	 */
	public static function get_license_key() {
		return defined( 'NEWSPACK_GRAVITY_FORMS_LICENSE_KEY' ) ? \NEWSPACK_GRAVITY_FORMS_LICENSE_KEY : '';
	}

	/**
	 * Process the license key.
	 */
	public static function process_license_key() {
		// Bail if expected Gravity Forms functions are not available.
		if ( ! method_exists( 'GFCommon', 'get_key' ) || ! method_exists( 'GFFormsModel', 'save_key' ) ) {
			return;
		}

		$key = self::get_license_key();
		// Bail if managed license key is not available.
		if ( ! $key ) {
			return;
		}

		// Bail if expected license key is already set.
		if ( md5( trim( $key ) ) === \GFCommon::get_key() ) {
			return;
		}

		// Save the license key.
		\GFFormsModel::save_key( $key );

		// Update cached remote message (removes the "unlicensed" banner).
		if ( method_exists( 'GFCommon', 'cache_remote_message' ) ) {
			\GFCommon::cache_remote_message();
		}
	}

	/**
	 * Hide the license key in the settings editor.
	 */
	public static function hide_license_key() {
		// Bail if not on Gravity Forms settings page.
		if ( ! isset( $_GET['page'] ) || 'gf_settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$key = self::get_license_key();
		// Bail if managed license key is not available.
		if ( ! $key ) {
			return;
		}
		?>
		<style>#gform-settings-section-support-license-key { display: none; }</style>
		<?php
	}
}
Gravity_Forms::instance();

