<?php
/**
 * Ping & API Settings Tab
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$ping_google    = get_option( 'nds_ping_google', true );
$ping_bing      = get_option( 'nds_ping_bing', true );
$indexnow       = get_option( 'nds_indexnow_enabled', false );
$indexnow_key   = get_option( 'nds_indexnow_key', '' );
$gsc_json       = get_option( 'nds_gsc_credentials_json', '' );
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'Standard Pings', 'newsdesk-sitemap' ); ?></th>
			<td>
				<label style="display:block; margin-bottom: 5px;">
					<input type="checkbox" name="nds_ping_google" value="1" <?php checked( $ping_google, 1 ); ?>> 
					<?php esc_html_e( 'Notify Google (Traditional)', 'newsdesk-sitemap' ); ?>
				</label>
				<label style="display:block;">
					<input type="checkbox" name="nds_ping_bing" value="1" <?php checked( $ping_bing, 1 ); ?>> 
					<?php esc_html_e( 'Notify Bing (Traditional)', 'newsdesk-sitemap' ); ?>
				</label>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_gsc_credentials_json"><?php esc_html_e( 'Google Search Console API', 'newsdesk-sitemap' ); ?></label>
			</th>
			<td>
				<textarea id="nds_gsc_credentials_json" name="nds_gsc_credentials_json" class="large-text code" rows="8" placeholder='{ "type": "service_account", ... }'><?php echo esc_textarea( $gsc_json ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Paste your Google Service Account JSON key here for official sitemap submission.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'IndexNow Protocol', 'newsdesk-sitemap' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="nds_indexnow_enabled" value="1" <?php checked( $indexnow, 1 ); ?>> 
					<strong><?php esc_html_e( 'Enable IndexNow', 'newsdesk-sitemap' ); ?></strong>
				</label>
				<p class="description"><?php esc_html_e( 'Instantly notifies Bing and Yandex about new content.', 'newsdesk-sitemap' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row"><label for="nds_indexnow_key"><?php esc_html_e( 'IndexNow API Key', 'newsdesk-sitemap' ); ?></label></th>
			<td>
				<input type="text" id="nds_indexnow_key" name="nds_indexnow_key" value="<?php echo esc_attr( $indexnow_key ); ?>" class="regular-text">
			</td>
		</tr>
	</tbody>
</table>