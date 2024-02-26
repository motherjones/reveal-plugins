<?php
/**
 * Newspack Manager restricted plugins.
 * Blocks certain plugins from being installed or activated.
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager restricted plugins class.
 */
final class Restricted_Plugins {
	/**
	 * The single instance of the class.
	 *
	 * @var Restricted_Plugins
	 */
	protected static $instance = null;

	/**
	 * Array of blocked plugin slugs.
	 *
	 * @var $blocked_plugins
	 */
	private static $blocked_plugins = [
		// Unpreferred.
		'adthrive-ads', // Raptive Ads.
		'advanced-custom-fields', // Advanced Custom Fields (ACF).
		'broken-link-checker', // Broken Link Checker.
		'featured-images-for-rss-feeds', // Featured Images in RSS for Mailchimp.
		'feedzy-rss-feeds', // RSS Aggregator by Feedzy.
		'hello-dolly', // Hello Dolly.
		'optinmonster', // Popup Builder by OptinMonster.
		'official-facebook-pixel', // Meta pixel for WordPress.
		'reusable-blocks-extended', // Reusable Blocks Extended.
		'wp-user-avatar', // ProfilePress.
		// Accordion alternatives.
		'accordion-box', // Accordion & FAQs.
		'accordion-faq-for-elementor', // Accordion FAQ with Category.
		'accordion-faq-plugin', // Accordion FAQ plugin.
		'accordion-slider-gallery', // Accordion Slider Gallery.
		'easy-accordion-block', // Easy Accordion Gutenberg Block.
		'easy-accordion-free', // Easy Accordion.
		'easy-accordion-menu', // Easy Accordion Menu.
		'gutena-accordion', // Gutena Accordion.
		'helpie-faq', // Helpie WordPress FAQ Accordion.
		'iks-menu', // Iks Menu – WordPress Category Accordion Menu & FAQs.
		'knowledge-center', // Easy Accordion FAQ and Knowledge Base Software .
		'plethora-tabs-accordions', // Plethora Plugins Tabs + Accordions.
		'quick-and-easy-faqs', // Quick and Easy FAQs.
		'responsive-accordion-and-collapse', // Accordion FAQ.
		'responsive-horizontal-vertical-and-accordion-tabs', // WP Responsive Tabs.
		'squelch-tabs-and-accordions-shortcodes', // Squelch Tabs and Accordions Shortcodes.
		'tabbed', // Tab – Accordion, FAQ.
		'tabs', // Tabs & Accordion.
		'wpb-accordion-menu-or-category', // WPB Accordion Menu – Elementor Accordion Menu – WooCommerce Category Accordion.
		// Ads.txt alternatives.
		'adstxt-guru-connect', // ads.txt Guru Connect.
		'ads-txt-admin', // Ads.txt Admin.
		'app-ads-txt', // Ads.txt & App-ads.txt Manager for WordPress.
		'ads-txt-manager', // Ads.txt Manager.
		// AMP.
		'amp', // AMP.
		'accelerated-mobile-pages', // AMP for WP – Accelerated Mobile Pages.
		// Analytics alternatives.
		'analytics-cat', // Analytics Cat.
		'analytics-insights', // Analytics Insights for Google Analytics 4.
		'analytify-analytics-dashboard-widget', // Google Analytics Dashboard Widget by Analytify.
		'beehive-analytics', // Beehive Analytics.
		'bws-google-analytics', // Analytics by BestWebSoft.
		'ga-google-analytics', // GA Google Analytics.
		'google-analytics-dashboard-for-wp', // ExactMetrics.
		'google-analytics-for-wordpress', // MonsterInsights.
		'goolytics-simple-google-analytics', // Goolytics.
		'host-analyticsjs-local', // CAOS.
		'ht-easy-google-analytics', // HT Easy GA4.
		'independent-analytics', // Independent Analytics.
		'lara-google-analytics', // Lara's Google Analytics.
		'no-nonsense-google-analytics', // No-Nonsense Google Analytics.
		'simple-universal-google-analytics', // Simple Universal Google Analytics.
		'wk-google-analytics', // Google Analytics and Google Tag Manager.
		'wp-analytify', // Analytify.
		'wp-google-analytics-events', // WP Google Analytics Events.
		// Author avatar alternatives.
		'author-avatars', // Author Avatars List/Block.
		'authors-list', // Authors List.
		'avatar-manager', // Avatar Manager.
		'basic-user-avatars', // Basic User Avatars.
		'easy-author-avatar-image', // Easy Author Avatar Image.
		'guest-author-name', // (Simply) Guest Author Name.
		'guest-author', // Guest Author.
		'image-and-media-byline-credits', // Image and Media Byline Credits.
		'media-credit', // Media Credit.
		'molongui-authorship', // Author Box, Guest Author and Co-Authors for Your Posts – Molongui.
		'one-user-avatar', // One User Avatar.
		'simple-author-box', // Simple Author Box.
		'user-avatar-reloaded', // User Avatar – Reloaded.
		'wp-custom-avatar', // WP Custom Avatar.
		'wp-post-author', // WP Post Author.
		'wp-user-profile-avatar', // WP User Profile Avatar.
		// Caching.
		'aruba-hispeed-cache', // Aruba HiSpeed Cache.
		'autoptimize', // Autoptimize.
		'breeze', // Breeze – WordPress Cache Plugin.
		'cache-enabler', // Cache Enabler.
		'docket-cache', // Docket Cache – Object Cache Accelerator.
		'hummingbird-performance', // Hummingbird – Optimize Speed, Enable Cache, Minify CSS & Defer Critical JS.
		'litespeed-cache', // LiteSpeed Cache.
		'object-cache-4-everyone', // Object Cache 4 everyone.
		'powered-cache', // Powered Cache – Caching and Optimization for WordPress.
		'redis-cache', // Redis Object Cache.
		'sqlite-object-cache', // SQLite Object Cache.
		'tenweb-speed-optimizer', // 10Web Booster.
		'w3-total-cache', // W3 Total Cache.
		'wp-cloudflare-page-cache', // Super Page Cache for Cloudflare.
		'wp-fastest-cache', // WP Fastest Cache.
		'wp-optimize', // WP-Optimize – Cache, Clean, Compress.
		'wp-rest-cache', // WP REST Cache.
		'wp-super-cache', // WP Super Cache.
		// Complianz alternatives.
		'axeptio-sdk-integration', // Axeptio – GDPR Cookie Consent & Compliance.
		'beautiful-and-responsive-cookie-consent', // Beautiful Cookie Consent Banner.
		'cookie-bar', // Cookie Bar.
		'cookie-consent-box', // GDPR Cookie Consent Notice Box.
		'cookie-law-info', // CookieYes | GDPR Cookie Consent & Compliance Notice (CCPA Ready).
		'cookie-notice', // Cookie Notice & Compliance for GDPR / CCPA.
		'cookiebot', // Cookie banner plugin for WordPress – Cookiebot CMP by Usercentrics.
		'gdpr-compliance-by-supsystic', // GDPR Cookie Consent by Supsystic.
		'gdpr-compliance-cookie-consent', // GDPR Compliance & Cookie Consent.
		'gdpr-cookie-compliance', // GDPR Cookie Compliance (CCPA, DSGVO, Cookie Consent).
		'gdpr-cookie-consent', // WP Cookie Consent (for GDPR, CCPA & ePrivacy).
		'gdpr-cookies-pro', // GDPR Cookies pro.
		'iubenda-cookie-law-solution', // iubenda | All-in-one Compliance for GDPR / CCPA Cookie Consent.
		'real-cookie-banner', // Real Cookie Banner: GDPR (DSGVO) & ePrivacy Cookie Consent.
		'simple-gdpr-cookie-compliance', // Simple GDPR Cookie Compliance.
		'uk-cookie-consent', // GDPR/CCPA Cookie Consent Banner.
		'wp-gdpr-compliance', // Cookie Information | Free GDPR Consent Solution.
		'wp-gdpr-cookie-notice', // WP GDPR Cookie Notice.
		// Donation alternatives.
		'charitable', // Donation Forms by Charitable.
		'donate-button', // Donate by BestWebSoft.
		'donate-me', // Donate Me.
		'donation-block-for-stripe-by-givewp', // Donation Form Block for Stripe.
		'donations-for-woocommerce', // Potent Donations for WooCommerce.
		'donorbox-donation-form', // Donorbox.
		'easy-paypal-donation', // Accept Donations with PayPal.
		'give', // GiveWP.
		'pal-donation-button', // Donation Button For PayPal.
		'paypal-donations', // Donations via PayPal.
		'paytm-donation', // Paytm – Donation Plugin.
		'seamless-donations', // Seamless Donations.
		'wc-donation-platform', // Donation Platform for WooCommerce.
		'woo-donations', // Woo Donations.
		'wp-donate', // WP Donate.
		'wp-fundraising-donation', // FundEngine.
		'wp-stripe-donation', // Accept Stripe Donation and Payments – AidWP.
		// Gravity Forms alternatives.
		'ninja-forms', // Ninja Forms Contact Form.
		// Gutenberg alternatives.
		'beaver-builder-lite-version', // Beaver Builder – WordPress Page Builder.
		'block-pattern-builder', // Block Pattern Builder.
		'classic-editor', // Classic Editor.
		'coming-soon', // Website Builder by SeedProd.
		'elementor', // Elementor Website Builder.
		'header-footer-elementor', // Elementor Header & Footer Builder.
		'visualcomposer', // Visual Composer Website Builder.
		// Header-footer scripts alternatives.
		'add-actions-and-filters', // Add Shortcodes Actions And Filters.
		'cm-header-footer-script-loader', // CM Header & Footer Script Loader.
		'code-snippets', // Code Snippets.
		'custom-script-for-customizer', // Custom Header Footer Scripts for Customizer.
		'header-footer', // Head, Footer and Post Injections.
		'header-footer-code-manager', // Header Footer Code Manager.
		'header-and-footer-scripts', // Header and Footer Scripts.
		'insert-headers-and-footers', // WPCode – Insert Headers and Footers + Custom Code Snippets .
		'insert-headers-and-footers-script', // Insert Headers and Footers Code – HT Script.
		'insert-php', // Woody code snippets.
		'insert-php-code-snippet', // Insert PHP Code Snippet.
		'insert-script-in-headers-and-footers', // Insert Script In Headers And Footers.
		'wp-headers-and-footers', // Insert Headers And Footers.
		// Jetpack alternatives.
		'add-to-any', // AddToAny Share Buttons.
		'copy-delete-posts', // Duplicate Post.
		'duplicate-page', // Duplicate Page.
		'duplicate-post', // Yoast Duplicate Post.
		'duplicate-post-page-menu-custom-post-type', // Duplicate Post Page Menu & Custom Post Type.
		// Jetpack backup alternatives.
		'backup-backup', // BackupBliss – Backup Migration Staging.
		'updraftplus', // UpdraftPlus WordPress Backup & Migration Plugin.
		'wp-database-backup', // WP Database Backup.
		'wp-db-backup', // Database Backup for WordPress.
		// Redirection alternatives.
		'301-redirects', // 301 Redirects & 404 Error Log.
		'404-to-301', // 404 to 301.
		'advanced-301-and-302-redirect', // Advanced 301 and 302 Redirect.
		'all-in-one-redirection', // All In One Redirection.
		'eps-301-redirects', // 301 Redirects – Easy Redirect Manager.
		'lh-page-links-to', // LH Page Links To.
		'pretty-link', // Pretty Links.
		'quick-301-redirects', // Quick 301 Redirects for WordPress.
		'quick-pagepost-redirect-plugin', // Quick Page/Post Redirect Plugin.
		'redirect-redirection', // Redirection.
		'safe-redirect-manager', // Safe Redirect Manager.
		'seo-redirection', // SEO Redirection Plugin – 301 Redirect Manager.
		'simple-301-redirects', // Simple 301 Redirects by BetterLinks.
		'trash-duplicate-and-301-redirect', // Trash Duplicate and 301 Redirect.
		'wp-seo-redirect-301', // WP SEO Redirect 301.
	];

