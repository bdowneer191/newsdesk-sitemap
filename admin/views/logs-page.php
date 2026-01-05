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
    <div class="nds-header">
        <h1><?php esc_html_e( 'Ping Logs', 'newsdesk-sitemap' ); ?></h1>
        <p><?php esc_html_e( 'Detailed history of search engine notifications.', 'newsdesk-sitemap' ); ?></p>
    </div>

    <div class="nds-panel">
        <table class="nds-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Date & Time', 'newsdesk-sitemap' ); ?></th>
                    <th><?php esc_html_e( 'Service', 'newsdesk-sitemap' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'newsdesk-sitemap' ); ?></th>
                    <th><?php esc_html_e( 'Response Message', 'newsdesk-sitemap' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( ! empty( $logs ) ) {
                    foreach ( $logs as $log ) {
                        $is_success = $log['response_code'] == 200;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log['timestamp'] ) ) ); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html( ucfirst( $log['search_engine'] ) ); ?>
                            </td>
                            <td>
                                <span class="nds-badge <?php echo $is_success ? 'success' : 'error'; ?>">
                                    <?php echo $is_success ? 'Success' : 'Failed (' . $log['response_code'] . ')'; ?>
                                </span>
                            </td>
                            <td>
                                <code style="background:rgba(0,0,0,0.05); padding:2px 4px; border-radius:3px; font-size:12px;">
                                    <?php echo esc_html( $log['response_message'] ); ?>
                                </code>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="4" style="text-align:center; padding:30px; color:#9ca3af;">' . esc_html__( 'No logs available.', 'newsdesk-sitemap' ) . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>