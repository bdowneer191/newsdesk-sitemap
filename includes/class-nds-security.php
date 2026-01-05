<?php
/**
 * Security utilities
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Security {

	/**
	 * Nonce action prefix
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $nonce_prefix = 'nds_nonce_';

	/**
	 * Initialize security class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Constructor can be empty - utility class
	}

	/**
	 * Verify nonce for admin actions
	 *
	 * @since    1.0.0
	 * @param    string    $action         Action name
	 * @param    string    $nonce_field    Nonce field name (default: '_wpnonce')
	 * @return   bool                      Verification status
	 */
	public function verify_nonce( $action, $nonce_field = '_wpnonce' ) {
		$nonce = isset( $_REQUEST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) ) : '';

		if ( empty( $nonce ) ) {
			return false;
		}

		return wp_verify_nonce( $nonce, $this->nonce_prefix . $action ) !== false;
	}

	/**
	 * Create nonce for admin actions
	 *
	 * @since    1.0.0
	 * @param    string    $action    Action name
	 * @return   string               Nonce value
	 */
	public function create_nonce( $action ) {
		return wp_create_nonce( $this->nonce_prefix . $action );
	}

	/**
	 * Generate nonce field HTML
	 *
	 * @since    1.0.0
	 * @param    string    $action     Action name
	 * @param    string    $name       Field name (default: '_wpnonce')
	 * @param    bool      $referer    Include referer field
	 * @param    bool      $echo       Echo or return
	 * @return   string                Nonce field HTML
	 */
	public function nonce_field( $action, $name = '_wpnonce', $referer = true, $echo = true ) {
		return wp_nonce_field( $this->nonce_prefix . $action, $name, $referer, $echo );
	}

	/**
	 * Check if user has required capability
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Required capability
	 * @param    bool      $die           Die on failure
	 * @return   bool                     Has capability
	 */
	public function check_capability( $capability = 'manage_options', $die = true ) {
		if ( ! current_user_can( $capability ) ) {
			if ( $die ) {
				wp_die(
					esc_html__( 'You do not have permission to access this page.', 'newsdesk-sitemap' ),
					esc_html__( 'Permission Denied', 'newsdesk-sitemap' ),
					array( 'response' => 403 )
				);
			}
			return false;
		}

		return true;
	}

	/**
	 * Sanitize text input
	 *
	 * @since    1.0.0
	 * @param    string    $input    Input text
	 * @return   string              Sanitized text
	 */
	public function sanitize_text( $input ) {
		return sanitize_text_field( trim( $input ) );
	}

	/**
	 * Sanitize textarea input
	 *
	 * @since    1.0.0
	 * @param    string    $input    Input text
	 * @return   string              Sanitized text
	 */
	public function sanitize_textarea( $input ) {
		return sanitize_textarea_field( trim( $input ) );
	}

	/**
	 * Sanitize URL
	 *
	 * @since    1.0.0
	 * @param    string    $url    URL to sanitize
	 * @return   string            Sanitized URL
	 */
	public function sanitize_url( $url ) {
		return esc_url_raw( $url );
	}

	/**
	 * Sanitize email
	 *
	 * @since    1.0.0
	 * @param    string    $email    Email to sanitize
	 * @return   string              Sanitized email
	 */
	public function sanitize_email( $email ) {
		return sanitize_email( $email );
	}

	/**
	 * Sanitize integer
	 *
	 * @since    1.0.0
	 * @param    mixed    $value      Value to sanitize
	 * @param    int      $default    Default value if invalid
	 * @return   int                  Sanitized integer
	 */
	public function sanitize_int( $value, $default = 0 ) {
		$value = intval( $value );
		return $value > 0 ? $value : $default;
	}

	/**
	 * Sanitize boolean
	 *
	 * @since    1.0.0
	 * @param    mixed    $value    Value to sanitize
	 * @return   bool               Sanitized boolean
	 */
	public function sanitize_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize array of integers
	 *
	 * @since    1.0.0
	 * @param    array    $array    Array to sanitize
	 * @return   array              Sanitized array
	 */
	public function sanitize_int_array( $array ) {
		if ( ! is_array( $array ) ) {
			return array();
		}

		return array_map( 'intval', $array );
	}

	/**
	 * Sanitize array of text values
	 *
	 * @since    1.0.0
	 * @param    array    $array    Array to sanitize
	 * @return   array              Sanitized array
	 */
	public function sanitize_text_array( $array ) {
		if ( ! is_array( $array ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $array );
	}

	/**
	 * Escape HTML for output
	 *
	 * @since    1.0.0
	 * @param    string    $text    Text to escape
	 * @return   string             Escaped text
	 */
	public function escape_html( $text ) {
		return esc_html( $text );
	}

	/**
	 * Escape HTML attribute
	 *
	 * @since    1.0.0
	 * @param    string    $text    Text to escape
	 * @return   string             Escaped text
	 */
	public function escape_attr( $text ) {
		return esc_attr( $text );
	}

	/**
	 * Escape JavaScript
	 *
	 * @since    1.0.0
	 * @param    string    $text    Text to escape
	 * @return   string             Escaped text
	 */
	public function escape_js( $text ) {
		return esc_js( $text );
	}

	/**
	 * Escape URL
	 *
	 * @since    1.0.0
	 * @param    string    $url    URL to escape
	 * @return   string            Escaped URL
	 */
	public function escape_url( $url ) {
		return esc_url( $url );
	}

	/**
	 * Validate API key format
	 *
	 * @since    1.0.0
	 * @param    string    $api_key    API key to validate
	 * @return   bool                  Valid format
	 */
	public function validate_api_key( $api_key ) {
		// API keys should be 32 character hex strings (MD5)
		return preg_match( '/^[a-f0-9]{32}$/i', $api_key ) === 1;
	}

	/**
	 * Prevent SQL injection in LIKE queries
	 *
	 * @since    1.0.0
	 * @param    string    $text    Text for LIKE query
	 * @return   string             Escaped text
	 */
	public function escape_like( $text ) {
		global $wpdb;
		return $wpdb->esc_like( $text );
	}

	/**
	 * Rate limit check for actions
	 *
	 * @since    1.0.0
	 * @param    string    $action          Action identifier
	 * @param    int       $max_attempts    Max attempts allowed
	 * @param    int       $time_window     Time window in seconds
	 * @return   bool                       Rate limit exceeded
	 */
	public function check_rate_limit( $action, $max_attempts = 10, $time_window = 3600 ) {
		$transient_key = 'nds_rate_limit_' . md5( $action . get_current_user_id() );
		$attempts      = get_transient( $transient_key );

		if ( $attempts === false ) {
			// First attempt
			set_transient( $transient_key, 1, $time_window );
			return false;
		}

		if ( $attempts >= $max_attempts ) {
			return true;  // Rate limit exceeded
		}

		// Increment attempt counter
		set_transient( $transient_key, $attempts + 1, $time_window );
		return false;
	}

	/**
	 * Log security event
	 *
	 * @since    1.0.0
	 * @param    string    $event       Event description
	 * @param    string    $severity    Severity level (info, warning, error)
	 * @param    array     $context     Additional context
	 */
	public function log_security_event( $event, $severity = 'info', $context = array() ) {
		if ( ! get_option( 'nds_debug_mode', false ) ) {
			return;
		}

		$log_entry = array(
			'timestamp'  => current_time( 'mysql' ),
			'event'      => $event,
			'severity'   => $severity,
			'user_id'    => get_current_user_id(),
			'ip_address' => $this->get_client_ip(),
			'context'    => $context,
		);

		error_log( 'NDS Security Event: ' . wp_json_encode( $log_entry ) );
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string    IP address
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',  // CloudFlare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
				return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			}
		}

		return '0.0.0.0';
	}
}