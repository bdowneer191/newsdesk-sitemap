<?php
/**
 * Dashboard Page View
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap nds-dashboard">
	<h1><?php esc_html_e( 'News Sitemap Dashboard', 'newsdesk-sitemap' ); ?></h1>

	<div class="nds-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
		<div class="nds-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<div class="nds-stat-content">
				<h3 style="margin-top: 0; font-size: 2em;"><?php echo esc_html( number_format( $summary['total_pings_30d'] ) ); ?></h3>
				<p><?php esc_html_e( 'Total Pings (30 days)', 'newsdesk-sitemap' ); ?></p>
			</div>
		</div>

		<div class="nds-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<div class="nds-stat-content">
				<h3 style="margin-top: 0; font-size: 2em;"><?php echo esc_html( $summary['success_rate'] ); ?>%</h3>
				<p><?php esc_html_e( 'Success Rate', 'newsdesk-sitemap' ); ?></p>
			</div>
		</div>

		<div class="nds-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<div class="nds-stat-content">
				<h3 style="margin-top: 0; font-size: 2em;"><?php echo esc_html( $summary['cache_hit_rate'] ); ?>%</h3>
				<p><?php esc_html_e( 'Cache Hit Rate', 'newsdesk-sitemap' ); ?></p>
			</div>
		</div>

		<div class="nds-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<div class="nds-stat-content">
				<h3 style="margin-top: 0; font-size: 2em;"><?php echo esc_html( number_format( $summary['avg_posts_in_sitemap'] ) ); ?></h3>
				<p><?php esc_html_e( 'Avg Posts in Sitemap', 'newsdesk-sitemap' ); ?></p>
			</div>
		</div>
	</div>

	<div class="nds-chart-container" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
		<h2><?php esc_html_e( '30-Day Performance', 'newsdesk-sitemap' ); ?></h2>
		<canvas id="nds-performance-chart"></canvas>
	</div>

	<div class="nds-two-column" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
		<div class="nds-panel" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<h2><?php esc_html_e( 'Recent Activity', 'newsdesk-sitemap' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'newsdesk-sitemap' ); ?></th>
						<th><?php esc_html_e( 'Action', 'newsdesk-sitemap' ); ?></th>
						<th><?php esc_html_e( 'Status', 'newsdesk-sitemap' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$recent_logs = $analytics->get_recent_pings( 10 );

					if ( empty( $recent_logs ) ) {
						echo '<tr><td colspan="3">' . esc_html__( 'No recent activity', 'newsdesk-sitemap' ) . '</td></tr>';
					} else {
						foreach ( $recent_logs as $log ) {
							$status_class = $log['response_code'] == 200 ? 'success' : 'error';
							$status_color = $log['response_code'] == 200 ? 'green' : 'red';
							printf(
								'<tr>
									<td>%s</td>
									<td>%s to %s</td>
									<td><span style="color: %s;">%s</span></td>
								</tr>',
								esc_html( human_time_diff( strtotime( $log['timestamp'] ), current_time( 'timestamp' ) ) . ' ago' ),
								esc_html( ucfirst( $log['action'] ) ),
								esc_html( ucfirst( $log['search_engine'] ) ),
								esc_attr( $status_color ),
								$log['response_code'] == 200 ? esc_html__( 'Success', 'newsdesk-sitemap' ) : esc_html__( 'Failed', 'newsdesk-sitemap' )
							);
						}
					}
					?>
				</tbody>
			</table>
		</div>

		<div class="nds-panel" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
			<h2><?php esc_html_e( 'System Info', 'newsdesk-sitemap' ); ?></h2>
			<dl class="nds-info-list">
				<dt><strong><?php esc_html_e( 'Last Generation:', 'newsdesk-sitemap' ); ?></strong></dt>
				<dd>
					<?php
					if ( ! empty( $summary['last_generation'] ) ) {
						echo esc_html( human_time_diff( strtotime( $summary['last_generation'] ), current_time( 'timestamp' ) ) . ' ago' );
					} else {
						esc_html_e( 'Never', 'newsdesk-sitemap' );
					}
					?>
				</dd>

				<dt style="margin-top: 10px;"><strong><?php esc_html_e( 'Total Lifetime Pings:', 'newsdesk-sitemap' ); ?></strong></dt>
				<dd><?php echo esc_html( number_format( $summary['total_pings_lifetime'] ) ); ?></dd>

				<dt style="margin-top: 10px;"><strong><?php esc_html_e( 'Plugin Version:', 'newsdesk-sitemap' ); ?></strong></dt>
				<dd><?php echo esc_html( NDS_VERSION ); ?></dd>
			</dl>
		</div>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
	// Initialize Chart.js
	const ctx = document.getElementById('nds-performance-chart').getContext('2d');
	const chartData = <?php echo wp_json_encode( $chart_data ); ?>;

	new Chart(ctx, {
		type: 'line',
		data: chartData,
		options: {
			responsive: true,
			maintainAspectRatio: false,
			interaction: {
				mode: 'index',
				intersect: false,
			},
			plugins: {
				legend: {
					position: 'top',
				},
				title: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
});
</script>