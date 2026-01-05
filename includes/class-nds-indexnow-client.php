<?php
/**
 * IndexNow API Client
 *
 * This class handles the automated submission of news URLs to the IndexNow 
 * protocol, supported by Bing, Yandex, and Seznam for instant crawling.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-2 of Complete Technical Implementation Guide (ANSP_IndexNow_Client logic)
 * IMPLEMENTATION: NDS_IndexNow_Client with verification file management and logging.
 */

// Exit if accessed directly - Security measure
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
	 * API key used for authentication
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $api_key;

	/**
	 * Initialize the class and ensure API key/verification file exist
	 * [cite: 2220-2240]
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->api_key = get_option( 'nds_indexnow_key', '' );

		// Generate API key if it doesn't exist yet
		if ( empty( $this->api_key ) ) {
			$this->api_key = $this->generate_api_key();
			update_option( 'nds_indexnow_key', $this->api_key );
		}

		// Ensure the verification file is present for search engines to crawl
		$this->create_verification_file();
	}

	/**
	 * Submit a single URL to the IndexNow API
	 * [cite: 2245-2275]
	 *
	 * @since    1.0.0
	 * @param    string    $url        The permalink to submit.
	 * @param    int       $post_id    Post ID for logging purposes.
	 * @return   bool                  Success status of the request.
	 */
	public function submit_url( $url, $post_id = 0 ) {
		if ( empty( $this->api_key ) || ! get_option( 'nds_indexnow_enabled', false ) ) {
			return false;
		}

		$body = array(
			'host'    => wp_parse_url( home_url(), PHP_URL_HOST ),
			'key'     => $this->api_key,
			'urlList' => array( esc_url_raw( $url ) ),
		);

		$response = wp_remote_post(
			$this->api_endpoint,
			array(
				'headers'     => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'        => wp_json_encode( $body ),
				'timeout'     => 15,
				'data_format' => 'body',
			)
		);

		$response_code = wp_remote_retrieve_response_code( $response );
		$success       = ( 200 === (int) $response_code );

		// Log the submission result to the database
		$this->log_submission( $post_id, $url, $response, $success );

		return $success;
	}

	/**
	 * Submit multiple URLs to IndexNow in a single batch request
	 * [cite: 2280-2305]
	 *
	 * @since    1.0.0
	 * @param    array    $urls    Array of strings containing full URLs.
	 * @return   bool              Success status.
	 */
	public function submit_urls_batch( $urls ) {
		if ( empty( $this->api_key ) || empty( $urls ) || ! get_option( 'nds_indexnow_enabled', false ) ) {
			return false;
		}

		// Protocol limit is 10,000 URLs per request
		$urls = array_slice( $urls, 0, 10000 );

		$body = array(
			'host'    => wp_parse_url( home_url(), PHP_URL_HOST ),
			'key'     => $this->api_key,
			'urlList' => array_map( 'esc_url_raw', $urls ),
		);

		$response = wp_remote_post(
			$this->api_endpoint,
			array(
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => wp_json_encode( $body ),
				'timeout'     => 30,
				'data_format' => 'body',
			)
		);

		return ( 200 === (int) wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Generate a unique API key following IndexNow standards
	 *
	 * @since    1.0.0
	 * @return   string    8-128 character alphanumeric key.
	 */
	private function generate_api_key() {
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Create the required verification TXT file in the site root or uploads
	 * [cite: 2310-2330]
	 *
	 * @since    1.0.0
	 */
	private function create_verification_file() {
		if ( empty( $this->api_key ) ) {
			return;
		}

		$filename = $this->api_key . '.txt';

		// Strategy 1: Attempt to write to ABSPATH (Site Root)
		$root_path = ABSPATH . $filename;
		if ( ! file_exists( $root_path ) && is_writable( ABSPATH ) ) {
			file_put_contents( $root_path, $this->api_key );
		}

		// Strategy 2: Fallback to Uploads directory
		$upload_dir = wp_upload_dir();
		$upload_path = $upload_dir['basedir'] . '/' . $filename;
		if ( ! file_exists( $upload_path ) ) {
			file_put_contents( $upload_path, $this->api_key );
		}
	}

	/**
	 * Log the IndexNow API response to our custom log table
	 * [cite: 2335-2350]
	 *
	 * @since    1.0.0
	 */
	private function log_submission( $post_id, $url, $response, $success ) {
		global $wpdb;

		$table = $wpdb->prefix . 'nds_sitemap_log';

		if ( is_wp_error( $response ) ) {
			$code    = 0;
			$message = $response->get_error_message();
		} else {
			$code    = (int) wp_remote_retrieve_response_code( $response );
			$message = wp_remote_retrieve_response_message( $response );
		}

		$wpdb->insert(
			$table,
			array(
				'post_id'          => absint( $post_id ),
				'action'           => 'indexnow',
				'search_engine'    => 'indexnow',
				'response_code'    => $code,
				'response_message' => sanitize_text_field( $message . ' | URL: ' . $url ),
				'timestamp'        => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Retrieve current API key
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Regenerate the API key and update verification files
	 *
	 * @since    1.0.0
	 */
	public function regenerate_api_key() {
		// Cleanup old file
		if ( ! empty( $this->api_key ) ) {
			$old_file = ABSPATH . $this->api_key . '.txt';
			if ( file_exists( $old_file ) ) {
				@unlink( $old_file );
			}
		}

		$this->api_key = $this->generate_api_key();
		update_option( 'nds_indexnow_key', $this->api_key );
		$this->create_verification_file();

		return $this->api_key;
	}
}