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

<div class="nds-admin-wrap">
	<div class="nds-header" style="margin-bottom: 30px;">
		<h1><?php esc_html_e( 'News Sitemap Dashboard', 'newsdesk-sitemap' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Real-time performance metrics and search indexing status.', 'newsdesk-sitemap' ); ?></p>
	</div>

	<div class="nds-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Total Pings (30d)', 'newsdesk-sitemap' ); ?></div>
			<div style="font-size: 28px; font-weight: 700; margin-top: 5px;"><?php echo esc_html( number_format( $summary['total_pings_30d'] ) ); ?></div>
		</div>

		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Success Rate', 'newsdesk-sitemap' ); ?></div>
			<div style="font-size: 28px; font-weight: 700; margin-top: 5px; color: <?php echo ( $summary['success_rate'] > 90 ) ? '#46b450' : '#dc3232'; ?>;">
				<?php echo esc_html( $summary['success_rate'] ); ?>%
			</div>
		</div>

		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Cache Hit Rate', 'newsdesk-sitemap' ); ?></div>
			<div style="font-size: 28px; font-weight: 700; margin-top: 5px; color: #2271b1;"><?php echo esc_html( $summary['cache_hit_rate'] ); ?>%</div>
		</div>

		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Avg Posts in Sitemap', 'newsdesk-sitemap' ); ?></div>
			<div style="font-size: 28px; font-weight: 700; margin-top: 5px;"><?php echo esc_html( number_format( $summary['avg_posts_in_sitemap'] ) ); ?></div>
		</div>
	</div>

	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
		<div class="nds-panel" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Indexing Velocity', 'newsdesk-sitemap' ); ?></h2>
			<div style="height: 300px;"><canvas id="nds-performance-chart"></canvas></div>
		</div>

		<div class="nds-panel" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<h2 style="margin-top:0;"><?php esc_html_e( 'System Status', 'newsdesk-sitemap' ); ?></h2>
			
			<div style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
				<div style="font-size: 11px; color: #646970; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Last Generation', 'newsdesk-sitemap' ); ?></div>
				<div style="font-weight: 500; font-size: 14px;">
					<?php echo ! empty( $summary['last_generation'] ) ? esc_html( human_time_diff( strtotime( $summary['last_generation'] ), current_time( 'timestamp' ) ) . ' ago' ) : esc_html__( 'Never', 'newsdesk-sitemap' ); ?>
				</div>
			</div>

			<div style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
				<div style="font-size: 11px; color: #646970; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Total Lifetime Pings', 'newsdesk-sitemap' ); ?></div>
				<div style="font-weight: 500; font-size: 14px;">
					<?php echo esc_html( number_format( $summary['total_pings_lifetime'] ) ); ?>
				</div>
			</div>

			<div>
				<button type="button" class="button button-primary nds-manual-ping" id="nds-manual-ping" style="width: 100%; margin-bottom: 10px;">
					<?php esc_html_e( 'Force Global Ping', 'newsdesk-sitemap' ); ?>
				</button>
				<button type="button" class="button button-secondary nds-clear-cache" id="nds-clear-cache" style="width: 100%; color:#dc3232;">
					<?php esc_html_e( 'Purge All Cache', 'newsdesk-sitemap' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="nds-panel" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; margin-top: 24px;">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Recent Activity', 'newsdesk-sitemap' ); ?></h2>
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
				$recent_logs = ( new NDS_Analytics() )->get_recent_pings( 10 );
				if ( ! empty( $recent_logs ) ) :
					foreach ( $recent_logs as $log ) :
						$is_success = ( 200 === (int) $log['response_code'] );
						?>
						<tr>
							<td><?php echo esc_html( human_time_diff( strtotime( $log['timestamp'] ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
							<td><?php echo esc_html( ucfirst( $log['action'] ) . ' to ' . ucfirst( $log['search_engine'] ) ); ?></td>
							<td>
								<span style="padding: 2px 8px; border-radius: 3px; font-size: 11px; background: <?php echo $is_success ? '#e7f6ed' : '#fcf0f1'; ?>; color: <?php echo $is_success ? '#2e7d32' : '#d32f2f'; ?>;">
									<?php echo $is_success ? esc_html__( 'Success', 'newsdesk-sitemap' ) : esc_html__( 'Failed', 'newsdesk-sitemap' ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No recent activity.', 'newsdesk-sitemap' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/**
 * [cite_start]SOURCE: Part-4 formatted chart datasets [cite: 251-274]
 */
jQuery(document).ready(function($) {
	const ctx = document.getElementById('nds-performance-chart').getContext('2d');
	const chartData = <?php echo wp_json_encode( $chart_data ); ?>;

	if ( chartData && chartData.labels && chartData.labels.length > 0 ) {
		new Chart(ctx, {
			type: 'line',
			data: chartData,
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: {
					mode: 'index',
					intersect: false
				},
				plugins: {
					legend: { position: 'top' }
				},
				scales: {
					y: { beginAtZero: true }
				}
			}
		});
	}
});
</script>