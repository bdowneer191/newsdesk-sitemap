<?php
/**
 * The core plugin class
 *
 * Maintains a reference to all plugin hooks and loads all dependencies
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      NDS_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Initialize the plugin and set its properties
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version = NDS_VERSION;
		$this->plugin_name = 'newsdesk-sitemap';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_sitemap_hooks();
		$this->define_ping_hooks();
	}

	/**
	 * Load the required dependencies for this plugin
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin
		 */
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 */
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-i18n.php';

		/**
		 * Core functionality classes
		 */
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-sitemap-generator.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-news-schema.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-ping-service.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-cache-manager.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-validation.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-analytics.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-indexnow-client.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-query-optimizer.php';
		require_once NDS_PLUGIN_DIR . 'includes/class-nds-security.php';

		/**
		 * Admin-specific classes
		 */
		if ( is_admin() ) {
			require_once NDS_PLUGIN_DIR . 'admin/class-nds-admin-settings.php';
			require_once NDS_PLUGIN_DIR . 'admin/class-nds-admin-dashboard.php';
			require_once NDS_PLUGIN_DIR . 'admin/class-nds-admin-validation.php';
			require_once NDS_PLUGIN_DIR . 'admin/class-nds-admin-logs.php';
			require_once NDS_PLUGIN_DIR . 'admin/meta-boxes/class-nds-breaking-news-meta.php';
			require_once NDS_PLUGIN_DIR . 'admin/meta-boxes/class-nds-genre-meta.php';
			require_once NDS_PLUGIN_DIR . 'admin/meta-boxes/class-nds-stock-ticker-meta.php';
		}

		/**
		 * Public-facing classes
		 */
		require_once NDS_PLUGIN_DIR . 'public/class-nds-public-sitemap.php';

		// Initialize the loader
		$this->loader = new NDS_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new NDS_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all hooks related to admin area functionality
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		// Admin settings page
		$admin_settings = new NDS_Admin_Settings( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $admin_settings, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $admin_settings, 'register_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin_settings, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin_settings, 'enqueue_scripts' );

		// Admin dashboard
		$admin_dashboard = new NDS_Admin_Dashboard( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_dashboard_setup', $admin_dashboard, 'add_dashboard_widgets' );

		// AJAX handlers
		$this->loader->add_action( 'wp_ajax_nds_validate_sitemap', $admin_settings, 'ajax_validate_sitemap' );
		$this->loader->add_action( 'wp_ajax_nds_manual_ping', $admin_settings, 'ajax_manual_ping' );
		$this->loader->add_action( 'wp_ajax_nds_clear_cache', $admin_settings, 'ajax_clear_cache' );

		// Meta boxes
		$breaking_news_meta = new NDS_Breaking_News_Meta();
		$this->loader->add_action( 'add_meta_boxes', $breaking_news_meta, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $breaking_news_meta, 'save_meta_box', 10, 2 );

		$genre_meta = new NDS_Genre_Meta();
		$this->loader->add_action( 'add_meta_boxes', $genre_meta, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $genre_meta, 'save_meta_box', 10, 2 );

		// Admin notices
		$this->loader->add_action( 'admin_notices', $admin_settings, 'display_admin_notices' );

		// Settings link on plugins page
		$plugin_basename = NDS_PLUGIN_BASENAME;
		$this->loader->add_filter( "plugin_action_links_{$plugin_basename}", $admin_settings, 'add_action_links' );
	}

	/**
	 * Register all hooks related to public-facing functionality
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$public_sitemap = new NDS_Public_Sitemap( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Register all hooks related to sitemap generation
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_sitemap_hooks() {
		$sitemap_generator = new NDS_Sitemap_Generator();
		// Register custom rewrite rules
		$this->loader->add_action( 'init', $sitemap_generator, 'add_rewrite_rules' );
		$this->loader->add_filter( 'query_vars', $sitemap_generator, 'add_query_vars' );

		// Handle sitemap rendering
		$this->loader->add_action( 'template_redirect', $sitemap_generator, 'render_sitemap' );

		// Clear cache on post publish/update
		$this->loader->add_action( 'save_post', $sitemap_generator, 'clear_cache_on_save', 10, 3 );
		$this->loader->add_action( 'transition_post_status', $sitemap_generator, 'clear_cache_on_transition', 10, 3 );

		// Add sitemap to robots.txt
		$this->loader->add_filter( 'robots_txt', $sitemap_generator, 'add_sitemap_to_robots', 10, 2 );
	}

	/**
	 * Register all hooks related to ping service
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_ping_hooks() {
		$ping_service = new NDS_Ping_Service();

		// Ping on post publish
		$this->loader->add_action( 'publish_post', $ping_service, 'ping_on_publish', 99, 2 );

		// Ping on post update (if enabled)
		if ( get_option( 'nds_ping_on_update', true ) ) {
			$this->loader->add_action( 'post_updated', $ping_service, 'ping_on_update', 99, 3 );
		}

		// Schedule ping retry for failed pings
		$this->loader->add_action( 'nds_retry_failed_pings', $ping_service, 'retry_failed_pings' );
	}

	/**
	 * Run the loader to execute all hooks
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks
	 *
	 * @since     1.0.0
	 * @return    NDS_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}