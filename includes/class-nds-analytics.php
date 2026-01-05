<?php
/**
 * Analytics tracking and reporting
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Analytics {

	/**
	 * Database table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $table_name;

	/**
	 * Log table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $log_table;

	/**
	 * Initialize the analytics class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'nds_analytics';
		$this->log_table  = $wpdb->prefix . 'nds_sitemap_log';
	}

	/**
	 * Record a ping attempt
	 *
	 * @since    1.0.0
	 * @param    string    $engine     Search engine name
	 * @param    bool      $success    Success status
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
			// Update existing record
			$wpdb->update(
				$this->table_name,
				array(
					'total_pings'      => $current['total_pings'] + 1,
					'successful_pings' => $success ? $current['successful_pings'] + 1 : $current['successful_pings'],
					'failed_pings'     => $success ? $current['failed_pings'] : $current['failed_pings'] + 1,
				),
				array( 'date' => $today ),
				array( '%d', '%d', '%d' ),
				array( '%s' )
			);
		} else {
			// Insert new record
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
	 * Record cache hit or miss
	 *
	 * @since    1.0.0
	 * @param    bool    $hit    True for cache hit, false for miss
	 */
	public function record_cache_stat( $hit ) {
		global $wpdb;

		$today = current_time( 'Y-m-d' );
		$field = $hit ? 'cache_hits' : 'cache_misses';

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
	 * Get analytics for date range
	 *
	 * @since    1.0.0
	 * @param    string    $start_date    Start date (Y-m-d)
	 * @param    string    $end_date      End date (Y-m-d)
	 * @return   array                    Analytics data
	 */
	public function get_stats( $start_date = null, $end_date = null ) {
		global $wpdb;

		// Default to last 30 days
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
		}
		if ( ! $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}
				WHERE date BETWEEN %s AND %s
				ORDER BY date ASC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Get ping statistics by search engine
	 *
	 * @since    1.0.0
	 * @param    int      $days    Number of days to look back
	 * @return   array             Stats by engine
	 */
	public function get_ping_stats_by_engine( $days = 30 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					search_engine,
					COUNT(*) as total_pings,
					SUM(CASE WHEN response_code = 200 THEN 1 ELSE 0 END) as successful,
					SUM(CASE WHEN response_code != 200 THEN 1 ELSE 0 END) as failed
				FROM {$this->log_table}
				WHERE timestamp > DATE_SUB(NOW(), INTERVAL %d DAY)
				AND action = 'ping'
				GROUP BY search_engine",
				$days
			),
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Get recent ping log entries
	 *
	 * @since    1.0.0
	 * @param    int       $limit     Number of entries to retrieve
	 * @param    string    $engine    Filter by search engine (optional)
	 * @return   array                Log entries
	 */
	public function get_recent_pings( $limit = 50, $engine = null ) {
		global $wpdb;

		$query = "SELECT l.*, p.post_title
			FROM {$this->log_table} l
			LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID
			WHERE l.action = 'ping'";

		if ( $engine ) {
			$query .= $wpdb->prepare( ' AND l.search_engine = %s', $engine );
		}

		$query .= ' ORDER BY l.timestamp DESC';
		$query .= $wpdb->prepare( ' LIMIT %d', $limit );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get summary statistics
	 *
	 * @since    1.0.0
	 * @return   array    Summary stats
	 */
	public function get_summary() {
		global $wpdb;

		$summary = array();

		// Total pings in last 30 days
		$summary['total_pings_30d'] = (int) $wpdb->get_var(
			"SELECT SUM(total_pings) FROM {$this->table_name}
			WHERE date > DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
		);

		// Success rate in last 30 days
		$success_data = $wpdb->get_row(
			"SELECT
				SUM(successful_pings) as successful,
				SUM(failed_pings) as failed
			FROM {$this->table_name}
			WHERE date > DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
			ARRAY_A
		);

		$total                   = $success_data['successful'] + $success_data['failed'];
		$summary['success_rate'] = $total > 0
			? round( ( $success_data['successful'] / $total ) * 100, 2 )
			: 0;

		// Cache performance
		$cache_data = $wpdb->get_row(
			"SELECT
				SUM(cache_hits) as hits,
				SUM(cache_misses) as misses
			FROM {$this->table_name}
			WHERE date > DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
			ARRAY_A
		);

		$cache_total              = $cache_data['hits'] + $cache_data['misses'];
		$summary['cache_hit_rate'] = $cache_total > 0
			? round( ( $cache_data['hits'] / $cache_total ) * 100, 2 )
			: 0;

		// Average posts in sitemap
		$summary['avg_posts_in_sitemap'] = (int) $wpdb->get_var(
			"SELECT AVG(posts_in_sitemap) FROM {$this->table_name}
			WHERE date > DATE_SUB(CURDATE(), INTERVAL 30 DAY)
			AND posts_in_sitemap > 0"
		);

		// Last sitemap generation
		$summary['last_generation'] = get_option( 'nds_last_sitemap_generation', '' );

		// Total lifetime pings
		$summary['total_pings_lifetime'] = get_option( 'nds_total_pings_sent', 0 );

		return $summary;
	}

	/**
	 * Get chart data for dashboard
	 *
	 * @since    1.0.0
	 * @param    int      $days    Number of days
	 * @return   array             Chart data
	 */
	public function get_chart_data( $days = 30 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					date,
					posts_in_sitemap,
					total_pings,
					successful_pings,
					failed_pings
				FROM {$this->table_name}
				WHERE date > DATE_SUB(CURDATE(), INTERVAL %d DAY)
				ORDER BY date ASC",
				$days
			),
			ARRAY_A
		);

		// Format for Chart.js
		$chart_data = array(
			'labels'   => array(),
			'datasets' => array(
				array(
					'label'           => 'Posts in Sitemap',
					'data'            => array(),
					'borderColor'     => '#4CAF50',
					'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
				),
				array(
					'label'           => 'Successful Pings',
					'data'            => array(),
					'borderColor'     => '#2196F3',
					'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
				),
				array(
					'label'           => 'Failed Pings',
					'data'            => array(),
					'borderColor'     => '#F44336',
					'backgroundColor' => 'rgba(244, 67, 54, 0.1)',
				),
			),
		);

		foreach ( $results as $row ) {
			$chart_data['labels'][]              = date( 'M j', strtotime( $row['date'] ) );
			$chart_data['datasets'][0]['data'][] = (int) $row['posts_in_sitemap'];
			$chart_data['datasets'][1]['data'][] = (int) $row['successful_pings'];
			$chart_data['datasets'][2]['data'][] = (int) $row['failed_pings'];
		}

		return $chart_data;
	}

	/**
	 * Clean old analytics data
	 *
	 * @since    1.0.0
	 * @param    int    $days    Keep data for this many days
	 * @return   int             Number of rows deleted
	 */
	public function cleanup_old_data( $days = 90 ) {
		global $wpdb;

		// Delete analytics older than specified days
		$deleted_analytics = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name}
				WHERE date < DATE_SUB(CURDATE(), INTERVAL %d DAY)",
				$days
			)
		);

		// Delete old log entries
		$deleted_logs = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->log_table}
				WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		return $deleted_analytics + $deleted_logs;
	}
}