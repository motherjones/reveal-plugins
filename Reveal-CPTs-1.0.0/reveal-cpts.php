<?php
/**
 * Plugin Name: Reveal CPTs
 * Description: Custom post types from old Reveal site.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://newspack.blog/
 * License: GPL2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register post types.
 */
add_action( 'init', function() {
	$post_types = [
		'article_legacy'    => 'Legacy Article',
		'content_type_blog' => 'Blog',
		'press_release'     => 'Press Release',
	];

	$rewrite = [
		'article_legacy'    => 'article-legacy',
		'content_type_blog' => 'blog',
		'press_release'     => 'press',
	];

	foreach ( $post_types as $slug => $name ) {
		$labels = array(
			'name'                  => $name . 's',
			'singular_name'         => $name,
			'menu_name'             => $name . 's',
			'name_admin_bar'        => $name,
			'add_new'               => 'Add New',
			'add_new_item'          => 'Add New ' . $name,
			'new_item'              => 'New ' . $name,
			'edit_item'             => 'Edit ' . $name,
			'view_item'             => 'View ' . $name . 's',
			'all_items'             => 'All ' . $name . 's',
			'search_items'          => 'Search ' . $name . 's',
			'parent_item_colon'     => 'Parent ' . $name . 's:',
			'not_found'             => 'No ' . $name . 's found',
			'not_found_in_trash'    => 'No ' . $name . 's found in Trash.',
			'archives'              => $name . ' archives',
			'insert_into_item'      => 'Insert into ' . $name,
			'uploaded_to_this_item' => 'Uploaded to this ' . $name,
			'filter_items_list'     => 'Filter ' . $name . ' list',
			'items_list_navigation' => $name . ' list navigation',
			'items_list'            => $name . ' list',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $rewrite[ $slug ] ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'newspack_blocks' ),
			'taxonomies'         => array('post_tag','category'),
		);

		register_post_type( $slug, $args );
	}
} );

/**
 * Add featured image options to post types.
 */
add_filter( 'newspack_get_featured_image_post_types', function( $post_types ) {
	$post_types[] = 'article_legacy';
	$post_types[] = 'content_type_blog';
	$post_types[] = 'press_release';

	return $post_types;
} );

/**
 * Add Newspack Campaigns support to post types
 */
add_filter( 'newspack_campaigns_post_types_for_campaigns', function( $post_types ) {
	$post_types[] = 'article_legacy';
	$post_types[] = 'content_type_blog';
	$post_types[] = 'press_release';

	return $post_types;
} );

/**
 * Add templates.
 */
function reveal_cpt_templates( $templates ) {
	if ( ! isset( $templates['single-feature.php'] ) ) {
		$templates['single-feature.php'] = 'One column';
	}

	if ( ! isset( $templates['single-wide.php'] ) ) {
		$templates['single-wide.php'] = 'One column wide';
	}

	return $templates;
}
add_filter( 'theme_article_legacy_templates', 'reveal_cpt_templates' );
add_filter( 'theme_content_type_blog_templates', 'reveal_cpt_templates' );
add_filter( 'theme_press_release_templates', 'reveal_cpt_templates' );

/**
 * Add body class so styling gets applied.
 */
add_filter( 'body_class', function( $classes ) {
	if ( in_array( 'article_legacy-template-single-wide', $classes ) || in_array( 'content_type_blog-template-single-wide', $classes ) || in_array( 'press_release-template-single-wide', $classes ) ) {
		$classes[] = 'post-template-single-wide';
	}

	if ( in_array( 'article_legacy-template-single-feature', $classes ) || in_array( 'content_type_blog-template-single-feature', $classes ) || in_array( 'press_release-template-single-feature', $classes ) ) {
		$classes[] = 'post-template-single-feature';
	}

	return $classes;
} );


/**
 * Make all the CPTs show up in archives.
 */
add_filter( 'pre_get_posts', function( $query ) {
	if ( ! $query->is_archive() ) {
		return;
	}

	$post_type = $query->get( 'post_type', '' );
	$updated_post_type = $post_type;
	if ( empty( $post_type ) || 'post' === $post_type ) {
		$updated_post_type = [ 'article_legacy', 'content_type_blog', 'press_release', 'post' ];
	} elseif ( is_array( $post_type ) && in_array( 'post', $post_type ) ) {
		$updated_post_type[] = 'article_legacy';
		$updated_post_type[] = 'content_type_blog';
		$updated_post_type[] = 'press_release';
	}

	if ( $updated_post_type !== $post_type ) {
		$query->set( 'post_type', $updated_post_type );
	}
} );

/**
 * Add CPTs to Jetpack sitemaps.
 */
function reaveal_add_cpts_to_sitemap( $post_types ) {
	$post_types[] = 'article_legacy';
	$post_types[] = 'content_type_blog';
	$post_types[] = 'press_release';
	return $post_types;
}
add_filter( 'jetpack_sitemap_post_types', 'reaveal_add_cpts_to_sitemap' );
add_filter( 'jetpack_sitemap_news_sitemap_post_types', 'reaveal_add_cpts_to_sitemap' ); 

function jetpackcom_add_myposttype_support_copy_post( $post_types ) {
    $post_types[] = 'press_release';
    return $post_types;
}
add_filter( 'jetpack_copy_post_post_types', 'jetpackcom_add_myposttype_support_copy_post' );

