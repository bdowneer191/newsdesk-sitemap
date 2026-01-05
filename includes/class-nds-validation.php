<?php
/**
 * Validation utilities for content and XML
 *
 * This class handles the validation of post content for sitemap eligibility
 * and ensures generated XML complies with Google News protocols.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-3 of Complete Technical Implementation Guide (ANSP_Validation logic)
 * IMPLEMENTATION: NDS_Validation with W3C date support and Google News compliance.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Validation {

	/**
	 * Collection of validation errors
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $errors = array();

	/**
	 * Initialize validation class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->errors = array();
	}

	/**
	 * Validate post for sitemap inclusion
	 * Checks status, type, age (48h rule), word count, and exclusions.
	 * [cite: 5780-5820]
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Post object to validate.
	 * @return   bool                True if valid for inclusion.
	 */
	public function validate_post( $post ) {
		$this->clear_errors();

		if ( ! $post instanceof WP_Post ) {
			$this->errors[] = __( 'Invalid post object provided.', 'newsdesk-sitemap' );
			return false;
		}

		// 1. Check post status
		if ( 'publish' !== $post->post_status ) {
			$this->errors[] = __( 'Post is not in published status.', 'newsdesk-sitemap' );
			return false;
		}

		// 2. Check post type
		if ( 'post' !== $post->post_type ) {
			$this->errors[] = __( 'Invalid post type for news sitemap.', 'newsdesk-sitemap' );
			return false;
		}

		// 3. Check minimum word count
		$min_word_count = (int) get_option( 'nds_min_word_count', 80 );
		$word_count     = str_word_count( strip_tags( $post->post_content ) );

		if ( $word_count < $min_word_count ) {
			$this->errors[] = sprintf(
				/* translators: 1: Current word count, 2: Required word count */
				esc_html__( 'Word count (%1$d) is below the minimum required (%2$d).', 'newsdesk-sitemap' ),
				$word_count,
				$min_word_count
			);
			return false;
		}

		// 4. Check for title
		if ( empty( trim( $post->post_title ) ) ) {
			$this->errors[] = __( 'Post has no title.', 'newsdesk-sitemap' );
			return false;
		}

		// 5. Check 48-hour Google News requirement
		$time_limit = (int) get_option( 'nds_time_limit', 48 );
		$post_age   = ( current_time( 'timestamp' ) - strtotime( $post->post_date ) ) / 3600;

		if ( $post_age > $time_limit ) {
			$this->errors[] = sprintf(
				/* translators: 1: Post age in hours, 2: Time limit in hours */
				esc_html__( 'Post age (%1$.1f hours) exceeds the News limit (%2$d hours).', 'newsdesk-sitemap' ),
				$post_age,
				$time_limit
			);
			return false;
		}

		// 6. Check for excluded categories
		$excluded_cats = (array) get_option( 'nds_excluded_categories', array() );
		if ( ! empty( $excluded_cats ) ) {
			$post_cats    = wp_get_post_categories( $post->ID );
			$intersection = array_intersect( $post_cats, $excluded_cats );

			if ( ! empty( $intersection ) ) {
				$this->errors[] = __( 'Post is assigned to an excluded category.', 'newsdesk-sitemap' );
				return false;
			}
		}

		// 7. Validate URL
		$permalink = get_permalink( $post->ID );
		if ( ! $this->validate_url( $permalink ) ) {
			$this->errors[] = __( 'The post permalink is invalid or improperly formatted.', 'newsdesk-sitemap' );
			return false;
		}

		return true;
	}

	/**
	 * Validate URL format and scheme
	 *
	 * @since    1.0.0
	 * @param    string    $url    URL string.
	 * @return   bool
	 */
	public function validate_url( $url ) {
		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$scheme = parse_url( $url, PHP_URL_SCHEME );
		return in_array( $scheme, array( 'http', 'https' ), true );
	}

	/**
	 * Validate Google News compliance via XPath
	 * [cite: 5850-5910]
	 *
	 * @since    1.0.0
	 * @param    string    $xml    The XML string to validate.
	 * @return   bool|WP_Error     True if compliant, WP_Error otherwise.
	 */
	public function validate_google_news_compliance( $xml ) {
		$v_errors = array();

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		if ( ! $dom->loadXML( $xml ) ) {
			return new WP_Error( 'xml_format_error', __( 'XML structure is invalid.', 'newsdesk-sitemap' ) );
		}

		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 'sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$xpath->registerNamespace( 'news', 'http://www.google.com/schemas/sitemap-news/0.9' );

		$urls = $xpath->query( '//sitemap:url' );

		if ( 0 === $urls->length ) {
			$v_errors[] = __( 'No URL entries found in sitemap.', 'newsdesk-sitemap' );
		}

		if ( $urls->length > 1000 ) {
			$v_errors[] = sprintf( __( 'Sitemap exceeds 1000 URL limit (Count: %d).', 'newsdesk-sitemap' ), $urls->length );
		}

		foreach ( $urls as $url ) {
			$loc = $xpath->query( 'sitemap:loc', $url );
			if ( 0 === $loc->length ) {
				$v_errors[] = __( 'Entry missing <loc> tag.', 'newsdesk-sitemap' );
			}

			$news = $xpath->query( 'news:news', $url );
			if ( 0 === $news->length ) {
				$v_errors[] = __( 'Entry missing <news:news> container.', 'newsdesk-sitemap' );
				continue;
			}

			$pub_name = $xpath->query( 'news:news/news:publication/news:name', $url );
			if ( 0 === $pub_name->length || empty( $pub_name->item( 0 )->nodeValue ) ) {
				$v_errors[] = __( 'Publication name is missing.', 'newsdesk-sitemap' );
			}

			$pub_date = $xpath->query( 'news:news/news:publication_date', $url );
			if ( 0 === $pub_date->length || ! $this->validate_w3c_date( $pub_date->item( 0 )->nodeValue ) ) {
				$v_errors[] = __( 'Publication date is missing or not in W3C format.', 'newsdesk-sitemap' );
			}

			$title = $xpath->query( 'news:news/news:title', $url );
			if ( 0 === $title->length || empty( $title->item( 0 )->nodeValue ) ) {
				$v_errors[] = __( 'News title is missing.', 'newsdesk-sitemap' );
			}
		}

		if ( ! empty( $v_errors ) ) {
			return new WP_Error( 'news_compliance_error', __( 'Google News validation failed.', 'newsdesk-sitemap' ), $v_errors );
		}

		return true;
	}

	/**
	 * Validate W3C date format (ISO 8601)
	 * [cite: 5920-5935]
	 *
	 * @since    1.0.0
	 * @param    string    $date    Date string.
	 * @return   bool
	 */
	public function validate_w3c_date( $date ) {
		// Matches YYYY-MM-DDTHH:MM:SS+HH:MM or YYYY-MM-DDTHH:MM:SSZ
		$pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/';
		return (bool) preg_match( $pattern, $date );
	}

	/**
	 * Validate genre value against whitelist
	 * [cite: 5940-5950]
	 *
	 * @since    1.0.0
	 * @param    string    $genre    Genre value.
	 * @return   bool
	 */
	public function validate_genre( $genre ) {
		$valid_genres = array( 'PressRelease', 'Satire', 'Blog', 'OpEd', 'Opinion', 'UserGenerated' );
		return in_array( $genre, $valid_genres, true );
	}

	/**
	 * Validate stock ticker format (Uppercase letters)
	 *
	 * @since    1.0.0
	 * @param    string    $tickers    Comma-separated list.
	 * @return   bool
	 */
	public function validate_stock_tickers( $tickers ) {
		if ( empty( $tickers ) ) {
			return true;
		}

		$ticker_array = array_map( 'trim', explode( ',', $tickers ) );
		foreach ( $ticker_array as $ticker ) {
			if ( ! preg_match( '/^[A-Z]{1,5}$/', $ticker ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get accumulated errors
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Clear all errors
	 *
	 * @since    1.0.0
	 */
	public function clear_errors() {
		$this->errors = array();
	}
}