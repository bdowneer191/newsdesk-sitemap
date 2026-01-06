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
	 * @access   private
	 * @var      string
	 */
	private $api_endpoint = 'https://api.indexnow.org/indexnow';

	/**
	 * API key
	 *
	 * @access   private
	 * @var      string
	 */
	private $api_key;

	/**
	 * Initialize the class and ensure API key and verification file exist
	 */
	public function __construct() {
		$this->api_key = get_option( 'nds_indexnow_key', '' );

		if ( empty( $this->api_key ) ) {
			$this->api_key = $this->generate_api_key();
			update_option( 'nds_indexnow_key', $this->api_key );
		}

		$this->create_verification_file();
	}

	/**
	 * Submit single URL to IndexNow
	 *
	 * @param    string    $url        URL to submit
	 * @param    int       $post_id    Post ID for logging
	 * @return   bool                  Success status
	 */
	public function submit_url( $url, $post_id = 0 ) {
		if ( empty( $this->api_key ) || ! get_option( 'nds_indexnow_enabled', false ) ) {
			return false;
		}

		$body = array(
			'host'    => parse_url( home_url(), PHP_URL_HOST ),
			'key'     => $this->api_key,
			'urlList' => array( $url ),
		);

		$response = wp_remote_post( $this->api_endpoint, array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $body ),
			'timeout'     => 15,
			'data_format' => 'body',
		) );

		$success = ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) );

		$this->log_submission( $post_id, $url, $response, $success );

		return $success;
	}

	/**
	 * Submit multiple URLs to IndexNow in a batch request (up to 10,000)
	 *
	 * @param    array    $urls    Array of strings containing full URLs
	 * @return   bool              Success status
	 */
	public function submit_urls_batch( $urls ) {
		if ( empty( $this->api_key ) || empty( $urls ) || ! get_option( 'nds_indexnow_enabled', false ) ) {
			return false;
		}

		$urls = array_slice( $urls, 0, 10000 );

		$body = array(
			'host'    => parse_url( home_url(), PHP_URL_HOST ),
			'key'     => $this->api_key,
			'urlList' => $urls,
		);

		$response = wp_remote_post( $this->api_endpoint, array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $body ),
			'timeout'     => 30,
			'data_format' => 'body',
		) );

		return ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Generate a unique API key following IndexNow standards
	 */
	private function generate_api_key() {
		return md5( uniqid( home_url(), true ) );
	}

	/**
	 * Create verification TXT file in site root or uploads as fallback
	 */
	private function create_verification_file() {
		if ( empty( $this->api_key ) ) {
			return;
		}

		$filename = $this->api_key . '.txt';

		// Strategy 1: Attempt to write to ABSPATH (Site Root)
		$root_path = ABSPATH . $filename;
		if ( ! file_exists( $root_path ) && is_writable( ABSPATH ) ) {
			@file_put_contents( $root_path, $this->api_key );
		}

		// Strategy 2: Fallback to Uploads directory
		$upload_dir = wp_upload_dir();
		$upload_path = $upload_dir['basedir'] . '/' . $filename;
		if ( ! file_exists( $upload_path ) ) {
			@file_put_contents( $upload_path, $this->api_key );
		}
	}

	/**
	 * Log the submission result to the custom log table
	 */
	private function log_submission( $post_id, $url, $response, $success ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nds_sitemap_log';

		$code    = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		$message = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_message( $response );

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

	public function get_api_key() {
		return $this->api_key;
	}

	public function regenerate_api_key() {
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