<?php
/**
 * Plugin Name:       Newspack Setka Integration
 * Description:       Tweaks to make Setka posts look nice on Newspack Theme.
 * Version:           1.0.0
 * Author:            Automattic
 * Author URI:        https://automattic.com
 * Text Domain:       newspack-setka
 * Domain Path:       /languages
 */

add_filter( 'body_class', function( $classes ) {
	if ( ! is_singular() ) {
		return $classes;
	}

	$post = get_post();
	if ( stripos( $post->post_content, 'wp:setka-editor' ) ) {
		$classes[] = 'has-setka';
	}

	return $classes;
} );

add_action( 'wp_head', function() {
	if ( is_admin() || ! is_singular() ) {
		return;
	}

	?>
	<style>
		body.has-setka header.entry-header {
			display: none;
		}

		body.has-setka #main > figure.post-thumbnail {
			display: none;
		}

		body.has-setka div#content.site-content {
			margin-top: 0;
		}

		body.has-setka div.entry-content > .alignfull {
			margin-top: 0;
		}

		body.has-setka img.stk-reset.stk-image {
			width: 100%;
		}
	</style>
	<?php
} );

// Disable AMP on Setka posts.
add_filter( 'get_post_metadata', function( $value, $object_id, $meta_key ) {
	if ( ! class_exists( 'AMP_Theme_Support' ) || ! class_exists( 'AMP_Post_Meta_Box' ) ) {
		return $value;
	}

	// If wrong meta key, do nothing.
	if ( AMP_Post_Meta_Box::STATUS_POST_META_KEY !== $meta_key ) {
		return $value;
	}

	// If post has setka content, disable AMP on it.
	$content = get_post_field( 'post_content', $object_id );
	if ( false !== stripos( $content, 'setka' ) ) {
		return AMP_Post_Meta_Box::DISABLED_STATUS;
	}

	return $value;

}, 10, 3 );


