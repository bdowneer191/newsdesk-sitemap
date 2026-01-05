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

/**
 * SOURCE: Main Plugin File (advanced-news-sitemap-pro.php logic)
 * IMPLEMENTATION: Adjusted for NewsDesk Sitemap (NDS) namespace.
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
 * * @return bool True if requirements are met, false otherwise.
 */
function nds_check_requirements() {
	// Check PHP version
	if ( version_compare( PHP_VERSION, NDS_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', function() {
			?>
			<div class="error">
				<p>
					<?php
					printf(
						/* translators: 1: Required PHP version, 2: Current PHP version */
						esc_html__( 'NewsDesk Sitemap requires PHP %1$s or higher. You are running %2$s.', 'newsdesk-sitemap' ),
						esc_html( NDS_MIN_PHP_VERSION ),
						esc_html( PHP_VERSION )
					);
					?>
				</p>
			</div>
			<?php
		} );
		return false;
	}

	// Check WordPress version
	global $wp_version;
	if ( version_compare( $wp_version, NDS_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', function() use ( $wp_version ) {
			?>
			<div class="error">
				<p>
					<?php
					printf(
						/* translators: 1: Required WordPress version, 2: Current WordPress version */
						esc_html__( 'NewsDesk Sitemap requires WordPress %1$s or higher. You are running %2$s.', 'newsdesk-sitemap' ),
						esc_html( NDS_MIN_WP_VERSION ),
						esc_html( $wp_version )
					);
					?>
				</p>
			</div>
			<?php
		} );
		return false;
	}

	return true;
}

// Require Logger early for activation tracking
require_once NDS_PLUGIN_DIR . 'includes/class-nds-logger.php';

/**
 * Activation Hook
 * Runs when plugin is activated
 */
function activate_nds() {
	try {
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-activator.php';
		NDS_Logger::log( 'Starting plugin activation...' );
		NDS_Activator::activate();
		NDS_Logger::log( 'Plugin activation successful.' );
	} catch ( Exception $e ) {
		NDS_Logger::error( 'Activation Failed: ' . $e->getMessage() );
		// In a strict Envato environment, logging is preferred over wp_die to prevent WSOD crashes.
	} catch ( Error $e ) {
		NDS_Logger::error( 'Activation Fatal Error: ' . $e->getMessage() );
	}
}
register_activation_hook( __FILE__, 'activate_nds' );

/**
 * Deactivation Hook
 * Runs when plugin is deactivated
 */
function deactivate_nds() {
	try {
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-deactivator.php';
		NDS_Deactivator::deactivate();
	} catch ( Exception $e ) {
		NDS_Logger::error( 'Deactivation Failed: ' . $e->getMessage() );
	}
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