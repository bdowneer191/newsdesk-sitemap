<?php
/**
 * Security utilities
 *
 * This class provides centralized security services including input sanitization,
 * output escaping, nonce verification with fallbacks, and rate limiting.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-3 of Complete Technical Implementation Guide (Security Class Implementation) [cite: 5420]
 * REFINEMENT: NDS prefixing with enhanced nonce compatibility for AJAX handlers.
 */

// Exit if accessed directly - Security measure [cite: 5050, 10383]
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Security {

	/**
	 * Nonce action prefix
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $nonce_prefix = 'nds_nonce_';

	/**
	 * Initialize security class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Constructor is an empty utility initialization. [cite: 5452]
	}

	/**
	 * Verify nonce for admin actions
	 * Checks multiple potential keys for maximum compatibility with various JS frameworks. [cite: 5463-5469]
	 *
	 * @since 1.0.0
	 * @param string $action      Action name without prefix.
	 * @param string $nonce_field Primary nonce field name (default: '_wpnonce').
	 * @return bool               Verification status.
	 */
	public function verify_nonce( $action, $nonce_field = '_wpnonce' ) {
		$nonce = '';

		// Check the provided field, or fallbacks used in common WordPress AJAX implementations
		if ( isset( $_REQUEST[ $nonce_field ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) );
		} elseif ( isset( $_REQUEST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );
		} elseif ( isset( $_REQUEST['security'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['security'] ) );
		}

		if ( empty( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, $this->nonce_prefix . $action );
	}

	/**
	 * Create nonce for admin actions
	 *
	 * @since 1.0.0
	 * @param string $action Action name.
	 * @return string        Nonce value.
	 */
	public function create_nonce( $action ) {
		return wp_create_nonce( $this->nonce_prefix . $action ); 
	}

	/**
	 * Generate nonce field HTML for forms
	 *
	 * @since 1.0.0
	 * @param string $action  Action name.
	 * @param string $name    Field name.
	 * @param bool   $referer Include referer field.
	 * @param bool   $echo    Echo or return.
	 * @return string         Nonce field HTML.
	 */
	public function nonce_field( $action, $name = '_wpnonce', $referer = true, $echo = true ) {
		return wp_nonce_field( $this->nonce_prefix . $action, $name, $referer, $echo ); 
	}

	/**
	 * Check if user has required capability
	 *
	 * @since 1.0.0
	 * @param string $capability Required capability.
	 * @param bool   $die        Whether to terminate on failure.
	 * @return bool              True if user has capability.
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
	 * Data Sanitization Methods [cite: 5515-5601]
	 */

	public function sanitize_text( $input ) {
		return sanitize_text_field( trim( (string) $input ) ); 
	}

	public function sanitize_textarea( $input ) {
		return sanitize_textarea_field( trim( (string) $input ) ); 
	}

	public function sanitize_url( $url ) {
		return esc_url_raw( (string) $url ); 
	}

	public function sanitize_email( $email ) {
		return sanitize_email( (string) $email ); 
	}

	public function sanitize_int( $value, $default = 0 ) {
		$value = intval( $value ); 
		return ( $value > 0 ) ? $value : $default;
	}

	public function sanitize_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN ); 
	}

	public function sanitize_int_array( $array ) {
		if ( ! is_array( $array ) ) {
			return array();
		}
		return array_map( 'absint', $array ); 
	}

	public function sanitize_text_array( $array ) {
		if ( ! is_array( $array ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $array ); 
	}

	/**
	 * Output Escaping Wrappers [cite: 5603-5641]
	 */

	public function escape_html( $text ) {
		return esc_html( (string) $text ); 
	}

	public function escape_attr( $text ) {
		return esc_attr( (string) $text ); 
	}

	public function escape_js( $text ) {
		return esc_js( (string) $text ); 
	}

	public function escape_url( $url ) {
		return esc_url( (string) $url ); 
	}

	/**
	 * Specialized Validations [cite: 5643-5668]
	 */

	public function validate_api_key( $api_key ) {
		// API keys should be 32 character hex strings (MD5) [cite: 5650]
		return (bool) preg_match( '/^[a-f0-9]{32}$/i', $api_key ); 
	}

	public function validate_sitemap_url( $url ) {
		$url = esc_url_raw( $url ); 
		if ( ! preg_match( '/\.xml$/i', $url ) ) { 
			return false;
		}
		return (bool) filter_var( $url, FILTER_VALIDATE_URL ); 
	}

	/**
	 * Database Security [cite: 5670-5679]
	 */

	public function escape_like( $text ) {
		global $wpdb;
		return $wpdb->esc_like( (string) $text ); 
	}

	/**
	 * Rate limit check for actions to prevent API abuse
	 *
	 * @since 1.0.0
	 * @param string $action       Unique action key.
	 * @param int    $max_attempts Max allowed in window.
	 * @param int    $time_window  Window duration in seconds.
	 * @return bool                True if rate limit exceeded.
	 */
	public function check_rate_limit( $action, $max_attempts = 10, $time_window = 3600 ) {
		$transient_key = 'nds_rate_limit_' . md5( $action . get_current_user_id() ); 
		$attempts      = get_transient( $transient_key ); 

		if ( false === $attempts ) {
			set_transient( $transient_key, 1, $time_window ); 
			return false;
		}

		if ( $attempts >= $max_attempts ) { 
			return true;
		}

		set_transient( $transient_key, (int) $attempts + 1, $time_window ); 
		return false;
	}

	/**
	 * Log security event (Used in debug mode)
	 *
	 * @since 1.0.0
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
	 * Get client IP address sanitized
	 * Supporting CloudFlare and Proxy headers. [cite: 5770-5782]
	 *
	 * @since 1.0.0
	 * @return string IP address.
	 */
	public function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // CloudFlare [cite: 5772]
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$ips = explode( ',', $_SERVER[ $key ] );
				foreach ( $ips as $ip ) {
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