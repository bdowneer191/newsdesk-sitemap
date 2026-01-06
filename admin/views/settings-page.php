<?php
/**
 * Modern Admin Settings Page View
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nds-admin-wrap">
    <div class="nds-header">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php esc_html_e( 'Configure your news sitemap preferences for maximum search visibility.', 'newsdesk-sitemap' ); ?></p>
    </div>

	<?php settings_errors(); ?>

    <div class="nds-settings-container">
        <div class="nds-nav">
		    <a href="?page=nds-settings&tab=general" class="<?php echo $active_tab === 'general' ? 'active' : ''; ?>">
			    <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e( 'General', 'newsdesk-sitemap' ); ?>
		    </a>
		    <a href="?page=nds-settings&tab=filters" class="<?php echo $active_tab === 'filters' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-filter"></span>
			    <?php esc_html_e( 'Content Filters', 'newsdesk-sitemap' ); ?>
		    </a>
		    <a href="?page=nds-settings&tab=ping" class="<?php echo $active_tab === 'ping' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-rss"></span>
			    <?php esc_html_e( 'Ping & API', 'newsdesk-sitemap' ); ?>
		    </a>
		    <a href="?page=nds-settings&tab=performance" class="<?php echo $active_tab === 'performance' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard"></span>
			    <?php esc_html_e( 'Performance', 'newsdesk-sitemap' ); ?>
		    </a>
        </div>

        <div class="nds-content">
            <form method="post" action="options.php" class="nds-form-section">
                <?php
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
                }

                submit_button( __( 'Save Changes', 'newsdesk-sitemap' ), 'primary button-primary' );
                ?>
            </form>
        </div>
    </div>
</div>