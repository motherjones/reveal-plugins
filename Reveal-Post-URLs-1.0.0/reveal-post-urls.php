<?php
/**
 * Plugin Name: Reveal Post URLs
 * Description: Adds /article/ to post URLs for backwards-compatibility.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://newspack.blog/
 * License: GPL2
 * Text Domain: reveal-post-urls
 * Domain Path: /languages/
 */

defined( 'ABSPATH' ) || exit;

function reveal_article_urls( $url, $post ) {
	if ( 'post' === $post->post_type && 'publish' === $post->post_status ) {
		return get_site_url( null, '/article/' . $post->post_name );
	}
	return $url;
}
add_filter( 'post_link', 'reveal_article_urls', 10, 2 );

function reveal_article_rewrite_rules() {
    add_rewrite_rule(
        'article/([^\/]+)(?:/page/?([0-9]{1,})|)/?$',
        'index.php?name=$matches[1]&paged=$matches[2]',
        'top' // The rule position; either 'top' or 'bottom' (default).
    );
}
dd_action( 'init', 'reveal_article_rewrite_rules' );
