<?php
/**
 * Security utilities
 *
 * This class provides a centralized system for data sanitization,
 * escaping, nonce verification, and permission checks to ensure
 * compliance with Envato/CodeCanyon and WordPress standards.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-1 of Complete Technical Implementation Guide (Security section)
 * IMPLEMENTATION: NDS_Security with strict sanitization and escaping wrappers.
 */

// Exit if accessed directly - Security measure
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
		// Constructor is an empty utility initialization.
	}

	/**
	 * Verify nonce for admin actions
	 *
	 *
	 * @since    1.0.0
	 * @param    string    $action         Action name without prefix.
	 * @param    string    $nonce_field    Nonce field name in request (default: '_wpnonce').
	 * @return   bool                      Verification status.
	 */
	public function verify_nonce( $action, $nonce_field = '_wpnonce' ) {
		$nonce = isset( $_REQUEST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) ) : '';

		if ( empty( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, $this->nonce_prefix . $action );
	}

	/**
	 * Create nonce for admin actions
	 *
	 * @since    1.0.0
	 * @param    string    $action    Action name.
	 * @return   string               Nonce value.
	 */
	public function create_nonce( $action ) {
		return wp_create_nonce( $this->nonce_prefix . $action );
	}

	/**
	 * Check if user has required capability
	 *
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Required capability.
	 * @param    bool      $die           Whether to exit on failure.
	 * @return   bool                     True if user has capability.
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
	 * @param    string    $input    Input text.
	 * @return   string              Sanitized text.
	 */
	public function sanitize_text( $input ) {
		return sanitize_text_field( trim( (string) $input ) );
	}

	/**
	 * Sanitize textarea input (allows line breaks)
	 *
	 * @since    1.0.0
	 */
	public function sanitize_textarea( $input ) {
		return sanitize_textarea_field( trim( (string) $input ) );
	}

	/**
	 * Sanitize URL for raw database storage or API calls
	 *
	 * @since    1.0.0
	 */
	public function sanitize_url( $url ) {
		return esc_url_raw( (string) $url );
	}

	/**
	 * Sanitize integer values safely
	 *
	 * @since    1.0.0
	 */
	public function sanitize_int( $value, $default = 0 ) {
		$value = (int) $value;
		return ( $value > 0 ) ? $value : $default;
	}

	/**
	 * Sanitize boolean values safely
	 *
	 * @since    1.0.0
	 */
	public function sanitize_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize array of integers (e.g., excluded categories)
	 *
	 * @since    1.0.0
	 */
	public function sanitize_int_array( $array ) {
		if ( ! is_array( $array ) ) {
			return array();
		}
		return array_map( 'absint', $array );
	}

	/**
	 * Escape HTML for safe output
	 *
	 * @since    1.0.0
	 */
	public function escape_html( $text ) {
		return esc_html( (string) $text );
	}

	/**
	 * Escape HTML attribute for safe tag output
	 *
	 * @since    1.0.0
	 */
	public function escape_attr( $text ) {
		return esc_attr( (string) $text );
	}

	/**
	 * Escape URL for safe frontend output
	 *
	 * @since    1.0.0
	 */
	public function escape_url( $url ) {
		return esc_url( (string) $url );
	}

	/**
	 * Validate API key format (specifically for IndexNow)
	 *
	 * @since    1.0.0
	 */
	public function validate_api_key( $api_key ) {
		// Keys are typically 32-character hex strings
		return (bool) preg_match( '/^[a-f0-9]{32,128}$/i', $api_key );
	}

	/**
	 * Prevent SQL injection in LIKE queries
	 *
	 * @since    1.0.0
	 */
	public function escape_like( $text ) {
		global $wpdb;
		return $wpdb->esc_like( (string) $text );
	}

	/**
	 * Rate limit check for actions to prevent abuse
	 *
	 * @since    1.0.0
	 * @param    string    $action          Unique action key.
	 * @param    int       $max_attempts    Max allowed in window.
	 * @param    int       $time_window     Window duration in seconds.
	 * @return   bool                       True if rate limit exceeded.
	 */
	public function check_rate_limit( $action, $max_attempts = 10, $time_window = 3600 ) {
		$transient_key = 'nds_rate_' . md5( $action . get_current_user_id() );
		$attempts      = (int) get_transient( $transient_key );

		if ( 0 === $attempts ) {
			set_transient( $transient_key, 1, $time_window );
			return false;
		}

		if ( $attempts >= $max_attempts ) {
			return true;
		}

		set_transient( $transient_key, $attempts + 1, $time_window );
		return false;
	}

	/**
	 * Get client IP address sanitized
	 *
	 * @since    1.0.0
	 * @return   string    IP address.
	 */
	public function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // CloudFlare support
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						return sanitize_text_field( wp_unslash( $ip ) );
					}
				}
			}
		}

		return '0.0.0.0';
	}
}