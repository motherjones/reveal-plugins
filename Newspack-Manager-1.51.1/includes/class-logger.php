<?php
/**
 * Newspack Logger
 *
 * @package Newspack
 */

namespace Newspack_Manager;

use Automattic\Jetpack\Connection\Client;

/**
 * Logger class for Newspack Manager
 */
class Logger {

	/**
	 * Creates a new log event
	 *
	 * @param string $code The log code. Like error_colde for errors, or event_code for debug events.
	 * @param string $message The log message.
	 * @param array  $params {
	 *
	 *      Optional. Additional parameters.
	 *
	 *      @type string $type The log type. 'error' or 'debug'.
	 *      @type int    $log_level The log level. Log levels are as follows.
	 *                          1 Normal: Logs only to the local php error log. @see self::local_log for details.
	 *                          2 Watch: Same as Normal but also log to the remote logstash server.
	 *                          3 Alert: Same as watch but will also alert in the newspack alerts slack channel.
	 *                          4 Critical: Same as Watch, but will also send an alert to the main Newspack slack channel.
	 *                          @see self::remote_log for details. This requires a Jetpack connection.
	 *      @type mixed  $data The data to log.
	 *      @type string $user_email The email of the user related to the log entry.
	 *
	 * }
	 *
	 * @return void
	 */
	public static function log( $code, $message, $params = [] ) {

		$defaults = [
			'type'       => 'debug',
			'log_level'  => 1,
			'data'       => [],
			'user_email' => '',
		];

		$params = wp_parse_args( $params, $defaults );

		$type       = $params['type'];
		$log_level  = $params['log_level'];
		$data       = $params['data'];
		$user_email = $params['user_email'];

		$message = 'string' === gettype( $message ) ? $message : wp_json_encode( $message, JSON_PRETTY_PRINT );

		self::local_log( $code, $message, $type, $data );

		if ( 2 > $log_level ) {
			return;
		}

		self::remote_log( $code, $message, $type, $log_level, $data, $user_email );
	}

	/**
	 * Writes log to the local error log.
	 *
	 * Will log only if NEWSPACh_LOG_LEVEL is defined and greater than 0.
	 * If NEWSPACK_LOG_LEVEL is greater than 1, it will also log the caller function.
	 * If NEWSPACK_LOG_LEVEL is greater than 2, it will also log the data.
	 *
	 * @param string $code The log code. Like error_colde for errors, or event_code for debug events.
	 * @param string $message The log message.
	 * @param string $type The log type. 'error' or 'debug'.
	 * @param mixed  $data The data to log.
	 * @return void
	 */
	private static function local_log( $code, $message, $type, $data ) {
		if ( ! defined( 'NEWSPACK_LOG_LEVEL' ) || 0 >= (int) NEWSPACK_LOG_LEVEL ) {
			return;
		}
		$caller = null;

		// Add information about the caller function, if log level is > 1.
		if ( 1 < NEWSPACK_LOG_LEVEL ) {
			try {
				$backtrace = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
				if ( 2 < count( $backtrace ) ) {
					$caller_frame = $backtrace[1];
					if ( stripos( $caller_frame['class'], 'Logger' ) !== false ) {
						// Logger was called by another *Logger class, let's move the backtrace one level up.
						if ( isset( $backtrace[2] ) ) {
							$caller_frame = $backtrace[2];
						}
					}
					$caller = ( $caller_frame['class'] ?? '' ) . ( $caller_frame['type'] ?? '' ) . $caller_frame['function'];
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Fail silently.
			}
		}

		$caller_prefix = $caller ? "[$caller]" : '';
		$type_prefix   = 'info' != $type ? "[$type]" : '';

		$data = '';
		if ( 2 < NEWSPACK_LOG_LEVEL ) {
			$data = ' - Data: ' . wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		error_log( self::get_pid() . '[' . $code . ']' . $caller_prefix . strtoupper( $type_prefix ) . ': ' . $message . $data ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * Get the current process ID and format it to the output in a way that keeps it aligned.
	 *
	 * @return string The process ID surrounded by brackets and followed by spaces to always match at least 7 characters.
	 */
	private static function get_pid() {
		$pid = '[' . getmypid() . ']';
		$len = strlen( $pid );
		while ( $len < 7 ) {
			$pid .= ' ';
			$len++;
		}
		return $pid;
	}

	/**
	 * Sends the log to the remote logstash server.
	 *
	 * @param string $code The log code. error_colde for errors, or event_code for debug events.
	 * @param string $message The log message.
	 * @param string $type The log type. 'error' or 'debug'.
	 * @param int    $log_level The log level. @see self::log for details.
	 * @param mixed  $data The data to log.
	 * @param string $user_email The user email related to the log entry.
	 * @return void
	 */
	private static function remote_log( $code, $message, $type, $log_level, $data, $user_email = '' ) {

		if ( ! class_exists( 'Automattic\Jetpack\Connection\Client' ) ) {
			return;
		}

		$api_params = wp_json_encode(
			array(
				'type'       => $type,
				'error_code' => $code,
				'message'    => $message,
				'log_level'  => $log_level,
				'data'       => wp_json_encode( $data ),
				'url'        => get_bloginfo( 'url' ),
				'user_email' => $user_email,
			)
		);


		Client::wpcom_json_api_request_as_blog(
			'/newspack-manager/log',
			2,
			array(
				'method'   => 'post',
				'headers'  => array( 'content-type' => 'application/json' ),
				'timeout'  => 0.01,
				'blocking' => false,
			),
			$api_params,
			'wpcom'
		);
	}
}

