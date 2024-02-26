<?php
/**
 * Plugin Name:     Newspack Podcasts
 * Plugin URI:      https://newspack.pub
 * Description:     Enhanced podcast support under Newspack
 * Author:          Automattic
 * Author URI:      https://newspack.pub
 * Text Domain:     newspack-podcasts
 * Domain Path:     /languages
 * Version:         1.2.1
 *
 * @package         Newspack_Podcasts
 */

defined( 'ABSPATH' ) || exit;

// Define NEWSPACK_PODCASTS_FILE.
if ( ! defined( 'NEWSPACK_PODCASTS_FILE' ) ) {
	define( 'NEWSPACK_PODCASTS_FILE', plugin_dir_path( __FILE__ ) );
}

// Include the main Newspack Podcasts class.
if ( ! class_exists( 'Newspack_Podcasts' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-newspack-podcasts.php';
}

