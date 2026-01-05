<?php
/**
 * Ping Service - Notify search engines about sitemap updates
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

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
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->indexnow_client   = new NDS_IndexNow_Client();
		$this->analytics         = new NDS_Analytics();
		$this->sitemap_generator = new NDS_Sitemap_Generator();
	}

	/**
	 * Ping search engines when post is published
	 *
	 * @since    1.0.0
	 * @param    int        $post_id    Post ID
	 * @param    WP_Post    $post       Post object
	 */
	public function ping_on_publish( $post_id, $post ) {
		// Skip autosaves, revisions, and non-posts
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( $post->post_type !== 'post' ) {
			return;
		}

		// Check throttle to avoid spamming search engines
		if ( $this->is_throttled() ) {
			$this->schedule_retry_ping( $post_id );
			return;
		}

		// Perform the ping
		$this->ping_all_engines( $post );
	}

	/**
	 * Ping search engines when post is updated
	 *
	 * @since    1.0.0
	 * @param    int        $post_id        Post ID
	 * @param    WP_Post    $post_after     Post object after update
	 * @param    WP_Post    $post_before    Post object before update
	 */
	public function ping_on_update( $post_id, $post_after, $post_before ) {
		// Only ping if post was already published and is still published
		if ( $post_before->post_status !== 'publish' || $post_after->post_status !== 'publish' ) {
			return;
		}

		if ( $post_after->post_type !== 'post' ) {
			return;
		}

		// Check if significant changes were made (title or content changed)
		$content_changed = $post_before->post_content !== $post_after->post_content;
		$title_changed   = $post_before->post_title !== $post_after->post_title;

		if ( $content_changed || $title_changed ) {
			$this->ping_all_engines( $post_after );
		}
	}

	/**
	 * Ping all enabled search engines
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Post object
	 */
	private function ping_all_engines( $post ) {
		$sitemap_url = $this->sitemap_generator->get_sitemap_url();
		$post_url    = get_permalink( $post->ID );

		// Ping Google
		if ( get_option( 'nds_ping_google', true ) ) {
			$this->ping_google( $sitemap_url, $post->ID );
		}

		// Ping Bing
		if ( get_option( 'nds_ping_bing', true ) ) {
			$this->ping_bing( $sitemap_url, $post->ID );
		}

		// Submit to IndexNow
		if ( get_option( 'nds_indexnow_enabled', false ) ) {
			$this->indexnow_client->submit_url( $post_url, $post->ID );
		}

		// Update last ping timestamp
		update_option( 'nds_last_ping_timestamp', time() );

		// Increment total pings counter
		$total_pings = get_option( 'nds_total_pings_sent', 0 );
		update_option( 'nds_total_pings_sent', $total_pings + 1 );
	}

	/**
	 * Ping Google Search Console
	 *
	 * @since    1.0.0
	 * @param    string    $sitemap_url    Sitemap URL
	 * @param    int       $post_id        Post ID for logging
	 * @return   bool                      Success status
	 */
	private function ping_google( $sitemap_url, $post_id ) {
		$ping_url = $this->ping_endpoints['google'] . urlencode( $sitemap_url );

		$response = wp_remote_get(
			$ping_url,
			array(
				'timeout'     => 10,
				'httpversion' => '1.1',
				'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			)
		);

		$success = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;

		// Log the ping
		$this->log_ping( $post_id, 'google', $response, $success );

		// Update analytics
		$this->analytics->record_ping( 'google', $success );

		return $success;
	}

	/**
	 * Ping Bing Webmaster Tools
	 *
	 * @since    1.0.0
	 * @param    string    $sitemap_url    Sitemap URL
	 * @param    int       $post_id        Post ID for logging
	 * @return   bool                      Success status
	 */
	private function ping_bing( $sitemap_url, $post_id ) {
		$ping_url = $this->ping_endpoints['bing'] . urlencode( $sitemap_url );

		$response = wp_remote_get(
			$ping_url,
			array(
				'timeout'     => 10,
				'httpversion' => '1.1',
				'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			)
		);

		$success = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;

		// Log the ping
		$this->log_ping( $post_id, 'bing', $response, $success );

		// Update analytics
		$this->analytics->record_ping( 'bing', $success );

		return $success;
	}

	/**
	 * Check if pinging is currently throttled
	 *
	 * @since    1.0.0
	 * @return   bool    True if throttled
	 */
	private function is_throttled() {
		$throttle_seconds = get_option( 'nds_ping_throttle', 60 );
		$last_ping        = get_option( 'nds_last_ping_timestamp', 0 );

		return ( time() - $last_ping ) < $throttle_seconds;
	}

	/**
	 * Schedule a retry ping for later
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Post ID
	 */
	private function schedule_retry_ping( $post_id ) {
		$throttle_seconds = get_option( 'nds_ping_throttle', 60 );

		if ( ! wp_next_scheduled( 'nds_retry_ping', array( $post_id ) ) ) {
			wp_schedule_single_event(
				time() + $throttle_seconds,
				'nds_retry_ping',
				array( $post_id )
			);
		}
	}

	/**
	 * Retry failed pings (called by cron)
	 *
	 * @since    1.0.0
	 */
	public function retry_failed_pings() {
		global $wpdb;

		$table = $wpdb->prefix . 'nds_sitemap_log';

		// Get failed pings from last 24 hours
		$failed_pings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT post_id
				FROM $table
				WHERE response_code != 200
				AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
				LIMIT 10"
			)
		);

		foreach ( $failed_pings as $row ) {
			$post = get_post( $row->post_id );
			if ( $post && $post->post_status === 'publish' ) {
				$this->ping_all_engines( $post );
			}
		}
	}

	/**
	 * Log ping attempt to database
	 *
	 * @since    1.0.0
	 * @param    int               $post_id          Post ID
	 * @param    string            $search_engine    Search engine name
	 * @param    array|WP_Error    $response         HTTP response
	 * @param    bool              $success          Success status
	 */
	private function log_ping( $post_id, $search_engine, $response, $success ) {
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
				'action'           => 'ping',
				'search_engine'    => $search_engine,
				'response_code'    => $response_code,
				'response_message' => $response_message,
				'timestamp'        => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Manual ping trigger (called from admin)
	 *
	 * @since    1.0.0
	 * @return   array    Results array
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