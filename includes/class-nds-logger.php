<?php
/**
 * Logger class for debugging
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Logger {

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private static $log_file;

	/**
	 * Initialize logger
	 */
	public static function init() {
		self::$log_file = WP_CONTENT_DIR . '/uploads/nds-debug.log';
	}

	/**
	 * Log a message
	 *
	 * @param string $message Message to log
	 * @param string $level   Level (INFO, PROCESSED, ERROR)
	 */
	public static function log( $message, $level = 'INFO' ) {
		if ( ! self::$log_file ) {
			self::init();
		}

		$timestamp = current_time( 'Y-m-d H:i:s' );
		$formatted_message = sprintf( "[%s] [%s] %s\n", $timestamp, $level, $message );

		// Append to log file
		// Use error_log if file write fails, or just standard error_log as fallback
		if ( ! file_put_contents( self::$log_file, $formatted_message, FILE_APPEND ) ) {
			error_log( 'NDS_Logger Failure: ' . $message );
		}
	}

	/**
	 * Log an error
	 *
	 * @param string $message Error message
	 */
	public static function error( $message ) {
		self::log( $message, 'ERROR' );
	}
}
