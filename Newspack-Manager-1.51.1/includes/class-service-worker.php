<?php
/**
 * Newspack Manager Service Worker
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager Service Worker class.
 */
final class Service_Worker {
	/**
	 * The single instance of the class.
	 *
	 * @var Service_Worker
	 */
	protected static $instance = null;

	/**
	 * Name of the Service Worker file.
	 *
	 * @var string
	 */
	protected static $service_worker_file_name = '/newspack-sw.js';

	/**
	 * Name of nonce field.
	 *
	 * @var string
	 */
	protected static $nonce_field = 'newspack_manager_service_worker_nonce';

	/**
	 * Feature version, for updates.
	 *
	 * @var string
	 */
	protected static $version = 1;

	/**
	 * Main Newspack Service_Worker Server Instance.
	 * Ensures only one instance of Newspack Service_Worker Server Instance is loaded or can be loaded.
	 *
	 * @return Service_Worker - Instance.
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
		// If PWA plugin is active, this hook will register the service worker within its scope.
		// Any SW with root scope will be overridden by the PWA plugin.
		add_action( 'wp_front_service_worker', [ __CLASS__, 'register_pwa_service_worker' ] );

		// If PWA is not active, register the service worker with the root scope.
		add_action( 'init', [ __CLASS__, 'handle_sw_request' ] );
		add_action( 'wp_head', [ __CLASS__, 'print_sw_registration_script' ] );

		// Add a script that will handle any messages for the service worker post-registration.
		add_action( 'wp_head', [ __CLASS__, 'print_sw_footer_script' ] );

		add_action( 'rest_api_init', [ $this, 'register_api_endpoints' ] );
	}

	/**
	 * Print the Service Worker registration script.
	 */
	public static function print_sw_registration_script() {
		if ( ! self::can_execute_frontend_script() ) {
			return;
		}
		if ( self::is_pwa_active() ) {
			return;
		}
		?>
			<script data-amp-plus-allowed>
				if ('serviceWorker' in navigator) {
					navigator.serviceWorker
						.register('<?php echo esc_html( self::$service_worker_file_name ); ?>')
						.then(function(registration) {
							console.log('Newspack ServiceWorker registration successful with scope: ', registration.scope);
						}).catch(function(err) {
							console.log('Newspack ServiceWorker registration failed: ', err);
						})}
			</script>
		<?php
	}

