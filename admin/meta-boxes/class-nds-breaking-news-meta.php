<?php
/**
 * Breaking News Meta Box
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/meta-boxes
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (Meta Box Implementation logic)
 * IMPLEMENTATION: NDS_Breaking_News_Meta for priority sitemap sorting.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Breaking_News_Meta {

	/**
	 * Register the meta box for allowed post types
	 * [cite: 456-467]
	 */
	public function add_meta_box() {
		$post_types = get_option( 'nds_included_post_types', array( 'post' ) );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'nds_breaking_news',
				__( 'NewsDesk: Breaking News', 'newsdesk-sitemap' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render the checkbox field
	 * [cite: 497-510]
	 */
	public function render( $post ) {
		wp_nonce_field( 'nds_save_breaking_news', 'nds_breaking_news_nonce' );
		$value = get_post_meta( $post->ID, '_nds_breaking_news', true );
		?>
		<div class="nds-meta-field">
			<label for="nds_breaking_news_field">
				<input type="checkbox" id="nds_breaking_news_field" name="nds_breaking_news" value="1" <?php checked( $value, '1' ); ?>>
				<strong><?php esc_html_e( 'Mark as Breaking News', 'newsdesk-sitemap' ); ?></strong>
			</label>
			<p class="description">
				<?php esc_html_e( 'Prioritizes this article at the top of the Google News sitemap.', 'newsdesk-sitemap' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save the meta value with security validation
	 [cite_start]* [cite: 606-625]
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['nds_breaking_news_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nds_breaking_news_nonce'] ), 'nds_save_breaking_news' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$val = isset( $_POST['nds_breaking_news'] ) ? '1' : '0';
		update_post_meta( $post_id, '_nds_breaking_news', $val );
	}
}