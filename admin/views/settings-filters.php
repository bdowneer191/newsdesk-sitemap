<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$min_word_count = get_option( 'nds_min_word_count', 80 );
// Categories and Tags logic would go here, simplified for brevity
?>
<table class="form-table">
<tbody>
	<tr>
		<th scope="row"><label for="nds_min_word_count"><?php esc_html_e( 'Minimum Word Count', 'newsdesk-sitemap' ); ?></label></th>
		<td>
			<input type="number" id="nds_min_word_count" name="nds_min_word_count" value="<?php echo esc_attr( $min_word_count ); ?>" class="small-text">
			<p class="description"><?php esc_html_e( 'Exclude thin content automatically.', 'newsdesk-sitemap' ); ?></p>
		</td>
	</tr>
</tbody>
</table>