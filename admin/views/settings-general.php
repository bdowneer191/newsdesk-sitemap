<?php
/**
 * General Settings Tab
 *
 * This template handles the core publication settings required for 
 * Google News Publisher Center compliance.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide [cite: 127-132]
 * IMPLEMENTATION: Integrated comprehensive language list and news-specific configuration fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch current options with defaults
$publication_name = get_option( 'nds_publication_name', get_bloginfo( 'name' ) );
$language         = get_option( 'nds_language', 'en' );
$time_limit       = (int) get_option( 'nds_time_limit', 48 );
$max_urls         = (int) get_option( 'nds_max_urls', 1000 );
$included_types   = get_option( 'nds_included_post_types', array( 'post' ) );
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
				<input type="text" id="nds_publication_name" name="nds_publication_name" value="<?php echo esc_attr( $publication_name ); ?>" class="regular-text" required>
				<p class="description">
					<?php esc_html_e( 'The name of your news publication. This MUST match the name used in the Google Publisher Center exactly to ensure indexing.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_language"><?php esc_html_e( 'Default Language', 'newsdesk-sitemap' ); ?></label>
			</th>
			<td>
				<select id="nds_language" name="nds_language">
					<?php
					$languages = array(
						'en' => 'English',
						'es' => 'Spanish',
						'fr' => 'French',
						'de' => 'German',
						'it' => 'Italian',
						'pt' => 'Portuguese',
						'ru' => 'Russian',
						'ja' => 'Japanese',
						'ko' => 'Korean',
						'zh' => 'Chinese',
						'ar' => 'Arabic',
						'hi' => 'Hindi',
					); 
					
					// Expanded language list 

					foreach ( $languages as $code => $name ) :
						printf(
							'<option value="%s" %s>%s (%s)</option>',
							esc_attr( $code ),
							selected( $language, $code, false ),
							esc_html( $name ),
							esc_html( $code )
						);
					endforeach;
					?>
				</select>
				<p class="description">
					<?php esc_html_e( 'Language code for articles (ISO 639-1). Google News requires the language to be correctly identified in the XML.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_time_limit"><?php esc_html_e( 'Time Limit', 'newsdesk-sitemap' ); ?></label>
			</th>
			<td>
				<input type="number" id="nds_time_limit" name="nds_time_limit" value="<?php echo esc_attr( $time_limit ); ?>" min="1" max="48" class="small-text">
				<span class="nds-unit"><?php esc_html_e( 'hours', 'newsdesk-sitemap' ); ?></span>
				<p class="description">
					<?php esc_html_e( 'Google News only includes articles published in the last 48 hours. Older articles will be automatically purged.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_max_urls"><?php esc_html_e( 'Maximum URLs', 'newsdesk-sitemap' ); ?></label>
			</th>
			<td>
				<input type="number" id="nds_max_urls" name="nds_max_urls" value="<?php echo esc_attr( $max_urls ); ?>" min="1" max="1000" class="small-text">
				<p class="description">
					<?php esc_html_e( 'Maximum number of URLs to include in each sitemap file (Google limit: 1000).', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Post Types', 'newsdesk-sitemap' ); ?></th>
			<td>
				<?php
				$post_types = get_post_types( array( 'public' => true ), 'objects' );

				foreach ( $post_types as $post_type ) :
					
					// Skip internal types
					
					if ( in_array( $post_type->name, array( 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
						continue;
					}

					printf(
						'<label style="display:block; margin-bottom: 5px;">
							<input type="checkbox" name="nds_included_post_types[]" value="%s" %s> %s
						</label>',
						esc_attr( $post_type->name ),
						checked( in_array( $post_type->name, $included_types, true ), true, false ),
						esc_html( $post_type->label )
					);
				endforeach;
				?>
				<p class="description">
					<?php esc_html_e( 'Select which post types should be eligible for the news sitemap.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>