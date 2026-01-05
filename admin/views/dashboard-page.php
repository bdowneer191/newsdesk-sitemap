<?php
/**
 * Modern Dashboard View
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
        <h1><?php esc_html_e( 'NewsDesk Dashboard', 'newsdesk-sitemap' ); ?></h1>
        <p><?php esc_html_e( 'Overview of your sitemap performance and search indexing status.', 'newsdesk-sitemap' ); ?></p>
    </div>

    <!-- Stats Grid -->
    <div class="nds-stats-grid">
        <div class="nds-stat-card">
            <div class="nds-stat-value"><?php echo esc_html( number_format( $summary['total_pings_30d'] ) ); ?></div>
            <div class="nds-stat-label"><?php esc_html_e( 'Monthly Pings', 'newsdesk-sitemap' ); ?></div>
        </div>
        
        <div class="nds-stat-card">
            <div class="nds-stat-value"><?php echo esc_html( $summary['success_rate'] ); ?>%</div>
            <div class="nds-stat-label"><?php esc_html_e( 'Success Rate', 'newsdesk-sitemap' ); ?></div>
        </div>
        
        <div class="nds-stat-card">
            <div class="nds-stat-value"><?php echo esc_html( $summary['cache_hit_rate'] ); ?>%</div>
            <div class="nds-stat-label"><?php esc_html_e( 'Cache Efficiency', 'newsdesk-sitemap' ); ?></div>
        </div>
        
        <div class="nds-stat-card">
            <div class="nds-stat-value"><?php echo esc_html( number_format( $summary['avg_posts_in_sitemap'] ) ); ?></div>
            <div class="nds-stat-label"><?php esc_html_e( 'Active News Posts', 'newsdesk-sitemap' ); ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Performance Chart -->
        <div class="nds-panel">
            <h2><?php esc_html_e( 'Indexing Performance', 'newsdesk-sitemap' ); ?></h2>
            <div style="height: 300px; width: 100%;">
                <canvas id="nds-performance-chart"></canvas>
            </div>
        </div>

        <!-- System Status -->
        <div class="nds-panel">
            <h2><?php esc_html_e( 'System Status', 'newsdesk-sitemap' ); ?></h2>
            <div class="nds-info-list" style="padding: 10px 0;">
                <div style="margin-bottom: 20px;">
                    <div style="color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; margin-bottom:5px;">Last Generation</div>
                    <div style="font-size:16px; font-weight:500;">
                        <?php 
                        if ( ! empty( $summary['last_generation'] ) ) {
                            echo esc_html( human_time_diff( strtotime( $summary['last_generation'] ), current_time( 'timestamp' ) ) . ' ago' );
                        } else {
                            echo 'Not yet generated';
                        }
                        ?>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <div style="color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; margin-bottom:5px;">Plugin Version</div>
                    <div style="font-size:16px; font-weight:500;"><?php echo esc_html( NDS_VERSION ); ?></div>
                </div>

                 <div style="margin-bottom: 20px;">
                    <button type="button" class="button button-secondary" id="nds-manual-ping" style="width:100%;">
                        <?php esc_html_e( 'Force Ping now', 'newsdesk-sitemap' ); ?>
                    </button>
                    <button type="button" class="button button-link" id="nds-clear-cache" style="width:100%; margin-top:10px; color:#ef4444;">
                        <?php esc_html_e( 'Clear Cache', 'newsdesk-sitemap' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="nds-panel">
        <h2><?php esc_html_e( 'Recent Ping Activity', 'newsdesk-sitemap' ); ?></h2>
        <table class="nds-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Time', 'newsdesk-sitemap' ); ?></th>
                    <th><?php esc_html_e( 'Event', 'newsdesk-sitemap' ); ?></th>
                    <th><?php esc_html_e( 'Service', 'newsdesk-sitemap' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'newsdesk-sitemap' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent_logs = $analytics->get_recent_pings( 8 );
                if ( ! empty( $recent_logs ) ) :
                    foreach ( $recent_logs as $log ) :
                        $success = $log['response_code'] == 200;
                        ?>
                        <tr>
                            <td><?php echo esc_html( human_time_diff( strtotime( $log['timestamp'] ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
                            <td><?php echo esc_html( ucfirst( $log['action'] ) ); ?></td>
                            <td><?php echo esc_html( ucfirst( $log['search_engine'] ) ); ?></td>
                            <td>
                                <span class="nds-badge <?php echo $success ? 'success' : 'error'; ?>">
                                    <?php echo $success ? 'Success' : 'Failed (' . $log['response_code'] . ')'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr><td colspan="4" style="text-align:center; padding:20px; color:#9ca3af;"><?php esc_html_e( 'No recent activity recorded', 'newsdesk-sitemap' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
	const ctx = document.getElementById('nds-performance-chart').getContext('2d');
	const chartData = <?php echo wp_json_encode( $chart_data ); ?>;

	new Chart(ctx, {
		type: 'line',
		data: {
            ...chartData,
            datasets: [{
                ...chartData.datasets[0],
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
		options: {
			responsive: true,
            maintainAspectRatio: false,
			plugins: {
				legend: { display: false }
			},
			scales: {
				y: {
					beginAtZero: true,
                    grid: { borderDash: [4, 4], color: '#e5e7eb' },
                    ticks: { padding: 10 }
				},
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 10 }
                }
			}
		}
	});
});
</script>