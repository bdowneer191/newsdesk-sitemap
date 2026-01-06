<?php
/**
 * Modern Logs Page View
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nds-admin-wrap">
    <div class="nds-header" style="margin-bottom: 20px;">
        <h1><?php esc_html_e( 'Sitemap Activity Logs', 'newsdesk-sitemap' ); ?></h1>
        <p><?php esc_html_e( 'Track every URL processed and notified to search engines.', 'newsdesk-sitemap' ); ?></p>
    </div>

    <div class="nds-panel" style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; overflow:hidden;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;"><?php esc_html_e( 'Date', 'newsdesk-sitemap' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Service', 'newsdesk-sitemap' ); ?></th>
                    <th style="width: 45%;"><?php esc_html_e( 'Processed URL / Title', 'newsdesk-sitemap' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Status', 'newsdesk-sitemap' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Response', 'newsdesk-sitemap' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( ! empty( $logs ) ) :
                    foreach ( $logs as $log ) :
                        $is_success = ( (int) $log['response_code'] === 200 || $log['response_code'] === '200' );
                        
                        // Extract URL from the response message if present
                        $parts = explode( ' | URL: ', $log['response_message'] );
                        $msg   = isset( $parts[0] ) ? $parts[0] : '';
                        $url   = isset( $parts[1] ) ? $parts[1] : '';
                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log['timestamp'] ) ) ); ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html( strtoupper( $log['search_engine'] ) ); ?></strong>
                            </td>
                            <td>
                                <?php if ( ! empty( $log['post_title'] ) ) : ?>
                                    <div style="font-weight:600;"><?php echo esc_html( $log['post_title'] ); ?></div>
                                <?php endif; ?>
                                <?php if ( ! empty( $url ) ) : ?>
                                    <code style="font-size:11px; color:#646970;"><?php echo esc_url( $url ); ?></code>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="nds-badge" style="background: <?php echo $is_success ? '#e7f6ed' : '#fcf0f1'; ?>; color: <?php echo $is_success ? '#2e7d32' : '#d32f2f'; ?>; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                    <?php echo $is_success ? esc_html__( 'SUCCESS', 'newsdesk-sitemap' ) : esc_html__( 'FAILED', 'newsdesk-sitemap' ); ?>
                                </span>
                            </td>
                            <td>
                                <span title="<?php echo esc_attr( $msg ); ?>" style="cursor:help;">
                                    <?php echo esc_html( $msg ); ?> (<?php echo (int) $log['response_code']; ?>)
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:40px; color:#646970;">
                            <?php esc_html_e( 'No URLs have been processed yet.', 'newsdesk-sitemap' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>