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
        <p><?php esc_html_e( 'Configure your news sitemap preferences.', 'newsdesk-sitemap' ); ?></p>
    </div>

	<?php settings_errors(); ?>

    <div class="nds-settings-container">
        <!-- Navigation Sidebar -->
        <div class="nds-nav">
		    <a href="?page=nds-settings&tab=general" class="<?php echo $active_tab === 'general' ? 'active' : ''; ?>">
			    <span class="dashicons dashicons-admin-generic" style="margin-right:8px;"></span>
                <?php esc_html_e( 'General', 'newsdesk-sitemap' ); ?>
		    </a>
		    <a href="?page=nds-settings&tab=filters" class="<?php echo $active_tab === 'filters' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-filter" style="margin-right:8px;"></span>
			    <?php esc_html_e( 'Content Filters', 'newsdesk-sitemap' ); ?>
		    </a>
		    <a href="?page=nds-settings&tab=ping" class="<?php echo $active_tab === 'ping' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-rss" style="margin-right:8px;"></span>
			    <?php esc_html_e( 'Ping Settings', 'newsdesk-sitemap' ); ?>
		    </a>
		    <a href="?page=nds-settings&tab=performance" class="<?php echo $active_tab === 'performance' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard" style="margin-right:8px;"></span>
			    <?php esc_html_e( 'Performance', 'newsdesk-sitemap' ); ?>
		    </a>
        </div>

        <!-- Main Content -->
        <div class="nds-content">
            <form method="post" action="options.php" class="nds-form-section">
                <?php
                switch ( $active_tab ) {
                    case 'general':
                        echo '<h2>' . esc_html__( 'General Configuration', 'newsdesk-sitemap' ) . '</h2>';
                        settings_fields( 'nds_general_options' );
                        do_settings_sections( 'nds-settings-general' );
                        break;

                    case 'filters':
                        echo '<h2>' . esc_html__( 'Sitemap Filters', 'newsdesk-sitemap' ) . '</h2>';
                        settings_fields( 'nds_filter_options' );
                        do_settings_sections( 'nds-settings-filters' );
                        break;

                    case 'ping':
                        echo '<h2>' . esc_html__( 'Ping Services', 'newsdesk-sitemap' ) . '</h2>';
                        settings_fields( 'nds_ping_options' );
                        do_settings_sections( 'nds-settings-ping' );
                        break;

                    case 'performance':
                        echo '<h2>' . esc_html__( 'System Performance', 'newsdesk-sitemap' ) . '</h2>';
                        settings_fields( 'nds_performance_options' );
                        do_settings_sections( 'nds-settings-performance' );
                        break;
                }

                submit_button( 'Save Changes', 'primary button-primary' );
                ?>
            </form>
        </div>
    </div>
</div>