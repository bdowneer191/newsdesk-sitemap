<?php
/**
 * Sitemap Generator - Core XML generation and URL management
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Sitemap_Generator {

	/**
	 * Plugin name identifier
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * Cache manager instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_Cache_Manager    $cache_manager
	 */
	private $cache_manager;

	/**
	 * News schema builder instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_News_Schema    $schema_builder
	 */
	private $schema_builder;

	/**
	 * Query optimizer instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_Query_Optimizer    $query_optimizer
	 */
	private $query_optimizer;

	/**
	 * Validation handler instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_Validation    $validator
	 */
	private $validator;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name     = 'newsdesk-sitemap';
		$this->cache_manager   = new NDS_Cache_Manager();
		$this->schema_builder  = new NDS_News_Schema();
		$this->query_optimizer = new NDS_Query_Optimizer();
		$this->validator       = new NDS_Validation();
	}

	/**
	 * Register custom rewrite rules for sitemap URLs
	 *
	 * Creates endpoints like:
	 * - /news-sitemap.xml (main sitemap)
	 * - /news-sitemap-index.xml (sitemap index if multiple files needed)
	 *
	 * @since    1.0.0
	 */
	public function add_rewrite_rules() {
		$slug = get_option( 'nds_custom_slug', 'news-sitemap' );

		// Main sitemap URL
		add_rewrite_rule(
			'^' . $slug . '\.xml$',
			'index.php?nds_sitemap=1',
			'top'
		);

		// Sitemap index (for sites with >1000 posts)
		add_rewrite_rule(
			'^' . $slug . '-index\.xml$',
			'index.php?nds_sitemap_index=1',
			'top'
		);

		// Paginated sitemaps (news-sitemap-1.xml, news-sitemap-2.xml, etc.)
		add_rewrite_rule(
			'^' . $slug . '-([0-9]+)\.xml$',
			'index.php?nds_sitemap=$matches[1]',
			'top'
		);
	}

	/**
	 * Add custom query variables
	 *
	 * @since    1.0.0
	 * @param    array    $vars    Existing query vars
	 * @return   array             Modified query vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'nds_sitemap';
		$vars[] = 'nds_sitemap_index';
		return $vars;
	}

	/**
	 * Handle sitemap rendering on template_redirect
	 *
	 * This is called by WordPress when someone visits the sitemap URL
	 *
	 * @since    1.0.0
	 */
	public function render_sitemap() {
		global $wp_query;

		// Check if this is a sitemap request
		if ( get_query_var( 'nds_sitemap' ) !== '' || get_query_var( 'nds_sitemap_index' ) !== '' ) {

			// Set headers for XML output
			$this->set_xml_headers();

			// Determine which sitemap to generate
			if ( get_query_var( 'nds_sitemap_index' ) !== '' ) {
				$this->generate_sitemap_index();
			} else {
				$page = get_query_var( 'nds_sitemap' ) ?: 1;
				$this->generate_sitemap( $page );
			}

			exit;
		}
	}

	/**
	 * Set proper HTTP headers for XML sitemap
	 *
	 * @since    1.0.0
	 */
	private function set_xml_headers() {
		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );

		// Cache headers for CDN compatibility
		if ( get_option( 'nds_cdn_compatibility', false ) ) {
			$cache_duration = get_option( 'nds_cache_duration', 1800 );
			header( 'Cache-Control: public, max-age=' . $cache_duration );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $cache_duration ) . ' GMT' );
		}
	}

	/**
	 * Generate sitemap index (for sites with >1000 URLs)
	 *
	 * @since    1.0.0
	 */
	private function generate_sitemap_index() {
		// Check cache first
		$cache_key = 'nds_sitemap_index';
		$cached    = $this->cache_manager->get( $cache_key );

		if ( $cached !== false ) {
			echo $cached;
			return;
		}

		// Get total post count
		$total_posts = $this->get_total_sitemap_posts();
		$max_urls    = get_option( 'nds_max_urls', 1000 );
		$total_pages = ceil( $total_posts / $max_urls );

		// Build sitemap index XML
		$xml = $this->schema_builder->build_sitemap_index( $total_pages );

		// Cache the result
		$this->cache_manager->set( $cache_key, $xml );

		echo $xml;
	}

	/**
	 * Generate main sitemap or paginated sitemap
	 *
	 * @since    1.0.0
	 * @param    int    $page    Page number for pagination
	 */
	private function generate_sitemap( $page = 1 ) {
		// Check cache first
		$cache_key = 'nds_sitemap_' . $page;
		$cached    = $this->cache_manager->get( $cache_key );

		if ( $cached !== false ) {
			echo $cached;
			return;
		}

		// Get posts for this sitemap
		$posts = $this->get_sitemap_posts( $page );

		// Validate posts
		$validated_posts = $this->validate_sitemap_posts( $posts );

		// Build sitemap XML
		$xml = $this->schema_builder->build_sitemap( $validated_posts );

		// Cache the result
		$this->cache_manager->set( $cache_key, $xml );

		// Update analytics
		$this->update_sitemap_analytics( count( $validated_posts ) );

		echo $xml;
	}

	/**
	 * Get posts for sitemap with optimized query
	 *
	 * @since    1.0.0
	 * @param    int    $page    Page number
	 * @return   array           Array of WP_Post objects
	 */
	private function get_sitemap_posts( $page = 1 ) {
		$time_limit = get_option( 'nds_time_limit', 48 ); // hours
		$max_urls   = get_option( 'nds_max_urls', 1000 );
		$offset     = ( $page - 1 ) * $max_urls;

		// Build query arguments
		$args = array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => $max_urls,
			'offset'                 => $offset,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'date_query'             => array(
				array(
					'after'     => $time_limit . ' hours ago',
					'inclusive' => true,
				),
			),
			'no_found_rows'          => true, // Performance optimization
			'update_post_meta_cache' => false, // We'll get meta separately
			'update_post_term_cache' => false, // We'll get terms separately
		);

		// Apply exclusion filters
		$excluded_categories = get_option( 'nds_excluded_categories', array() );
		if ( ! empty( $excluded_categories ) ) {
			$args['category__not_in'] = $excluded_categories;
		}

		$excluded_tags = get_option( 'nds_excluded_tags', array() );
		if ( ! empty( $excluded_tags ) ) {
			$args['tag__not_in'] = $excluded_tags;
		}

		// Handle breaking news priority
		if ( get_option( 'nds_breaking_news_first', true ) ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_nds_breaking_news',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => '_nds_breaking_news',
					'compare' => 'NOT EXISTS',
				),
			);
			$args['orderby']    = array(
				'meta_value' => 'DESC',
				'date'       => 'DESC',
			);
		}

		// Use query optimizer for better performance
		return $this->query_optimizer->get_optimized_posts( $args );
	}

	/**
	 * Get total count of posts eligible for sitemap
	 *
	 * @since    1.0.0
	 * @return   int    Total post count
	 */
	private function get_total_sitemap_posts() {
		$time_limit = get_option( 'nds_time_limit', 48 );

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'after'     => $time_limit . ' hours ago',
					'inclusive' => true,
				),
			),
		);

		$excluded_categories = get_option( 'nds_excluded_categories', array() );
		if ( ! empty( $excluded_categories ) ) {
			$args['category__not_in'] = $excluded_categories;
		}

		$query = new WP_Query( $args );
		return $query->post_count;
	}

	/**
	 * Validate posts before including in sitemap
	 *
	 * @since    1.0.0
	 * @param    array    $posts    Array of WP_Post objects
	 * @return   array              Validated posts
	 */
	private function validate_sitemap_posts( $posts ) {
		$validated      = array();
		$min_word_count = get_option( 'nds_min_word_count', 80 );

		foreach ( $posts as $post ) {
			// Skip if post doesn't meet minimum word count
			$word_count = str_word_count( strip_tags( $post->post_content ) );
			if ( $word_count < $min_word_count ) {
				continue;
			}

			// Skip if validation fails
			if ( ! $this->validator->validate_post( $post ) ) {
				continue;
			}

			// Enrich post with metadata needed for sitemap
			$post->sitemap_data = $this->get_post_sitemap_data( $post );

			$validated[] = $post;
		}

		return $validated;
	}

	/**
	 * Get sitemap-specific metadata for a post
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Post object
	 * @return   array               Sitemap metadata
	 */
	private function get_post_sitemap_data( $post ) {
		$data = array(
			'url'              => get_permalink( $post->ID ),
			'publication_date' => get_the_date( 'c', $post->ID ),
			'title'            => get_the_title( $post->ID ),

			// News-specific fields
			'breaking_news'    => get_post_meta( $post->ID, '_nds_breaking_news', true ) === '1',
			'genre'            => get_post_meta( $post->ID, '_nds_genre', true ) ?: get_option( 'nds_default_genre', 'Blog' ),
			'keywords'         => $this->get_post_keywords( $post->ID ),
			'stock_tickers'    => get_post_meta( $post->ID, '_nds_stock_tickers', true ),

			// Image data (if image sitemap enabled)
			'images'           => array(),
		);

		// Get featured image
		if ( get_option( 'nds_enable_image_sitemap', true ) ) {
			$data['images'] = $this->get_post_images( $post->ID );
		}

		return $data;
	}

	/**
	 * Extract keywords from post (tags and categories)
	 *
	 * @since    1.0.0
	 * @param    int       $post_id    Post ID
	 * @return   string                Comma-separated keywords
	 */
	private function get_post_keywords( $post_id ) {
		$keywords = array();

		// Get tags
		$tags = get_the_tags( $post_id );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$keywords[] = $tag->name;
			}
		}

		// Get categories
		$categories = get_the_category( $post_id );
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$keywords[] = $category->name;
			}
		}

		// Limit to 10 keywords as per Google News guidelines
		$keywords = array_slice( $keywords, 0, 10 );

		return implode( ', ', $keywords );
	}

	/**
	 * Get images from post for image sitemap
	 *
	 * @since    1.0.0
	 * @param    int      $post_id    Post ID
	 * @return   array                Array of image data
	 */
	private function get_post_images( $post_id ) {
		$images = array();

		// Get featured image
		if ( has_post_thumbnail( $post_id ) ) {
			$image_id      = get_post_thumbnail_id( $post_id );
			$image_url     = wp_get_attachment_image_src( $image_id, 'full' );
			$image_caption = wp_get_attachment_caption( $image_id );

			if ( $image_url ) {
				$images[] = array(
					'url'     => $image_url[0],
					'caption' => $image_caption ?: get_the_title( $post_id ),
				);
			}
		}

		return $images;
	}

	/**
	 * Clear cache when post is saved
	 *
	 * @since    1.0.0
	 * @param    int        $post_id    Post ID
	 * @param    WP_Post    $post       Post object
	 * @param    bool       $update     Whether this is an update
	 */
	public function clear_cache_on_save( $post_id, $post, $update ) {
		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only clear for published posts
		if ( $post->post_status !== 'publish' || $post->post_type !== 'post' ) {
			return;
		}

		$this->cache_manager->clear_all();
	}

	/**
	 * Clear cache on post status transition
	 *
	 * @since    1.0.0
	 * @param    string     $new_status    New post status
	 * @param    string     $old_status    Old post status
	 * @param    WP_Post    $post          Post object
	 */
	public function clear_cache_on_transition( $new_status, $old_status, $post ) {
		if ( $post->post_type !== 'post' ) {
			return;
		}

		// Clear cache if post is published or unpublished
		if ( $new_status === 'publish' || $old_status === 'publish' ) {
			$this->cache_manager->clear_all();
		}
	}

	/**
	 * Add sitemap to robots.txt
	 *
	 * @since    1.0.0
	 * @param    string    $output    Existing robots.txt content
	 * @param    bool      $public    Whether site is public
	 * @return   string               Modified robots.txt content
	 */
	public function add_sitemap_to_robots( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}

		$slug        = get_option( 'nds_custom_slug', 'news-sitemap' );
		$sitemap_url = home_url( '/' . $slug . '.xml' );

		$output .= "\n# NewsDesk Sitemap\n";
		$output .= "Sitemap: " . $sitemap_url . "\n";

		return $output;
	}

	/**
	 * Update sitemap generation analytics
	 *
	 * @since    1.0.0
	 * @param    int    $post_count    Number of posts in sitemap
	 */
	private function update_sitemap_analytics( $post_count ) {
		global $wpdb;

		$table = $wpdb->prefix . 'nds_analytics';
		$today = current_time( 'Y-m-d' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $table (date, posts_in_sitemap)
				VALUES (%s, %d)
				ON DUPLICATE KEY UPDATE posts_in_sitemap = %d",
				$today,
				$post_count,
				$post_count
			)
		);

		// Update last generation timestamp
		update_option( 'nds_last_sitemap_generation', current_time( 'mysql' ) );
	}

	/**
	 * Get sitemap URL (helper method for other classes)
	 *
	 * @since    1.0.0
	 * @return   string    Sitemap URL
	 */
	public function get_sitemap_url() {
		$slug = get_option( 'nds_custom_slug', 'news-sitemap' );
		return home_url( '/' . $slug . '.xml' );
	}
}