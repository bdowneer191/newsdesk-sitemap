<?php
/**
 * The public-facing functionality of the plugin.
 *
 * This class handles frontend-specific assets and logic. While the News 
 * Sitemap is primarily XML-based, this class allows for the inclusion 
 * of styles (XSLT) or frontend discovery features.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/public
 */

/**
 * SOURCE: Logic synthesized from WP Best Practices and NDS_Core dependencies.
 * IMPLEMENTATION: NDS_Public_Sitemap placeholder for frontend asset orchestration.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Public_Sitemap {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The unique ID for script/style handles.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version        The current version for cache-busting.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 [cite_start]* [cite: 3439-3453]
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * News sitemaps are typically XML. Styles should only be enqueued 
		 * if we are serving a human-readable discovery page or if an XSL 
		 * stylesheet requires external CSS dependencies.
		 */
		$style_url = NDS_PLUGIN_URL . 'public/css/nds-public.css';

		// Register the style for later use or selective enqueuing
		wp_register_style(
			$this->plugin_name,
			esc_url( $style_url ),
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$script_url = NDS_PLUGIN_URL . 'public/js/nds-public.js';

		// Register the script for later use
		wp_register_script(
			$this->plugin_name,
			esc_url( $script_url ),
			array( 'jquery' ),
			$this->version,
			false
		);
	}
}