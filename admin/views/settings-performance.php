<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$cache_duration = get_option( 'nds_cache_duration', 1800 );
$object_cache = get_option( 'nds_enable_object_cache', false );
?>
<table class="form-table">
<tbody>
	<tr>
		<th scope="row"><label for="nds_cache_duration"><?php esc_html_e( 'Cache Duration (seconds)', 'newsdesk-sitemap' ); ?></label></th>
		<td>
			<input type="number" id="nds_cache_duration" name="nds_cache_duration" value="<?php echo esc_attr( $cache_duration ); ?>" class="small-text">
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Object Cache', 'newsdesk-sitemap' ); ?></th>
		<td>
			<label><input type="checkbox" name="nds_enable_object_cache" value="1" <?php checked( $object_cache, 1 ); ?>> <?php esc_html_e( 'Use Redis/Memcached if available', 'newsdesk-sitemap' ); ?></label>
		</td>
	</tr>
</tbody>
</table>