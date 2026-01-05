<?php
/**
 * Ping Service - Notify search engines about sitemap updates
 *
 * This class orchestrates the notification process to search engines,
 * combining traditional XML sitemap pings with the modern IndexNow protocol.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-2 of Complete Technical Implementation Guide (ANSP_Ping_Service logic)
 * IMPLEMENTATION: NDS_Ping_Service with throttling, retry logic, and strict SQL hardening.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Ping_Service {

	/**
	 * Search engine ping endpoints
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $ping_endpoints = array(
		'google' => 'https://www.google.com/ping?sitemap=',
		'bing'   => 'https://www.bing.com/ping?sitemap=',
	);

	/**
	 * IndexNow client instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_IndexNow_Client
	 */
	private $indexnow_client;

	/**
	 * Analytics tracker instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_Analytics
	 */
	private $analytics;

	/**
	 * Sitemap generator instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_Sitemap_Generator
	 */
	private $sitemap_generator;

	/**
	 * Initialize the service dependencies
	 * [cite: 2015-2025]
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->indexnow_client   = new NDS_IndexNow_Client();
		$this->analytics         = new NDS_Analytics();
		$this->sitemap_generator = new NDS_Sitemap_Generator();
	}

	/**
	 * Triggered when a post is published
	 * [cite: 2030-2055]
	 *
	 * @since    1.0.0
	 * @param    int        $post_id    Post ID.
	 * @param    WP_Post    $post       Post object.
	 */
	public function ping_on_publish( $post_id, $post ) {
		// Skip autosaves, revisions, and non-post types
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Throttle pings to prevent rate-limiting by search engines
		if ( $this->is_throttled() ) {
			$this->schedule_retry_ping( $post_id );
			return;
		}

		$this->ping_all_engines( $post );
	}

	/**
	 * Triggered when a published post is updated
	 * [cite: 2060-2085]
	 *
	 * @since    1.0.0
	 * @param    int        $post_id        Post ID.
	 * @param    WP_Post    $post_after     Updated post object.
	 * @param    WP_Post    $post_before    Original post object.
	 */
	public function ping_on_update( $post_id, $post_after, $post_before ) {
		// Only ping if both statuses are 'publish' and significant changes were made
		if ( 'publish' !== $post_before->post_status || 'publish' !== $post_after->post_status ) {
			return;
		}

		if ( 'post' !== $post_after->post_type ) {
			return;
		}

		$content_changed = ( $post_before->post_content !== $post_after->post_content );
		$title_changed   = ( $post_before->post_title !== $post_after->post_title );

		if ( $content_changed || $title_changed ) {
			$this->ping_all_engines( $post_after );
		}
	}

	/**
	 * Coordinate the submission to all enabled engines
	 * [cite: 2090-2115]
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    The post to be crawled.
	 */
	private function ping_all_engines( $post ) {
		$sitemap_url = $this->sitemap_generator->get_sitemap_url();
		$post_url    = get_permalink( $post->ID );

		// Standard XML Pings
		if ( get_option( 'nds_ping_google', true ) ) {
			$this->ping_google( $sitemap_url, $post->ID );
		}

		if ( get_option( 'nds_ping_bing', true ) ) {
			$this->ping_bing( $sitemap_url, $post->ID );
		}

		// Modern IndexNow Submission
		if ( get_option( 'nds_indexnow_enabled', false ) ) {
			$this->indexnow_client->submit_url( $post_url, $post->ID );
		}

		// Update global tracking options
		update_option( 'nds_last_ping_timestamp', time() );
		$total_pings = (int) get_option( 'nds_total_pings_sent', 0 );
		update_option( 'nds_total_pings_sent', $total_pings + 1 );
	}

	/**
	 * Ping Google using the traditional sitemap endpoint
	 *
	 * @since    1.0.0
	 */
	private function ping_google( $sitemap_url, $post_id ) {
		$ping_url = $this->ping_endpoints['google'] . rawurlencode( $sitemap_url );

		$response = wp_remote_get(
			esc_url_raw( $ping_url ),
			array(
				'timeout'     => 10,
				'user-agent'  => 'NewsDesk-Sitemap/' . NDS_VERSION . '; ' . home_url(),
			)
		);

		$success = ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) );

		$this->log_ping( $post_id, 'google', $response, $success );
		$this->analytics->record_ping( 'google', $success );

		return $success;
	}

	/**
	 * Ping Bing using the traditional sitemap endpoint
	 *
	 * @since    1.0.0
	 */
	private function ping_bing( $sitemap_url, $post_id ) {
		$ping_url = $this->ping_endpoints['bing'] . rawurlencode( $sitemap_url );

		$response = wp_remote_get(
			esc_url_raw( $ping_url ),
			array(
				'timeout'     => 10,
				'user-agent'  => 'NewsDesk-Sitemap/' . NDS_VERSION . '; ' . home_url(),
			)
		);

		$success = ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) );

		$this->log_ping( $post_id, 'bing', $response, $success );
		$this->analytics->record_ping( 'bing', $success );

		return $success;
	}

	/**
	 * Prevent spamming endpoints by checking the last ping time
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	private function is_throttled() {
		$throttle_limit = (int) get_option( 'nds_ping_throttle', 60 );
		$last_ping      = (int) get_option( 'nds_last_ping_timestamp', 0 );

		return ( time() - $last_ping ) < $throttle_limit;
	}

	/**
	 * Schedule a retry via WP-Cron if throttled or failed
	 *
	 * @since    1.0.0
	 */
	private function schedule_retry_ping( $post_id ) {
		if ( ! wp_next_scheduled( 'nds_retry_ping', array( absint( $post_id ) ) ) ) {
			wp_schedule_single_event( time() + 120, 'nds_retry_ping', array( absint( $post_id ) ) );
		}
	}

	/**
	 * Cron callback to retry failed or throttled pings
	 * [cite: 2135-2155]
	 *
	 * @since    1.0.0
	 */
	public function retry_failed_pings() {
		global $wpdb;
		$table = $wpdb->prefix . 'nds_sitemap_log';

		// Get posts that failed in the last 24 hours
		$failed_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM $table 
				 WHERE response_code != 200 
				 AND timestamp > %s 
				 LIMIT 10",
				gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
			)
		);

		foreach ( $failed_ids as $id ) {
			$post = get_post( $id );
			if ( $post && 'publish' === $post->post_status ) {
				$this->ping_all_engines( $post );
			}
		}
	}

	/**
	 * Log the ping attempt result to the database
	 * [cite: 2160-2180]
	 *
	 * @since    1.0.0
	 */
	private function log_ping( $post_id, $search_engine, $response, $success ) {
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
				'action'           => 'ping',
				'search_engine'    => sanitize_key( $search_engine ),
				'response_code'    => $code,
				'response_message' => sanitize_text_field( $message ),
				'timestamp'        => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Manual ping handler for admin testing
	 *
	 * @since    1.0.0
	 */
	public function manual_ping() {
		$sitemap_url = $this->sitemap_generator->get_sitemap_url();
		$results     = array();

		if ( get_option( 'nds_ping_google', true ) ) {
			$results['google'] = $this->ping_google( $sitemap_url, 0 );
		}

		if ( get_option( 'nds_ping_bing', true ) ) {
			$results['bing'] = $this->ping_bing( $sitemap_url, 0 );
		}

		return $results;
	}
}