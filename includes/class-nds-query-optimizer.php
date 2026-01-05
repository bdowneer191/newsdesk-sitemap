<?php
/**
 * Query Optimizer - Improve database performance
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Query_Optimizer {

	/**
	 * Cache for query results
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
		// Add custom indexes on plugin activation (hooked in loader)
		add_action( 'nds_add_indexes', array( $this, 'add_database_indexes' ) );
	}

	/**
	 * Get optimized posts for sitemap
	 *
	 * @since    1.0.0
	 * @param    array    $args    WP_Query arguments
	 * @return   array             Post objects
	 */
	public function get_optimized_posts( $args ) {
		// Generate cache key from args
		$cache_key = 'optimized_posts_' . md5( serialize( $args ) );

		// Check if we have cached results
		if ( isset( $this->query_cache[ $cache_key ] ) ) {
			return $this->query_cache[ $cache_key ];
		}

		// Disable unnecessary features for performance
		$args['no_found_rows']          = true;
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;

		// Execute query
		$query = new WP_Query( $args );
		$posts = $query->posts;

		// If we have posts, batch-load metadata we need
		if ( ! empty( $posts ) ) {
			$posts = $this->batch_load_metadata( $posts );
		}

		// Cache results
		$this->query_cache[ $cache_key ] = $posts;

		return $posts;
	}

	/**
	 * Batch load post metadata
	 *
	 * @since    1.0.0
	 * @param    array    $posts    Array of post objects
	 * @return   array              Posts with metadata
	 */
	private function batch_load_metadata( $posts ) {
		global $wpdb;

		$post_ids = wp_list_pluck( $posts, 'ID' );

		if ( empty( $post_ids ) ) {
			return $posts;
		}

		// Batch query for all needed metadata
		$meta_keys = array(
			'_nds_breaking_news',
			'_nds_genre',
			'_nds_stock_tickers',
		);

		$placeholders      = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$meta_placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

		$query = "SELECT post_id, meta_key, meta_value
			FROM {$wpdb->postmeta}
			WHERE post_id IN ($placeholders)
			AND meta_key IN ($meta_placeholders)";

		$prepared     = $wpdb->prepare( $query, array_merge( $post_ids, $meta_keys ) );
		$meta_results = $wpdb->get_results( $prepared );

		// Organize metadata by post ID
		$metadata = array();
		foreach ( $meta_results as $meta ) {
			if ( ! isset( $metadata[ $meta->post_id ] ) ) {
				$metadata[ $meta->post_id ] = array();
			}
			$metadata[ $meta->post_id ][ $meta->meta_key ] = $meta->meta_value;
		}

		// Attach metadata to posts
		foreach ( $posts as &$post ) {
			$post->_metadata = isset( $metadata[ $post->ID ] ) ? $metadata[ $post->ID ] : array();
		}

		return $posts;
	}

	/**
	 * Add database indexes for better performance
	 *
	 * @since    1.0.0
	 */
	public function add_database_indexes() {
		global $wpdb;

		// Add index on post_date for date_query performance
		$wpdb->query(
			"CREATE INDEX IF NOT EXISTS idx_nds_post_date_status
			ON {$wpdb->posts} (post_date, post_status, post_type)"
		);

		// Add index on postmeta for our custom fields
		$wpdb->query(
			"CREATE INDEX IF NOT EXISTS idx_nds_meta_keys
			ON {$wpdb->postmeta} (meta_key, post_id)"
		);
	}

	/**
	 * Get post count efficiently
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   int               Post count
	 */
	public function get_post_count( $args ) {
		global $wpdb;

		// Build efficient COUNT query
		$time_limit     = get_option( 'nds_time_limit', 48 );
		$date_threshold = date( 'Y-m-d H:i:s', strtotime( "-{$time_limit} hours" ) );

		$excluded_categories = get_option( 'nds_excluded_categories', array() );

		$query = "SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p";

		// Add category exclusion join if needed
		if ( ! empty( $excluded_categories ) ) {
			$query .= " LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
				LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
		}

		$query .= " WHERE p.post_type = 'post'
			AND p.post_status = 'publish'
			AND p.post_date > %s";

		if ( ! empty( $excluded_categories ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $excluded_categories ), '%d' ) );
			$query       .= " AND (tt.term_id NOT IN ($placeholders) OR tt.term_id IS NULL)";
			$prepared     = $wpdb->prepare( $query, array_merge( array( $date_threshold ), $excluded_categories ) );
		} else {
			$prepared = $wpdb->prepare( $query, $date_threshold );
		}

		return (int) $wpdb->get_var( $prepared );
	}

	/**
	 * Prefetch terms for posts
	 *
	 * @since    1.0.0
	 * @param    array    $posts         Post objects
	 * @param    array    $taxonomies    Taxonomies to fetch
	 * @return   array                   Posts with terms
	 */
	public function prefetch_terms( $posts, $taxonomies = array( 'category', 'post_tag' ) ) {
		if ( empty( $posts ) ) {
			return $posts;
		}

		$post_ids = wp_list_pluck( $posts, 'ID' );

		// Use WordPress's built-in function but cache results
		update_object_term_cache( $post_ids, 'post' );

		return $posts;
	}

	/**
	 * Clear query cache
	 *
	 * @since    1.0.0
	 */
	public function clear_cache() {
		$this->query_cache = array();
	}

	/**
	 * Get database query performance stats
	 *
	 * @since    1.0.0
	 * @return   array    Performance statistics
	 */
	public function get_performance_stats() {
		global $wpdb;

		return array(
			'total_queries' => $wpdb->num_queries,
			'query_time'    => timer_stop( 0, 3 ),
			'memory_usage'  => size_format( memory_get_usage( true ) ),
			'memory_peak'   => size_format( memory_get_peak_usage( true ) ),
		);
	}
}