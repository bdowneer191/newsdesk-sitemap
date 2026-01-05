<?php
/**
 * General Settings Tab
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$publication_name = get_option( 'nds_publication_name', get_bloginfo( 'name' ) );
$language         = get_option( 'nds_language', 'en' );
$time_limit       = get_option( 'nds_time_limit', 48 );
$max_urls         = get_option( 'nds_max_urls', 1000 );
?>

<table class="form-table" role="presentation">
<tbody>
	<tr>
		<th scope="row">
			<label for="nds_publication_name"><?php esc_html_e( 'Publication Name', 'newsdesk-sitemap' ); ?> <span class="required">*</span></label>
		</th>
		<td>
			<input type="text" id="nds_publication_name" name="nds_publication_name" value="<?php echo esc_attr( $publication_name ); ?>" class="regular-text" required>
			<p class="description"><?php esc_html_e( 'Must match the name on your Google News Publisher Center.', 'newsdesk-sitemap' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="nds_language"><?php esc_html_e( 'Default Language', 'newsdesk-sitemap' ); ?></label></th>
		<td>
			<input type="text" id="nds_language" name="nds_language" value="<?php echo esc_attr( $language ); ?>" class="small-text">
			<p class="description"><?php esc_html_e( 'ISO 639-1 code (e.g., en, es, fr).', 'newsdesk-sitemap' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="nds_time_limit"><?php esc_html_e( 'Time Limit (Hours)', 'newsdesk-sitemap' ); ?></label></th>
		<td>
			<input type="number" id="nds_time_limit" name="nds_time_limit" value="<?php echo esc_attr( $time_limit ); ?>" min="1" max="72" class="small-text">
			<p class="description"><?php esc_html_e( 'Include articles published within this many hours.', 'newsdesk-sitemap' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="nds_max_urls"><?php esc_html_e( 'Max URLs', 'newsdesk-sitemap' ); ?></label></th>
		<td>
			<input type="number" id="nds_max_urls" name="nds_max_urls" value="<?php echo esc_attr( $max_urls ); ?>" min="1" max="1000" class="small-text">
			<p class="description"><?php esc_html_e( 'Maximum 1,000 URLs per sitemap allowed by Google.', 'newsdesk-sitemap' ); ?></p>
		</td>
	</tr>
</tbody>
</table>