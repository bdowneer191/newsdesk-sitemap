<?php
/**
 * Fired during plugin deactivation
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-1 of Complete Technical Implementation Guide (ANSP_Deactivator logic)
 * IMPLEMENTATION: Adapted for NDS namespace with strict security enhancements.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Deactivator {

	/**
	 * Plugin deactivation handler
	 *
	 * Cleans up scheduled events and flushes rewrite rules.
	 * Does NOT delete data (that's handled in uninstall.php). [cite: 3344]
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear scheduled cron events [cite: 3349]
		self::clear_scheduled_events();

		// Flush rewrite rules to remove our custom endpoints [cite: 3351]
		flush_rewrite_rules();

		// Clear all cached sitemaps [cite: 3353]
		self::clear_sitemap_cache();

		// Log deactivation for troubleshooting [cite: 3355]
		self::log_deactivation();
	}

	/**
	 * Clear all scheduled WordPress cron events registered by the plugin
	 * [cite: 3363-3374]
	 *
	 * @since    1.0.0
	 */
	private static function clear_scheduled_events() {
		// Remove daily cleanup schedule
		$timestamp_cleanup = wp_next_scheduled( 'nds_daily_cleanup' );
		if ( $timestamp_cleanup ) {
			wp_unschedule_event( $timestamp_cleanup, 'nds_daily_cleanup' );
		}

		// Remove hourly analytics schedule
		$timestamp_analytics = wp_next_scheduled( 'nds_hourly_analytics' );
		if ( $timestamp_analytics ) {
			wp_unschedule_event( $timestamp_analytics, 'nds_hourly_analytics' );
		}
	}

	/**
	 * Clear all sitemap transient caches
	 * Enhanced with $wpdb->prepare for security compliance. [cite: 3380-3387]
	 *
	 * @since    1.0.0
	 */
	private static function clear_sitemap_cache() {
		global $wpdb;

		$prefix = 'nds_sitemap_';

		/** * Use prepare and esc_like for strict security compliance. 
		 * This targets transients created by set_transient().
		 */
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				'_transient_' . $wpdb->esc_like( $prefix ) . '%',
				'_transient_timeout_' . $wpdb->esc_like( $prefix ) . '%'
			)
		);
	}

	/**
	 * Log plugin deactivation timestamp if debugging is enabled
	 * [cite: 3394-3398]
	 *
	 * @since    1.0.0
	 */
	private static function log_deactivation() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && WP_DEBUG_LOG ) {
			error_log( 'NewsDesk Sitemap deactivated at ' . current_time( 'mysql' ) );
		}
	}
}