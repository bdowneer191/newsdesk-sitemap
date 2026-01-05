<?php
/**
 * Content Filters Settings Tab
 *
 * This template handles the exclusion of specific categories and tags,
 * as well as minimum quality thresholds like word count.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (Content Filters Tab logic)
 * IMPLEMENTATION: NDS-prefixed implementation with category/tag multi-select UI.
 */

// Prevent direct access - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve current filter options
$excluded_categories = (array) get_option( 'nds_excluded_categories', array() );
$excluded_tags       = (array) get_option( 'nds_excluded_tags', array() );
$min_word_count      = (int) get_option( 'nds_min_word_count', 80 );
?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Exclude Categories', 'newsdesk-sitemap' ); ?>
			</th>
			<td>
				<div class="nds-term-list-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff; max-width: 450px;">
					<?php
					$categories = get_categories( array( 'hide_empty' => 0 ) );
					if ( ! empty( $categories ) ) :
						foreach ( $categories as $category ) :
							?>
							<label style="display: block; margin-bottom: 5px;">
								<input type="checkbox" name="nds_excluded_categories[]" value="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( in_array( $category->term_id, $excluded_categories ) ); ?>>
								<?php echo esc_html( $category->name ); ?>
							</label>
							<?php
						endforeach;
					else :
						esc_html_e( 'No categories found.', 'newsdesk-sitemap' );
					endif;
					?>
				</div>
				<p class="description">
					<?php esc_html_e( 'Selected categories will be completely ignored by the News Sitemap.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<?php esc_html_e( 'Exclude Tags', 'newsdesk-sitemap' ); ?>
			</th>
			<td>
				<div class="nds-term-list-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff; max-width: 450px;">
					<?php
					$tags = get_tags( array( 'hide_empty' => 0 ) );
					if ( ! empty( $tags ) ) :
						foreach ( $tags as $tag ) :
							?>
							<label style="display: block; margin-bottom: 5px;">
								<input type="checkbox" name="nds_excluded_tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>" <?php checked( in_array( $tag->term_id, $excluded_tags ) ); ?>>
								<?php echo esc_html( $tag->name ); ?>
							</label>
							<?php
						endforeach;
					else :
						esc_html_e( 'No tags found.', 'newsdesk-sitemap' );
					endif;
					?>
				</div>
				<p class="description">
					<?php esc_html_e( 'Selected tags will be completely ignored by the News Sitemap.', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="nds_min_word_count"><?php esc_html_e( 'Minimum Word Count', 'newsdesk-sitemap' ); ?></label>
			</th>
			<td>
				<input type="number" id="nds_min_word_count" name="nds_min_word_count" value="<?php echo esc_attr( $min_word_count ); ?>" class="small-text" min="0">
				<p class="description">
					<?php esc_html_e( 'Articles with fewer words than this limit will be treated as thin content and excluded (Google requirement).', 'newsdesk-sitemap' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>