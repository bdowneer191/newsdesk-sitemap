<?php
/**
 * Dashboard functionality
 *
 * This class handles the registration and rendering of the NewsDesk Sitemap
 * widget on the main WordPress Dashboard.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SOURCE: Part-4 and Part-5 of Complete Technical Implementation Guide
 * IMPLEMENTATION: Controller for the WP Dashboard Widget.
 */
class NDS_Admin_Dashboard {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Add widgets to the WordPress Dashboard
	 *
	 * @since    1.0.0
	 */
	public function add_dashboard_widgets() {
		add_meta_box(
			'nds_dashboard_widget',
			__( 'News Sitemap Status', 'newsdesk-sitemap' ),
			array( $this, 'render_dashboard_widget' ),
			'dashboard',
			'side',
			'high'
		);
	}

	/**
	 * Render the dashboard widget content using a partial template
	 *
	 * @since    1.0.0
	 */
	public function render_dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Retrieve analytics summary for the widget [cite: 22306-22351]
		$analytics = new NDS_Analytics();
		$summary   = $analytics->get_summary();

		// Set the sitemap URL for the view
		$generator   = new NDS_Sitemap_Generator();
		$sitemap_url = $generator->get_sitemap_url();

		// Load the partial template
		$partial_path = NDS_PLUGIN_DIR . 'admin/partials/nds-dashboard-widget.php';
		if ( file_exists( $partial_path ) ) {
			include $partial_path;
		}
	}
}