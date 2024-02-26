<?php
/**
 * Newspack centralized GA4 connection integration.
 *
 * @package Newspack
 */

namespace Newspack_Manager;

/**
 * Newspack centralized GA4 connection class.
 */
final class Newspack_GA4 {

	/**
	 * Option name for the GA4 info.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'newspack_ga4_info';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'wp_head', [ __CLASS__, 'add_property' ], 100 );
		add_filter( 'newspack_data_events_ga4_properties', [ __CLASS__, 'add_data_event_api_property' ] );
	}

	/**
	 * Get the GA4 info.
	 *
	 * @return array
	 */
	public static function get_info() {
		return get_option( self::OPTION_NAME, [] );
	}

	/**
	 * Set the GA4 info.
	 *
	 * @param array $info The GA4 info.
	 */
	public static function set_info( $info ) {
		update_option( self::OPTION_NAME, $info );
	}

	/**
	 * Get the GA4 measurement ID.
	 *
	 * @return string
	 */
	public static function get_measurement_id() {
		$ga4_info = self::get_info();
		return $ga4_info['measurement_id'] ?? '';
	}

	/**
	 * Get the GA4 measurement protocol secret.
	 *
	 * @return string
	 */
	public static function get_measurement_protocol_secret() {
		$ga4_info = self::get_info();
		return $ga4_info['measurement_protocol_secret'] ?? '';
	}

	/**
	 * Add gtag config to the head.
	 *
	 * Example of complete gtag setup:
	 * ```
	 * <!-- Google tag (gtag.js) -->
	 * <script async src="https://www.googletagmanager.com/gtag/js?id={PROPERTY_ID}"></script>
	 * <script>
	 * window.dataLayer = window.dataLayer || [];
	 * function gtag(){dataLayer.push(arguments);}
	 * gtag('js', new Date());
	 * gtag('config', '{PROPERTY_ID}');
	 * </script>
	 * ```
	 */
	public static function add_property() {
		$ga_id = self::get_measurement_id();
		if ( empty( $ga_id ) ) {
			// Don't load GA4 if no property ID is set.
			return;
		}
		if ( \is_user_logged_in() && \current_user_can( 'manage_options' ) ) {
			// Don't load GA4 for admin users.
			return;
		}
		?>
		<script>
			( function() {
				// Load GA script if not yet found
				if ( 'undefined' === typeof gtag ) {
					var element = document.createElement( 'script' );
					element.src = 'https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>';
					element.async = true;
					document.head.appendChild( element );
					window.dataLayer = window.dataLayer || [];
					window.gtag = function() { window.dataLayer.push( arguments ) };
					gtag( 'js', new Date() );
				}
				gtag( 'config', '<?php echo esc_attr( $ga_id ); ?>' );
			} )();
		</script>
		<?php
	}

	/**
	 * Adds the GA4 property to the data events API.
	 *
	 * @param array $properties The properties.
	 * @return array
	 */
	public static function add_data_event_api_property( $properties ) {
		$info = self::get_info();
		if ( ! empty( $info ) ) {
			$properties[] = $info;
		}
		return $properties;
	}
}
Newspack_GA4::init();

