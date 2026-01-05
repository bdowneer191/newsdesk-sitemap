<?php
/**
 * Modern Dashboard View
 *
 * This template displays the plugin's performance metrics, search engine
 * indexing stats, and recent activity logs.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

/**
 * SOURCE: Part-4 and Part-5 of Complete Technical Implementation Guide (Dashboard logic)
 * IMPLEMENTATION: NDS-prefixed implementation with Chart.js and SQL-hardened analytics.
 */

// Prevent direct access - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nds-admin-wrap">
	<div class="nds-header" style="margin-bottom: 30px;">
		<h1><?php esc_html_e( 'NewsDesk Dashboard', 'newsdesk-sitemap' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Overview of your sitemap performance and search indexing status.', 'newsdesk-sitemap' ); ?></p>
	</div>

	<div class="nds-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div class="nds-stat-value" style="font-size: 24px; font-weight: 600;"><?php echo esc_html( number_format( $summary['total_pings_30d'] ) ); ?></div>
			<div class="nds-stat-label" style="color: #646970;"><?php esc_html_e( 'Monthly Pings', 'newsdesk-sitemap' ); ?></div>
		</div>

		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div class="nds-stat-value" style="font-size: 24px; font-weight: 600; color: <?php echo ( $summary['success_rate'] > 90 ) ? '#46b450' : '#dc3232'; ?>;">
				<?php echo esc_html( $summary['success_rate'] ); ?>%
			</div>
			<div class="nds-stat-label" style="color: #646970;"><?php esc_html_e( 'Success Rate', 'newsdesk-sitemap' ); ?></div>
		</div>

		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div class="nds-stat-value" style="font-size: 24px; font-weight: 600;"><?php echo esc_html( $summary['cache_hit_rate'] ); ?>%</div>
			<div class="nds-stat-label" style="color: #646970;"><?php esc_html_e( 'Cache Efficiency', 'newsdesk-sitemap' ); ?></div>
		</div>

		<div class="nds-stat-card" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<div class="nds-stat-value" style="font-size: 24px; font-weight: 600;"><?php echo esc_html( number_format( $summary['avg_posts_in_sitemap'] ) ); ?></div>
			<div class="nds-stat-label" style="color: #646970;"><?php esc_html_e( 'Active News Posts', 'newsdesk-sitemap' ); ?></div>
		</div>
	</div>

	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 30px;">
		<div class="nds-panel" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<h2><?php esc_html_e( 'Indexing Performance', 'newsdesk-sitemap' ); ?></h2>
			<div style="height: 300px; width: 100%;">
				<canvas id="nds-performance-chart"></canvas>
			</div>
		</div>

		<div class="nds-panel" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
			<h2><?php esc_html_e( 'System Status', 'newsdesk-sitemap' ); ?></h2>
			<div class="nds-info-list" style="padding: 10px 0;">
				<div style="margin-bottom: 20px;">
					<div style="color: #646970; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">
						<?php esc_html_e( 'Last Generation', 'newsdesk-sitemap' ); ?>
					</div>
					<div style="font-size: 16px; font-weight: 500;">
						<?php
						if ( ! empty( $summary['last_generation'] ) ) {
							echo esc_html( human_time_diff( strtotime( $summary['last_generation'] ), current_time( 'timestamp' ) ) . ' ago' );
						} else {
							esc_html_e( 'Not yet generated', 'newsdesk-sitemap' );
						}
						?>
					</div>
				</div>

				<div style="margin-bottom: 20px;">
					<div style="color: #646970; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 5px;">
						<?php esc_html_e( 'Plugin Version', 'newsdesk-sitemap' ); ?>
					</div>
					<div style="font-size: 16px; font-weight: 500;"><?php echo esc_html( NDS_VERSION ); ?></div>
				</div>

				<div style="margin-top: 30px;">
					<button type="button" class="button button-secondary nds-manual-ping" style="width: 100%; margin-bottom: 10px;">
						<?php esc_html_e( 'Force Ping All Engines', 'newsdesk-sitemap' ); ?>
					</button>
					<button type="button" class="button button-link nds-clear-cache" style="width: 100%; color: #dc3232; text-decoration: none;">
						<?php esc_html_e( 'Invalidate Cache', 'newsdesk-sitemap' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="nds-panel" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
		<h2><?php esc_html_e( 'Recent Ping Activity', 'newsdesk-sitemap' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
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
				if ( ! empty( $recent_logs ) ) :
					foreach ( $recent_logs as $log ) :
						$is_success = ( 200 === (int) $log['response_code'] );
						?>
						<tr>
							<td><?php echo esc_html( human_time_diff( strtotime( $log['timestamp'] ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
							<td><?php echo esc_html( ucfirst( $log['action'] ) ); ?></td>
							<td><?php echo esc_html( ucfirst( $log['search_engine'] ) ); ?></td>
							<td>
								<span class="nds-badge" style="padding: 2px 8px; border-radius: 3px; font-size: 11px; background: <?php echo $is_success ? '#e7f6ed' : '#fcf0f1'; ?>; color: <?php echo $is_success ? '#2e7d32' : '#d32f2f'; ?>;">
									<?php echo $is_success ? esc_html__( 'Success', 'newsdesk-sitemap' ) : sprintf( esc_html__( 'Failed (%d)', 'newsdesk-sitemap' ), (int) $log['response_code'] ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4" style="text-align: center; padding: 20px; color: #646970;">
							<?php esc_html_e( 'No recent activity recorded.', 'newsdesk-sitemap' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/**
 * [cite_start]SOURCE: Part-5 performance chart initialization [cite: 399-430]
 */
jQuery(document).ready(function($) {
	const ctx = document.getElementById('nds-performance-chart').getContext('2d');
	const chartData = <?php echo wp_json_encode( $chart_data ); ?>;

	if ( chartData && chartData.labels.length > 0 ) {
		new Chart(ctx, {
			type: 'line',
			data: chartData,
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } }
				},
				scales: {
					y: {
						beginAtZero: true,
						grid: { color: '#f0f0f1' }
					},
					x: {
						grid: { display: false }
					}
				},
				interaction: { mode: 'index', intersect: false }
			}
		});
	}
});
</script>