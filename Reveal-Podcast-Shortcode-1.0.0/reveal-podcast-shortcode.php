<?php
/**
 * Plugin Name:       Reveal Podcast Shortcode
 * Description:       Handling for legacy [latest_podcast] shortcode.
 * Version:           1.0.0
 * Author:            Automattic
 * Author URI:        https://automattic.com
 * Text Domain:       reveal-podcast-shortcode
 * Domain Path:       /languages
 */


function reveal_podcast_shortcode( $atts ) {
	if ( ! isset( $atts['id'] ) ) {
		return '';
	}

	ob_start();
	?>
<!-- wp:newspack-blocks/homepage-articles {"className":"is-style-borders inline-podcast-module","showReadMore":true,"readMoreLabel":"Listen Now","showDate":false,"showAuthor":false,"postsToShow":1,"mediaPosition":"left","specificPosts":["<?php echo intval( $atts['id'] ); ?>"],"imageScale":2,"specificMode":true,"postType":["post","episodes"]} /-->
	<?php
	$block_markup = ob_get_clean();
	return do_blocks( $block_markup );
}
add_shortcode( 'latest_podcast', 'reveal_podcast_shortcode' );

