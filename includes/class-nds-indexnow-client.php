<?php
/**
 * IndexNow API Client
 *
 * Submits URLs to IndexNow-supporting search engines
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_IndexNow_Client {

	/**
	 * IndexNow API endpoint
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $api_endpoint = 'https://api.indexnow.org/indexnow';

	/**
	 * API key
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $api_key;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->api_key = get_option( 'nds_indexnow_key', '' );

		// Generate API key if not exists
		if ( empty( $this->api_key ) ) {
			$this->api_key = $this->generate_api_key();
			update_option( 'nds_indexnow_key', $this->api_key );
		}

		// Create verification file
		$this->create_verification_file();
	}

	/**
	 * Submit single URL to IndexNow
	 *
	 * @since    1.0.0
	 * @param    string    $url        URL to submit
	 * @param    int       $post_id    Post ID for logging
	 * @return   bool                  Success status
	 */
	public function submit_url( $url, $post_id = 0 ) {
		if ( empty( $this->api_key ) ) {
			return false;
		}

		$body = array(
			'host'    => parse_url( home_url(), PHP_URL_HOST ),
			'key'     => $this->api_key,
			'urlList' => array( $url ),
		);

		$response = wp_remote_post(
			$this->api_endpoint,
			array(
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
				'body'        => wp_json_encode( $body ),
				'timeout'     => 10,
				'data_format' => 'body',
			)
		);

		$success = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;

		// Log the submission
		$this->log_submission( $post_id, $url, $response, $success );

		return $success;
	}

	/**
	 * Submit multiple URLs to IndexNow (batch)
	 *
	 * @since    1.0.0
	 * @param    array    $urls    Array of URLs
	 * @return   bool              Success status
	 */
	public function submit_urls_batch( $urls ) {
		if ( empty( $this->api_key ) || empty( $urls ) ) {
			return false;
		}

		// IndexNow supports up to 10,000 URLs per request
		$urls = array_slice( $urls, 0, 10000 );

		$body = array(
			'host'    => parse_url( home_url(), PHP_URL_HOST ),
			'key'     => $this->api_key,
			'urlList' => $urls,
		);

		$response = wp_remote_post(
			$this->api_endpoint,
			array(
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
				'body'        => wp_json_encode( $body ),
				'timeout'     => 30,
				'data_format' => 'body',
			)
		);

		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Generate unique API key
	 *
	 * @since    1.0.0
	 * @return   string    API key
	 */
	private function generate_api_key() {
		return md5( uniqid( home_url(), true ) );
	}

	/**
	 * Create verification file in site root
	 *
	 * IndexNow requires a file named {apikey}.txt in site root
	 * containing the API key
	 *
	 * @since    1.0.0
	 */
	private function create_verification_file() {
		if ( empty( $this->api_key ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['basedir'] . '/' . $this->api_key . '.txt';

		// Create file if doesn't exist
		if ( ! file_exists( $file_path ) ) {
			file_put_contents( $file_path, $this->api_key );
		}

		// Also create in root if possible (better for verification)
		$root_file = ABSPATH . $this->api_key . '.txt';
		if ( ! file_exists( $root_file ) && is_writable( ABSPATH ) ) {
			file_put_contents( $root_file, $this->api_key );
		}
	}

	/**
	 * Log IndexNow submission
	 *
	 * @since    1.0.0
	 * @param    int               $post_id     Post ID
	 * @param    string            $url         Submitted URL
	 * @param    array|WP_Error    $response    HTTP response
	 * @param    bool              $success     Success status
	 */
	private function log_submission( $post_id, $url, $response, $success ) {
		global $wpdb;

		$table = $wpdb->prefix . 'nds_sitemap_log';

		$response_code = is_wp_error( $response )
			? 0
			: wp_remote_retrieve_response_code( $response );

		$response_message = is_wp_error( $response )
			? $response->get_error_message()
			: wp_remote_retrieve_response_message( $response );

		$wpdb->insert(
			$table,
			array(
				'post_id'          => $post_id,
				'action'           => 'indexnow',
				'search_engine'    => 'indexnow',
				'response_code'    => $response_code,
				'response_message' => $response_message . ' | URL: ' . $url,
				'timestamp'        => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Get API key (for display in settings)
	 *
	 * @since    1.0.0
	 * @return   string    API key
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Regenerate API key
	 *
	 * @since    1.0.0
	 * @return   string    New API key
	 */
	public function regenerate_api_key() {
		// Delete old verification file
		if ( ! empty( $this->api_key ) ) {
			$old_file = ABSPATH . $this->api_key . '.txt';
			if ( file_exists( $old_file ) ) {
				unlink( $old_file );
			}
		}

		// Generate new key
		$this->api_key = $this->generate_api_key();
		update_option( 'nds_indexnow_key', $this->api_key );

		// Create new verification file
		$this->create_verification_file();

		return $this->api_key;
	}
}