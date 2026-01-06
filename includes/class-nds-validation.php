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

/**
 * SOURCE: Part-3 of Complete Technical Implementation Guide (ANSP_Validation logic)
 * IMPLEMENTATION: NDS_Validation with Advanced Quality Filtering (Enhancement 3).
 */
class NDS_Validation {

	/**
	 * Collection of validation errors
	 * @var array
	 */
	private $errors = array();

	/**
	 * Validate post for sitemap inclusion
	 * Checks status, type, age (48h rule), word count, and exclusions. [cite: 6992-7062]
	 */
	public function validate_post( $post ) {
		$this->errors = array();

		if ( ! $post instanceof WP_Post ) {
			$this->errors[] = __( 'Invalid post object.', 'newsdesk-sitemap' );
			return false;
		}

		// 1. Check Status and Type
		if ( 'publish' !== $post->post_status || 'post' !== $post->post_type ) {
			$this->errors[] = __( 'Post is not a published standard post.', 'newsdesk-sitemap' );
			return false;
		}

		// 2. Check 48-hour Google News requirement [cite: 7025-7033]
		$time_limit = (int) get_option( 'nds_time_limit', 48 );
		$post_age   = ( current_time( 'timestamp' ) - strtotime( $post->post_date ) ) / 3600;

		if ( $post_age > $time_limit ) {
			$this->errors[] = sprintf( __( 'Post age (%.1f hours) exceeds news limit (%d hours).', 'newsdesk-sitemap' ), $post_age, $time_limit );
			return false;
		}

		// 3. Basic Content Filtering (Word Count) [cite: 7011-7021]
		$min_word_count = (int) get_option( 'nds_min_word_count', 80 );
		$word_count     = str_word_count( strip_tags( $post->post_content ) );

		if ( $word_count < $min_word_count ) {
			$this->errors[] = sprintf( __( 'Word count (%d) is below minimum (%d).', 'newsdesk-sitemap' ), $word_count, $min_word_count );
			return false;
		}

		// 4. Advanced Content Quality Filtering (Enhancement 3)
		$quality_check = $this->validate_post_quality( $post );
		if ( is_wp_error( $quality_check ) ) {
			$this->errors[] = $quality_check->get_error_message();
			return false;
		}

		return true;
	}

	/**
	 * Advanced Quality Filtering (Enhancement 3)
	 * Checks for duplicate content hashes and thin content patterns.
	 */
	public function validate_post_quality( $post ) {
		$issues = array();

		// A. Check for duplicate content (internal site-wide)
		$content_hash = md5( strip_tags( $post->post_content ) );
		$existing_id  = get_transient( 'nds_hash_' . $content_hash );
		
		if ( $existing_id && (int) $existing_id !== $post->ID ) {
			$issues[] = __( 'Duplicate content detected (internal conflict).', 'newsdesk-sitemap' );
		} else {
			set_transient( 'nds_hash_' . $content_hash, $post->ID, DAY_IN_SECONDS );
		}

		// B. Check for thin content patterns (Avg sentence length)
		$text = strip_tags( $post->post_content );
		$sentences = preg_split( '/[.!?]/', $text, -1, PREG_SPLIT_NO_EMPTY );
		$avg_sentence_length = strlen( $text ) / max( count( $sentences ), 1 );

		if ( $avg_sentence_length < 15 ) {
			$issues[] = __( 'Thin content pattern: Average sentence length too short.', 'newsdesk-sitemap' );
		}

		// C. Check image presence
		if ( ! has_post_thumbnail( $post->ID ) && get_option( 'nds_require_featured_image', false ) ) {
			$issues[] = __( 'Missing required featured image.', 'newsdesk-sitemap' );
		}

		return empty( $issues ) ? true : new WP_Error( 'quality_failed', implode( ' ', $issues ) );
	}

	/**
	 * Validate Google News compliance via XPath [cite: 7121-7199]
	 */
	public function validate_google_news_compliance( $xml ) {
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		if ( ! @$dom->loadXML( $xml ) ) {
			return new WP_Error( 'xml_error', __( 'Invalid XML structure.', 'newsdesk-sitemap' ) );
		}

		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 's', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$xpath->registerNamespace( 'n', 'http://www.google.com/schemas/sitemap-news/0.9' );

		$errors = array();
		$urls   = $xpath->query( '//s:url' );

		if ( $urls->length > 1000 ) {
			$errors[] = __( 'Sitemap exceeds 1,000 URL limit.', 'newsdesk-sitemap' );
		}

		foreach ( $urls as $url ) {
			if ( $xpath->query( 's:loc', $url )->length === 0 ) $errors[] = __( 'Missing <loc> tag.', 'newsdesk-sitemap' );
			
			$news = $xpath->query( 'n:news', $url );
			if ( $news->length === 0 ) {
				$errors[] = __( 'Missing <news:news> tag.', 'newsdesk-sitemap' );
				continue;
			}

			if ( $xpath->query( 'n:publication/n:name', $url )->length === 0 ) $errors[] = __( 'Missing publication name.', 'newsdesk-sitemap' );
			if ( $xpath->query( 'n:publication_date', $url )->length === 0 ) $errors[] = __( 'Missing publication date.', 'newsdesk-sitemap' );
			if ( $xpath->query( 'n:title', $url )->length === 0 ) $errors[] = __( 'Missing news title.', 'newsdesk-sitemap' );
		}

		return empty( $errors ) ? true : new WP_Error( 'compliance_failed', __( 'Compliance check failed.', 'newsdesk-sitemap' ), $errors );
	}

	public function get_errors() {
		return $this->errors;
	}
}