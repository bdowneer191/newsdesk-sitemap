<?php
/**
 * Admin settings page controller
 *
 * This class handles the registration of plugin settings, the admin menu,
 * and the orchestration of the settings interface.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SOURCE: Section 15 of Part-4 of Complete Technical Implementation Guide
 * IMPLEMENTATION: NDS_Admin_Settings with Google Search Console API, 
 * Mandatory Approval, and Author Exclusion features.
 */
class NDS_Admin_Settings {

	/**
	 * The ID of this plugin.
	 *
	 * @access   private
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @access   private
	 * @var      string    $version
	 */
	private $version;

	/**
	 * Security utility instance.
	 *
	 * @access   private
	 * @var      NDS_Security    $security
	 */
	private $security;

	/**
	 * Validation utility instance.
	 *
	 * @access   private
	 * @var      NDS_Validation    $validator
	 */
	private $validator;

	/**
	 * Initialize the class and set its properties.
	 [cite_start]* [cite: 1750-1761]
	 *
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->security    = new NDS_Security();
		$this->validator   = new NDS_Validation();
	}

	/**
	 * Register admin menu and submenus.
	 [cite_start]* [cite: 1763-1814]
	 */
	public function add_plugin_admin_menu() {
		// Main settings page
		add_menu_page(
			__( 'News Sitemap', 'newsdesk-sitemap' ),
			__( 'News Sitemap', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-settings',
			array( $this, 'display_settings_page' ),
			'dashicons-rss',
			80
		);

		add_submenu_page(
			'nds-settings',
			__( 'Settings', 'newsdesk-sitemap' ),
			__( 'Settings', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'nds-settings',
			__( 'Dashboard', 'newsdesk-sitemap' ),
			__( 'Dashboard', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-dashboard',
			array( $this, 'display_dashboard_page' )
		);

		add_submenu_page(
			'nds-settings',
			__( 'Validation', 'newsdesk-sitemap' ),
			__( 'Validation', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-validation',
			array( $this, 'display_validation_page' )
		);

		add_submenu_page(
			'nds-settings',
			__( 'Logs', 'newsdesk-sitemap' ),
			__( 'Logs', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-logs',
			array( $this, 'display_logs_page' )
		);
	}

	/**
	 * Register plugin settings with sanitize callbacks.
	 [cite_start]* [cite: 1816-1897]
	 */
	public function register_settings() {
		// --- General Settings Section ---
		add_settings_section( 
			'nds_general_section', 
			__( 'General Configuration', 'newsdesk-sitemap' ), 
			array( $this, 'render_general_section' ), 
			'nds-settings-general' 
		);
		register_setting( 'nds_general_options', 'nds_publication_name', array( 'sanitize_callback' => array( $this->security, 'sanitize_text' ) ) );
		register_setting( 'nds_general_options', 'nds_language', array( 'sanitize_callback' => array( $this->security, 'sanitize_text' ) ) );
		register_setting( 'nds_general_options', 'nds_time_limit', array( 'sanitize_callback' => array( $this->security, 'sanitize_int' ) ) );
		register_setting( 'nds_general_options', 'nds_max_urls', array( 'sanitize_callback' => array( $this->security, 'sanitize_int' ) ) );
		register_setting( 'nds_general_options', 'nds_included_post_types', array( 'sanitize_callback' => array( $this->security, 'sanitize_text_array' ) ) );
		register_setting( 'nds_general_options', 'nds_require_approval', array( 'sanitize_callback' => array( $this->security, 'sanitize_bool' ) ) );

		// --- Content Filters Section ---
		add_settings_section( 
			'nds_filters_section', 
			__( 'Sitemap Filters', 'newsdesk-sitemap' ), 
			array( $this, 'render_filters_section' ), 
			'nds-settings-filters' 
		);
		register_setting( 'nds_filter_options', 'nds_excluded_categories', array( 'sanitize_callback' => array( $this->security, 'sanitize_int_array' ) ) );
		register_setting( 'nds_filter_options', 'nds_excluded_tags', array( 'sanitize_callback' => array( $this->security, 'sanitize_int_array' ) ) );
		register_setting( 'nds_filter_options', 'nds_excluded_authors', array( 'sanitize_callback' => array( $this->security, 'sanitize_int_array' ) ) );
		register_setting( 'nds_filter_options', 'nds_min_word_count', array( 'sanitize_callback' => array( $this->security, 'sanitize_int' ) ) );

		// --- Ping & API Settings Section ---
		add_settings_section( 
			'nds_ping_section', 
			__( 'Search Engine Notifications', 'newsdesk-sitemap' ), 
			array( $this, 'render_ping_section' ), 
			'nds-settings-ping' 
		);
		register_setting( 'nds_ping_options', 'nds_ping_google', array( 'sanitize_callback' => array( $this->security, 'sanitize_bool' ) ) );
		register_setting( 'nds_ping_options', 'nds_ping_bing', array( 'sanitize_callback' => array( $this->security, 'sanitize_bool' ) ) );
		register_setting( 'nds_ping_options', 'nds_indexnow_enabled', array( 'sanitize_callback' => array( $this->security, 'sanitize_bool' ) ) );
		register_setting( 'nds_ping_options', 'nds_indexnow_key', array( 'sanitize_callback' => array( $this->security, 'sanitize_text' ) ) );
		register_setting( 'nds_ping_options', 'nds_gsc_credentials_json', array( 'sanitize_callback' => array( $this->security, 'sanitize_textarea' ) ) );

		// --- Performance Settings Section ---
		add_settings_section( 
			'nds_performance_section', 
			__( 'Performance Optimization', 'newsdesk-sitemap' ), 
			array( $this, 'render_performance_section' ), 
			'nds-settings-performance' 
		);
		register_setting( 'nds_performance_options', 'nds_cache_duration', array( 'sanitize_callback' => array( $this->security, 'sanitize_int' ) ) );
		register_setting( 'nds_performance_options', 'nds_enable_object_cache', array( 'sanitize_callback' => array( $this->security, 'sanitize_bool' ) ) );
		register_setting( 'nds_performance_options', 'nds_cdn_compatibility', array( 'sanitize_callback' => array( $this->security, 'sanitize_bool' ) ) );
	}

	/**
	 * Main Settings Page Loader.
	 [cite_start]* [cite: 1899-1912]
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		include NDS_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Dashboard Loader.
	 [cite_start]* [cite: 1914-1926]
	 */
	public function display_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$analytics  = new NDS_Analytics();
		$summary    = $analytics->get_summary();
		$chart_data = $analytics->get_chart_data( 30 );

		include NDS_PLUGIN_DIR . 'admin/views/dashboard-page.php';
	}

	/**
	 * Validation Tool Loader.
	 [cite_start]* [cite: 1928-1937]
	 */
	public function display_validation_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include NDS_PLUGIN_DIR . 'admin/views/validation-page.php';
	}

	/**
	 * Logs Loader.
	 [cite_start]* [cite: 1939-1950]
	 */
	public function display_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$analytics = new NDS_Analytics();
		$logs      = $analytics->get_recent_pings( 100 );
		include NDS_PLUGIN_DIR . 'admin/views/logs-page.php';
	}

	/**
	 * General Section Callback.
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure core requirements for Google News and editorial approval settings.', 'newsdesk-sitemap' ) . '</p>';
	}

	/**
	 * Filters Section Callback.
	 */
	public function render_filters_section() {
		echo '<p>' . esc_html__( 'Restrict specific categories, authors, or short content from being indexed in the sitemap.', 'newsdesk-sitemap' ) . '</p>';
	}

	/**
	 * Ping Section Callback with Google API Documentation.
	 */
	public function render_ping_section() {
		echo '<p>' . esc_html__( 'Configure automated notifications for search engines.', 'newsdesk-sitemap' ) . '</p>';
		
		echo '<div class="notice notice-info inline" style="margin-top: 20px; padding: 15px; border-left-width: 4px;">';
		echo '<h4 style="margin-top:0;">' . esc_html__( 'Enterprise Setup: Google Search Console API', 'newsdesk-sitemap' ) . '</h4>';
		echo '<p>' . esc_html__( 'Using the official API is recommended over legacy pings for reliable indexing.', 'newsdesk-sitemap' ) . '</p>';
		echo '<ol style="margin-bottom:0;">';
		echo '<li>' . sprintf( __( 'Go to the %s.', 'newsdesk-sitemap' ), '<a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>' ) . '</li>';
		echo '<li>' . __( 'Enable the <strong>Google Search Console API</strong> for your project.', 'newsdesk-sitemap' ) . '</li>';
		echo '<li>' . __( 'Create a <strong>Service Account</strong>, generate a <strong>JSON Key</strong>, and download it.', 'newsdesk-sitemap' ) . '</li>';
		echo '<li>' . __( 'Paste the contents of that JSON file into the credentials field below.', 'newsdesk-sitemap' ) . '</li>';
		echo '<li>' . __( 'Add the Service Account email address as a <strong>Full User</strong> in your Google Search Console property settings.', 'newsdesk-sitemap' ) . '</li>';
		echo '</ol>';
		echo '</div>';
	}

	/**
	 * Performance Section Callback.
	 */
	public function render_performance_section() {
		echo '<p>' . esc_html__( 'Optimize resource usage, caching behavior, and CDN compatibility.', 'newsdesk-sitemap' ) . '</p>';
	}

	/**
	 * Add action links on plugins page.
	 [cite_start]* [cite: 2076-2090]
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=nds-settings' ), __( 'Settings', 'newsdesk-sitemap' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Display admin notices for plugin health.
	 [cite_start]* [cite: 2092-2115]
	 */
	public function display_admin_notices() {
		// Activation Success Notice
		if ( get_transient( 'nds_activation_redirect' ) ) {
			delete_transient( 'nds_activation_redirect' );
			printf( 
				'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>', 
				esc_html__( 'NewsDesk Sitemap has been activated!', 'newsdesk-sitemap' ), 
				esc_url( admin_url( 'admin.php?page=nds-settings' ) ), 
				esc_html__( 'Configure settings', 'newsdesk-sitemap' ) 
			);
		}

		// Missing Publication Name Warning
		if ( empty( get_option( 'nds_publication_name', '' ) ) ) {
			printf( 
				'<div class="notice notice-warning"><p>%s</p></div>', 
				esc_html__( 'Important: Please set your publication name in News Sitemap settings to comply with Google News requirements.', 'newsdesk-sitemap' ) 
			);
		}
	}
}