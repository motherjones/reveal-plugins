<?php
/**
 * Newspack_Podcasts_CPT
 *
 * @package Newspack
 */

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Podcasts CPT Class.
 */
class Newspack_Podcasts_CPT {

	/**
	 * The podcast CPT slug. This is plural for backwards-compatibility reasons.
	 *
	 * @var string
	 */
	const NEWSPACK_PODCASTS_CPT = 'episodes';

	/**
	 * The meta key for the podcast file.
	 *
	 * @var string
	 */
	const META_PODCAST_FILE = 'newspack_podcasts_podcast_file';

	/**
	 * The single instance of the class.
	 *
	 * @var Newspack_Podcasts_CPT
	 */
	protected static $instance = null;

	/**
	 * Newspack_Podcasts_CPT Instance.
	 * Ensures only one instance of Newspack_Podcasts_CPT is loaded or can be loaded.
	 *
	 * @return Newspack_Podcasts_CPT Instance
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
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
		add_action( 'pre_get_posts', [ $this, 'query_like_posts' ] );
		add_filter( 'newspack_theme_featured_image_post_types', [ $this, 'add_featured_image_support' ] );
		add_filter( 'jetpack_sitemap_post_types', [ $this, 'newspack_podcasts_jetpack_sitemap_post_types' ] );
		add_filter( 'newspack_campaigns_post_types_for_campaigns', [ $this, 'add_campaigns_support' ] );
		self::add_templates();
	}

	/**
	 * Registers Episode custom post type.
	 */
	public function register_cpt() {
		$labels = [
			'name'               => _x( 'Episodes', 'post type general name', 'newspack-podcasts' ),
			'singular_name'      => _x( 'Episode', 'post type singular name', 'newspack-podcasts' ),
			'menu_name'          => _x( 'Episodes', 'admin menu', 'newspack-podcasts' ),
			'name_admin_bar'     => _x( 'Episode', 'add new on admin bar', 'newspack-podcasts' ),
			'add_new'            => _x( 'Add New', 'popup', 'newspack-podcasts' ),
			'add_new_item'       => __( 'Add New Episode', 'newspack-podcasts' ),
			'new_item'           => __( 'New Episode', 'newspack-podcasts' ),
			'edit_item'          => __( 'Edit Episode', 'newspack-podcasts' ),
			'view_item'          => __( 'View Episode', 'newspack-podcasts' ),
			'all_items'          => __( 'All Episodes', 'newspack-podcasts' ),
			'search_items'       => __( 'Search Episodes', 'newspack-podcasts' ),
			'parent_item_colon'  => __( 'Parent Episodes:', 'newspack-podcasts' ),
			'not_found'          => __( 'No episodes found.', 'newspack-podcasts' ),
			'not_found_in_trash' => __( 'No episodes found in Trash.', 'newspack-podcasts' ),
		];

		$cpt_args = [
			'labels'       => $labels,
			'public'       => true,
			'show_in_rest' => true,
			'supports'     => [ 'title', 'editor', 'comments', 'revisions', 'trackbacks', 'author', 'excerpt', 'page-attributes', 'thumbnail', 'custom-fields', 'newspack_blocks' ],
			'taxonomies'   => [ 'category', 'post_tag' ], // Regular post categories and tags.
			'menu_icon'    => 'dashicons-microphone',
			'has_archive'  => 'episodes',
			'rewrite'      => [ 'slug' => 'podcast' ],
		];

		register_post_type( self::NEWSPACK_PODCASTS_CPT, $cpt_args );
	}

	/**
	 * Adds the podcast episode CPT to Jetpack sitemaps.
	 * https://developer.jetpack.com/hooks/jetpack_sitemap_post_types/
	 *
	 * @param array $post_types Array of post type slugs to be included in Jetpack sitemaps.
	 * @return array Filtered array of post types.
	 */
	public static function newspack_podcasts_jetpack_sitemap_post_types( $post_types ) {
		$post_types[] = self::NEWSPACK_PODCASTS_CPT;
		return $post_types;
	}

