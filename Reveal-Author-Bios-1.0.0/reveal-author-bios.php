<?php
/**
 * Plugin Name: Reveal Author Bios
 * Description: Tweaks around author bio fields/display.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://newspack.blog/
 * License: GPL2
 */

defined( 'ABSPATH' ) || exit;

class Reveal_Author_Bios {

	const AUTHOR_JOB_TITLE_META = 'author_job_title';
	const AUTHOR_PHONE_NUMBER_META = 'author_phone';

	const GUEST_AUTHOR_JOB_TITLE_META = 'guest-author-job-title';
	const GUEST_AUTHOR_PHONE_NUMBER_META = 'guest-author-phone';
	const GUEST_AUTHOR_TWITTER_META = 'guest-author-twitter';

	/**
	 * Hooks and filters.
	 */
	public static function init() {

		add_action( 'show_user_profile', [ __CLASS__, 'extra_user_fields' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'extra_user_fields' ] );
		add_action( 'personal_options_update', [ __CLASS__, 'save_extra_user_fields' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'save_extra_user_fields' ] );

		add_action( 'add_meta_boxes', [ __CLASS__, 'add_guest_author_extra_fields' ] );
		add_action( 'save_post', [ __CLASS__, 'save_guest_author_extra_fields' ] );

		add_action( 'newspack_theme_below_author_archive_meta', [ __CLASS__, 'output_author_info' ] );
		add_action( 'wp_head', [ __CLASS__, 'output_styles' ] );
		add_action( 'newspack_theme_below_archive_title', [ __CLASS__, 'output_author_title' ] );
	}

	/**
	 * Register meta boxes for guest author.
	 */
	public static function add_guest_author_extra_fields() {
		add_meta_box(
			'reveal-author-bios',
			'Extra Profile Info',
			[ __CLASS__, 'render_guest_author_extra_fields' ],
			'guest-author'
		);
	}

