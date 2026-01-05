<?php
/**
 * Ping Settings Tab
 *
 * This template handles the configuration for notifying search engines (Google, Bing)
 * and managing the IndexNow protocol for instant indexing.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

/**
 * SOURCE: Part-4 and Part-5 of Complete Technical Implementation Guide (Ping Settings logic)
 * IMPLEMENTATION: NDS-prefixed implementation with Throttling and IndexNow support.
 */

// Prevent direct access - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve current options
$ping_google    = get_option( 'nds_ping_google', true );
$ping_bing      = get_option( 'nds_ping_bing', true );
$ping_on_update = get_option( 'nds_ping_on_update', true );
$ping_throttle  = (int) get_option( 'nds_ping_throttle', 60 );
$indexnow       = get_option( 'nds_indexnow_enabled', false );
$indexnow_key   = get_option( 'nds_indexnow_key', '' );
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'Standard Ping Services', 'newsdesk-sitemap' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php esc_html_e( 'Standard Ping Services', 'newsdesk-sitemap' ); ?></span></legend>
					<label style="display:block; margin-bottom: 5px;">
						<input type="checkbox" name="nds_ping_google" value="1" <?php checked( $ping_google, 1 ); ?>> 
						<?php esc_html_e( 'Notify Google (via Sitemap Ping)', 'newsdesk-sitemap' ); ?>
					</label>
					<label style="display:block; margin-bottom: 5px;">
						<input type="checkbox" name="nds_ping_bing" value="1" <?php checked( $ping_bing, 1 ); ?>> 
						<?php esc_html_e( 'Notify Bing (via Sitemap Ping)', 'newsdesk-sitemap' ); ?>
					</label>
				</fieldset>
				<p class="description">
					<?php esc_html_e( 'Sends a GET request to traditional search engine sitemap endpoints upon publishing.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'IndexNow Protocol', 'newsdesk-sitemap' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="nds_indexnow_enabled" value="1" <?php checked( $indexnow, 1 ); ?>> 
					<strong><?php esc_html_e( 'Enable IndexNow (Recommended)', 'newsdesk-sitemap' ); ?></strong>
				</label>
				<p class="description">
					<?php esc_html_e( 'Instantly notifies Bing, Yandex, and Seznam about new or updated content. Bypasses the need for crawling the XML sitemap.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><label for="nds_indexnow_key"><?php esc_html_e( 'IndexNow API Key', 'newsdesk-sitemap' ); ?></label></th>
			<td>
				<input type="text" id="nds_indexnow_key" name="nds_indexnow_key" value="<?php echo esc_attr( $indexnow_key ); ?>" class="regular-text">
				<p class="description">
					<?php esc_html_e( 'This key is used to verify site ownership. If empty, the plugin will auto-generate a secure 32-character key.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Ping Automation', 'newsdesk-sitemap' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="nds_ping_on_update" value="1" <?php checked( $ping_on_update, 1 ); ?>> 
					<?php esc_html_e( 'Ping search engines when an existing post is updated', 'newsdesk-sitemap' ); ?>
				</label>
				<div style="margin-top: 15px;">
					<label for="nds_ping_throttle">
						<?php esc_html_e( 'Minimum interval between pings:', 'newsdesk-sitemap' ); ?>
						<input type="number" id="nds_ping_throttle" name="nds_ping_throttle" value="<?php echo esc_attr( $ping_throttle ); ?>" class="small-text" min="10" max="3600">
						<?php esc_html_e( 'seconds', 'newsdesk-sitemap' ); ?>
					</label>
				</div>
				<p class="description">
					<?php esc_html_e( 'Throttling prevents your server from being flagged as a spam source if you publish many articles simultaneously.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>