<?php
/**
 * Validation utilities for content and XML
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Validation {

	/**
	 * Validation errors
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
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Post object
	 * @return   bool                Valid for inclusion
	 */
	public function validate_post( $post ) {
		$this->errors = array();

		// Check post status
		if ( $post->post_status !== 'publish' ) {
			$this->errors[] = 'Post is not published';
			return false;
		}

		// Check post type
		if ( $post->post_type !== 'post' ) {
			$this->errors[] = 'Invalid post type';
			return false;
		}

		// Check minimum word count
		$min_word_count = get_option( 'nds_min_word_count', 80 );
		$word_count     = str_word_count( strip_tags( $post->post_content ) );

		if ( $word_count < $min_word_count ) {
			$this->errors[] = sprintf(
				'Word count (%d) below minimum (%d)',
				$word_count,
				$min_word_count
			);
			return false;
		}

		// Check if post has title
		if ( empty( $post->post_title ) ) {
			$this->errors[] = 'Post has no title';
			return false;
		}

		// Check if post date is within time window
		$time_limit = get_option( 'nds_time_limit', 48 );
		$post_age   = ( current_time( 'timestamp' ) - strtotime( $post->post_date ) ) / 3600;

		if ( $post_age > $time_limit ) {
			$this->errors[] = sprintf(
				'Post age (%.1f hours) exceeds time limit (%d hours)',
				$post_age,
				$time_limit
			);
			return false;
		}

		// Check for excluded categories
		$excluded_categories = get_option( 'nds_excluded_categories', array() );
		if ( ! empty( $excluded_categories ) ) {
			$post_categories = wp_get_post_categories( $post->ID );
			$intersection    = array_intersect( $post_categories, $excluded_categories );

			if ( ! empty( $intersection ) ) {
				$this->errors[] = 'Post in excluded category';
				return false;
			}
		}

		// Check for excluded tags
		$excluded_tags = get_option( 'nds_excluded_tags', array() );
		if ( ! empty( $excluded_tags ) ) {
			$post_tags    = wp_get_post_tags( $post->ID, array( 'fields' => 'ids' ) );
			$intersection = array_intersect( $post_tags, $excluded_tags );

			if ( ! empty( $intersection ) ) {
				$this->errors[] = 'Post has excluded tag';
				return false;
			}
		}

		// Validate URL
		$permalink = get_permalink( $post->ID );
		if ( ! $this->validate_url( $permalink ) ) {
			$this->errors[] = 'Invalid permalink';
			return false;
		}

		return true;
	}

	/**
	 * Validate URL format
	 *
	 * @since    1.0.0
	 * @param    string    $url    URL to validate
	 * @return   bool              Valid URL
	 */
	public function validate_url( $url ) {
		// Must be valid URL
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return false;
		}

		// Must use HTTP or HTTPS
		$scheme = parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}

		// Must not be localhost (in production)
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			$host = parse_url( $url, PHP_URL_HOST );
			if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate XML structure
	 *
	 * @since    1.0.0
	 * @param    string           $xml    XML string
	 * @return   bool|WP_Error            True if valid, WP_Error if invalid
	 */
	public function validate_xml( $xml ) {
		libxml_use_internal_errors( true );

		$dom    = new DOMDocument( '1.0', 'UTF-8' );
		$loaded = $dom->loadXML( $xml );

		$errors = libxml_get_errors();
		libxml_clear_errors();

		if ( ! $loaded || ! empty( $errors ) ) {
			$error_messages = array();

			foreach ( $errors as $error ) {
				$error_messages[] = sprintf(
					'Line %d, Column %d: %s',
					$error->line,
					$error->column,
					trim( $error->message )
				);
			}

			return new WP_Error(
				'xml_validation_error',
				'XML validation failed',
				array( 'errors' => $error_messages )
			);
		}

		return true;
	}

	/**
	 * Validate Google News compliance
	 *
	 * @since    1.0.0
	 * @param    string           $xml    XML string
	 * @return   bool|WP_Error            True if compliant, WP_Error if not
	 */
	public function validate_google_news_compliance( $xml ) {
		$validation_errors = array();

		// First validate basic XML structure
		$xml_valid = $this->validate_xml( $xml );
		if ( is_wp_error( $xml_valid ) ) {
			return $xml_valid;
		}

		$dom = new DOMDocument();
		$dom->loadXML( $xml );
		$xpath = new DOMXPath( $dom );

		// Register namespaces
		$xpath->registerNamespace( 'sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$xpath->registerNamespace( 'news', 'http://www.google.com/schemas/sitemap-news/0.9' );

		// Check for urlset root element
		$urlset = $xpath->query( '/sitemap:urlset' );
		if ( $urlset->length === 0 ) {
			$validation_errors[] = 'Missing urlset root element';
		}

		// Check each URL entry
		$urls = $xpath->query( '//sitemap:url' );

		if ( $urls->length === 0 ) {
			$validation_errors[] = 'No URL entries found';
		}

		if ( $urls->length > 1000 ) {
			$validation_errors[] = sprintf(
				'Too many URLs (%d). Maximum is 1000 per sitemap.',
				$urls->length
			);
		}

		foreach ( $urls as $url ) {
			// Check for required elements
			$loc = $xpath->query( 'sitemap:loc', $url );
			if ( $loc->length === 0 ) {
				$validation_errors[] = 'URL entry missing <loc> element';
			}

			$news = $xpath->query( 'news:news', $url );
			if ( $news->length === 0 ) {
				$validation_errors[] = 'URL entry missing <news:news> element';
				continue;
			}

			// Check news:publication
			$publication = $xpath->query( 'news:news/news:publication', $url );
			if ( $publication->length === 0 ) {
				$validation_errors[] = 'News entry missing <news:publication>';
			} else {
				// Check publication name and language
				$pub_name = $xpath->query( 'news:news/news:publication/news:name', $url );
				$pub_lang = $xpath->query( 'news:news/news:publication/news:language', $url );

				if ( $pub_name->length === 0 ) {
					$validation_errors[] = 'Publication missing <news:name>';
				}

				if ( $pub_lang->length === 0 ) {
					$validation_errors[] = 'Publication missing <news:language>';
				}
			}

			// Check news:publication_date
			$pub_date = $xpath->query( 'news:news/news:publication_date', $url );
			if ( $pub_date->length === 0 ) {
				$validation_errors[] = 'News entry missing <news:publication_date>';
			} else {
				// Validate date format (W3C/ISO 8601)
				$date_value = $pub_date->item( 0 )->nodeValue;
				if ( ! $this->validate_w3c_date( $date_value ) ) {
					$validation_errors[] = 'Invalid publication date format: ' . $date_value;
				}
			}

			// Check news:title
			$title = $xpath->query( 'news:news/news:title', $url );
			if ( $title->length === 0 ) {
				$validation_errors[] = 'News entry missing <news:title>';
			}
		}

		if ( ! empty( $validation_errors ) ) {
			return new WP_Error(
				'google_news_validation_error',
				'Google News compliance validation failed',
				array( 'errors' => $validation_errors )
			);
		}

		return true;
	}

	/**
	 * Validate W3C date format (ISO 8601)
	 *
	 * @since    1.0.0
	 * @param    string    $date    Date string
	 * @return   bool               Valid format
	 */
	private function validate_w3c_date( $date ) {
		// W3C datetime format: YYYY-MM-DDTHH:MM:SS±HH:MM
		$pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';

		if ( preg_match( $pattern, $date ) ) {
			return true;
		}

		// Also accept format with Z for UTC
		$pattern_z = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/';
		return preg_match( $pattern_z, $date ) === 1;
	}

	/**
	 * Validate genre value
	 *
	 * @since    1.0.0
	 * @param    string    $genre    Genre value
	 * @return   bool                Valid genre
	 */
	public function validate_genre( $genre ) {
		$valid_genres = array(
			'PressRelease',
			'Satire',
			'Blog',
			'OpEd',
			'Opinion',
			'UserGenerated',
		);

		return in_array( $genre, $valid_genres, true );
	}

	/**
	 * Validate stock ticker format
	 *
	 * @since    1.0.0
	 * @param    string    $tickers    Comma-separated stock tickers
	 * @return   bool                  Valid format
	 */
	public function validate_stock_tickers( $tickers ) {
		if ( empty( $tickers ) ) {
			return true; // Optional field
		}

		// Stock tickers should be uppercase letters, comma-separated
		$ticker_array = array_map( 'trim', explode( ',', $tickers ) );

		foreach ( $ticker_array as $ticker ) {
			if ( ! preg_match( '/^[A-Z]{1,5}$/', $ticker ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get validation errors
	 *
	 * @since    1.0.0
	 * @return   array    Validation errors
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Check if there are validation errors
	 *
	 * @since    1.0.0
	 * @return   bool    Has errors
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}

	/**
	 * Clear validation errors
	 *
	 * @since    1.0.0
	 */
	public function clear_errors() {
		$this->errors = array();
	}
}