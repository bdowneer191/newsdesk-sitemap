<?php
/**
 * Fired during plugin activation
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly [cite: 10381]
}

class NDS_Activator {

	/**
	 * Plugin activation handler
	 *
	 * Creates database tables, sets default options, and flushes rewrite rules
	 * Logic synthesized from Part-1 Technical Implementation Guide [cite: 3159]
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		try {
			NDS_Logger::log( 'Starting plugin activation sequence...' );

			// Create database tables [cite: 3160]
			self::create_tables();

			// Set default options [cite: 3162]
			self::set_default_options();

			// Create necessary directories [cite: 3164]
			self::create_directories();

			// Schedule cron events [cite: 3166]
			self::schedule_events();

			// Flush rewrite rules to register our custom endpoints [cite: 3168]
			flush_rewrite_rules();

			// Log activation status [cite: 3170]
			NDS_Logger::log( 'Plugin activation successful.' );

		} catch ( Exception $e ) {
			NDS_Logger::error( 'Activation Error: ' . $e->getMessage() );

			// Set transient for admin notice display [cite: 3318]
			set_transient( 'nds_activation_error', $e->getMessage(), 45 );

			// Deactivate self to prevent broken site state in case of failure [cite: 3108]
			deactivate_plugins( NDS_PLUGIN_BASENAME );

			wp_die(
				esc_html__( 'NewsDesk Sitemap activation failed: ', 'newsdesk-sitemap' ) . esc_html( $e->getMessage() ),
				esc_html__( 'Activation Error', 'newsdesk-sitemap' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create plugin database tables using dbDelta
	 * [cite: 3178-3225]
	 *
	 * @since    1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table for ping/crawl logs [cite: 3182]
		$table_log = $wpdb->prefix . 'nds_sitemap_log';
		$sql_log   = "CREATE TABLE IF NOT EXISTS $table_log (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			action VARCHAR(50) NOT NULL,
			search_engine VARCHAR(50) NOT NULL,
			response_code INT(3) DEFAULT 0,
			response_message TEXT,
			timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_post_id (post_id),
			INDEX idx_timestamp (timestamp),
			INDEX idx_search_engine (search_engine)
		) $charset_collate;";

		// Table for analytics [cite: 3196]
		$table_analytics = $wpdb->prefix . 'nds_analytics';
		$sql_analytics   = "CREATE TABLE IF NOT EXISTS $table_analytics (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			date DATE NOT NULL,
			posts_in_sitemap INT DEFAULT 0,
			total_pings INT DEFAULT 0,
			successful_pings INT DEFAULT 0,
			failed_pings INT DEFAULT 0,
			cache_hits INT DEFAULT 0,
			cache_misses INT DEFAULT 0,
			UNIQUE KEY idx_date (date)
		) $charset_collate;";

		// Table for custom settings [cite: 3209]
		$table_settings = $wpdb->prefix . 'nds_settings';
		$sql_settings   = "CREATE TABLE IF NOT EXISTS $table_settings (
			id INT AUTO_INCREMENT PRIMARY KEY,
			setting_key VARCHAR(100) UNIQUE NOT NULL,
			setting_value LONGTEXT,
			autoload TINYINT(1) DEFAULT 1,
			last_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql_log );
		dbDelta( $sql_analytics );
		dbDelta( $sql_settings );

		update_option( 'nds_db_version', '1.0.0' );
	}

	/**
	 * Set default plugin options if they don't exist
	 * [cite: 3231-3273]
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$defaults = array(
			// General Settings [cite: 2767]
			'nds_publication_name'        => get_bloginfo( 'name' ),
			'nds_language'                => 'en',
			'nds_time_limit'              => 48,
			'nds_max_urls'                => 1000,

			// Content Filters [cite: 2772]
			'nds_excluded_categories'     => array(),
			'nds_excluded_tags'           => array(),
			'nds_excluded_post_types'     => array(),
			'nds_min_word_count'          => 80,
			'nds_breaking_news_first'     => true,

			// News Metadata [cite: 2777]
			'nds_default_genre'           => 'Blog',
			'nds_geo_location'            => '',
			'nds_copyright_info'          => '',

			// Ping Settings [cite: 2781]
			'nds_ping_google'             => true,
			'nds_ping_bing'               => true,
			'nds_indexnow_enabled'        => false,
			'nds_indexnow_key'            => '',
			'nds_ping_on_update'          => true,
			'nds_ping_throttle'           => 60,

			// Performance [cite: 2786]
			'nds_cache_duration'          => 1800,
			'nds_enable_object_cache'     => false,
			'nds_cdn_compatibility'       => false,

			// Internal Tracking [cite: 3264]
			'nds_activation_date'         => current_time( 'mysql' ),
			'nds_last_sitemap_generation' => '',
			'nds_total_pings_sent'        => 0,
		);

		foreach ( $defaults as $key => $value ) {
			add_option( $key, $value ); // Only adds if it doesn't exist [cite: 3270]
		}
	}

	/**
	 * Create cache directory for static sitemap storage
	 * [cite: 3279-3291]
	 *
	 * @since    1.0.0
	 */
	private static function create_directories() {
		$upload_dir = wp_upload_dir();
		$cache_dir  = $upload_dir['basedir'] . '/nds-cache';

		if ( ! file_exists( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
			file_put_contents( $cache_dir . '/.htaccess', "Order deny,allow\nDeny from all" );
			file_put_contents( $cache_dir . '/index.php', '<?php // Silence is golden' );
		}
	}

	/**
	 * Schedule WordPress cron events for maintenance
	 * [cite: 3297-3306]
	 *
	 * @since    1.0.0
	 */
	private static function schedule_events() {
		// Daily cleanup of old log entries [cite: 3298]
		if ( ! wp_next_scheduled( 'nds_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'nds_daily_cleanup' );
		}

		// Hourly analytics aggregation [cite: 3302]
		if ( ! wp_next_scheduled( 'nds_hourly_analytics' ) ) {
			wp_schedule_event( time(), 'hourly', 'nds_hourly_analytics' );
		}
	}
}