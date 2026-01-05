<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$ping_google = get_option( 'nds_ping_google', true );
$ping_bing = get_option( 'nds_ping_bing', true );
$indexnow = get_option( 'nds_indexnow_enabled', false );
$indexnow_key = get_option( 'nds_indexnow_key', '' );
?>
<table class="form-table">
<tbody>
	<tr>
		<th scope="row"><?php esc_html_e( 'Ping Services', 'newsdesk-sitemap' ); ?></th>
		<td>
			<fieldset>
				<label><input type="checkbox" name="nds_ping_google" value="1" <?php checked( $ping_google, 1 ); ?>> Google</label><br>
				<label><input type="checkbox" name="nds_ping_bing" value="1" <?php checked( $ping_bing, 1 ); ?>> Bing</label>
			</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'IndexNow', 'newsdesk-sitemap' ); ?></th>
		<td>
			<fieldset>
				<label><input type="checkbox" name="nds_indexnow_enabled" value="1" <?php checked( $indexnow, 1 ); ?>> <?php esc_html_e( 'Enable IndexNow Protocol', 'newsdesk-sitemap' ); ?></label>
			</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="nds_indexnow_key"><?php esc_html_e( 'IndexNow API Key', 'newsdesk-sitemap' ); ?></label></th>
		<td>
			<input type="text" id="nds_indexnow_key" name="nds_indexnow_key" value="<?php echo esc_attr( $indexnow_key ); ?>" class="regular-text">
			<p class="description"><?php esc_html_e( 'Auto-generated if left blank.', 'newsdesk-sitemap' ); ?></p>
		</td>
	</tr>
</tbody>
</table>