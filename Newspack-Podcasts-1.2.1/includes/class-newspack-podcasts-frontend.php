<?php
/**
 * Newspack Podcasts Frontend
 *
 * @package Newspack
 */

defined( 'ABSPATH' ) || exit;

/**
 * Newspack_Podcasts_Frontend Class.
 */
class Newspack_Podcasts_Frontend {

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Podcasts_Settings
	 */
	protected static $instance = null;

	/**
	 * Newspack_Podcasts_Frontend Instance.
	 * Ensures only one instance of Newspack_Podcasts_Frontend is loaded or can be loaded.
	 *
	 * @return Newspack_Podcasts_Frontend instance.
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
		add_filter( 'the_content', [ $this, 'add_podcast_components_to_single' ], 999 );
		add_filter( 'the_excerpt', [ $this, 'add_player_on_archives' ], 999 );
		add_action( 'newspack_theme_below_archive_title', [ $this, 'add_podcast_description_to_archive' ] );
	}

	/**
	 * Add podcast components (player, etc.) on Podcast episode posts.
	 *
	 * @param string $content Post content.
	 * @return string Modified $content.
	 */
	public function add_podcast_components_to_single( $content ) {
		if ( is_admin() || ! is_single() || Newspack_Podcasts_CPT::NEWSPACK_PODCASTS_CPT !== get_post_type() ) {
			return $content;
		}

		// Don't inject these components into empty image captions on the post.
		if ( empty( trim( wp_strip_all_tags( $content ) ) ) ) {
			return $content;
		}

		$markup  = $this->get_player_html();
		$markup .= $this->get_next_prev_links_html();

		return $markup . $content;
	}

	/**
	 * Add podcast player on Episode archive pages.
	 *
	 * @param string $excerpt Post excerpt.
	 * @return string Modified $excerpt.
	 */
	public function add_player_on_archives( $excerpt ) {
		if ( is_admin() || ! is_archive() || Newspack_Podcasts_CPT::NEWSPACK_PODCASTS_CPT !== get_post_type() ) {
			return $excerpt;
		}

		return $excerpt . $this->get_player_html();
	}

	/**
	 * Add the description to the Episode archive page.
	 */
	public function add_podcast_description_to_archive() {
		if ( ! is_post_type_archive( Newspack_Podcasts_CPT::NEWSPACK_PODCASTS_CPT ) ) {
			return;
		}

		echo $this->get_podcast_description_html(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get the HTML for the podcast player.
	 *
	 * @param int $post_id The post ID to get the player for (optional, default current post).
	 * @return string Player HTML.
	 */
	protected function get_player_html( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id ) {
			return '';
		}

		$podcast_url = Newspack_Podcasts_CPT::get_podcast_url();
		if ( empty( $podcast_url ) ) {
			return '';
		}

		ob_start();
		?>
<!-- wp:audio {"className":"newspack-podcast-player"} -->
<figure class="wp-block-audio newspack-podcast-player"><audio controls src="<?php echo esc_url( $podcast_url ); ?>"></audio></figure>
<!-- /wp:audio -->
		<?php

		return do_blocks( ob_get_clean() );
	}

	/**
	 * Get the HTML for next/previous episode links.
	 *
	 * @param int $post_id The post ID to get the links for (optional, default current post).
	 * @return string Next/prev episode link HTML.
	 */
	protected function get_next_prev_links_html( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id ) {
			return '';
		}

		ob_start();
		?>

		<nav class="newspack-podcast-pagination navigation post-navigation" role="navigation" aria-label="Podcast Episodes">
			<h2 class="screen-reader-text"><?php esc_html_e( 'Episode Navigation', 'newspack-podcasts' ); ?></h2>
			<div class="nav-links">

				<?php
					previous_post_link(
						sprintf(
							'<div class="nav-previous"><span class="meta-nav">%1$s</span> <span class="post-title">%2$s</span></div>',
							__( 'Previous episode', 'newspack-podcasts' ),
							'%link'
						)
					);

					next_post_link(
						sprintf(
							'<div class="nav-next"><span class="meta-nav">%1$s</span> <span class="post-title">%2$s</span></div>',
							__( 'Next episode', 'newspack-podcasts' ),
							'%link'
						)
					);
				?>


			</div>
		</nav>

		<?php
		return ob_get_clean();
	}

	/**
	 * Get HTML for the podcast archive description.
	 *
	 * @param int $post_id The post ID to get the player for (optional, default current post).
	 * @return string Description HTML.
	 */
	protected function get_podcast_description_html( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id ) {
			return '';
		}

		$podcast_description = Newspack_Podcasts_Settings::get_podcast_description();
		if ( empty( $podcast_description ) ) {
			return;
		}

		ob_start();
		?>

		<div class="taxonomy-description">
			<?php echo wp_kses_post( $podcast_description ); ?>
		</div>

		<?php
		return ob_get_clean();
	}
}
Newspack_Podcasts_Frontend::instance();

