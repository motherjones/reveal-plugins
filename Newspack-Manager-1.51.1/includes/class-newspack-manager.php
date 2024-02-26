<?php
/**
 * Newspack Manager
 *
 * @package Newspack
 */

defined( 'ABSPATH' ) || exit;

use Newspack_Manager\Utils;
use Newspack_Manager\Batcache_Manager;
use Newspack_Manager\Newspack_GA4;
use Newspack_Manager\Mail;

/**
 * Main Newspack Manager Class.
 */
final class Newspack_Manager {

	/**
	 * The option name where we store the time of the last successful request by the Newspack Manager.
	 */
	const LAST_REQUEST_FROM_MANAGER_OPTION_NAME = 'newspack_manager_last_request';

	/**
	 * The option name where we store the domain of the Manager client making requests to this site.
	 */
	const MANAGER_REQUEST_DOMAIN_OPTION_NAME = 'newspack_manager_request_domain';

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Manager
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Manager Instance.
	 * Ensures only one instance of Newspack Manager Instance is loaded or can be loaded.
	 *
	 * @return Newspack Manager Instance - Main instance.
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
		add_action( 'rest_api_init', [ __CLASS__, 'register_api_endpoints' ] );
		add_filter( 'password_protected_is_active', [ __CLASS__, 'bypass_password_protected' ] );
	}

	/**
	 * Get the URL for the Newspack Manager plugin directory.
	 *
	 * @return string URL
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', NEWSPACK_MANAGER_FILE ) );
	}

	/**
	 * Get API key for the Newspack Manager Client.
	 */
	public static function get_manager_client_api_key() {
		return get_option( NEWSPACK_MANAGER_API_KEY_OPTION_NAME );
	}

	/**
	 * Checks whether the site is connected to the Newspack Manager.
	 *
	 * Site is considered connected if it received a successful request from the Manger in the last 24 hours.
	 */
	public static function is_connected_to_manager() {
		$last_request = get_option( self::LAST_REQUEST_FROM_MANAGER_OPTION_NAME, 0 );
		return time() - $last_request < DAY_IN_SECONDS;
	}

	/**
	 * Checks whether the site is connected to the production Newspack Manager.
	 */
	public static function is_connected_to_production_manager() {
		return 'newspack.com' === self::get_domain_from_last_request() && 'https://newspack.com' === self::get_manager_client_url() && self::is_connected_to_manager();
	}

	/**
	 * Get the domain of the last successful request by the Newspack Manager.
	 */
	public static function get_domain_from_last_request() {
		return get_option( self::MANAGER_REQUEST_DOMAIN_OPTION_NAME, '' );
	}

	/**
	 * Get Newspack Manager Client URL.
	 */
	private static function get_manager_client_url() {
		if ( defined( 'NEWSPACK_GOOGLE_OAUTH_PROXY_OVERRIDE' ) ) {
			return NEWSPACK_GOOGLE_OAUTH_PROXY_OVERRIDE;
		}
		if ( defined( 'NEWSPACK_GOOGLE_OAUTH_PROXY' ) ) {
			return NEWSPACK_GOOGLE_OAUTH_PROXY;
		}
		return false;
	}

	/**
	 * Process a Newspack Manager Client request URL.
	 *
	 * @param string $path Path to append to base URL.
	 * @param array  $query_args Query params.
	 */
	public static function authenticate_manager_client_url( string $path = '', array $query_args = [] ) {
		$manager_client_url = self::get_manager_client_url();
		$api_key            = self::get_manager_client_api_key();
		if ( ! $manager_client_url || ! $api_key ) {
			return false;
		}
		return add_query_arg(
			array_merge(
				[
					'api_key' => urlencode( $api_key ),
				],
				$query_args
			),
			$manager_client_url . $path
		);
	}

	/**
	 * Whether to bypass "Password Protected" protection.
	 *
	 * @param bool $is_active Whether the protection is active.
	 */
	public static function bypass_password_protected( $is_active ) {
		if (
			defined( 'REST_REQUEST' ) &&
			REST_REQUEST &&
			isset( $_SERVER['REQUEST_URI'] ) &&
			false !== strpos( sanitize_text_field( $_SERVER['REQUEST_URI'] ), 'newspack-manager' )
		) {
			$is_active = false;
		}
		return $is_active;
	}

	/**
	 * Register REST API endpoints.
	 */
	public static function register_api_endpoints() {
		\register_rest_route(
			NEWSPACK_MANAGER_REST_BASE,
			'info',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'api_get_site_info' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
				'args'                => [
					'nonce'          => [
						'required' => true,
						'type'     => 'string',
					],
					'signature'      => [
						'required' => true,
						'type'     => 'string',
					],
					'timestamp'      => [
						'required' => true,
						'type'     => 'integer',
					],
					'issued_api_key' => [
						'type' => 'string',
					],
				],
			]
		);

		\register_rest_route(
			NEWSPACK_MANAGER_REST_BASE,
			'configure',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ __CLASS__, 'api_configure_site' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
				'args'                => [
					'nonce'     => [
						'required' => true,
						'type'     => 'string',
					],
					'signature' => [
						'required' => true,
						'type'     => 'string',
					],
					'timestamp' => [
						'required' => true,
						'type'     => 'integer',
					],
				],
			]
		);
	}

	/**
	 * Check permissions. The request has to be proxied, and the API key has to match.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public static function permission_callback( $request ) {
		$signature    = $request->get_param( 'signature' );
		$nonce        = $request->get_param( 'nonce' );
		$timestamp    = $request->get_param( 'timestamp' );
		$time_elapsed = time() - (int) $timestamp;
		// The request must be at most 60 seconds old.
		if ( 0 > $time_elapsed || $time_elapsed > 60 ) {
			return false;
		}

		$url_to_verify = remove_query_arg( 'signature', $request->get_header( 'referer' ) );
		try {
			$public_key = sodium_base642bin( NEWSPACK_MANAGER_API_PUBLIC_KEY, SODIUM_BASE64_VARIANT_ORIGINAL );
			return sodium_crypto_sign_verify_detached( sodium_base642bin( $signature, SODIUM_BASE64_VARIANT_URLSAFE ), $url_to_verify, $public_key );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Configure site.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public static function api_configure_site( $request ) {
		$errors = [];

		// Plugin configuration.
		if (
			method_exists( 'Newspack\API\Plugins_Controller', 'configure_item' )
			&& method_exists( 'Newspack\API\Plugins_Controller', 'deactivate_item' )
		) {
			$plugins_controller = new Newspack\API\Plugins_Controller();

			$plugins_to_configure = $request->get_param( 'plugins_to_configure' );
			if ( is_array( $plugins_to_configure ) ) {
				foreach ( $plugins_to_configure as $plugin_slug ) {
					$request = new WP_REST_Request();
					$request->set_param( 'slug', $plugin_slug );
					$result = $plugins_controller->configure_item( $request );
					if ( is_wp_error( $result ) ) {
						$errors[] = $result;
					}
				}
			}
			$plugins_to_deactivate = $request->get_param( 'plugins_to_deactivate' );
			if ( is_array( $plugins_to_deactivate ) ) {
				foreach ( $plugins_to_deactivate as $plugin_slug ) {
					$request = new WP_REST_Request();
					$request->set_param( 'slug', $plugin_slug );
					$result = $plugins_controller->deactivate_item( $request );
					if ( is_wp_error( $result ) ) {
						$errors[] = $result;
					}
				}
			}
		} else {
			$errors[] = new \WP_Error(
				'newspack_configure_site',
				__( 'Plugins Controller missing', 'newspack-manager' )
			);
		}

		$plugins_to_activate_auto_update = $request->get_param( 'plugins_to_activate_auto_update' );
		if ( is_array( $plugins_to_activate_auto_update ) ) {
			foreach ( $plugins_to_activate_auto_update as $plugin_slug ) {
				Newspack_Plugin_Autoupdates::set_plugin_auto_update_disabled_state( Newspack_Plugin_Autoupdates::get_plugin_slug_from_file( $plugin_slug ), false );
			}
		}

		$plugins_to_deactivate_auto_update = $request->get_param( 'plugins_to_deactivate_auto_update' );
		if ( is_array( $plugins_to_deactivate_auto_update ) ) {
			foreach ( $plugins_to_deactivate_auto_update as $plugin_slug ) {
				Newspack_Plugin_Autoupdates::set_plugin_auto_update_disabled_state( Newspack_Plugin_Autoupdates::get_plugin_slug_from_file( $plugin_slug ), true );
			}
		}

		// Custom Batcache TTL.
		$custom_batcache_values = $request->get_param( 'custom_batcache' );
		if ( is_array( $custom_batcache_values ) ) {
			$six_hours = 21600;
			if ( isset( $custom_batcache_values['custom_ttl_for_site'] ) && '' !== $custom_batcache_values['custom_ttl_for_site'] ) {
				$custom_ttl_for_site = intval( $custom_batcache_values['custom_ttl_for_site'] );
				if ( $six_hours <= $custom_ttl_for_site ) {
					$custom_ttl_for_site = $six_hours;
				}
				update_option( Batcache_Manager::CUSTOM_TTL_FOR_SITE_OPTION, $custom_ttl_for_site );
			} else {
				delete_option( Batcache_Manager::CUSTOM_TTL_FOR_SITE_OPTION );
			}

			if ( isset( $custom_batcache_values['custom_ttl_per_url'] ) && '' !== $custom_batcache_values['custom_ttl_per_url'] ) {
				$url_rule_lines = explode( "\n", $custom_batcache_values['custom_ttl_per_url'] );
				$url_rules      = [];
				foreach ( $url_rule_lines as $url_rule_line ) {
					// (e.g. $url_rule_line = /general-election-2020,30)
					$url_rule = explode( ',', trim( $url_rule_line ) );
					if ( 2 === count( $url_rule ) ) {
						$custom_ttl_per_url = intval( $url_rule[1] );
						if ( $six_hours <= $custom_ttl_per_url ) {
							$custom_ttl_per_url = $six_hours;
						}
						$url_rules[ trim( $url_rule[0] ) ] = $custom_ttl_per_url;
					}
				}

				if ( ! empty( $url_rules ) ) {
					update_option( Batcache_Manager::CUSTOM_TTL_PER_URL_OPTION, $url_rules );
				}
			} else {
				delete_option( Batcache_Manager::CUSTOM_TTL_PER_URL_OPTION );
			}
		}

		$disable_custom_batcache = $request->get_param( 'disable_custom_batcache' );
		if ( $disable_custom_batcache ) {
			delete_option( Batcache_Manager::CUSTOM_TTL_FOR_SITE_OPTION );
			delete_option( Batcache_Manager::CUSTOM_TTL_PER_URL_OPTION );
		}

		// GA4 info configuration.
		$ga4_info = $request->get_param( 'ga4_info' );
		if ( ! is_null( $ga4_info ) ) {
			Newspack_GA4::set_info( $ga4_info );
		}

		if ( 0 < count( $errors ) ) {
			$error_response = new \WP_Error();
			foreach ( $errors as $error ) {
				$error_response->add( 'newspack_configure_site', $error->get_error_message() );
			}
			return $error_response;
		} else {
			return self::api_get_site_info();
		}
	}

	/**
	 * Get request's domain from user agent string.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function get_request_domain( $request ) {
		$ua = explode( ';', $request->get_header( 'user_agent' ) );
		if ( 2 > count( $ua ) ) {
			return false;
		}
		$url        = trim( $ua[1] );
		$parsed_url = wp_parse_url( $url );
		if ( is_array( $parsed_url ) && isset( $parsed_url ) ) {
			return $parsed_url['host'];
		}
		return false;
	}

	/**
	 * Get site info.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	public static function api_get_site_info( $request = null ) {
		try {
			global $wpdb;

			$adminnewspack_user = get_user_by( 'slug', NEWSPACK_MANAGER_ADMIN_USERNAME );
			if ( $adminnewspack_user ) {
				// Needs to set a user to pass permissions check.
				wp_set_current_user( $adminnewspack_user->ID );
			}

			/**
			 * Plugins.
			 */
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins             = [];
			$auto_update_plugins = get_option( 'auto_update_plugins', [] );
			if ( method_exists( '\Newspack\Plugin_Manager', 'get_managed_plugins' ) ) {
				$managed_plugins = \Newspack\Plugin_Manager::get_managed_plugins();
				unset( $managed_plugins['newspack-theme'] );
				$newspack_managed_plugins = array_keys( $managed_plugins );
			} else {
				$newspack_managed_plugins = [];
			}

			// Check updateable plugins.
			$updateable_plugins_slugs = [];
			if ( Newspack_Plugin_Autoupdates::are_auto_updates_by_manager_enabled() ) {
				$plugin_updates = get_site_transient( 'update_plugins' );
				if ( $plugin_updates ) {
					if ( ! empty( $plugin_updates->response ) ) {
						foreach ( $plugin_updates->response as $plugin ) {
							$updateable_plugins_slugs[] = Newspack_Plugin_Autoupdates::get_plugin_slug_from_file( $plugin->plugin );
						}
					}
					if ( ! empty( $plugin_updates->no_update ) ) {
						foreach ( $plugin_updates->no_update as $plugin ) {
							$updateable_plugins_slugs[] = Newspack_Plugin_Autoupdates::get_plugin_slug_from_file( $plugin->plugin );
						}
					}
				}
			}

			$plugins_info = get_site_transient( 'update_plugins' );

			foreach ( get_plugins() as $plugin_path => $plugin_data ) {
				$filtered_update_setting    = apply_filters( 'plugin_auto_update_setting_html', '', $plugin_path, [] );
				$can_set_auto_update_status = false;
				$auto_update_status         = in_array( $plugin_path, $auto_update_plugins ) ? 'on' : 'off';
				if ( false !== strpos( $filtered_update_setting, 'Managed by host' ) ) {
					$auto_update_status = 'managed';
				}

				if ( Newspack_Plugin_Autoupdates::are_auto_updates_by_manager_enabled() ) {
					$slug = Newspack_Plugin_Autoupdates::get_plugin_slug_from_file( $plugin_path );
					if ( in_array( $slug, $updateable_plugins_slugs ) ) {
						$is_disabled        = Newspack_Plugin_Autoupdates::is_disabled_for_plugin( $slug );
						$auto_update_status = $is_disabled ? 'off' : 'on';
					}
				}

				// Check if we can set the plugin auto update.
				if ( isset( $plugins_info->response[ $plugin_path ] ) || isset( $plugins_info->no_update[ $plugin_path ] ) ) {
					$can_set_auto_update_status = true;
				}

				$plugin_name = Newspack_Plugin_Autoupdates::get_plugin_slug_from_file( $plugin_path );
				$plugins[]   = [
					'name'                       => $plugin_name,
					'version'                    => $plugin_data['Version'],
					'status'                     => is_plugin_active( $plugin_path ) ? 'active' : 'inactive',
					'auto_update_status'         => $auto_update_status,
					'can_set_auto_update_status' => $can_set_auto_update_status,
					'is_newspack_managed'        => in_array( $plugin_name, $newspack_managed_plugins ),
				];
			}

			/**
			 * Database.
			 */
			$db_info = [
				'options_length'         => (int) $wpdb->get_row( "SELECT COUNT(*) as length FROM $wpdb->options;" )->length, // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				'tags_length'            => (int) $wpdb->get_row( "SELECT COUNT(*) as length FROM $wpdb->term_taxonomy WHERE taxonomy = 'post_tag';" )->length,
				'categories_length'      => (int) $wpdb->get_row( "SELECT COUNT(*) as length FROM $wpdb->term_taxonomy WHERE taxonomy = 'category';" )->length,
				'published_posts_length' => (int) $wpdb->get_row( "SELECT COUNT(*) as length FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post';" )->length,
			];

			/**
			 * Newspack-specific entities.
			 */
			$newspack_entities = [
				'listings'    => [],
				'prompts'     => 0,
				'segments'    => 0,
				'newsletters' => 0,
			];
			if ( defined( '\Newspack_Listings\Core::NEWSPACK_LISTINGS_POST_TYPES' ) ) {
				$listings_cpts_names = \Newspack_Listings\Core::NEWSPACK_LISTINGS_POST_TYPES;
				foreach ( $listings_cpts_names as $cpt_name ) {
					$newspack_entities['listings'][ $cpt_name ] = (int) $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT COUNT(*) as length FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = %s;",
							$cpt_name
						)
					)->length;
				}
			}
			if ( defined( '\Newspack_Popups::NEWSPACK_POPUPS_CPT' ) ) {
				$newspack_entities['prompts'] = (int) $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT COUNT(*) as length FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = %s;",
						Newspack_Popups::NEWSPACK_POPUPS_CPT
					)
				)->length;
			}
			if ( defined( '\Newspack_Popups_Segmentation::SEGMENTS_OPTION_NAME' ) ) {
				$segments                      = get_option( Newspack_Popups_Segmentation::SEGMENTS_OPTION_NAME, [] );
				$newspack_entities['segments'] = count( $segments );
			}
			if ( defined( '\Newspack_Newsletters::NEWSPACK_NEWSLETTERS_CPT' ) ) {
				$newspack_entities['newsletters'] = (int) $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT COUNT(*) as length FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = %s;",
						Newspack_Newsletters::NEWSPACK_NEWSLETTERS_CPT
					)
				)->length;
			}

			/**
			 * Premium Newsletter lists usage.
			 */
			$premium_newsletter_lists = 0;
			if ( function_exists( 'wc_memberships_get_membership_plans' ) ) {
				foreach ( wc_memberships_get_membership_plans() as $plan ) {
					foreach ( $plan->get_rules( 'content_restriction' ) as $rule ) {
						if ( 'newspack_nl_list' === $rule->get_content_type_name() ) {
							$premium_newsletter_lists++;
						}
					}
				}
			}

			/**
			 * Advertising data – connection to Google Ad Manager, ad units.
			 */
			$ads_config = [];
			try {
				if ( method_exists( '\Newspack_Ads\Providers\GAM_Model', 'get_connection_status' ) ) {
					$ads_config['gam_connection_status'] = \Newspack_Ads\Providers\GAM_Model::get_connection_status();
				}
				if ( method_exists( '\Newspack_Ads\Providers\GAM_Model', 'get_ad_units' ) ) {
					$ads_config['ad_units'] = \Newspack_Ads\Providers\GAM_Model::get_ad_units();
				}
			} catch ( \Throwable $th ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Fail silently.
			}

			/**
			 * Google Site Kit plugin data.
			 */
			$site_kit_data     = [];
			$site_kit_settings = Utils::get_sitekit_settings();
			if ( isset( $site_kit_settings['ga4'], $site_kit_settings['ga4']['useSnippet'] ) ) {
				$site_kit_data['ga4'] = $site_kit_settings['ga4']['useSnippet'];
			}

			/**
			 * General configuration.
			 */
			$parsely_settings        = get_option( 'parsely', [] );
			$front_page_amp_disabled = null;
			if ( class_exists( 'AMP_Post_Meta_Box' ) ) {
				$front_page_amp_status   = \AMP_Post_Meta_Box::get_status_and_errors( get_post( get_option( 'page_on_front' ) ) );
				$front_page_amp_disabled = 'disabled' === $front_page_amp_status['status'];
			}
			$letterhead = class_exists( '\Newspack_Newsletters_Letterhead' ) ? new \Newspack_Newsletters_Letterhead() : false;

			/**
			 * NEWSPACK_* environment variables.
			 */
			$newspack_env_vars   = [];
			$wp_config_file_path = dirname( WP_CONTENT_DIR ) . '/wp-config.php';
			if ( file_exists( $wp_config_file_path ) ) {
				$wp_config_file = file_get_contents( $wp_config_file_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
				if ( false !== $wp_config_file ) {
					preg_match_all( "/'(NEWSPACK_\w*)',\s?('?.*'?)\s?\)/", $wp_config_file, $matches );
					if ( ! empty( $matches ) ) {
						foreach ( $matches[1] as $index => $match ) {
							$newspack_env_vars[ $match ] = trim( $matches[2][ $index ], " '" );
						}
					}
				}
			}

			$config = [
				'table_prefix'                        => $wpdb->prefix,
				'has_adminnewspack_user'              => false !== $adminnewspack_user,
				'np_env_vars'                         => $newspack_env_vars,
				'is_parsely_configured'               => is_plugin_active( 'wp-parsely/wp-parsely.php' ) && ! empty( $parsely_settings['apikey'] ),
				'front_page_amp_disabled'             => $front_page_amp_disabled,
				'is_ras_enabled'                      => method_exists( '\Newspack\Reader_Activation', 'is_enabled' ) && \Newspack\Reader_Activation::is_enabled(),
				'ras_connected_esp'                   => method_exists( '\Newspack_Newsletters', 'service_provider' ) ? Newspack_Newsletters::service_provider() : 'none',
				'ras_last_updated'                    => class_exists( '\Newspack_Popups_Presets' ) ? \get_option( \Newspack_Popups_Presets::NEWSPACK_POPUPS_RAS_LAST_UPDATED, 0 ) : 0,
				'newsletters_is_letterhead_connected' => $letterhead ? $letterhead->has_api_credentials() : false,
				'premium_newsletter_lists'            => $premium_newsletter_lists,
			];

			/**
			 * Third-party connections statuses.
			 */
			$connections = [
				'google'    => [
					'can_connect'       => false,
					'connected_account' => false,
					'error'             => false,
					'ga4_info'          => Newspack_GA4::get_info(),
				],
				'mailchimp' => [
					'can_connect'       => true,
					'connected_account' => false,
					'error'             => false,
				],
			];
			if ( method_exists( '\Newspack\Google_OAuth', 'api_google_auth_status' ) && method_exists( '\Newspack\OAuth', 'is_proxy_configured' ) ) {
				$is_configured = \Newspack\OAuth::is_proxy_configured( 'google' );
				if ( $is_configured ) {
					$connections['google']['can_connect'] = true;
					if ( false === $adminnewspack_user ) {
						$connections['google']['error'] = 'missing_admin_user';
					} else {
						$status = \Newspack\Google_OAuth::api_google_auth_status();
						if ( is_wp_error( $status ) ) {
							$connections['google']['error'] = $status->get_error_message();
						} elseif ( false !== $status->data['user_basic_info'] ) {
							$connections['google']['connected_account'] = $status->data['user_basic_info']['email'];
						}
					}
				}
			}
			if ( method_exists( '\Newspack\Mailchimp_API', 'api_mailchimp_auth_status' ) ) {
				$status_response = \Newspack\Mailchimp_API::api_mailchimp_auth_status();
				if ( isset( $status_response->data['username'] ) ) {
					$connections['mailchimp']['connected_account'] = $status_response->data['username'];
				}
			}

			// Custome batcache TTL.
			$batcache_custom_ttl_per_url_option = get_option( Batcache_Manager::CUSTOM_TTL_PER_URL_OPTION, [] );
			$batcache_custom_ttl_per_url        = implode(
				"\n",
				array_map(
					function( $ttl, $url ) {
						return "$url,$ttl";
					},
					$batcache_custom_ttl_per_url_option,
					array_keys( $batcache_custom_ttl_per_url_option )
				)
			);

			$custom_batcache = [
				'custom_ttl_for_site' => get_option( Batcache_Manager::CUSTOM_TTL_FOR_SITE_OPTION, null ),
				'custom_ttl_per_url'  => $batcache_custom_ttl_per_url,
			];

			/**
			 * Knife site creation meta.
			 */
			$knife_meta = [
				'creation_date'   => get_option( 'newspack_knife_creation_date', '' ),
				'creator'         => get_option( 'newspack_knife_creator', '' ),
				'expiration_date' => get_option( 'newspack_knife_expiration_date', '' ),
				'purpose'         => get_option( 'newspack_knife_purpose', '' ),
			];

			$response = [
				'health_check'             => [],
				'plugins'                  => $plugins,
				'newspack_managed_plugins' => $newspack_managed_plugins,
				'site_url'                 => get_site_url(),
				'donations'                => [],
				'config'                   => $config,
				'db'                       => $db_info,
				'ads'                      => $ads_config,
				'site_kit'                 => $site_kit_data,
				'connections'              => $connections,
				'newspack_entities'        => $newspack_entities,
				'custom_batcache'          => $custom_batcache,
				'knife_meta'               => $knife_meta,
				'mail'                     => [
					'sender_blocked' => Mail::get_blocked_addresses(),
				],
			];

			if ( method_exists( '\Newspack\Health_Check_Wizard', 'retrieve_data' ) ) {
				$response['health_check'] = \Newspack\Health_Check_Wizard::retrieve_data();
			}
			if ( method_exists( '\Newspack\Donations', 'get_platform_slug' ) ) {
				$response['donations']['platform'] = \Newspack\Donations::get_platform_slug();
			}

			/**
			 * Update the API key as the last step. If there are any errors before this point,
			 * the API key will not be updated.
			 */
			if ( null !== $request ) {
				$issued_api_key = $request->get_param( 'issued_api_key' );
				if ( is_string( $issued_api_key ) ) {
					delete_option( NEWSPACK_MANAGER_API_KEY_OPTION_NAME );
					$option_update_result = update_option( NEWSPACK_MANAGER_API_KEY_OPTION_NAME, $issued_api_key );

					// An unofficial solution to the notoptions bug. See:
					// - https://atomicp2.wordpress.com/2023/02/21/notoptions-versus-wpdb/
					// - https://a8c.slack.com/archives/C013N9S2C7Q/p1696599186431409?thread_ts=1696586762.638759&cid=C013N9S2C7Q .
					wp_cache_delete( 'notoptions', 'options' );
					wp_cache_delete( 'alloptions', 'options' );

					if ( false === $option_update_result ) {
						return new \WP_Error(
							'newspack_configure_site',
							__( 'Failed to update API key.', 'newspack-manager' )
						);
					}
				}
			}

			update_option( self::LAST_REQUEST_FROM_MANAGER_OPTION_NAME, time() );
			$manager_client_domain = self::get_request_domain( $request );
			if ( $manager_client_domain ) {
				update_option( self::MANAGER_REQUEST_DOMAIN_OPTION_NAME, $manager_client_domain );
			}

			return \rest_ensure_response( $response );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'newspack_get_site_info',
				$e->getMessage()
			);
		}
	}
}
Newspack_Manager::instance();