	/**
	 * Register the podcast location meta box.
	 *
	 * @todo This should be done as a panel in the block editor instead of legacy metabox.
	 *
	 * @param string $post_type The post type of the current post.
	 */
	public function register_meta_box( $post_type ) {
		if ( self::NEWSPACK_PODCASTS_CPT !== $post_type ) {
			return;
		}

		add_meta_box(
			'newspack_podcasts_file',
			__( 'Podcast Location', 'newspack-podcasts' ),
			[ $this, 'render_meta_box' ],
			self::NEWSPACK_PODCASTS_CPT
		);
	}

	/**
	 * Render the podcast location meta box.
	 *
	 * @todo This should be done as a panel in the block editor instead of legacy metabox.
	 *
	 * @param WP_Post $post Post object of the current post.
	 */
	public function render_meta_box( $post ) {

        //begin prx embed url
        $player_url = get_post_meta( $post->ID, 'podcast_player_url', true );
?>
        <label for="podcast_player_url"><strong>PRX Embed URL</strong></label>
        <p><input type="url" name="podcast_player_url" placeholder="https://play.prx.org/e?uf=https://feeds.revealnews&ge=prx_123_-abcdef-1234-abcd-1234" value="<?php echo esc_attr( $player_url ); ?>"></p>
        <p><em>Paste in the url of a podcast player player to get an embeded podcast player</em></p> 
<?php
        if ( $player_url ) {
?>
        <p><strong>
            Current podcast player url:
            <?php echo esc_html( $player_url); ?>
        </strong></p>
<?php
        }
            //end prx embed url
            //begin podcast file
		$saved = self::get_podcast_file( $post->ID );
		?>
        <label for="<?php echo esc_attr( self::META_PODCAST_FILE ); ?>">
            <strong>Podcast Media File</strong>
        </label>
		<p><input type='text' name='<?php echo esc_attr( self::META_PODCAST_FILE ); ?>' placeholder='example.mp3' value='<?php echo esc_attr( $saved ); ?>' /></p>
		<p><em>Enter the filename of the media file (example.mp3) or a full path to the podcast media file (https://example.com/example.mp3)</em></p>
		<?php

		if ( $saved ) :
			$podcast_location = self::get_podcast_url( $post->ID );
			if ( ! $podcast_location ) {
				$podcast_location = __( 'Unable to build URL to podcast. Are your settings configured correctly?', 'newspack-podcasts' );
			}
			?>
			<p>
				<strong>
					<?php
					echo esc_html(
						/* translators: %s - podcast URL. */
						sprintf( __( 'Podcast location: %s', 'newspack-podcasts' ), $podcast_location )
					);
					?>
				</strong>
			</p>
			<?php
		endif;
            //end podcast file
	}

	/**
	 * Save the podcast location meta box.
	 *
	 * @todo This should be done as a panel in the block editor instead of legacy metabox.
	 *
	 * @param int $post_id The post ID of the current post.
	 */
	public function save_meta_box( $post_id ) {
		$podcast_file = filter_input( INPUT_POST, self::META_PODCAST_FILE, FILTER_SANITIZE_STRING );
		if ( $podcast_file ) {
            $podcast_file = sanitize_text_field( $podcast_file );
            update_post_meta( $post_id, self::META_PODCAST_FILE, $podcast_file );
        }
		if ( '' === $podcast_file ) {
            delete_post_meta( $post_id, self::META_PODCAST_FILE );
        }

		$podcast_player_url = filter_input( INPUT_POST, 'podcast_player_url', FILTER_SANITIZE_STRING );
        if ( $podcast_player_url ) {
            $podcast_player_url = esc_url($podcast_player_url);
            update_post_meta( $post_id, 'podcast_player_url', $podcast_player_url );
        }
        if ( '' ===  $podcast_player_url ) {
            delete_post_meta( $post_id, 'podcast_player_url' );
        }
    }

	/**
	 * Treat posts of this post type like regular posts when querying.
	 *
	 * @param WP_Query $query WP_Query object.
	 */
	public function query_like_posts( $query ) {
		// Only modify the main query, and only on the front-end.
		if ( ! $query->is_main_query() || is_admin() ) {
			return;
		}

		// Only on the homepage, archive pages, and search results.
		if ( ! $query->is_home() && ! $query->is_archive() && ! $query->is_search() ) {
			return;
		}

		$post_type         = $query->get( 'post_type', '' );
		$updated_post_type = $post_type;
		if ( empty( $post_type ) || 'post' === $post_type ) {
			$updated_post_type = [ self::NEWSPACK_PODCASTS_CPT, 'post' ];
		} elseif ( is_array( $post_type ) && in_array( 'post', $post_type ) ) {
			$updated_post_type[] = self::NEWSPACK_PODCASTS_CPT;
		}

		if ( $updated_post_type !== $post_type ) {
			$query->set( 'post_type', $updated_post_type ); //phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
		}
	}

	/**
	 * Add support for featured image settings.
	 *
	 * @param array $post_types Array of post type slugs.
	 * @return array Modified $post_types.
	 */
	public static function add_featured_image_support( $post_types ) {
		$post_types[] = self::NEWSPACK_PODCASTS_CPT;
		return $post_types;
	}

	/**
	 * Add support for Newspack Campaigns modals.
	 *
	 * @param array $post_types Array of post type slugs.
	 * @return array Modified $post_types.
	 */
	public static function add_campaigns_support( $post_types ) {
		$post_types[] = self::NEWSPACK_PODCASTS_CPT;
		return $post_types;
	}

	/**
	 * Add Wide and One-Column templates to this CPT.
	 */
	public static function add_templates() {
		// Add templates to post type.
		add_filter(
			'theme_' . self::NEWSPACK_PODCASTS_CPT . '_templates',
			function( $templates ) {
				if ( ! isset( $templates['single-feature.php'] ) ) {
					$templates['single-feature.php'] = 'One column';
				}

				if ( ! isset( $templates['single-wide.php'] ) ) {
					$templates['single-wide.php'] = 'One column wide';
				}

				return $templates;
			}
		);

		// Add body classes so styling gets applied.
		add_filter(
			'body_class',
			function( $classes ) {
				if ( in_array( self::NEWSPACK_PODCASTS_CPT . '-template-single-wide', $classes ) ) {
					$classes[] = 'post-template-single-wide';
				}

				if ( in_array( self::NEWSPACK_PODCASTS_CPT . '-template-single-feature', $classes ) ) {
					$classes[] = 'post-template-single-feature';
				}

				return $classes;
			}
		);
	}

	/**
	 * Get the podcast file (the value entered in the meta box).
	 *
	 * @param int $post_id The post ID (optional: default - current post ID).
	 * @return string Podcast file/URL. Whatever the raw input was.
	 */
	public static function get_podcast_file( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id ) {
			return '';
		}

		$saved = get_post_meta( $post_id, self::META_PODCAST_FILE, true );
		if ( empty( $saved ) ) {
			return '';
		}

		if ( 0 === stripos( $saved, 'http' ) ) {
			return esc_url( $saved );
		}

		return sanitize_text_field( $saved );
	}

	/**
	 * Get the full URL to a podcast. This is the function you'd want to use on the frontend.
	 *
	 * @param int $post_id The post ID (optional: default - current post ID).
	 * @return string Podcast URL. Full URL to podcast.
	 */
	public static function get_podcast_url( $post_id = null ) {
		$podcast_file = self::get_podcast_file( $post_id );

		if ( empty( $podcast_file ) ) {
			return '';
		}

		// If it's a full URL, return that URL.
		if ( 0 === stripos( $podcast_file, 'http' ) ) {
			return esc_url( $podcast_file );
		}

		// Otherwise, we need to build a URL.
		$podcast_cdn = Newspack_Podcasts_Settings::get_podcast_cdn_url();
		if ( empty( $podcast_cdn ) ) {
			return '';
		}

		return esc_url( trailingslashit( $podcast_cdn ) . $podcast_file );
	}
}
Newspack_Podcasts_CPT::instance();