	/**
	 * Main Newspack Restricted_Plugins Server Instance.
	 * Ensures only one instance of Newspack Restricted_Plugins Server Instance is loaded or can be loaded.
	 *
	 * @return Restricted_Content - Instance.
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
		add_filter( 'plugin_action_links', [ $this, 'remove_action_links' ], 10, 3 );
		add_filter( 'plugin_install_action_links', [ $this, 'remove_install_action_links' ], 10, 2 );
		add_filter( 'plugins_api_result', [ $this, 'remove_install_now_button' ], 10, 2 );
	}

	/**
	 * Remove 'Activate' links for blocked plugins.
	 * Note that this will not actually prevent the activation of these plugins.
	 * It will only remove the ability to do so from the WP plugin admin screen.
	 *
	 * @param  array  $actions Array of plugin action links.
	 * @param  string $plugin_file The plugin file.
	 * @param  array  $plugin_data Information about the plugin.
	 * @return array  Modified $actions.
	 */
	public function remove_action_links( $actions, $plugin_file, $plugin_data ) {
		$plugin_slug = isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : $plugin_file;

		if ( in_array( $plugin_slug, self::$blocked_plugins, true ) ) {
			unset( $actions['activate'] );
		}

		return $actions;
	}

	/**
	 * Remove 'Install' and 'Activate' buttons in the Add New plugins screen.
	 * Note that this will not actually prevent the installation or activation of these plugins.
	 * It will only remove the ability to do so from the Add New plugins screen.
	 *
	 * @param array $action_links Array of plugin action links.
	 * @param array $plugin Array of plugin data.
	 *
	 * @return array Filtered array of action links.
	 */
	public function remove_install_action_links( $action_links, $plugin ) {
		if ( isset( $plugin['slug'] ) && in_array( $plugin['slug'], self::$blocked_plugins, true ) ) {
			$action_links[0] = sprintf(
				'<button type="button" class="button button-disabled" disabled="disabled">%s</button>',
				__( 'Plugin not allowed', 'newspack-manager' )
			);
		}

		return $action_links;
	}

	/**
	 * Remove 'Install Now' button from the iframed plugin details page.
	 * 
	 * @param array  $res API response from WP plugins repository.
	 * @param string $action Action describing the request to the plugins repository.
	 * 
	 * @return array Filtered response.
	 */
	public function remove_install_now_button( $res, $action ) {
		if ( 'plugin_information' === $action && isset( $res->slug ) && in_array( $res->slug, self::$blocked_plugins, true ) ) {
			unset( $res->download_link );
		}
		return $res;
	}
}

Restricted_Plugins::instance();

