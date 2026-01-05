<?php
/**
 * Fired during plugin activation
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

class NDS_Activator {

	/**
	 * Plugin activation handler
	 *
	 * Creates database tables, sets default options, and flushes rewrite rules
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Create database tables
		self::create_tables();

		// Set default options
		self::set_default_options();

		// Create necessary directories
		self::create_directories();

		// Schedule cron events
		self::schedule_events();

		// Flush rewrite rules to register our custom endpoints
		flush_rewrite_rules();

		// Log activation
		self::log_activation();
	}

	/**
	 * Create plugin database tables
	 *
	 * @since    1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table for ping/crawl logs
		$table_log = $wpdb->prefix . 'nds_sitemap_log';
		$sql_log = "CREATE TABLE IF NOT EXISTS $table_log (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			action VARCHAR(50) NOT NULL,
			search_engine VARCHAR(50) NOT NULL,
			response_code INT(3),
			response_message TEXT,
			timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_post_id (post_id),
			INDEX idx_timestamp (timestamp),
			INDEX idx_search_engine (search_engine)
		) $charset_collate;";

		// Table for analytics
		$table_analytics = $wpdb->prefix . 'nds_analytics';
		$sql_analytics = "CREATE TABLE IF NOT EXISTS $table_analytics (
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

		// Table for custom settings (alternative to wp_options for complex data)
		$table_settings = $wpdb->prefix . 'nds_settings';
		$sql_settings = "CREATE TABLE IF NOT EXISTS $table_settings (
			id INT AUTO_INCREMENT PRIMARY KEY,
			setting_key VARCHAR(100) UNIQUE NOT NULL,
			setting_value LONGTEXT,
			autoload TINYINT(1) DEFAULT 1,
			last_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) $charset_collate;";

		// Include WordPress upgrade script
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Execute table creation
		dbDelta( $sql_log );
		dbDelta( $sql_analytics );
		dbDelta( $sql_settings );

		// Store database version for future migrations
		update_option( 'nds_db_version', '1.0.0' );
	}

	/**
	 * Set default plugin options
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		// Only set if not already set (for re-activation)
		$defaults = array(
			// General Settings
			'nds_publication_name'       => get_bloginfo( 'name' ),
			'nds_language'               => get_locale(),
			'nds_time_limit'             => 48, // hours
			'nds_max_urls'               => 1000,

			// Content Filters
			'nds_excluded_categories'    => array(),
			'nds_excluded_tags'          => array(),
			'nds_excluded_post_types'    => array(),
			'nds_min_word_count'         => 80,
			'nds_breaking_news_first'    => true,

			// News Metadata
			'nds_default_genre'          => 'Blog',
			'nds_geo_location'           => '',
			'nds_copyright_info'         => '',

			// Ping Settings
			'nds_ping_google'            => true,
			'nds_ping_bing'              => true,
			'nds_indexnow_enabled'       => false,
			'nds_indexnow_key'           => '',
			'nds_ping_on_update'         => true,
			'nds_ping_throttle'          => 60, // seconds between pings

			// Performance
			'nds_cache_duration'         => 1800, // 30 minutes
			'nds_enable_object_cache'    => false,
			'nds_cdn_compatibility'      => false,

			// Advanced
			'nds_custom_slug'            => 'news-sitemap',
			'nds_debug_mode'             => false,
			'nds_enable_image_sitemap'   => true,

			// Internal tracking
			'nds_activation_date'        => current_time( 'mysql' ),
			'nds_last_sitemap_generation'=> '',
			'nds_total_pings_sent'       => 0
		);

		foreach ( $defaults as $key => $value ) {
			// add_option only adds if it doesn't exist
			add_option( $key, $value );
		}
	}

	/**
	 * Create necessary plugin directories
	 *
	 * @since    1.0.0
	 */
	private static function create_directories() {
		$upload_dir = wp_upload_dir();
		$plugin_dir = $upload_dir['basedir'] . '/nds-cache';

		// Create cache directory if it doesn't exist
		if ( ! file_exists( $plugin_dir ) ) {
			wp_mkdir_p( $plugin_dir );

			// Create .htaccess to protect directory
			$htaccess_content = "Order deny,allow\nDeny from all";
			file_put_contents( $plugin_dir . '/.htaccess', $htaccess_content );

			// Create index.php to prevent directory listing
			file_put_contents( $plugin_dir . '/index.php', '<?php // Silence is golden' );
		}
	}

	/**
	 * Schedule WordPress cron events
	 *
	 * @since    1.0.0
	 */
	private static function schedule_events() {
		// Schedule daily cleanup of old log entries (keep last 30 days)
		if ( ! wp_next_scheduled( 'nds_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'nds_daily_cleanup' );
		}

		// Schedule hourly analytics aggregation
		if ( ! wp_next_scheduled( 'nds_hourly_analytics' ) ) {
			wp_schedule_event( time(), 'hourly', 'nds_hourly_analytics' );
		}
	}

	/**
	 * Log plugin activation
	 *
	 * @since    1.0.0
	 */
	private static function log_activation() {
		// Log to file for debugging if needed
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NewsDesk Sitemap activated at ' . current_time( 'mysql' ) );
		}

		// Store activation flag for showing welcome screen
		set_transient( 'nds_activation_redirect', true, 30 );
	}
}