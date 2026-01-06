<?php
/**
 * The core plugin class.
 *
 * This class serves as the central hub of the plugin, responsible for
 * loading dependencies, managing internationalization, and orchestrating 
 * all hooks for admin and public-facing functionality.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      NDS_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Initialize the plugin and set its properties.
	 *
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = NDS_VERSION;
		$this->plugin_name = 'newsdesk-sitemap';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_sitemap_hooks();
		$this->define_ping_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 * Includes core engine, integration clients, and admin components.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Core Registry & i18n
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-loader.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-i18n.php';

		// Core Engine Classes
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-sitemap-generator.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-news-schema.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-cache-manager.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-validation.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-analytics.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-query-optimizer.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-security.php';

		// Integration & API Clients
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-indexnow-client.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-google-search-console.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-ping-service.php';

		// Admin-Specific Components
		if ( is_admin() ) {
			require_once NDS_PLUGIN_DIR . 'admin/class-nds-admin-settings.php';
			require_once NDS_PLUGIN_DIR . 'admin/class-nds-admin-dashboard.php';
			
			// Meta Box Implementation Classes
			require_once NDS_PLUGIN_DIR . 'admin/meta-boxes/class-nds-editorial-meta.php';
			require_once NDS_PLUGIN_DIR . 'admin/meta-boxes/class-nds-breaking-news-meta.php';
			require_once NDS_PLUGIN_DIR . 'admin/meta-boxes/class-nds-genre-meta.php';
			require_once NDS_PLUGIN_DIR . 'admin/meta-boxes/class-nds-stock-ticker-meta.php';
		}

		// Public Components
		require_once NDS_PLUGIN_DIR . 'public/class-nds-public-sitemap.php';

		$this->loader = new NDS_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new NDS_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all hooks related to the admin area.
	 * Includes settings, dashboard widgets, AJAX handlers, and meta boxes.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		$admin_settings = new NDS_Admin_Settings( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $admin_settings, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $admin_settings, 'register_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin_settings, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin_settings, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_notices', $admin_settings, 'display_admin_notices' );
		$this->loader->add_filter( 'plugin_action_links_' . NDS_PLUGIN_BASENAME, $admin_settings, 'add_action_links' );

		// AJAX Core Actions
		$this->loader->add_action( 'wp_ajax_nds_validate_sitemap', $admin_settings, 'ajax_validate_sitemap' );
		$this->loader->add_action( 'wp_ajax_nds_manual_ping', $admin_settings, 'ajax_manual_ping' );
		$this->loader->add_action( 'wp_ajax_nds_clear_cache', $admin_settings, 'ajax_clear_cache' );

		// Dashboard & Analytics
		$admin_dashboard = new NDS_Admin_Dashboard( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_dashboard_setup', $admin_dashboard, 'add_dashboard_widgets' );

		// Enterprise Editorial & SEO Meta Boxes
		$editorial_meta = new NDS_Editorial_Meta();
		$this->loader->add_action( 'add_meta_boxes', $editorial_meta, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $editorial_meta, 'save_meta_box', 10, 2 );

		$breaking_meta = new NDS_Breaking_News_Meta();
		$this->loader->add_action( 'add_meta_boxes', $breaking_meta, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $breaking_meta, 'save_meta_box', 10, 2 );

		$genre_meta = new NDS_Genre_Meta();
		$this->loader->add_action( 'add_meta_boxes', $genre_meta, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $genre_meta, 'save_meta_box', 10, 2 );

		$ticker_meta = new NDS_Stock_Ticker_Meta();
		$this->loader->add_action( 'add_meta_boxes', $ticker_meta, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $ticker_meta, 'save_meta_box', 10, 2 );
	}

	/**
	 * Register hooks for the public-facing sitemap interface.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		new NDS_Public_Sitemap( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Register hooks for sitemap generation, rewrite rules, and cache invalidation.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_sitemap_hooks() {
		$sitemap_generator = new NDS_Sitemap_Generator();
		$this->loader->add_action( 'init', $sitemap_generator, 'add_rewrite_rules' );
		$this->loader->add_filter( 'query_vars', $sitemap_generator, 'add_query_vars' );
		$this->loader->add_action( 'template_redirect', $sitemap_generator, 'render_sitemap' );
		$this->loader->add_action( 'save_post', $sitemap_generator, 'clear_cache_on_save', 10, 3 );
		$this->loader->add_action( 'transition_post_status', $sitemap_generator, 'clear_cache_on_transition', 10, 3 );
		$this->loader->add_filter( 'robots_txt', $sitemap_generator, 'add_sitemap_to_robots', 10, 2 );
	}

	/**
	 * Register hooks for the automated ping service and background retries.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_ping_hooks() {
		$ping_service = new NDS_Ping_Service();
		
		// Notifications to search engines on publishing
		$this->loader->add_action( 'publish_post', $ping_service, 'ping_on_publish', 99, 2 );

		// Conditional notification on updates (if enabled in settings)
		if ( get_option( 'nds_ping_on_update', true ) ) {
			$this->loader->add_action( 'post_updated', $ping_service, 'ping_on_update', 99, 3 );
		}

		// Cron action for background retries
		$this->loader->add_action( 'nds_retry_failed_pings', $ping_service, 'retry_failed_pings' );
	}

	/**
	 * Run the loader to execute all hooks with WordPress.
	 *
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Retrieve the unique identifier of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}