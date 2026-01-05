<?php
/**
 * Admin settings page controller
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Admin_Settings {

	/**
	 * Plugin name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $version;

	/**
	 * Security handler
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_Security
	 */
	private $security;

	/**
	 * Validation handler
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      NDS_Validation
	 */
	private $validator;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    Plugin name
	 * @param    string    $version        Plugin version
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->security    = new NDS_Security();
		$this->validator   = new NDS_Validation();
	}

	/**
	 * Register admin menu
	 *
	 * @since    1.0.0
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

		// Settings submenu
		add_submenu_page(
			'nds-settings',
			__( 'Settings', 'newsdesk-sitemap' ),
			__( 'Settings', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-settings',
			array( $this, 'display_settings_page' )
		);

		// Dashboard submenu
		add_submenu_page(
			'nds-settings',
			__( 'Dashboard', 'newsdesk-sitemap' ),
			__( 'Dashboard', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-dashboard',
			array( $this, 'display_dashboard_page' )
		);

		// Validation submenu
		add_submenu_page(
			'nds-settings',
			__( 'Validation', 'newsdesk-sitemap' ),
			__( 'Validation', 'newsdesk-sitemap' ),
			'manage_options',
			'nds-validation',
			array( $this, 'display_validation_page' )
		);

		// Logs submenu
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
	 * Register plugin settings
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// General Settings Section
		add_settings_section(
			'nds_general_section',
			__( 'General Settings', 'newsdesk-sitemap' ),
			array( $this, 'render_general_section' ),
			'nds-settings-general'
		);

		register_setting( 'nds_general_options', 'nds_publication_name', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this->security, 'sanitize_text' ),
		) );

		register_setting( 'nds_general_options', 'nds_language', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this->security, 'sanitize_text' ),
		) );

		register_setting( 'nds_general_options', 'nds_time_limit', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this->security, 'sanitize_int' ),
		) );

		register_setting( 'nds_general_options', 'nds_max_urls', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this->security, 'sanitize_int' ),
		) );

		// Content Filters Section
		add_settings_section(
			'nds_filters_section',
			__( 'Content Filters', 'newsdesk-sitemap' ),
			array( $this, 'render_filters_section' ),
			'nds-settings-filters'
		);

		register_setting( 'nds_filter_options', 'nds_excluded_categories', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this->security, 'sanitize_int_array' ),
		) );

		register_setting( 'nds_filter_options', 'nds_excluded_tags', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this->security, 'sanitize_int_array' ),
		) );

		register_setting( 'nds_filter_options', 'nds_min_word_count', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this->security, 'sanitize_int' ),
		) );

		// Ping Settings Section
		add_settings_section(
			'nds_ping_section',
			__( 'Ping Settings', 'newsdesk-sitemap' ),
			array( $this, 'render_ping_section' ),
			'nds-settings-ping'
		);

		register_setting( 'nds_ping_options', 'nds_ping_google', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this->security, 'sanitize_bool' ),
		) );

		register_setting( 'nds_ping_options', 'nds_ping_bing', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this->security, 'sanitize_bool' ),
		) );

		register_setting( 'nds_ping_options', 'nds_indexnow_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this->security, 'sanitize_bool' ),
		) );

		// Performance Settings Section
		add_settings_section(
			'nds_performance_section',
			__( 'Performance Settings', 'newsdesk-sitemap' ),
			array( $this, 'render_performance_section' ),
			'nds-settings-performance'
		);

		register_setting( 'nds_performance_options', 'nds_cache_duration', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this->security, 'sanitize_int' ),
		) );

		register_setting( 'nds_performance_options', 'nds_enable_object_cache', array(
			'type'              => 'boolean',
			'sanitize_callback' => array( $this->security, 'sanitize_bool' ),
		) );
	}

	/**
	 * Display main settings page
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get active tab
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

		// Load view
		include NDS_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Display dashboard page
	 *
	 * @since    1.0.0
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
	 * Display validation page
	 *
	 * @since    1.0.0
	 */
	public function display_validation_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include NDS_PLUGIN_DIR . 'admin/views/validation-page.php';
	}

	/**
	 * Display logs page
	 *
	 * @since    1.0.0
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
	 * Enqueue admin styles
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();

		if ( strpos( $screen->id, 'nds' ) !== false ) {
			wp_enqueue_style(
				$this->plugin_name . '-admin',
				NDS_PLUGIN_URL . 'admin/assets/css/admin-style.css',
				array(),
				$this->version
			);
		}
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( strpos( $screen->id, 'nds' ) !== false ) {
			wp_enqueue_script(
				$this->plugin_name . '-admin',
				NDS_PLUGIN_URL . 'admin/assets/js/admin-script.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			// Localize script with AJAX URL and nonces
			wp_localize_script(
				$this->plugin_name . '-admin',
				'nds_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => $this->security->create_nonce( 'nds_ajax' ),
					'strings'  => array(
						'validating' => __( 'Validating...', 'newsdesk-sitemap' ),
						'success'    => __( 'Success!', 'newsdesk-sitemap' ),
						'error'      => __( 'Error occurred', 'newsdesk-sitemap' ),
					),
				)
			);
		}
	}

	/**
	 * AJAX handler for sitemap validation
	 *
	 * @since    1.0.0
	 */
	public function ajax_validate_sitemap() {
		// Security check
		if ( ! $this->security->verify_nonce( 'nds_ajax' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'newsdesk-sitemap' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'newsdesk-sitemap' ) ) );
		}

		// Get sitemap generator
		$sitemap_generator = new NDS_Sitemap_Generator();
		$sitemap_url       = $sitemap_generator->get_sitemap_url();

		// Fetch sitemap
		$response = wp_remote_get( $sitemap_url );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Could not fetch sitemap', 'newsdesk-sitemap' ),
					'error'   => $response->get_error_message(),
				)
			);
		}

		$xml = wp_remote_retrieve_body( $response );

		// Validate XML
		$validation_result = $this->validator->validate_google_news_compliance( $xml );

		if ( is_wp_error( $validation_result ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Validation failed', 'newsdesk-sitemap' ),
					'errors'  => $validation_result->get_error_data(),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Sitemap is valid!', 'newsdesk-sitemap' ),
				'url'     => $sitemap_url,
			)
		);
	}

	/**
	 * AJAX handler for manual ping
	 *
	 * @since    1.0.0
	 */
	public function ajax_manual_ping() {
		// Security check
		if ( ! $this->security->verify_nonce( 'nds_ajax' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'newsdesk-sitemap' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'newsdesk-sitemap' ) ) );
		}

		// Perform ping
		$ping_service = new NDS_Ping_Service();
		$results      = $ping_service->manual_ping();

		wp_send_json_success(
			array(
				'message' => __( 'Ping completed', 'newsdesk-sitemap' ),
				'results' => $results,
			)
		);
	}

	/**
	 * AJAX handler for cache clearing
	 *
	 * @since    1.0.0
	 */
	public function ajax_clear_cache() {
		// Security check
		if ( ! $this->security->verify_nonce( 'nds_ajax' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'newsdesk-sitemap' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'newsdesk-sitemap' ) ) );
		}

		// Clear cache
		$cache_manager = new NDS_Cache_Manager();
		$cache_manager->clear_all();

		wp_send_json_success(
			array(
				'message' => __( 'Cache cleared successfully', 'newsdesk-sitemap' ),
			)
		);
	}

	/**
	 * Add settings link on plugins page
	 *
	 * @since    1.0.0
	 * @param    array    $links    Existing links
	 * @return   array              Modified links
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=nds-settings' ),
			__( 'Settings', 'newsdesk-sitemap' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Display admin notices
	 *
	 * @since    1.0.0
	 */
	public function display_admin_notices() {
		// Check if activation redirect flag is set
		if ( get_transient( 'nds_activation_redirect' ) ) {
			delete_transient( 'nds_activation_redirect' );

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'NewsDesk Sitemap has been activated!', 'newsdesk-sitemap' ),
				esc_url( admin_url( 'admin.php?page=nds-settings' ) ),
				esc_html__( 'Configure settings', 'newsdesk-sitemap' )
			);
		}

		// Check if publication name matches requirement
		$publication_name = get_option( 'nds_publication_name', '' );
		if ( empty( $publication_name ) ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'Please set your publication name in News Sitemap settings to comply with Google News requirements.', 'newsdesk-sitemap' )
			);
		}
	}

	// Section render callbacks
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure basic sitemap settings.', 'newsdesk-sitemap' ) . '</p>';
	}

	public function render_filters_section() {
		echo '<p>' . esc_html__( 'Control which content appears in your news sitemap.', 'newsdesk-sitemap' ) . '</p>';
	}

	public function render_ping_section() {
		echo '<p>' . esc_html__( 'Configure how search engines are notified about updates.', 'newsdesk-sitemap' ) . '</p>';
	}

	public function render_performance_section() {
		echo '<p>' . esc_html__( 'Optimize sitemap generation and caching.', 'newsdesk-sitemap' ) . '</p>';
	}
}