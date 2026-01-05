<?php
/**
 * Fired during plugin deactivation
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

class NDS_Deactivator {

	/**
	 * Plugin deactivation handler
	 *
	 * Cleans up scheduled events and flushes rewrite rules
	 * Does NOT delete data (that's done in uninstall.php)
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear scheduled cron events
		self::clear_scheduled_events();

		// Flush rewrite rules to remove our custom endpoints
		flush_rewrite_rules();

		// Clear all cached sitemaps
		self::clear_sitemap_cache();

		// Log deactivation
		self::log_deactivation();
	}

	/**
	 * Clear all scheduled WordPress cron events
	 *
	 * @since    1.0.0
	 */
	private static function clear_scheduled_events() {
		// Remove daily cleanup schedule
		$timestamp = wp_next_scheduled( 'nds_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'nds_daily_cleanup' );
		}

		// Remove hourly analytics schedule
		$timestamp = wp_next_scheduled( 'nds_hourly_analytics' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'nds_hourly_analytics' );
		}
	}

	/**
	 * Clear all sitemap transient caches
	 *
	 * @since    1.0.0
	 */
	private static function clear_sitemap_cache() {
		global $wpdb;

		// Delete all transients related to sitemap
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_nds_sitemap_%'
			OR option_name LIKE '_transient_timeout_nds_sitemap_%'"
		);
	}

	/**
	 * Log plugin deactivation
	 *
	 * @since    1.0.0
	 */
	private static function log_deactivation() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NewsDesk Sitemap deactivated at ' . current_time( 'mysql' ) );
		}
	}
}