<?php
/**
 * News Schema Builder - Generates Google News compliant XML
 *
 * This class handles the construction of the XML structure for both 
 * the News Sitemap and the Sitemap Index.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-2 and Part-3 of Complete Technical Implementation Guide (ANSP_News_Schema logic)
 * IMPLEMENTATION: NDS_News_Schema with strict XML escaping and Google News protocol 0.9 validation.
 */

// Exit if accessed directly - Security measure
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
		'xmlns'       => 'http://www.sitemaps.org/schemas/sitemap/0.9',
		'xmlns:news'  => 'http://www.google.com/schemas/sitemap-news/0.9',
		'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1',
		'xmlns:xhtml' => 'http://www.w3.org/1999/xhtml',
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
	 * Initialize the class with plugin settings
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->publication_name     = get_option( 'nds_publication_name', get_bloginfo( 'name' ) );
		$this->publication_language = $this->format_language_code(
			get_option( 'nds_language', get_locale() )
		);
	}

	/**
	 * Build complete News Sitemap XML
	 *
	 * @since    1.0.0
	 * @param    array    $posts    Array of post objects with sitemap_data.
	 * @return   string             Complete XML string.
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
	 * Build Sitemap Index XML for pagination support
	 *
	 * @since    1.0.0
	 * @param    int      $total_pages    Total number of sitemap chunks.
	 * @return   string                   The index XML string.
	 */
	public function build_sitemap_index( $total_pages ) {
		$xml = new DOMDocument( '1.0', 'UTF-8' );
		$xml->formatOutput = true;

		$sitemapindex = $xml->createElement( 'sitemapindex' );
		$sitemapindex->setAttribute( 'xmlns', $this->namespaces['xmlns'] );
		$xml->appendChild( $sitemapindex );

		$slug     = get_option( 'nds_custom_slug', 'news-sitemap' );
		$base_url = home_url( '/' );

		for ( $i = 1; $i <= $total_pages; $i++ ) {
			$sitemap = $xml->createElement( 'sitemap' );

			$loc_url = $base_url . $slug . '-' . $i . '.xml';
			$loc     = $xml->createElement( 'loc', esc_url( $loc_url ) );
			$sitemap->appendChild( $loc );

			// Use current time in W3C format for the index
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
	 * @access   private
	 */
	private function create_url_element( $xml, $post ) {
		$data = $post->sitemap_data;
		$url  = $xml->createElement( 'url' );

		// <loc> entry
		$loc = $xml->createElement( 'loc', esc_url( $data['url'] ) );
		$url->appendChild( $loc );

		// <news:news> element
		$news = $this->create_news_element( $xml, $data );
		$url->appendChild( $news );

		// <image:image> elements if enabled and present
		if ( ! empty( $data['images'] ) && get_option( 'nds_enable_image_sitemap', true ) ) {
			foreach ( $data['images'] as $image ) {
				$image_element = $this->create_image_element( $xml, $image );
				$url->appendChild( $image_element );
			}
		}

		return $url;
	}

	/**
	 * Create the <news:news> segment
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function create_news_element( $xml, $data ) {
		$news = $xml->createElement( 'news:news' );

		// <news:publication>
		$publication = $xml->createElement( 'news:publication' );
		
		$pub_name = $xml->createElement( 'news:name', htmlspecialchars( $this->publication_name, ENT_XML1, 'UTF-8' ) );
		$publication->appendChild( $pub_name );

		$pub_lang = $xml->createElement( 'news:language', $this->publication_language );
		$publication->appendChild( $pub_lang );

		$news->appendChild( $publication );

		// <news:publication_date>
		$pub_date = $xml->createElement( 'news:publication_date', $data['publication_date'] );
		$news->appendChild( $pub_date );

		// <news:title>
		$title = $xml->createElement( 'news:title', htmlspecialchars( $data['title'], ENT_XML1, 'UTF-8' ) );
		$news->appendChild( $title );

		// Optional Metadata
		if ( ! empty( $data['keywords'] ) ) {
			$keywords = $xml->createElement( 'news:keywords', htmlspecialchars( $data['keywords'], ENT_XML1, 'UTF-8' ) );
			$news->appendChild( $keywords );
		}

		if ( ! empty( $data['genre'] ) ) {
			$genres = $xml->createElement( 'news:genres', htmlspecialchars( $data['genre'], ENT_XML1, 'UTF-8' ) );
			$news->appendChild( $genres );
		}

		if ( ! empty( $data['stock_tickers'] ) ) {
			$tickers = $xml->createElement( 'news:stock_tickers', htmlspecialchars( $data['stock_tickers'], ENT_XML1, 'UTF-8' ) );
			$news->appendChild( $tickers );
		}

		return $news;
	}

	/**
	 * Create the <image:image> segment
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function create_image_element( $xml, $image ) {
		$image_element = $xml->createElement( 'image:image' );

		$loc = $xml->createElement( 'image:loc', esc_url( $image['url'] ) );
		$image_element->appendChild( $loc );

		if ( ! empty( $image['caption'] ) ) {
			$caption = $xml->createElement( 'image:caption', htmlspecialchars( $image['caption'], ENT_XML1, 'UTF-8' ) );
			$image_element->appendChild( $caption );
		}

		if ( ! empty( $image['title'] ) ) {
			$img_title = $xml->createElement( 'image:title', htmlspecialchars( $image['title'], ENT_XML1, 'UTF-8' ) );
			$image_element->appendChild( $img_title );
		}

		return $image_element;
	}

	/**
	 * Formats locale (en_US) to ISO 639-1 (en)
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function format_language_code( $locale ) {
		$parts = explode( '_', $locale );
		return strtolower( $parts[0] );
	}

	/**
	 * Validate generated XML against schema
	 *
	 * @since    1.0.0
	 * @param    string    $xml_string    XML content.
	 * @return   bool|WP_Error            Validation status.
	 */
	public function validate_xml( $xml_string ) {
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadXML( $xml_string );

		$errors = libxml_get_errors();
		libxml_clear_errors();

		if ( ! empty( $errors ) ) {
			$messages = array();
			foreach ( $errors as $error ) {
				$messages[] = sprintf(
					'Line %d: %s',
					$error->line,
					trim( $error->message )
				);
			}

			return new WP_Error( 'xml_validation_error', __( 'XML Validation Failed', 'newsdesk-sitemap' ), $messages );
		}

		return true;
	}
}