<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-1 of Complete Technical Implementation Guide (ANSP_i18n logic)
 * IMPLEMENTATION: NDS_i18n implementation for translation loading.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */
class NDS_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * Uses load_plugin_textdomain to make the plugin strings translatable.
	 * The path is calculated relative to the plugin root's languages folder.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'newsdesk-sitemap',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}