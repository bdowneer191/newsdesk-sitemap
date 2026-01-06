<?php
/**
 * System Health / Diagnostics View
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function nds_render_status( $bool ) {
	if ( $bool ) {
		return '<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span> ' . esc_html__( 'Connected / OK', 'newsdesk-sitemap' );
	}
	return '<span class="dashicons dashicons-dismiss" style="color:#dc3232;"></span> ' . esc_html__( 'Missing / Issue Detected', 'newsdesk-sitemap' );
}
?>

<div class="nds-admin-wrap">
    <div class="nds-header">
        <h1><?php esc_html_e( 'System Health Check', 'newsdesk-sitemap' ); ?></h1>
        <p><?php esc_html_e( 'Verify if all plugin components are correctly configured and connected.', 'newsdesk-sitemap' ); ?></p>
    </div>

    <div class="nds-panel" style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px;">
        <h2><?php esc_html_e( 'Connection Status', 'newsdesk-sitemap' ); ?></h2>
        
        <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Component', 'newsdesk-sitemap' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'newsdesk-sitemap' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Database Tables', 'newsdesk-sitemap' ); ?></strong></td>
                    <td>
                        <?php 
                        $db_ok = ! in_array( false, $health_data['database'] );
                        echo nds_render_status( $db_ok );
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Rewrite Rules (Permalink Structure)', 'newsdesk-sitemap' ); ?></strong></td>
                    <td><?php echo nds_render_status( $health_data['rewrites_active'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Sitemap Reachability', 'newsdesk-sitemap' ); ?></strong></td>
                    <td><?php echo nds_render_status( $health_data['sitemap_reachable'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'IndexNow Configuration', 'newsdesk-sitemap' ); ?></strong></td>
                    <td><?php echo nds_render_status( $health_data['apis']['indexnow'] ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Google Search Console API', 'newsdesk-sitemap' ); ?></strong></td>
                    <td><?php echo nds_render_status( $health_data['apis']['gsc'] ); ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ( ! $health_data['rewrites_active'] || ! $health_data['sitemap_reachable'] ) : ?>
            <div class="notice notice-error inline" style="margin-top:20px;">
                <p>
                    <strong><?php esc_html_e( 'Troubleshooting Tip:', 'newsdesk-sitemap' ); ?></strong> 
                    <?php esc_html_e( 'If your sitemap is not reachable, go to Settings > Permalinks and click "Save Changes" to flush the WordPress rewrite rules.', 'newsdesk-sitemap' ); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>