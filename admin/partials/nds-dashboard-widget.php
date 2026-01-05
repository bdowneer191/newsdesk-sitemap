<?php
/**
 * Dashboard Widget Partial View
 *
 * This file provides the HTML structure for the NewsDesk Sitemap widget
 * displayed on the main WordPress Dashboard (index.php).
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/partials
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (Dashboard Page Template logic)
 * IMPLEMENTATION: Miniaturized summary stats for the WP Dashboard Widget.
 */

// Prevent direct access - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nds-dashboard-widget-content">
	<div class="nds-widget-stats" style="margin-bottom: 15px;">
		<p style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 8px;">
			<strong><?php esc_html_e( 'Total Pings (30d):', 'newsdesk-sitemap' ); ?></strong>
			<span><?php echo esc_html( number_format( $summary['total_pings_30d'] ) ); ?></span>
		</p>
		<p style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 8px;">
			<strong><?php esc_html_e( 'Success Rate:', 'newsdesk-sitemap' ); ?></strong>
			<span class="nds-status-text" style="color: <?php echo ( $summary['success_rate'] > 90 ) ? '#46b450' : '#dc3232'; ?>;">
				<?php echo esc_html( $summary['success_rate'] ); ?>%
			</span>
		</p>
		<p style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 8px;">
			<strong><?php esc_html_e( 'Cache Hit Rate:', 'newsdesk-sitemap' ); ?></strong>
			<span><?php echo esc_html( $summary['cache_hit_rate'] ); ?>%</span>
		</p>
		<p style="display: flex; justify-content: space-between;">
			<strong><?php esc_html_e( 'Avg Posts:', 'newsdesk-sitemap' ); ?></strong>
			<span><?php echo esc_html( number_format( $summary['avg_posts_in_sitemap'] ) ); ?></span>
		</p>
	</div>

	<div class="nds-widget-actions" style="display: flex; gap: 10px;">
		<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" class="button button-secondary">
			<?php esc_html_e( 'View Sitemap', 'newsdesk-sitemap' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nds-settings' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Configure', 'newsdesk-sitemap' ); ?>
		</a>
	</div>
</div>