	/**
	 * Render meta boxes for guest author.
	 */
	public static function render_guest_author_extra_fields( $post ) {
		$job_title = (string) get_post_meta( $post->ID, self::GUEST_AUTHOR_JOB_TITLE_META, true );
		$phone_number = (string) get_post_meta( $post->ID, self::GUEST_AUTHOR_PHONE_NUMBER_META, true );
		$twitter_handle = (string) get_post_meta( $post->ID, self::GUEST_AUTHOR_TWITTER_META, true );
		?>
		<table class="form-table">
			<tr>
				<th>
					<label for="job_title">Job Title</label>
				</th>
				<td>
					<input type="text" class="regular-text" id="job_title" name="<?php echo self::GUEST_AUTHOR_JOB_TITLE_META; ?>" value="<?php echo esc_attr( $job_title ); ?>" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="phone_number">Phone Number</label>
				</th>
				<td>
					<input type="text" class="regular-text" id="phone_number" name="<?php echo self::GUEST_AUTHOR_PHONE_NUMBER_META; ?>" value="<?php echo esc_attr( $phone_number ); ?>" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="twitter_handle">Twitter Handle</label>
				</th>
				<td>
					<input type="text" class="regular-text" id="twitter_handle" name="<?php echo self::GUEST_AUTHOR_TWITTER_META; ?>" value="<?php echo esc_attr( $twitter_handle ); ?>" />
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta boxes for guest author.
	 */
	public static function save_guest_author_extra_fields( $post_id ) {
		if ( ! isset( $_POST[ self::GUEST_AUTHOR_JOB_TITLE_META ] ) ) {
			return;
		}

		$job_title = sanitize_text_field( filter_input( INPUT_POST, self::GUEST_AUTHOR_JOB_TITLE_META, FILTER_SANITIZE_STRING ) );
		$phone_number = sanitize_text_field( filter_input( INPUT_POST, self::GUEST_AUTHOR_PHONE_NUMBER_META, FILTER_SANITIZE_STRING ) );
		$twitter_handle = sanitize_text_field( filter_input( INPUT_POST, self::GUEST_AUTHOR_TWITTER_META, FILTER_SANITIZE_STRING ) );
		update_post_meta( $post_id, self::GUEST_AUTHOR_JOB_TITLE_META, $job_title );
		update_post_meta( $post_id, self::GUEST_AUTHOR_PHONE_NUMBER_META, $phone_number );
		update_post_meta( $post_id, self::GUEST_AUTHOR_TWITTER_META, $twitter_handle );
	}

	/**
	 * Register meta boxes for author.
	 */
	public static function extra_user_fields( $user ) {
		$job_title    = (string) get_user_meta( $user->ID, self::AUTHOR_JOB_TITLE_META, true );
		$phone_number = (string) get_user_meta( $user->ID, self::AUTHOR_PHONE_NUMBER_META, true );
		?>
		<h3>Extra profile info</h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="job_title">Job Title</label>
				</th>
				<td>
					<input type="text" class="regular-text" id="job_title" name="<?php echo self::AUTHOR_JOB_TITLE_META; ?>" value="<?php echo esc_attr( $job_title ); ?>" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="phone_number">Phone Number</label>
				</th>
				<td>
					<input type="text" class="regular-text" id="phone_number" name="<?php echo self::AUTHOR_PHONE_NUMBER_META; ?>" value="<?php echo esc_attr( $phone_number ); ?>" />
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Register meta boxes for author.
	 */
	public static function save_extra_user_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::AUTHOR_JOB_TITLE_META ] ) ) {
			return;
		}

		$job_title    = sanitize_text_field( filter_input( INPUT_POST, self::AUTHOR_JOB_TITLE_META, FILTER_SANITIZE_STRING ) );
		$phone_number = sanitize_text_field( filter_input( INPUT_POST, self::AUTHOR_PHONE_NUMBER_META, FILTER_SANITIZE_STRING ) );
		update_user_meta( $user_id, self::AUTHOR_JOB_TITLE_META, $job_title );
		update_user_meta( $user_id, self::AUTHOR_PHONE_NUMBER_META, $phone_number );
	}

	/**
	 * Output CSS on author pages.
	 */
	public static function output_styles() {
		if ( ! is_author() ) {
			return;
		}
		?>
		<style>
			.author-meta {
				display: none;
			}

			.reveal-author-bio.author-meta {
				display: block;
				margin-top: 2em;
			}

			.reveal-author-bio.author-meta > * {
				display: block;
				margin-bottom: .25rem;
			}

			.reveal-author-bio.author-meta .author-expanded-social-link a svg {
				margin-right: .25em;
			}

			.reveal-author-bio.author-meta a {
				align-items: center;
				display: flex;
			}

			@media (min-width: 782px) {
				.reveal-author-bio.author-meta {
					display: flex;
					flex-wrap: wrap;
				}

				.reveal-author-bio.author-meta > * {
					border-right: 1px solid #ccc;
					margin: 0 1rem 0 0;
					padding-right: 1rem;
					align-items: center;
					display: flex;
				}

				.reveal-author-bio.author-meta > *:last-child {
					border: 0;
					margin: 0;
					padding: 0;
				}

			}
		</style>

		<?php
	}

	/**
	 * Output contact info on author pages.
	 */
	public static function output_author_info() {
		if ( ! is_author() ) {
			return;
		}

		$author = get_queried_object();
		$author_phone = get_user_meta( $author->ID, self::AUTHOR_PHONE_NUMBER_META, true );
		$author_email = get_the_author_meta( 'user_email', get_query_var( 'author' ) );
		$guest_author_twitter = '';

		if ( function_exists( 'coauthors_posts_links' ) ) {
			if ( 'guest-author' === get_post_type( $author->ID ) ) {
				$author_phone  = get_post_meta( $author->ID, self::GUEST_AUTHOR_PHONE_NUMBER_META, true );
				$guest_author_twitter = get_post_meta( $author->ID, self::GUEST_AUTHOR_TWITTER_META, true );
			}
		}

		?>
		<div class="reveal-author-bio author-meta">
			<?php if ( true === get_theme_mod( 'show_author_email', false ) && '' !== $author_email ) : ?>
				<a class="author-email" href="<?php echo 'mailto:' . esc_attr( $author_email ); ?>">
					<?php echo wp_kses( newspack_get_social_icon_svg( 'mail', 18 ), newspack_sanitize_svgs() ); ?>
					<?php echo esc_html( $author_email ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $author_phone ) : ?>
				<span class="author-expanded-social-link">
					<a href="tel:<?php echo esc_attr( $author_phone ); ?>">
						<?php echo newspack_get_social_icon_svg( 'phone', 20 ); ?>
						<?php echo esc_html( $author_phone ); ?>
					</a>
				</span>
			<?php endif; ?>

			<?php if ( $guest_author_twitter ) : ?>
				<span class="author-expanded-social-link">
					<a href="https://twitter.com/<?php echo str_replace( '@', '', $guest_author_twitter ); ?>" rel="nofollow">
						<?php echo newspack_get_social_icon_svg( 'twitter', 20 ); ?>
						<?php echo esc_html( $guest_author_twitter ); ?>
					</a>
				</span>
			<?php endif; ?>

			<?php newspack_author_social_links( get_the_author_meta( 'ID' ), 20 ); ?>
		</div>
		<?php
	}

	/**
	 * Output author role on author pages.
	 */
	public static function output_author_title() {
		if ( ! is_author() ) {
			return;
		}

		$author = get_queried_object();
		$author_title = get_user_meta( $author->ID, self::AUTHOR_JOB_TITLE_META, true );

		if ( function_exists( 'coauthors_posts_links' ) ) {
			if ( 'guest-author' === get_post_type( $author->ID ) ) {
				$author_title  = get_post_meta( $author->ID, self::GUEST_AUTHOR_JOB_TITLE_META, true );
			}
		}

		if ( ! $author_title ) {
			return;
		}
		?>
		<h3 class="reveal-author-job-title">
			<?php echo esc_html( $author_title ); ?>
		</h3>
		<?php
	}
}
Reveal_Author_Bios::init();


