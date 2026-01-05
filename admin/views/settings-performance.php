<?php
/**
 * Performance Settings Tab
 *
 * This template handles settings related to sitemap caching, CDN compatibility,
 * and high-performance object cache backends like Redis or Memcached.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (Performance Settings logic)
 * IMPLEMENTATION: NDS-prefixed implementation with multi-layer cache controls.
 */

// Prevent direct access - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve current performance options
$cache_duration    = (int) get_option( 'nds_cache_duration', 1800 );
$object_cache      = get_option( 'nds_enable_object_cache', false );
$cdn_compatibility = get_option( 'nds_cdn_compatibility', false );
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="nds_cache_duration"><?php esc_html_e( 'Cache Duration', 'newsdesk-sitemap' ); ?></label>
			</th>
			<td>
				<input type="number" id="nds_cache_duration" name="nds_cache_duration" 
					   value="<?php echo esc_attr( $cache_duration ); ?>" class="small-text" min="60">
				<?php esc_html_e( 'seconds', 'newsdesk-sitemap' ); ?>
				<p class="description">
					<?php esc_html_e( 'How long to store the generated XML in the cache before refreshing. Default: 1800s (30 minutes).', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Object Cache Backend', 'newsdesk-sitemap' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="nds_enable_object_cache" value="1" <?php checked( $object_cache, 1 ); ?>> 
					<?php esc_html_e( 'Use Redis or Memcached for sitemap storage', 'newsdesk-sitemap' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'If enabled, the plugin will attempt to use your server\'s persistent object cache instead of the database (WordPress Transients).', 'newsdesk-sitemap' ); ?>
				</p>
				<?php if ( ! wp_using_ext_object_cache() ) : ?>
					<div class="notice notice-warning inline" style="margin-top: 10px; border-left-color: #ffb900;">
						<p><?php esc_html_e( 'Notice: No external object cache (Redis/Memcached) was detected on this server. This setting may have no effect.', 'newsdesk-sitemap' ); ?></p>
					</div>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'CDN Compatibility', 'newsdesk-sitemap' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="nds_cdn_compatibility" value="1" <?php checked( $cdn_compatibility, 1 ); ?>> 
					<?php esc_html_e( 'Enable CDN Cache Headers', 'newsdesk-sitemap' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Adds "Cache-Control" and "Expires" headers to the XML output, allowing services like Cloudflare or KeyCDN to cache the sitemap at the edge.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Query Optimization', 'newsdesk-sitemap' ); ?></th>
			<td>
				<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> 
				<span style="font-weight: 600; color: #46b450;"><?php esc_html_e( 'Active', 'newsdesk-sitemap' ); ?></span>
				<p class="description">
					<?php esc_html_e( 'Database query bypass (no_found_rows) and batch metadata loading are enabled by default for maximum performance.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>