<?php
/**
 * Admin Settings Page View
 *
 * This file serves as the main template for the NewsDesk Sitemap settings page.
 * It handles the tabbed navigation and orchestrates the inclusion of individual
 * settings sections.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/partials
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (Settings Page Template logic)
 * IMPLEMENTATION: NDS-prefixed implementation with strict escaping and path resolution.
 */

// Prevent direct access - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap nds-settings-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<nav class="nav-tab-wrapper">
		<a href="?page=nds-settings&tab=general" 
		   class="nav-tab <?php echo ( 'general' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="?page=nds-settings&tab=filters" 
		   class="nav-tab <?php echo ( 'filters' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Content Filters', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="?page=nds-settings&tab=ping" 
		   class="nav-tab <?php echo ( 'ping' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Ping Settings', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="?page=nds-settings&tab=performance" 
		   class="nav-tab <?php echo ( 'performance' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Performance', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="?page=nds-settings&tab=advanced" 
		   class="nav-tab <?php echo ( 'advanced' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Advanced', 'newsdesk-sitemap' ); ?>
		</a>
	</nav>

	<div class="nds-tab-content" style="display: flex; margin-top: 20px;">
		<div class="nds-main-content" style="flex: 0 0 70%; padding-right: 20px;">
			<form method="post" action="options.php">
				<?php
				/**
				 * [cite_start]Logic synthesized from Part-5 [cite: 10513-10538]
				 * Each tab displays its registered settings fields and sections.
				 */
				switch ( $active_tab ) {
					case 'general':
						settings_fields( 'nds_general_options' );
						do_settings_sections( 'nds-settings-general' );
						if ( file_exists( NDS_PLUGIN_DIR . 'admin/views/settings-general.php' ) ) {
							include NDS_PLUGIN_DIR . 'admin/views/settings-general.php';
						}
						break;

					case 'filters':
						settings_fields( 'nds_filter_options' );
						do_settings_sections( 'nds-settings-filters' );
						if ( file_exists( NDS_PLUGIN_DIR . 'admin/views/settings-filters.php' ) ) {
							include NDS_PLUGIN_DIR . 'admin/views/settings-filters.php';
						}
						break;

					case 'ping':
						settings_fields( 'nds_ping_options' );
						do_settings_sections( 'nds-settings-ping' );
						if ( file_exists( NDS_PLUGIN_DIR . 'admin/views/settings-ping.php' ) ) {
							include NDS_PLUGIN_DIR . 'admin/views/settings-ping.php';
						}
						break;

					case 'performance':
						settings_fields( 'nds_performance_options' );
						do_settings_sections( 'nds-settings-performance' );
						if ( file_exists( NDS_PLUGIN_DIR . 'admin/views/settings-performance.php' ) ) {
							include NDS_PLUGIN_DIR . 'admin/views/settings-performance.php';
						}
						break;

					case 'advanced':
						settings_fields( 'nds_advanced_options' );
						do_settings_sections( 'nds-settings-advanced' );
						if ( file_exists( NDS_PLUGIN_DIR . 'admin/views/settings-advanced.php' ) ) {
							include NDS_PLUGIN_DIR . 'admin/views/settings-advanced.php';
						}
						break;
				}

				submit_button();
				?>
			</form>
		</div>

		<div class="nds-sidebar" style="flex: 1;">
			<div class="postbox" style="padding: 15px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Sitemap URL', 'newsdesk-sitemap' ); ?></h3>
				<p>
					<?php $sitemap_url = home_url( '/news-sitemap.xml' ); ?>
					<code><?php echo esc_url( $sitemap_url ); ?></code>
					<button type="button" class="button button-small nds-copy-url"
							data-url="<?php echo esc_url( $sitemap_url ); ?>"
							style="margin-left: 5px;">
						<?php esc_html_e( 'Copy', 'newsdesk-sitemap' ); ?>
					</button>
				</p>
				<p>
					<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" class="button">
						<?php esc_html_e( 'View Sitemap', 'newsdesk-sitemap' ); ?>
					</a>
				</p>
			</div>

			<div class="postbox" style="padding: 15px;">
				<h3><?php esc_html_e( 'Quick Actions', 'newsdesk-sitemap' ); ?></h3>
				<p>
					<button type="button" class="button button-secondary nds-validate-sitemap" style="width: 100%;">
						<?php esc_html_e( 'Validate Sitemap', 'newsdesk-sitemap' ); ?>
					</button>
				</p>
				<p>
					<button type="button" class="button button-secondary nds-manual-ping" style="width: 100%;">
						<?php esc_html_e( 'Ping Search Engines', 'newsdesk-sitemap' ); ?>
					</button>
				</p>
				<p>
					<button type="button" class="button button-secondary nds-clear-cache" style="width: 100%;">
						<?php esc_html_e( 'Clear All Cache', 'newsdesk-sitemap' ); ?>
					</button>
				</p>
			</div>

			<div class="postbox" style="padding: 15px;">
				<h3><?php esc_html_e( 'Documentation', 'newsdesk-sitemap' ); ?></h3>
				<ul>
					<li>
						<a href="https://developers.google.com/search/docs/advanced/sitemaps/news-sitemap" target="_blank" rel="noopener">
							<?php esc_html_e( 'Google News Requirements', 'newsdesk-sitemap' ); ?>
						</a>
					</li>
					<li>
						<a href="https://www.bing.com/webmasters/help/webmaster-guidelines-30f4c1a4" target="_blank" rel="noopener">
							<?php esc_html_e( 'Bing Webmaster Tools', 'newsdesk-sitemap' ); ?>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>