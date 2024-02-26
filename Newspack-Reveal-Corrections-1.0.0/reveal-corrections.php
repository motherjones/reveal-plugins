<?php
/**
 * Plugin Name: Newspack - Reveal Corrections
 * Description: Adds "Corrections" functionality to Reveal.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://newspack.blog/
 * License: GPL2
 * Text Domain: reveal-corrections
 * Domain Path: /languages/
 */

defined( 'ABSPATH' ) || exit;

class Reveal_Corrections {

	const CORRECTION_META = 'article-corrections';

	const CORRECTION_ACTIVE_META = 'has_corrections';

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_corrections_metabox' ] );
		add_action( 'save_post', [ __CLASS__, 'save_corrections_metabox' ] );
		add_filter( 'the_content', [ __CLASS__, 'output_corrections_on_post' ] );
		add_action( 'init', [ __CLASS__, 'add_corrections_shortcode' ] );
	}

	public static function add_corrections_shortcode() {
		add_shortcode( 'corrections', [ __CLASS__, 'handle_corrections_shortcode' ] );
	}

	public static function handle_corrections_shortcode() {
		global $wpdb;

		$post_ids = get_posts( [
			'posts_per_page' => -1,
			'meta_key'       => self::CORRECTION_ACTIVE_META,
			'meta_value'     => 1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		ob_start();
		foreach ( $post_ids as $post_id ) :
			$corrections = get_post_meta( $post_id, self::CORRECTION_META, true );
			if ( empty( $corrections ) ) {
				continue;
			}

			?>
<!-- wp:group {"className":"is-style-default correction-shortcode-item"} -->
<div class="wp-block-group is-style-default correction-shortcode-item">
	<div class="wp-block-group__inner-container">
		<!-- wp:newspack-blocks/homepage-articles {"showExcerpt":false,"showDate":false,"showAuthor":false,"mediaPosition":"left","specificPosts":["<?php echo intval( $post_id ); ?>"],"imageScale":2,"specificMode":true} /-->

		<div class="correction-list">
			<?php foreach ( $corrections as $correction ) : ?>
				<?php $correction_heading = ! empty( $correction['date'] ) ? 'Correction on ' . date( 'M j, Y', strtotime( $correction['date'] ) ) : 'Correction'; ?>
				<p>
					<span class="correction-date"><?php echo esc_html( $correction_heading ); ?><span>:</span></span>
					<?php echo esc_html( $correction['correction'] ); ?>
				</p>
			<?php endforeach; ?>
		</div>

</div></div>
<!-- /wp:group -->
			<?php
		endforeach;
		return do_blocks( ob_get_clean() );
	}

	public static function add_corrections_metabox( $post_type ) {
		$valid_post_types = [ 'article_legacy', 'content_type_blog', 'post', 'press_release' ];
		if ( in_array( $post_type, $valid_post_types ) ) {
            add_meta_box(
                'reveal_corrections',
                'Corrections',
                [ __CLASS__, 'render_corrections_metabox' ],
                $post_type,
                'advanced',
                'high'
            );
		}
	}

	public static function render_corrections_metabox( $post ) {
		$is_active = (bool) get_post_meta( $post->ID, self::CORRECTION_ACTIVE_META, true );
		$existing_corrections = get_post_meta( $post->ID, self::CORRECTION_META, true );
		if ( ! is_array( $existing_corrections ) ) {
			$existing_corrections = [];
		}
		?>
		<style>
			.activate-corrections {
				padding-top: 1em;
				padding-bottom: 1em;
				border-bottom: 2px solid silver;
				margin-bottom: 1em;
			}

			.reveal-correction {
				position: relative;
				border-bottom: 1px solid silver;
				padding-top: 1em;
				padding-bottom: 1em;
			}

			.delete-correction {
				position: absolute;
				top: 2em;
				right: 2em;
				font-weight: bold;
				color: silver;
				border: 1px solid silver;
				padding-left: .25em;
				padding-right: .25em;
				cursor: pointer;
			}

			.delete-correction:hover {
				color: red;
			}

			.add-correction {
				margin-top: 2em;
				cursor: pointer;
			}
		</style>

		<div class="corrections-metabox-container">
			<div class="activate-corrections">
				<input type="hidden" value="0" name="<?php echo self::CORRECTION_ACTIVE_META; ?>" />
				<input type="checkbox" value="1" name="<?php echo self::CORRECTION_ACTIVE_META; ?>" <?php checked( $is_active ); ?> />
				Activate Corrections
			</div>
			<div class="manage-corrections">
				<div class="existing-corrections">
					<?php foreach ( $existing_corrections as $existing_correction ) : ?>
						<div class="reveal-correction">
							<p>Article Correction</p>
							<textarea name="reveal_correction[]" rows="3" cols="60">
<?php echo sanitize_textarea_field( $existing_correction['correction'] ); ?>
							</textarea><br/>
							<p>Date: <input type="date" name="reveal_correction_date[]" value="<?php echo sanitize_text_field( $existing_correction['date'] ); ?>"></p>
							<span class="delete-correction">X</span>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="add-correction">Add new Correction</button>
			</div>
		</div>

		<script>
			( function($) {
				if ( ! $( '.corrections-metabox-container' ).length ) {
					return;
				}

				// Click handler for Add Correction button.
				$( '.add-correction' ).click( function() {
					$( '.existing-corrections' ).append( '<div class="reveal-correction"><p>Article Correction</p><textarea name="reveal_correction[]" rows="3" cols="60"></textarea><br/><p>Date: <input type="date" name="reveal_correction_date[]"></p><span class="delete-correction">X</span>' );
				} );

				// Click handler for Delete Correction button.
				$( document ).on( 'click', '.delete-correction', function( evt ) {
					$( evt.target ).parent().remove();
				} );
			} )( jQuery );
		</script>

		<?php
	}

	public static function save_corrections_metabox( $post_id ) {
		if ( ! isset( $_POST[ self::CORRECTION_ACTIVE_META ] ) ) {
			return;
		}

		$is_active = (bool) filter_input( INPUT_POST, self::CORRECTION_ACTIVE_META, FILTER_SANITIZE_NUMBER_INT );

		$corrections_data = filter_input_array(
			INPUT_POST,
			[
				'reveal_correction' => [
					'flags' => FILTER_REQUIRE_ARRAY,
					'filter' => FILTER_SANITIZE_STRING,
				],
				'reveal_correction_date' => [
					'flags' => FILTER_REQUIRE_ARRAY,
					'filter' => FILTER_SANITIZE_STRING,
				],
			]
		);

		$corrections = [];
		foreach ( $corrections_data['reveal_correction'] as $index => $correction_text ) {
			$corrections[] = [
				'correction' => sanitize_textarea_field( $correction_text ),
				'date' => ! empty( $corrections_data['reveal_correction_date'][ $index ] ) ? sanitize_text_field( $corrections_data['reveal_correction_date'][ $index ] ) : date( 'Y-m-d' ),
			];
		}

		update_post_meta( $post_id, self::CORRECTION_ACTIVE_META, $is_active );
		update_post_meta( $post_id, self::CORRECTION_META, $corrections );
	}

	public static function output_corrections_on_post( $content ) {
		if ( is_admin() || ! is_single() ) {
			return $content;
		}

		$corrections_active = get_post_meta( get_the_ID(), self::CORRECTION_ACTIVE_META, true );
		if ( ! $corrections_active ) {
			return $content;
		}

		$corrections = get_post_meta( get_the_ID(), self::CORRECTION_META, true );
		$has_valid_correction = false;
		foreach ( $corrections as $correction ) {
			if ( ! empty( trim( $correction['correction'] ) ) ) {
				$has_valid_correction = true;
				break;
			}
		}

		if ( ! $has_valid_correction ) {
			return $content;
		}

		ob_start();
		?>
<!-- wp:group {"className":"correction-module","backgroundColor":"light-gray"} -->
<div class="wp-block-group correction-module has-light-gray-background-color has-background"><div class="wp-block-group__inner-container">
	<?php foreach ( $corrections as $correction ) :
		if ( empty( trim( $correction['correction'] ) ) ) {
			continue;
		}

		$correction_heading = ! empty( $correction['date'] ) ? 'Correction on ' . date( 'M j, Y', strtotime( $correction['date'] ) ) : 'Correction';
		?>
		<!-- wp:paragraph {"fontSize":"small"} -->
		<p class="has-small-font-size correction-heading"><?php echo esc_html( $correction_heading ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"normal"} -->
		<p class="has-normal-font-size correction-body"><?php echo esc_html( $correction['correction'] ); ?></p>
		<!-- /wp:paragraph -->
	<?php endforeach; ?>
	</div></div>
<!-- /wp:group -->
		<?php
		$markup = do_blocks( ob_get_clean() );
		return $content . $markup;
	}
}
Reveal_Corrections::init();


