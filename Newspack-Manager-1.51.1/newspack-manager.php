<?php
/**
 * Plugin Name:     Newspack Manager
 * Plugin URI:      https://newspack.pub
 * Description:     Site management API for Newspack team.
 * Author:          Automattic
 * Author URI:      https://newspack.pub
 * Text Domain:     newspack-manager
 * Domain Path:     /languages
 * Version:         1.51.1
 *
 * @package         Newspack_Manager
 */

defined( 'ABSPATH' ) || exit;

// Define NEWSPACK_MANAGER_FILE.
if ( ! defined( 'NEWSPACK_MANAGER_FILE' ) ) {
	define( 'NEWSPACK_MANAGER_FILE', __FILE__ );
}

if ( ! defined( 'NEWSPACK_MANAGER_API_KEY_OPTION_NAME' ) ) {
	define( 'NEWSPACK_MANAGER_API_KEY_OPTION_NAME', 'newspack_manager_api_key' );
}

if ( ! defined( 'NEWSPACK_MANAGER_REST_BASE' ) ) {
	define( 'NEWSPACK_MANAGER_REST_BASE', 'newspack-manager/v1' );
}

if ( ! defined( 'NEWSPACK_MANAGER_ADMIN_USERNAME' ) ) {
	define( 'NEWSPACK_MANAGER_ADMIN_USERNAME', 'adminnewspack' );
}

// Include the plugin files.
if ( ! class_exists( 'Newspack_Manager' ) ) {
	include_once __DIR__ . '/includes/class-newspack-manager.php';
	include_once __DIR__ . '/includes/class-utils.php';
	include_once __DIR__ . '/includes/class-updater.php';
	include_once __DIR__ . '/includes/class-mail.php';
	include_once __DIR__ . '/includes/class-batcache-manager.php';
	include_once __DIR__ . '/includes/class-newspack-plugin-autoupdates.php';
	include_once __DIR__ . '/includes/class-performance.php';
	include_once __DIR__ . '/includes/class-restricted-content.php';
	include_once __DIR__ . '/includes/class-restricted-plugins.php';
	include_once __DIR__ . '/includes/class-service-worker.php';
	include_once __DIR__ . '/includes/class-issues-checker.php';
	include_once __DIR__ . '/includes/class-notifier.php';
	include_once __DIR__ . '/includes/class-gravity-forms.php';
	include_once __DIR__ . '/includes/class-newspack-ga4.php';
	include_once __DIR__ . '/includes/class-data-report.php';
	include_once __DIR__ . '/includes/class-logger.php';
}

