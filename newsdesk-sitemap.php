<?php
/**
 * Plugin Name: NewsDesk Sitemap
 * Plugin URI: https://yoursite.com/newsdesk-sitemap
 * Description: Professional-grade news sitemap generator for maximum search exposure. Complies with Google News, Bing News, and IndexNow protocols.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: newsdesk-sitemap
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Constants
 * These are used throughout the plugin for paths and versioning
 */
define( 'NDS_VERSION', '1.0.0' );
define( 'NDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NDS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'NDS_PLUGIN_FILE', __FILE__ );
define( 'NDS_MIN_PHP_VERSION', '7.4' );
define( 'NDS_MIN_WP_VERSION', '5.8' );

/**
 * Check minimum requirements before loading
 */
function nds_check_requirements() {
	// Check PHP version
	if ( version_compare( PHP_VERSION, NDS_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="error"><p>';
			printf(
				esc_html__( 'NewsDesk Sitemap requires PHP %s or higher. You are running %s.', 'newsdesk-sitemap' ),
				NDS_MIN_PHP_VERSION,
				PHP_VERSION
			);
			echo '</p></div>';
		} );
		return false;
	}

	// Check WordPress version
	global $wp_version;
	if ( version_compare( $wp_version, NDS_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', function() use ( $wp_version ) {
			echo '<div class="error"><p>';
			printf(
				esc_html__( 'NewsDesk Sitemap requires WordPress %s or higher. You are running %s.', 'newsdesk-sitemap' ),
				NDS_MIN_WP_VERSION,
				$wp_version
			);
			echo '</p></div>';
		} );
		return false;
	}

	return true;
}

/**
 * Activation Hook
 * Runs when plugin is activated
 */
function activate_nds() {
	require_once NDS_PLUGIN_DIR . 'includes/class-nds-activator.php';
	NDS_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_nds' );

/**
 * Deactivation Hook
 * Runs when plugin is deactivated
 */
function deactivate_nds() {
	require_once NDS_PLUGIN_DIR . 'includes/class-nds-deactivator.php';
	NDS_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_nds' );

/**
 * Load the main plugin class
 * Only if requirements are met
 */
if ( nds_check_requirements() ) {
	require_once NDS_PLUGIN_DIR . 'includes/class-nds-core.php';

	/**
	 * Initialize the plugin
	 */
	function run_nds() {
		$plugin = new NDS_Core();
		$plugin->run();
	}

	// Start the plugin
	run_nds();
}