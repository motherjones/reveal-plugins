<?php
/**
 * Newspack Manager Mail
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager Mail Class.
 */
final class Mail {
	/**
	 * The single instance of the class.
	 *
	 * @var Mail
	 */
	protected static $instance = null;

	/**
	 * Main Newspack Mail Server Instance.
	 * Ensures only one instance of Newspack Mail Server Instance is loaded or can be loaded.
	 *
	 * @return Mail - Instance.
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
		add_action( 'wp_mail_failed', [ __CLASS__, 'handle_failed_email' ] );
	}

	/**
	 * Monitor a failed email.
	 *
	 * @param WP_Error $error The error object.
	 */
	public static function handle_failed_email( $error ) {
		$message = $error->get_error_message();
		/**
		 * Store "sender blocked" errors.
		 */
		if ( false !== strpos( $message, 'sender blocked' ) ) {
			preg_match( '/\<.*?\>/', $message, $email_match );
			$email_address = isset( $email_match[0] ) ? str_replace( [ '<', '>' ], '', $email_match[0] ) : '';
			preg_match( '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $message, $expire_match );
			$expire = end( $expire_match );
			if ( empty( $email_address ) || empty( $expire ) ) {
				return;
			}
			$addresses                   = get_option( 'newspack_manager_email_blocked_until', [] );
			$addresses[ $email_address ] = strtotime( $expire );
			update_option( 'newspack_manager_email_blocked_until', $addresses );
		}
	}

	/**
	 * Get email addreses that are blocked from sending emails.
	 *
	 * @return int[] Associative array of email addresses and expiration timestamp.
	 */
	public static function get_blocked_addresses() {
		$emails = get_option( 'newspack_manager_email_blocked_until', [] );
		$now    = time();
		foreach ( $emails as $email => $expire ) {
			if ( $now > $expire ) {
				unset( $emails[ $email ] );
			}
		}
		update_option( 'newspack_manager_email_blocked_until', $emails );
		return $emails;
	}
}
Mail::instance();

