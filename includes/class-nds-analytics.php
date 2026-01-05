<?php
/**
 * Analytics tracking and reporting
 *
 * This class handles the recording and retrieval of sitemap performance metrics,
 * ping statistics, and cache efficiency data.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-4 of Complete Technical Implementation Guide (ANSP_Analytics logic)
 * IMPLEMENTATION: NDS_Analytics with full metric tracking and SQL hardening.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Analytics {

	/**
	 * Database table name for daily aggregated stats
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $table_name;

	/**
	 * Database table name for individual ping logs
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $log_table;

	/**
	 * Initialize the analytics class and set table names
	 *
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'nds_analytics';
		$this->log_table  = $wpdb->prefix . 'nds_sitemap_log';
	}

	/**
	 * Record a ping attempt to the daily aggregated table
	 *
	 *
	 * @since    1.0.0
	 * @param    string    $engine     Search engine name.
	 * @param    bool      $success    Success status of the ping.
	 */
	public function record_ping( $engine, $success ) {
		global $wpdb;

		$today = current_time( 'Y-m-d' );

		// Get current stats for today
		$current = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE date = %s",
				$today
			),
			ARRAY_A
		);

		if ( $current ) {
			// Update existing record for the day
			$wpdb->update(
				$this->table_name,
				array(
					'total_pings'      => (int) $current['total_pings'] + 1,
					'successful_pings' => $success ? (int) $current['successful_pings'] + 1 : (int) $current['successful_pings'],
					'failed_pings'     => $success ? (int) $current['failed_pings'] : (int) $current['failed_pings'] + 1,
				),
				array( 'date' => $today ),
				array( '%d', '%d', '%d' ),
				array( '%s' )
			);
		} else {
			// Insert new record for a new day
			$wpdb->insert(
				$this->table_name,
				array(
					'date'             => $today,
					'total_pings'      => 1,
					'successful_pings' => $success ? 1 : 0,
					'failed_pings'     => $success ? 0 : 1,
				),
				array( '%s', '%d', '%d', '%d' )
			);
		}
	}

	/**
	 * Record cache performance statistics
	 *
	 *
	 * @since    1.0.0
	 * @param    bool    $hit    True for cache hit, false for miss.
	 */
	public function record_cache_stat( $hit ) {
		global $wpdb;

		$today = current_time( 'Y-m-d' );
		$field = $hit ? 'cache_hits' : 'cache_misses';

		/** * Use ON DUPLICATE KEY UPDATE for atomic performance tracking
		 *
		 */
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->table_name} (date, {$field}) 
				 VALUES (%s, 1) 
				 ON DUPLICATE KEY UPDATE {$field} = {$field} + 1",
				$today
			)
		);
	}

	/**
	 * Retrieve aggregated analytics for a specific date range
	 *
	 *
	 * @since    1.0.0
	 */
	public function get_stats( $start_date = null, $end_date = null ) {
		global $wpdb;

		if ( ! $start_date ) {
			$start_date = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				 WHERE date BETWEEN %s AND %s 
				 ORDER BY date ASC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);
	}

	/**
	 * Aggregate ping performance by engine over a period of time
	 *
	 *
	 * @since    1.0.0
	 */
	public function get_ping_stats_by_engine( $days = 30 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT search_engine, 
				 COUNT(*) as total_pings, 
				 SUM(CASE WHEN response_code = 200 THEN 1 ELSE 0 END) as successful, 
				 SUM(CASE WHEN response_code != 200 THEN 1 ELSE 0 END) as failed 
				 FROM {$this->log_table} 
				 WHERE timestamp > DATE_SUB(NOW(), INTERVAL %d DAY) 
				 AND action = 'ping' 
				 GROUP BY search_engine",
				absint( $days )
			),
			ARRAY_A
		);
	}

	/**
	 * Fetch recent detailed log entries with post titles
	 *
	 *
	 * @since    1.0.0
	 */
	public function get_recent_pings( $limit = 50, $engine = null ) {
		global $wpdb;

		$query = "SELECT l.*, p.post_title 
			      FROM {$this->log_table} l 
			      LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID 
			      WHERE l.action = 'ping'";

		if ( $engine ) {
			$query .= $wpdb->prepare( ' AND l.search_engine = %s', sanitize_key( $engine ) );
		}

		$query .= ' ORDER BY l.timestamp DESC';
		$query .= $wpdb->prepare( ' LIMIT %d', absint( $limit ) );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Calculate a 30-day performance summary for the dashboard
	 *
	 *
	 * @since    1.0.0
	 */
	public function get_summary() {
		global $wpdb;
		$summary = array();

		// 30-day Ping count
		$summary['total_pings_30d'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(total_pings) FROM {$this->table_name} 
				 WHERE date > DATE_SUB(CURDATE(), INTERVAL %d DAY)",
				30
			)
		);

		// Success/Failure rates
		$success_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(successful_pings) as successful, SUM(failed_pings) as failed 
				 FROM {$this->table_name} 
				 WHERE date > DATE_SUB(CURDATE(), INTERVAL %d DAY)",
				30
			),
			ARRAY_A
		);

		$total = (int) $success_data['successful'] + (int) $success_data['failed'];
		$summary['success_rate'] = $total > 0 ? round( ( (int) $success_data['successful'] / $total ) * 100, 2 ) : 0;

		// Cache hit efficiency
		$cache_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(cache_hits) as hits, SUM(cache_misses) as misses 
				 FROM {$this->table_name} 
				 WHERE date > DATE_SUB(CURDATE(), INTERVAL %d DAY)",
				30
			),
			ARRAY_A
		);

		$c_total = (int) $cache_data['hits'] + (int) $cache_data['misses'];
		$summary['cache_hit_rate'] = $c_total > 0 ? round( ( (int) $cache_data['hits'] / $c_total ) * 100, 2 ) : 0;

		// Average daily post volume
		$summary['avg_posts_in_sitemap'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(posts_in_sitemap) FROM {$this->table_name} 
				 WHERE date > DATE_SUB(CURDATE(), INTERVAL %d DAY) AND posts_in_sitemap > 0",
				30
			)
		);

		$summary['last_generation']       = get_option( 'nds_last_sitemap_generation', '' );
		$summary['total_pings_lifetime']  = (int) get_option( 'nds_total_pings_sent', 0 );

		return $summary;
	}

	/**
	 * Format data for Chart.js display on the dashboard
	 *
	 *
	 * @since    1.0.0
	 */
	public function get_chart_data( $days = 30 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date, posts_in_sitemap, total_pings, successful_pings, failed_pings 
				 FROM {$this->table_name} 
				 WHERE date > DATE_SUB(CURDATE(), INTERVAL %d DAY) 
				 ORDER BY date ASC",
				absint( $days )
			),
			ARRAY_A
		);

		$chart_data = array(
			'labels'   => array(),
			'datasets' => array(
				array( 'label' => __( 'Posts', 'newsdesk-sitemap' ), 'data' => array(), 'borderColor' => '#4CAF50', 'backgroundColor' => 'rgba(76, 175, 80, 0.1)' ),
				array( 'label' => __( 'Success', 'newsdesk-sitemap' ), 'data' => array(), 'borderColor' => '#2196F3', 'backgroundColor' => 'rgba(33, 150, 243, 0.1)' ),
				array( 'label' => __( 'Failed', 'newsdesk-sitemap' ), 'data' => array(), 'borderColor' => '#F44336', 'backgroundColor' => 'rgba(244, 67, 54, 0.1)' ),
			),
		);

		foreach ( $results as $row ) {
			$chart_data['labels'][]              = date_i18n( 'M j', strtotime( $row['date'] ) );
			$chart_data['datasets'][0]['data'][] = (int) $row['posts_in_sitemap'];
			$chart_data['datasets'][1]['data'][] = (int) $row['successful_pings'];
			$chart_data['datasets'][2]['data'][] = (int) $row['failed_pings'];
		}

		return $chart_data;
	}

	/**
	 * Cleanup old data via WP-Cron to keep the DB lean
	 *
	 *
	 * @since    1.0.0
	 */
	public function cleanup_old_data( $days = 90 ) {
		global $wpdb;
		$limit = absint( $days );

		$del_analytics = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE date < DATE_SUB(CURDATE(), INTERVAL %d DAY)", $limit ) );
		$del_logs      = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->log_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)", $limit ) );

		return (int) $del_analytics + (int) $del_logs;
	}

	/**
	 * Export analytics data to a CSV format
	 *
	 *
	 * @since    1.0.0
	 */
	public function export_csv( $start_date = null, $end_date = null ) {
		$data = $this->get_stats( $start_date, $end_date );
		$csv  = "Date,Posts in Sitemap,Total Pings,Successful Pings,Failed Pings,Cache Hits,Cache Misses\n";

		foreach ( $data as $row ) {
			$csv .= sprintf(
				"%s,%d,%d,%d,%d,%d,%d\n",
				$row['date'],
				$row['posts_in_sitemap'],
				$row['total_pings'],
				$row['successful_pings'],
				$row['failed_pings'],
				$row['cache_hits'],
				$row['cache_misses']
			);
		}

		return $csv;
	}
}