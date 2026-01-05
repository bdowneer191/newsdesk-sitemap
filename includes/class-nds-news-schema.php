<?php
/**
 * News Schema Builder - Generates Google News compliant XML
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_News_Schema {

	/**
	 * XML namespace declarations
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $namespaces = array(
		'xmlns'              => 'http://www.sitemaps.org/schemas/sitemap/0.9',
		'xmlns:news'         => 'http://www.google.com/schemas/sitemap-news/0.9',
		'xmlns:image'        => 'http://www.google.com/schemas/sitemap-image/1.1',
		'xmlns:xhtml'        => 'http://www.w3.org/1999/xhtml',
	);

	/**
	 * Publication name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $publication_name;

	/**
	 * Publication language
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $publication_language;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->publication_name = get_option( 'nds_publication_name', get_bloginfo( 'name' ) );
		$this->publication_language = $this->format_language_code(
			get_option( 'nds_language', get_locale() )
		);
	}

	/**
	 * Build complete sitemap XML
	 *
	 * @since    1.0.0
	 * @param    array     $posts    Array of post objects with sitemap_data
	 * @return   string              Complete XML string
	 */
	public function build_sitemap( $posts ) {
		$xml = new DOMDocument( '1.0', 'UTF-8' );
		$xml->formatOutput = true;

		// Create urlset root element with namespaces
		$urlset = $xml->createElement( 'urlset' );
		foreach ( $this->namespaces as $prefix => $uri ) {
			$urlset->setAttribute( $prefix, $uri );
		}
		$xml->appendChild( $urlset );

		// Add each post as URL entry
		foreach ( $posts as $post ) {
			$url_element = $this->create_url_element( $xml, $post );
			$urlset->appendChild( $url_element );
		}

		return $xml->saveXML();
	}

	/**
	 * Build sitemap index XML
	 *
	 * @since    1.0.0
	 * @param    int       $total_pages    Total number of sitemap pages
	 * @return   string                    Complete XML string
	 */
	public function build_sitemap_index( $total_pages ) {
		$xml = new DOMDocument( '1.0', 'UTF-8' );
		$xml->formatOutput = true;

		// Create sitemapindex root element
		$sitemapindex = $xml->createElement( 'sitemapindex' );
		$sitemapindex->setAttribute( 'xmlns', $this->namespaces['xmlns'] );
		$xml->appendChild( $sitemapindex );

		$slug = get_option( 'nds_custom_slug', 'news-sitemap' );
		$base_url = home_url( '/' );

		// Add each sitemap file
		for ( $i = 1; $i <= $total_pages; $i++ ) {
			$sitemap = $xml->createElement( 'sitemap' );

			$loc = $xml->createElement( 'loc', esc_url( $base_url . $slug . '-' . $i . '.xml' ) );
			$sitemap->appendChild( $loc );

			$lastmod = $xml->createElement( 'lastmod', current_time( 'c' ) );
			$sitemap->appendChild( $lastmod );

			$sitemapindex->appendChild( $sitemap );
		}

		return $xml->saveXML();
	}

	/**
	 * Create URL element for a single post
	 *
	 * @since    1.0.0
	 * @param    DOMDocument    $xml     XML document object
	 * @param    WP_Post        $post    Post object with sitemap_data
	 * @return   DOMElement              URL element
	 */
	private function create_url_element( $xml, $post ) {
		$data = $post->sitemap_data;

		// Create <url> element
		$url = $xml->createElement( 'url' );

		// Add <loc>
		$loc = $xml->createElement( 'loc', esc_url( $data['url'] ) );
		$url->appendChild( $loc );

		// Add <news:news> element
		$news = $this->create_news_element( $xml, $data );
		$url->appendChild( $news );

		// Add image sitemap elements if images exist
		if ( ! empty( $data['images'] ) ) {
			foreach ( $data['images'] as $image ) {
				$image_element = $this->create_image_element( $xml, $image );
				$url->appendChild( $image_element );
			}
		}

		return $url;
	}

	/**
	 * Create <news:news> element
	 *
	 * @since    1.0.0
	 * @param    DOMDocument    $xml     XML document object
	 * @param    array          $data    Post sitemap data
	 * @return   DOMElement              news:news element
	 */
	private function create_news_element( $xml, $data ) {
		$news = $xml->createElement( 'news:news' );

		// <news:publication>
		$publication = $xml->createElement( 'news:publication' );

		$pub_name = $xml->createElement( 'news:name', htmlspecialchars( $this->publication_name ) );
		$publication->appendChild( $pub_name );

		$pub_lang = $xml->createElement( 'news:language', $this->publication_language );
		$publication->appendChild( $pub_lang );

		$news->appendChild( $publication );

		// <news:publication_date>
		$pub_date = $xml->createElement( 'news:publication_date', $data['publication_date'] );
		$news->appendChild( $pub_date );

		// <news:title>
		$title = $xml->createElement( 'news:title', htmlspecialchars( $data['title'] ) );
		$news->appendChild( $title );

		// <news:keywords> (optional)
		if ( ! empty( $data['keywords'] ) ) {
			$keywords = $xml->createElement( 'news:keywords', htmlspecialchars( $data['keywords'] ) );
			$news->appendChild( $keywords );
		}

		// <news:genres> (optional)
		if ( ! empty( $data['genre'] ) ) {
			$genre = $xml->createElement( 'news:genres', htmlspecialchars( $data['genre'] ) );
			$news->appendChild( $genre );
		}

		// <news:stock_tickers> (optional)
		if ( ! empty( $data['stock_tickers'] ) ) {
			$stock_tickers = $xml->createElement( 'news:stock_tickers', htmlspecialchars( $data['stock_tickers'] ) );
			$news->appendChild( $stock_tickers );
		}

		return $news;
	}

	/**
	 * Create <image:image> element
	 *
	 * @since    1.0.0
	 * @param    DOMDocument    $xml      XML document object
	 * @param    array          $image    Image data (url, caption)
	 * @return   DOMElement               image:image element
	 */
	private function create_image_element( $xml, $image ) {
		$image_element = $xml->createElement( 'image:image' );

		// <image:loc>
		$loc = $xml->createElement( 'image:loc', esc_url( $image['url'] ) );
		$image_element->appendChild( $loc );

		// <image:caption> (optional)
		if ( ! empty( $image['caption'] ) ) {
			$caption = $xml->createElement( 'image:caption', htmlspecialchars( $image['caption'] ) );
			$image_element->appendChild( $caption );
		}

		return $image_element;
	}

	/**
	 * Format language code to ISO 639-1 standard
	 *
	 * @since    1.0.0
	 * @param    string    $locale    WordPress locale code
	 * @return   string               ISO 639-1 language code
	 */
	private function format_language_code( $locale ) {
		// Extract language part before underscore
		$parts = explode( '_', $locale );
		return strtolower( $parts[0] );
	}

	/**
	 * Validate generated XML against schema
	 *
	 * @since    1.0.0
	 * @param    string           $xml    XML string
	 * @return   bool|WP_Error            True if valid, WP_Error if invalid
	 */
	public function validate_xml( $xml ) {
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadXML( $xml );

		$errors = libxml_get_errors();
		libxml_clear_errors();

		if ( ! empty( $errors ) ) {
			$error_messages = array();
			foreach ( $errors as $error ) {
				$error_messages[] = sprintf(
					'Line %d: %s',
					$error->line,
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
}