	/**
	 * Print the Service Worker footer script.
	 */
	public static function print_sw_footer_script() {
		if ( ! self::can_execute_frontend_script() ) {
			return;
		}
		?>
			<script data-amp-plus-allowed>
				if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
					navigator.serviceWorker.controller.postMessage('newspack-reset-request-count');
					navigator.serviceWorker.controller.postMessage('newspack-nonce-<?php echo esc_html( \wp_create_nonce( self::$nonce_field ) ); ?>');
					navigator.serviceWorker.controller.postMessage('newspack-version-<?php echo esc_html( self::$version ); ?>');
				}
			</script>
		<?php
	}

	/**
	 * The front-end script can only be executed if AMP is inactive or site is AMP Plus.
	 */
	public static function can_execute_frontend_script() {
		if ( method_exists( '\Newspack\AMP_Enhancements', 'is_amp_plus_configured' ) ) {
			return \Newspack\AMP_Enhancements::is_amp_plus_configured();
		} else {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			return ! \is_plugin_active( 'amp/amp.php' );
		}
		return true;
	}

	/**
	 * Handle request for the service worker, as a root request.
	 */
	public static function handle_sw_request() {
		if ( ! self::is_pwa_active() && isset( $_SERVER['REQUEST_URI'] ) ) {
			$raw_uri = sanitize_text_field(
				wp_unslash( $_SERVER['REQUEST_URI'] )
			);
			if ( self::$service_worker_file_name === $raw_uri ) {
				header( 'content-type: application/x-javascript' );
				// Prevent `SSL routines:tls_process_server_certificate:certificate verify failed` error.
				$options = [
					'ssl' => [
						'verify_peer'      => false,
						'verify_peer_name' => false,
					],
				];
				echo file_get_contents( self::sw_file_url(), false, stream_context_create( $options ) ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown, WordPress.Security.EscapeOutput.OutputNotEscaped
				exit;
			}
		}
	}

	/**
	 * Register API endpoints.
	 */
	public static function register_api_endpoints() {
		register_rest_route(
			NEWSPACK_MANAGER_REST_BASE,
			'/sw-message',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'receive_sw_message' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'message' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'nonce'   => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'version' => [
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Is the message deprecated?
	 * Updated service workers will not send messages that are deprecated, but older service workers will.
	 *
	 * @param string $message The message.
	 */
	private static function is_message_deprecated( $message ) {
		if ( false !== strpos( $message, 'No GA property found in a request' ) ) {
			return true;
		}
		if ( preg_match( '/has sent \*\d\* pageviews per request/', $message ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Receive a message from the Service Worker.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public static function receive_sw_message( $request ) {
		$referer = wp_get_referer();
		if (
			false === stripos( $referer, '/wp.serviceworker' )
			&& false === stripos( $referer, '/newspack-sw.js' )
		) {
			return new \WP_Error( 'newspack_manager_sw_message_error', __( 'Invalid referer.', 'newspack' ) );
		}
		$version = $request->get_param( 'version' );
		if ( $version !== self::$version ) {
			return new \WP_Error( 'newspack_manager_sw_message_error', __( 'Invalid version.', 'newspack' ) );
		}
		$nonce = $request->get_param( 'nonce' );
		if ( ! \wp_verify_nonce( $nonce, self::$nonce_field ) ) {
			return new \WP_Error( 'newspack_manager_sw_message_error', __( 'Invalid nonce.', 'newspack' ) );
		}

		$message             = $request->get_param( 'message' );
		$message_for_hashing = preg_replace( '/\(URL: .*\)/', '', $message );
		if ( self::is_message_deprecated( $message_for_hashing ) ) {
			return;
		}
		$message_id = hash( 'md5', $message_for_hashing );

		$option_name        = '_newspack_manager_sent_reports_times';
		$message_timestamps = \get_option( $option_name, [] );
		$now                = microtime( true );
		if ( isset( $message_timestamps[ $message_id ] ) ) {
			$time_diff_hours = ( $now - $message_timestamps[ $message_id ] ) / 60;
			if ( 60 * 24 > $time_diff_hours ) {
				// This message was sent within last 24 hours, ignore it.
				return;
			}
		}

		$message_timestamps[ $message_id ] = $now;
		\update_option( $option_name, $message_timestamps );

		$params = [
			'site_url' => site_url(),
			'message'  => $message,
		];
		$url    = \Newspack_Manager::authenticate_manager_client_url(
			'/wp-json/newspack-manager-client/v1/frontend-report',
			$params
		);
		$result = \wp_safe_remote_post( $url );
		if ( 200 === $result['response']['code'] ) {
			return rest_ensure_response( [] );
		} else {
			return new \WP_Error( 'newspack_manager_sw_message_error', __( 'Error sending message to Newspack Manager', 'newspack' ) );
		}
	}

	/**
	 * Register Newspack Service Worker with PWA plugin.
	 *
	 * @param string $scripts PWA SW scripts.
	 */
	public static function register_pwa_service_worker( $scripts ) {
		if ( ! self::can_execute_frontend_script() ) {
			return;
		}
		$scripts->register( 'newspack-sw', [ 'src' => self::sw_file_url() ] );
	}

	/**
	 * Get the Service Worker file URL.
	 */
	private static function sw_file_url() {
		return \Newspack_Manager::plugin_url() . '/includes/service-worker.js';
	}

	/**
	 * Is PWA plugin active?
	 */
	private static function is_pwa_active() {
		return defined( 'PWA_VERSION' );
	}
}
Service_Worker::instance();

