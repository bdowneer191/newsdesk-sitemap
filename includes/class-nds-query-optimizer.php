<?php
/**
 * Query Optimizer - Improve database performance
 *
 * This class optimizes WordPress queries for sitemap generation by bypassing
 * unnecessary overhead and utilizing efficient SQL for batch operations.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-2 and Part-3 of Complete Technical Implementation Guide (ANSP_Query_Optimizer logic)
 * IMPLEMENTATION: NDS_Query_Optimizer with no_found_rows and batch metadata loading.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Query_Optimizer {

	/**
	 * Cache for query results during the current request
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $query_cache = array();

	/**
	 * Initialize the optimizer
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Hook for index creation is managed via NDS_Activator
	}

	/**
	 * Get optimized posts for sitemap
	 * * Utilizes no_found_rows to bypass SQL_CALC_FOUND_ROWS for speed.
	 * [cite: 5590-5620]
	 *
	 * @since    1.0.0
	 * @param    array    $args    WP_Query arguments
	 * @return   array             Post objects
	 */
	public function get_optimized_posts( $args ) {
		// Generate cache key from args to prevent duplicate queries in same request
		$cache_key = 'optimized_posts_' . md5( wp_json_encode( $args ) );

		if ( isset( $this->query_cache[ $cache_key ] ) ) {
			return $this->query_cache[ $cache_key ];
		}

		/**
		 * Optimization Flags:
		 * 1. no_found_rows: We don't need total count for pagination here.
		 * 2. update_post_meta_cache: Set to false as we batch load specific keys.
		 * 3. update_post_term_cache: Set to false as we handle terms separately.
		 */
		$args['no_found_rows']          = true;
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;
		$args['ignore_sticky_posts']    = true;

		$query = new WP_Query( $args );
		$posts = $query->posts;

		if ( ! empty( $posts ) ) {
			// Batch-load only the metadata required for the News Sitemap
			$posts = $this->batch_load_metadata( $posts );
		}

		$this->query_cache[ $cache_key ] = $posts;

		return $posts;
	}

	/**
	 * Batch load post metadata in a single query
	 * [cite: 5625-5660]
	 *
	 * @since    1.0.0
	 * @param    array    $posts    Array of post objects
	 * @return   array              Posts with meta-data attached
	 */
	private function batch_load_metadata( $posts ) {
		global $wpdb;

		$post_ids = wp_list_pluck( $posts, 'ID' );
		if ( empty( $post_ids ) ) {
			return $posts;
		}

		// Ensure IDs are sanitized
		$post_ids = array_map( 'absint', $post_ids );

		// Specific keys needed for the sitemap logic
		$meta_keys = array(
			'_nds_breaking_news',
			'_nds_genre',
			'_nds_stock_tickers',
		);

		$id_placeholders   = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$meta_placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

		$query = "SELECT post_id, meta_key, meta_value 
                  FROM {$wpdb->postmeta} 
                  WHERE post_id IN ($id_placeholders) 
                  AND meta_key IN ($meta_placeholders)";

		$prepared     = $wpdb->prepare( $query, array_merge( $post_ids, $meta_keys ) );
		$meta_results = $wpdb->get_results( $prepared );

		$meta_map = array();
		foreach ( $meta_results as $meta ) {
			$meta_map[ $meta->post_id ][ $meta->meta_key ] = $meta->meta_value;
		}

		foreach ( $posts as &$post ) {
			// Attach to a custom property to avoid interference with standard WP properties
			$post->nds_metadata = isset( $meta_map[ $post->ID ] ) ? $meta_map[ $post->ID ] : array();
		}

		return $posts;
	}

	/**
	 * Add database indexes for better sitemap query performance
	 * [cite: 5710-5730]
	 *
	 * @since    1.0.0
	 */
	public function add_database_indexes() {
		global $wpdb;

		// Check and add index for posts table (status and date)
		$index_exists = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->posts} WHERE Key_name = 'idx_nds_sitemap_lookup'" );
		if ( empty( $index_exists ) ) {
			$wpdb->query( "CREATE INDEX idx_nds_sitemap_lookup ON {$wpdb->posts} (post_type, post_status, post_date)" );
		}

		// Check and add index for postmeta table (key and value optimization)
		$meta_index_exists = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_nds_meta_lookup'" );
		if ( empty( $meta_index_exists ) ) {
			$wpdb->query( "CREATE INDEX idx_nds_meta_lookup ON {$wpdb->postmeta} (meta_key(191))" );
		}
	}

	/**
	 * Efficiently count posts within the news time threshold
	 * [cite: 5670-5700]
	 *
	 * @since    1.0.0
	 * @return   int    Number of matching posts
	 */
	public function get_post_count() {
		global $wpdb;

		$time_limit     = (int) get_option( 'nds_time_limit', 48 );
		$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$time_limit} hours" ) );
		$excluded_cats  = get_option( 'nds_excluded_categories', array() );

		$query = "SELECT COUNT(DISTINCT p.ID) 
                  FROM {$wpdb->posts} p";

		if ( ! empty( $excluded_cats ) ) {
			$query .= " LEFT JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
                        LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
		}

		$query .= " WHERE p.post_type = 'post' 
                    AND p.post_status = 'publish' 
                    AND p.post_date > %s";

		if ( ! empty( $excluded_cats ) ) {
			$excluded_cats = array_map( 'absint', $excluded_cats );
			$placeholders  = implode( ',', array_fill( 0, count( $excluded_cats ), '%d' ) );
			$query        .= " AND (tt.term_id NOT IN ($placeholders) OR tt.term_id IS NULL)";
			$prepared      = $wpdb->prepare( $query, array_merge( array( $date_threshold ), $excluded_cats ) );
		} else {
			$prepared = $wpdb->prepare( $query, $date_threshold );
		}

		return (int) $wpdb->get_var( $prepared );
	}

	/**
	 * Prefetch terms for a batch of posts to avoid N+1 query issues
	 *
	 * @since    1.0.0
	 * @param    array    $posts    Array of post objects
	 */
	public function prefetch_terms( $posts ) {
		if ( empty( $posts ) ) {
			return;
		}

		$post_ids = wp_list_pluck( $posts, 'ID' );
		update_object_term_cache( $post_ids, 'post' );
	}

	/**
	 * Get database query performance statistics for the admin dashboard
	 * [cite: 5740-5750]
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_performance_stats() {
		global $wpdb;

		return array(
			'total_queries' => $wpdb->num_queries,
			'execution_time' => timer_stop( 0, 3 ),
			'memory_usage'  => size_format( memory_get_usage( true ) ),
			'memory_peak'   => size_format( memory_get_peak_usage( true ) ),
		);
	}
}