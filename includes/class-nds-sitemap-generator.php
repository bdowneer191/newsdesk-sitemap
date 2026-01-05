<?php
/**
 * Sitemap Generator - Core XML generation and URL management
 *
 * This class handles the logic for registering sitemap URLs, querying
 * eligible news posts, and orchestrating the final XML output.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-2 of Complete Technical Implementation Guide (ANSP_Sitemap_Generator logic)
 * IMPLEMENTATION: NDS_Sitemap_Generator with optimized queries and analytics tracking.
 */

// Exit if accessed directly - Security measure
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
	 * Initialize the generator and its dependencies
	 * [cite: 1495-1510]
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
	 * Register custom rewrite rules for "virtual" sitemap XML files
	 * [cite: 1515-1545]
	 *
	 * @since    1.0.0
	 */
	public function add_rewrite_rules() {
		$slug = sanitize_title( get_option( 'nds_custom_slug', 'news-sitemap' ) );

		// 1. Main sitemap URL (e.g., news-sitemap.xml)
		add_rewrite_rule(
			'^' . $slug . '\.xml$',
			'index.php?nds_sitemap=1',
			'top'
		);

		// 2. Sitemap index (for sites with >1000 posts)
		add_rewrite_rule(
			'^' . $slug . '-index\.xml$',
			'index.php?nds_sitemap_index=1',
			'top'
		);

		// 3. Paginated sitemaps (e.g., news-sitemap-1.xml)
		add_rewrite_rule(
			'^' . $slug . '-([0-9]+)\.xml$',
			'index.php?nds_sitemap=$matches[1]',
			'top'
		);
	}

	/**
	 * Register sitemap query variables with WordPress
	 *
	 * @since    1.0.0
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'nds_sitemap';
		$vars[] = 'nds_sitemap_index';
		return $vars;
	}

	/**
	 * Intercept template redirection to render the XML sitemap
	 * [cite: 1555-1580]
	 *
	 * @since    1.0.0
	 */
	public function render_sitemap() {
		$sitemap_query = get_query_var( 'nds_sitemap' );
		$index_query   = get_query_var( 'nds_sitemap_index' );

		if ( '' !== $sitemap_query || '' !== $index_query ) {
			$this->set_xml_headers();

			if ( '' !== $index_query ) {
				$this->generate_sitemap_index();
			} else {
				$page = absint( $sitemap_query ) ?: 1;
				$this->generate_sitemap( $page );
			}

			exit;
		}
	}

	/**
	 * Set XML and caching headers for search engine crawlers
	 *
	 * @since    1.0.0
	 */
	private function set_xml_headers() {
		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );

		if ( get_option( 'nds_cdn_compatibility', false ) ) {
			$duration = (int) get_option( 'nds_cache_duration', 1800 );
			header( "Cache-Control: public, max-age=$duration" );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $duration ) . ' GMT' );
		}
	}

	/**
	 * Build and output the sitemap index
	 *
	 * @since    1.0.0
	 */
	private function generate_sitemap_index() {
		$cache_key = 'index';
		$cached    = $this->cache_manager->get( $cache_key );

		if ( false !== $cached ) {
			echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$total_posts = $this->query_optimizer->get_post_count();
		$max_urls    = (int) get_option( 'nds_max_urls', 1000 );
		$total_pages = max( 1, ceil( $total_posts / $max_urls ) );

		$xml = $this->schema_builder->build_sitemap_index( $total_pages );
		$this->cache_manager->set( $cache_key, $xml );

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build and output a specific sitemap page
	 * [cite: 1600-1630]
	 *
	 * @since    1.0.0
	 */
	private function generate_sitemap( $page = 1 ) {
		$cache_key = 'page_' . $page;
		$cached    = $this->cache_manager->get( $cache_key );

		if ( false !== $cached ) {
			echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$posts           = $this->get_sitemap_posts( $page );
		$validated_posts = $this->validate_sitemap_posts( $posts );
		$xml             = $this->schema_builder->build_sitemap( $validated_posts );

		$this->cache_manager->set( $cache_key, $xml );
		$this->update_sitemap_analytics( count( $validated_posts ) );

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Fetch posts for the sitemap using optimized logic
	 * [cite: 1635-1670]
	 *
	 * @since    1.0.0
	 */
	private function get_sitemap_posts( $page = 1 ) {
		$time_limit = (int) get_option( 'nds_time_limit', 48 );
		$max_urls   = (int) get_option( 'nds_max_urls', 1000 );
		$offset     = ( $page - 1 ) * $max_urls;

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $max_urls,
			'offset'         => $offset,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'date_query'     => array(
				array(
					'after'     => "$time_limit hours ago",
					'inclusive' => true,
				),
			),
		);

		// Handle category exclusions
		$excluded_cats = (array) get_option( 'nds_excluded_categories', array() );
		if ( ! empty( $excluded_cats ) ) {
			$args['category__not_in'] = array_map( 'absint', $excluded_cats );
		}

		// Handle breaking news priority sorting
		if ( get_option( 'nds_breaking_news_first', true ) ) {
			$args['meta_key'] = '_nds_breaking_news';
			$args['orderby']  = array(
				'meta_value' => 'DESC',
				'date'       => 'DESC',
			);
		}

		return $this->query_optimizer->get_optimized_posts( $args );
	}

	/**
	 * Validate and enrich post data before XML generation
	 * [cite: 1675-1700]
	 *
	 * @since    1.0.0
	 */
	private function validate_sitemap_posts( $posts ) {
		$validated = array();

		foreach ( $posts as $post ) {
			if ( $this->validator->validate_post( $post ) ) {
				$post->sitemap_data = $this->get_post_sitemap_data( $post );
				$validated[]        = $post;
			}
		}

		return $validated;
	}

	/**
	 * Consolidate metadata for the XML builder
	 *
	 * @since    1.0.0
	 */
	private function get_post_sitemap_data( $post ) {
		// Metadata was batch-loaded by the optimizer into nds_metadata
		$meta = isset( $post->nds_metadata ) ? $post->nds_metadata : array();

		$data = array(
			'url'              => get_permalink( $post->ID ),
			'publication_date' => get_the_date( 'c', $post->ID ),
			'title'            => get_the_title( $post->ID ),
			'genre'            => isset( $meta['_nds_genre'] ) ? $meta['_nds_genre'] : get_option( 'nds_default_genre', 'Blog' ),
			'stock_tickers'    => isset( $meta['_nds_stock_tickers'] ) ? $meta['_nds_stock_tickers'] : '',
			'keywords'         => $this->get_post_keywords( $post->ID ),
			'images'           => array(),
		);

		if ( get_option( 'nds_enable_image_sitemap', true ) ) {
			$data['images'] = $this->get_post_images( $post->ID );
		}

		return $data;
	}

	/**
	 * Extract tags and categories as News Keywords
	 *
	 * @since    1.0.0
	 */
	private function get_post_keywords( $post_id ) {
		$keywords = array();
		$tags     = get_the_tags( $post_id );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$keywords[] = $tag->name;
			}
		}

		$cats = get_the_category( $post_id );
		if ( $cats ) {
			foreach ( $cats as $cat ) {
				$keywords[] = $cat->name;
			}
		}

		return implode( ', ', array_slice( $keywords, 0, 10 ) );
	}

	/**
	 * Fetch image data for News Image support
	 *
	 * @since    1.0.0
	 */
	private function get_post_images( $post_id ) {
		$images = array();
		if ( has_post_thumbnail( $post_id ) ) {
			$thumb_id = get_post_thumbnail_id( $post_id );
			$src      = wp_get_attachment_image_src( $thumb_id, 'full' );
			if ( $src ) {
				$images[] = array(
					'url'     => $src[0],
					'caption' => wp_get_attachment_caption( $thumb_id ) ?: get_the_title( $post_id ),
					'title'   => get_the_title( $thumb_id ),
				);
			}
		}
		return $images;
	}

	/**
	 * Invalidate all sitemap cache entries
	 *
	 * @since    1.0.0
	 */
	public function clear_cache_on_save( $post_id, $post, $update ) {
		if ( ! wp_is_post_revision( $post_id ) && 'post' === $post->post_type && 'publish' === $post->post_status ) {
			$this->cache_manager->clear_all();
		}
	}

	public function clear_cache_on_transition( $new_status, $old_status, $post ) {
		if ( 'post' === $post->post_type && ( 'publish' === $new_status || 'publish' === $old_status ) ) {
			$this->cache_manager->clear_all();
		}
	}

	/**
	 * Inject the News Sitemap into robots.txt for auto-discovery
	 * [cite: 1740-1750]
	 *
	 * @since    1.0.0
	 */
	public function add_sitemap_to_robots( $output, $public ) {
		if ( $public ) {
			$slug    = sanitize_title( get_option( 'nds_custom_slug', 'news-sitemap' ) );
			$url     = home_url( '/' . $slug . '.xml' );
			$output .= "\n# NewsDesk Sitemap Discovery\n";
			$output .= "Sitemap: " . esc_url( $url ) . "\n";
		}
		return $output;
	}

	/**
	 * Update internal analytics for the sitemap generation
	 *
	 * @since    1.0.0
	 */
	private function update_sitemap_analytics( $post_count ) {
		global $wpdb;
		$table = $wpdb->prefix . 'nds_analytics';
		$today = current_time( 'Y-m-d' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $table (date, posts_in_sitemap) VALUES (%s, %d) 
				ON DUPLICATE KEY UPDATE posts_in_sitemap = %d",
				$today,
				$post_count,
				$post_count
			)
		);

		update_option( 'nds_last_sitemap_generation', current_time( 'mysql' ) );
	}
}