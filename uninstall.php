<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    NewsDesk_Sitemap
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Drop custom tables
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nds_sitemap_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nds_analytics" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nds_settings" );

// Delete plugin options
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
	'nds_db_version'
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear transients
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	WHERE option_name LIKE '_transient_nds_%'
	OR option_name LIKE '_transient_timeout_nds_%'"
);

// Remove directory if empty
$upload_dir = wp_upload_dir();
$plugin_dir = $upload_dir['basedir'] . '/nds-cache';
if ( is_dir( $plugin_dir ) ) {
	// Simple cleanup: try to remove files first
	$files = glob( $plugin_dir . '/*' );
	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			unlink( $file );
		}
	}
	rmdir( $plugin_dir );
}