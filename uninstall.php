<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This file follows the standard WordPress uninstall procedure to ensure
 * all custom database tables, options, and file artifacts are removed.
 *
 * @package    NewsDesk_Sitemap
 */

/**
 * SOURCE: Part-1 and Part-2 of Complete Technical Implementation Guide
 * IMPLEMENTATION: Logic ensures 100% data removal for Envato compliance.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * 1. Drop custom database tables
 * These tables were created during activation in NDS_Activator::create_tables()
 */
$tables = array(
	$wpdb->prefix . 'nds_sitemap_log',
	$wpdb->prefix . 'nds_analytics',
	$wpdb->prefix . 'nds_settings',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS $table" );
}

/**
 * 2. Delete plugin options
 * Removes all settings stored in the wp_options table.
 */
$options = array(
	'nds_publication_name',
	'nds_language',
	'nds_time_limit',
	'nds_max_urls',
	'nds_excluded_categories',
	'nds_excluded_tags',
	'nds_excluded_post_types',
	'nds_min_word_count',
	'nds_breaking_news_first',
	'nds_default_genre',
	'nds_geo_location',
	'nds_copyright_info',
	'nds_ping_google',
	'nds_ping_bing',
	'nds_indexnow_enabled',
	'nds_indexnow_key',
	'nds_ping_on_update',
	'nds_ping_throttle',
	'nds_cache_duration',
	'nds_enable_object_cache',
	'nds_cdn_compatibility',
	'nds_custom_slug',
	'nds_debug_mode',
	'nds_enable_image_sitemap',
	'nds_activation_date',
	'nds_last_sitemap_generation',
	'nds_total_pings_sent',
	'nds_db_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

/**
 * 3. Clear transient caches
 * Uses prepare and esc_like for strict security compliance.
 */
$prefix = 'nds_';
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE %s 
		OR option_name LIKE %s",
		'_transient_' . $wpdb->esc_like( $prefix ) . '%',
		'_transient_timeout_' . $wpdb->esc_like( $prefix ) . '%'
	)
);

/**
 * 4. Remove cache directory
 * Safely removes the nds-cache folder in the uploads directory.
 */
$upload_dir = wp_upload_dir();
$plugin_dir = $upload_dir['basedir'] . '/nds-cache';

if ( is_dir( $plugin_dir ) ) {
	$files = glob( $plugin_dir . '/*', GLOB_MARK );
	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			unlink( $file );
		}
	}
	// Check for existing .htaccess or index.php before removal
	if ( file_exists( $plugin_dir . '/.htaccess' ) ) {
		unlink( $plugin_dir . '/.htaccess' );
	}
	if ( file_exists( $plugin_dir . '/index.php' ) ) {
		unlink( $plugin_dir . '/index.php' );
	}
	rmdir( $plugin_dir );
}