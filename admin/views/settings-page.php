<?php
/**
 * Admin Settings Page View
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap nds-settings-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<nav class="nav-tab-wrapper">
		<a href="?page=nds-settings&tab=general"
		   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="?page=nds-settings&tab=filters"
		   class="nav-tab <?php echo $active_tab === 'filters' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Content Filters', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="?page=nds-settings&tab=ping"
		   class="nav-tab <?php echo $active_tab === 'ping' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Ping Settings', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="?page=nds-settings&tab=performance"
		   class="nav-tab <?php echo $active_tab === 'performance' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Performance', 'newsdesk-sitemap' ); ?>
		</a>
	</nav>

	<div class="nds-tab-content">
		<form method="post" action="options.php">
			<?php
			switch ( $active_tab ) {
				case 'general':
					settings_fields( 'nds_general_options' );
					do_settings_sections( 'nds-settings-general' );
					break;

				case 'filters':
					settings_fields( 'nds_filter_options' );
					do_settings_sections( 'nds-settings-filters' );
					break;

				case 'ping':
					settings_fields( 'nds_ping_options' );
					do_settings_sections( 'nds-settings-ping' );
					break;

				case 'performance':
					settings_fields( 'nds_performance_options' );
					do_settings_sections( 'nds-settings-performance' );
					break;
			}

			submit_button();
			?>
		</form>
	</div>

	<div class="nds-sidebar" style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
		<div class="nds-widget">
			<h3><?php esc_html_e( 'Sitemap URL', 'newsdesk-sitemap' ); ?></h3>
			<p>
				<code><?php echo esc_url( home_url( '/news-sitemap.xml' ) ); ?></code>
			</p>
			<p>
				<a href="<?php echo esc_url( home_url( '/news-sitemap.xml' ) ); ?>"
				   target="_blank" class="button">
					<?php esc_html_e( 'View Sitemap', 'newsdesk-sitemap' ); ?>
				</a>
			</p>
		</div>

		<div class="nds-widget">
			<h3><?php esc_html_e( 'Quick Actions', 'newsdesk-sitemap' ); ?></h3>
			<p>
				<button type="button" class="button button-secondary nds-manual-ping" id="nds-manual-ping">
					<?php esc_html_e( 'Ping Search Engines', 'newsdesk-sitemap' ); ?>
				</button>
			</p>
			<p>
				<button type="button" class="button button-secondary nds-clear-cache" id="nds-clear-cache">
					<?php esc_html_e( 'Clear Cache', 'newsdesk-sitemap' ); ?>
				</button>
			</p>
		</div>
	</div>
</div>