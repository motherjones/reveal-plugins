<?php
/**
 * Newspack_Podcasts_Settings
 *
 * @package Newspack
 */

defined( 'ABSPATH' ) || exit;

/**
 * Newspack_Podcasts_Settings Class.
 */
final class Newspack_Podcasts_Settings {

	/**
	 * Option key for the CDN URL setting.
	 *
	 * @var string
	 */
	const OPTION_PODCAST_CDN = 'newspack_podcasts_cdn';

	/**
	 * Option key for the description setting.
	 *
	 * @var string
	 */
	const OPTION_PODCAST_DESCRIPTION = 'newspack_podcasts_description';

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Podcasts_Settings
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Podcasts Settings Instance.
	 * Ensures only one instance of Newspack Podcasts Settings is loaded or can be loaded.
	 *
	 * @return Newspack Podcasts Settings instance.
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
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add settings page to the post type area.
	 */
	public function register_settings_page() {
		add_submenu_page(
			'edit.php?post_type=' . Newspack_Podcasts_CPT::NEWSPACK_PODCASTS_CPT,
			__( 'Settings', 'newspack-podcasts' ),
			__( 'Settings', 'newspack-podcasts' ),
			'manage_options',
			'episodes_settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Podcast Settings', 'newspack-podcasts' ); ?></h1>
			<form method='post' action='options.php'>
				<?php settings_fields( 'newspack_podcasts' ); ?>
				<?php do_settings_sections( 'newspack-podcasts-settings' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register the settings.
	 */
	public function register_settings() {
		register_setting(
			'newspack_podcasts',
			self::OPTION_PODCAST_CDN,
			[ $this, 'sanitize_podcast_cdn_option' ]
		);

		register_setting(
			'newspack_podcasts',
			self::OPTION_PODCAST_DESCRIPTION,
			[ $this, 'sanitize_podcast_description' ]
		);

		add_settings_section(
			'newspack_podcast_settings',
			'',
			null,
			'newspack-podcasts-settings'
		);

		add_settings_field(
			self::OPTION_PODCAST_CDN,
			__( 'Podcast CDN', 'newspack-podcasts' ),
			[ $this, 'render_podcast_cdn_settings' ],
			'newspack-podcasts-settings',
			'newspack_podcast_settings'
		);

		add_settings_field(
			self::OPTION_PODCAST_DESCRIPTION,
			__( 'Podcast Description', 'newspack-podcasts' ),
			[ $this, 'render_podcast_description_settings' ],
			'newspack-podcasts-settings',
			'newspack_podcast_settings'
		);
	}

	/**
	 * Render the CDN URL setting.
	 */
	public function render_podcast_cdn_settings() {
		$saved_settings = self::get_podcast_cdn_url();
		?>
		<input type="url" value="<?php echo esc_attr( $saved_settings ); ?>" name="<?php echo esc_attr( self::OPTION_PODCAST_CDN ); ?>" placeholder="https://example.com" pattern="https://.*" size="30" />
		<p><em><?php esc_html_e( 'Enter the URL of the CDN your podcasts are hosted at.', 'newspack-podcasts' ); ?></em></p>
		<p><em><?php esc_html_e( 'For example, if your podcasts are hosted at "https://example.com/podcast.mp3", enter "https://example.com" here. If your podcasts are hosted locally, you can enter your site\'s URL followed by "/wp-content/uploads" (e.g. "https://example.com/wp-content/uploads")', 'newspack-podcasts' ); ?></em></p>
		<?php
	}

	/**
	 * Render the Description setting.
	 */
	public function render_podcast_description_settings() {
		$saved_settings = self::get_podcast_description();
		?>
		<textarea type="url" name="<?php echo esc_attr( self::OPTION_PODCAST_DESCRIPTION ); ?>" placeholder="" rows="10" cols="60"><?php echo wp_kses_post( $saved_settings ); ?></textarea>
		<p><em><?php esc_html_e( 'Enter the description for your podcast archives.', 'newspack-podcasts' ); ?></em></p>
		<?php
	}

	/**
	 * Sanitize the CDN URL setting.
	 *
	 * @param string $input Raw input from the setting field.
	 * @return string Sanitized input.
	 */
	public function sanitize_podcast_cdn_option( $input ) {
		if ( empty( $input ) ) {
			return '';
		}

		return esc_url( $input );
	}

	/**
	 * Sanitize the Description field.
	 *
	 * @param string $input Raw input from the setting field.
	 * @return string Sanitized input.
	 */
	public function sanitize_podcast_description( $input ) {
		if ( empty( $input ) ) {
			return '';
		}

		return wp_kses_post( $input );
	}

	/**
	 * Get the podcast CDN URL.
	 *
	 * @return string Podcast CDN URL.
	 */
	public static function get_podcast_cdn_url() {
		$cdn_url = get_option( self::OPTION_PODCAST_CDN, '' );
		if ( empty( $cdn_url ) ) {
			return '';
		}

		return esc_url( $cdn_url );
	}

	/**
	 * Get the podcast description.
	 *
	 * @return string Podcast description.
	 */
	public static function get_podcast_description() {
		$description = get_option( self::OPTION_PODCAST_DESCRIPTION, '' );
		if ( empty( $description ) ) {
			return '';
		}

		return wp_kses_post( $description );
	}
}
Newspack_Podcasts_Settings::instance();

