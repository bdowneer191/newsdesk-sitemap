<?php
/**
 * Dashboard functionality
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Admin_Dashboard {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Add widgets to the WordPress Dashboard
	 */
	public function add_dashboard_widgets() {
		add_meta_box(
			'nds_dashboard_widget',
			__( 'News Sitemap Status', 'newsdesk-sitemap' ),
			array( $this, 'render_dashboard_widget' ),
			'dashboard',
			'normal',
			'high'
		);
	}

	/**
	 * Render the dashboard widget content
	 */
	public function render_dashboard_widget() {
		$analytics = new NDS_Analytics();
		$summary   = $analytics->get_summary();
		
		echo '<div class="nds-dashboard-widget">';
		echo '<p><strong>' . __( 'Total Pings (30d):', 'newsdesk-sitemap' ) . '</strong> ' . number_format( $summary['total_pings_30d'] ) . '</p>';
		echo '<p><strong>' . __( 'Success Rate:', 'newsdesk-sitemap' ) . '</strong> ' . $summary['success_rate'] . '%</p>';
		
		$sitemap_url = home_url( '/news-sitemap.xml' );
		echo '<p><a href="' . esc_url( $sitemap_url ) . '" target="_blank" class="button button-primary">' . __( 'View Sitemap', 'newsdesk-sitemap' ) . '</a></p>';
		echo '</div>';
	}
}