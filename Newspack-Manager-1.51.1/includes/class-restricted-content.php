<?php
/**
 * Newspack Manager restricted content.
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager restricted content class.
 */
final class Restricted_Content {
	const PARAM_NAME  = 'key';
	const COOKIE_NAME = 'newspack-key';

	/**
	 * The single instance of the class.
	 *
	 * @var Restricted_Content
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Restricted_Content Server Instance.
	 * Ensures only one instance of Newspack Restricted_Content Server Instance is loaded or can be loaded.
	 *
	 * @return Restricted_Content - Instance.
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
		if ( defined( 'NEWSPACK_IS_RESTRICTED_CONTENT_SITE' ) && NEWSPACK_IS_RESTRICTED_CONTENT_SITE ) {
			// This site has restricted content.
			add_filter( 'the_content', [ __CLASS__, 'restrict_content' ] );
		} else {
			// Other sites link to the restricted content site from their Newspack dashboard.
			add_filter( 'newspack_plugin_dashboard_items', [ __CLASS__, 'add_support_link' ] );
		}
	}

	/**
	 * Get value of the API key bearing cookie.
	 */
	private static function get_cookie_value() {
		// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		return isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( $_COOKIE[ self::COOKIE_NAME ] ) : false;
	}

	/**
	 * Get current global access key value.
	 * This key will be issued to all requesters who pass initial validation (with a per-site API key).
	 * This key can be rotated to invalidate all existing access.
	 */
	private static function get_current_global_access_key() {
		$global_key_value = \get_option( 'newspack_manager_content_restriction_global_key' );
		if ( ! $global_key_value ) {
			$global_key_value = \wp_generate_password( 32, false );
			\update_option( 'newspack_manager_content_restriction_global_key', $global_key_value );
		}
		return $global_key_value;
	}

	/**
	 * Can view content?
	 */
	private static function can_view_content() {
		if ( isset( $_GET[ self::PARAM_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$api_key        = $_GET[ self::PARAM_NAME ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$validation_url = \Newspack_Manager::authenticate_manager_client_url(
				'/wp-json/newspack-manager-client/v1/validate-key/' . $api_key
			);
			$result         = \wp_safe_remote_get( $validation_url );
			if ( 200 === $result['response']['code'] ) {
				// Save the global access key in a cookie for a year.
				$expire_at = time() + YEAR_IN_SECONDS;
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
				setcookie( self::COOKIE_NAME, self::get_current_global_access_key(), $expire_at, COOKIEPATH, COOKIE_DOMAIN, true );
				return true;
			}
		} else {
			$access_cookie = self::get_cookie_value();
			return $access_cookie && self::get_current_global_access_key() === $access_cookie;
		}

		return false;
	}

	/**
	 * Restrict content.
	 *
	 * @param string $content Content.
	 */
	public static function restrict_content( $content ) {
		// can_view_content will check for a valid API key in the URL, and if it's valid, save it in a cookie.
		$can_view = self::can_view_content();
		if ( ! is_home() && ! is_front_page() && ! $can_view ) {
			ob_start();
			?>
				<p>
					<b>
						<?php echo esc_html__( 'This documentation is restricted to Newspack publishers only. Visit this help site through the Support link in your site\'s Newspack dashboard to access our docs.', 'newspack-manager' ); ?>
					</b>
				</p>
			<?php
			return ob_get_clean();
		}
		return $content;
	}

	/**
	 * Add the support link to the Newspack plugin dashboard.
	 *
	 * @param string $menu_items Menu items.
	 */
	public static function add_support_link( $menu_items ) {
		$menu_items[] = [
			'slug'        => 'support',
			'name'        => __( 'Support', 'newspack-manager' ),
			'url'         => \add_query_arg(
				self::PARAM_NAME,
				\Newspack_Manager::get_manager_client_api_key(),
				'https://help.newspack.com'
			),
			'description' => __( 'Publisher-only support portal', 'newspack-manager' ),
			'is_external' => true,
		];
		return $menu_items;
	}
}
Restricted_Content::instance();

