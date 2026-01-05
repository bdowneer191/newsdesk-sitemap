<?php
/**
 * General Settings Tab
 *
 * This template displays the primary configuration options for the news sitemap,
 * including publication name, language, and time limits.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (General Settings Tab logic)
 * IMPLEMENTATION: NDS-prefixed implementation with strict escaping and Google News validation.
 */

// Prevent direct access - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve current options with defaults from activator
$publication_name = get_option( 'nds_publication_name', get_bloginfo( 'name' ) );
$language         = get_option( 'nds_language', 'en' );
$time_limit       = (int) get_option( 'nds_time_limit', 48 );
$max_urls         = (int) get_option( 'nds_max_urls', 1000 );
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="nds_publication_name">
					<?php esc_html_e( 'Publication Name', 'newsdesk-sitemap' ); ?>
					<span class="required" style="color: #dc3232;">*</span>
				</label>
			</th>
			<td>
				<input type="text" id="nds_publication_name" name="nds_publication_name" 
					   value="<?php echo esc_attr( $publication_name ); ?>" class="regular-text" required>
				<p class="description">
					<?php esc_html_e( 'The name of your news publication. This MUST match the name used in the Google Publisher Center exactly.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_language">
					<?php esc_html_e( 'Default Language', 'newsdesk-sitemap' ); ?>
				</label>
			</th>
			<td>
				<select id="nds_language" name="nds_language">
					<?php
					$languages = array(
						'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
						'it' => 'Italian', 'pt' => 'Portuguese', 'ru' => 'Russian', 'ja' => 'Japanese',
						'ko' => 'Korean', 'zh' => 'Chinese', 'ar' => 'Arabic', 'hi' => 'Hindi'
					);

					foreach ( $languages as $code => $name ) {
						printf(
							'<option value="%s" %s>%s (%s)</option>',
							esc_attr( $code ),
							selected( $language, $code, false ),
							esc_html( $name ),
							esc_html( $code )
						);
					}
					?>
				</select>
				<p class="description">
					<?php esc_html_e( 'The primary language of your articles (ISO 639-1 code).', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_time_limit">
					<?php esc_html_e( 'Time Limit', 'newsdesk-sitemap' ); ?>
				</label>
			</th>
			<td>
				<input type="number" id="nds_time_limit" name="nds_time_limit" 
					   value="<?php echo esc_attr( $time_limit ); ?>" min="1" max="48" class="small-text">
				<?php esc_html_e( 'hours', 'newsdesk-sitemap' ); ?>
				<p class="description">
					<?php esc_html_e( 'Google News only includes articles published within the last 48 hours.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_max_urls">
					<?php esc_html_e( 'Maximum URLs', 'newsdesk-sitemap' ); ?>
				</label>
			</th>
			<td>
				<input type="number" id="nds_max_urls" name="nds_max_urls" 
					   value="<?php echo esc_attr( $max_urls ); ?>" min="1" max="1000" class="small-text">
				<p class="description">
					<?php esc_html_e( 'Max URLs per sitemap file (Google limit: 1000). Excess URLs will trigger pagination.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Post Types', 'newsdesk-sitemap' ); ?>
			</th>
			<td>
				<?php
				$included_types = get_option( 'nds_included_post_types', array( 'post' ) );
				$post_types     = get_post_types( array( 'public' => true ), 'objects' );

				foreach ( $post_types as $post_type ) {
					if ( in_array( $post_type->name, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
						continue;
					}

					printf(
						'<label style="display:block; margin-bottom: 5px;">' .
						'<input type="checkbox" name="nds_included_post_types[]" value="%s" %s> %s' .
						'</label>',
						esc_attr( $post_type->name ),
						checked( in_array( $post_type->name, $included_types, true ), true, false ),
						esc_html( $post_type->label )
					);
				}
				?>
				<p class="description">
					<?php esc_html_e( 'Select which content types should be tracked in the News Sitemap.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>