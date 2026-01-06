<?php
/**
 * Content Filters Settings Tab
 *
 * This template handles editorial gatekeeping and exclusion rules to ensure
 * only high-quality, approved content reaches search engines.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve current options
$excluded_categories = (array) get_option( 'nds_excluded_categories', array() );
$excluded_tags       = (array) get_option( 'nds_excluded_tags', array() );
$excluded_authors    = (array) get_option( 'nds_excluded_authors', array() );
$require_approval    = get_option( 'nds_require_approval', false );
$min_word_count      = (int) get_option( 'nds_min_word_count', 80 );
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Editorial Gate', 'newsdesk-sitemap' ); ?>
			</th>
			<td>
				<label>
					<input type="checkbox" name="nds_require_approval" value="1" <?php checked( $require_approval, 1 ); ?>>
					<strong><?php esc_html_e( 'Enable Mandatory "Sitemap-Ready" Approval', 'newsdesk-sitemap' ); ?></strong>
				</label>
				<p class="description">
					<?php esc_html_e( 'Enterprise Workflow: If enabled, articles will only appear in the XML sitemap if an editor checks the "Sitemap-Ready" checkbox in the post sidebar.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Exclude Authors', 'newsdesk-sitemap' ); ?>
			</th>
			<td>
				<div class="nds-term-list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff; border-radius: 4px;">
					<?php
					// Fetch users with publication capabilities
					$authors = get_users( array(
						'capability__in' => array( 'edit_posts' ),
						'fields'         => array( 'ID', 'display_name' ),
					) );

					if ( ! empty( $authors ) ) {
						foreach ( $authors as $user ) {
							printf(
								'<label style="display:block; margin-bottom: 5px;"><input type="checkbox" name="nds_excluded_authors[]" value="%d" %s> %s</label>',
								$user->ID,
								checked( in_array( $user->ID, $excluded_authors ), true, false ),
								esc_html( $user->display_name )
							);
						}
					} else {
						esc_html_e( 'No staff accounts found.', 'newsdesk-sitemap' );
					}
					?>
				</div>
				<p class="description">
					<?php esc_html_e( 'Prevent content from guest contributors, interns, or automated bots from being indexed.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Exclude Categories', 'newsdesk-sitemap' ); ?>
			</th>
			<td>
				<div class="nds-term-list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff; border-radius: 4px;">
					<?php
					$categories = get_categories( array( 'hide_empty' => 0 ) );
					if ( ! empty( $categories ) ) {
						foreach ( $categories as $cat ) {
							printf(
								'<label style="display:block; margin-bottom: 5px;"><input type="checkbox" name="nds_excluded_categories[]" value="%d" %s> %s</label>',
								$cat->term_id,
								checked( in_array( $cat->term_id, $excluded_categories ), true, false ),
								esc_html( $cat->name )
							);
						}
					} else {
						esc_html_e( 'No categories found.', 'newsdesk-sitemap' );
					}
					?>
				</div>
				<p class="description">
					<?php esc_html_e( 'Exclude non-news categories like "Sponsored Content", "Promotions", or "Archives".', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Exclude Tags', 'newsdesk-sitemap' ); ?>
			</th>
			<td>
				<div class="nds-term-list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff; border-radius: 4px;">
					<?php
					$tags = get_tags( array( 'hide_empty' => 0 ) );
					if ( ! empty( $tags ) ) {
						foreach ( $tags as $tag ) {
							printf(
								'<label style="display:block; margin-bottom: 5px;"><input type="checkbox" name="nds_excluded_tags[]" value="%d" %s> %s</label>',
								$tag->term_id,
								checked( in_array( $tag->term_id, $excluded_tags ), true, false ),
								esc_html( $tag->name )
							);
						}
					} else {
						esc_html_e( 'No tags found.', 'newsdesk-sitemap' );
					}
					?>
				</div>
				<p class="description">
					<?php esc_html_e( 'Filter out articles associated with specific internal tracking tags or administrative labels.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_min_word_count"><?php esc_html_e( 'Minimum Word Count', 'newsdesk-sitemap' ); ?></label>
			</th>
			<td>
				<input type="number" id="nds_min_word_count" name="nds_min_word_count" value="<?php echo esc_attr( $min_word_count ); ?>" class="small-text">
				<span class="description" style="margin-left: 10px;"><?php esc_html_e( 'words', 'newsdesk-sitemap' ); ?></span>
				<p class="description">
					<?php esc_html_e( 'Google News favors high-quality journalism. Short snippets or "thin content" (under 80 words) are automatically excluded to protect your site authority.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>