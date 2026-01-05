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

/**
 * SOURCE: Part-4 and Part-5 of Complete Technical Implementation Guide
 * IMPLEMENTATION: Controller for the WP Dashboard Widget.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Admin_Dashboard {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	private $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of the plugin.
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
	 * [cite: 3511-3542]
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
	 * [cite: 11824-11869, 10746-10784]
	 *
	 * @since    1.0.0
	 */
	public function render_dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Retrieve analytics summary for the widget
		$analytics = new NDS_Analytics();
		$summary   = $analytics->get_summary();

		// Set the sitemap URL for the view
		$sitemap_url = home_url( '/news-sitemap.xml' );

		// Load the partial template
		$partial_path = NDS_PLUGIN_DIR . 'admin/partials/nds-dashboard-widget.php';
		if ( file_exists( $partial_path ) ) {
			include $partial_path;
		}
	}
}