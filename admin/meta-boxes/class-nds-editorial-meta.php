<?php
/**
 * Editorial Controls Meta Box
 *
 * Handles post-level approval for sitemap inclusion and major update signaling.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/meta-boxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Editorial_Meta {

	/**
	 * Add the meta box to supported post types
	 */
	public function add_meta_box() {
		$screens = get_option( 'nds_included_post_types', array( 'post' ) );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'nds_editorial_controls',
				__( 'News Sitemap: Editorial Controls', 'newsdesk-sitemap' ),
				array( $this, 'render_meta_box' ),
				$screen,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render the UI for editorial toggles
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'nds_editorial_meta_nonce', 'nds_editorial_meta_nonce' );

		$ready        = get_post_meta( $post->ID, '_nds_sitemap_ready', true );
		$last_major   = get_post_meta( $post->ID, '_nds_last_major_update', true );
		$is_mandatory = get_option( 'nds_require_approval', false );

		?>
		<div class="nds-meta-field">
			<label>
				<input type="checkbox" name="nds_sitemap_ready" value="1" <?php checked( $ready, '1' ); ?>>
				<strong><?php esc_html_e( 'Sitemap-Ready Approval', 'newsdesk-sitemap' ); ?></strong>
			</label>
			<p class="description">
				<?php if ( $is_mandatory ) : ?>
					<span style="color: #dc3232;"><?php esc_html_e( 'Required for indexing.', 'newsdesk-sitemap' ); ?></span>
				<?php else : ?>
					<?php esc_html_e( 'Force include this article.', 'newsdesk-sitemap' ); ?>
				<?php endif; ?>
			</p>
		</div>

		<hr />

		<div class="nds-meta-field">
			<label>
				<input type="checkbox" name="nds_signal_major_update" value="1">
				<strong><?php esc_html_e( 'Signal Major Update', 'newsdesk-sitemap' ); ?></strong>
			</label>
			<p class="description">
				<?php esc_html_e( 'Updates the sitemap timestamp to trigger a re-crawl. Use for significant content changes only.', 'newsdesk-sitemap' ); ?>
			</p>
			<?php if ( $last_major ) : ?>
				<small style="display:block; margin-top:5px;">
					<?php esc_html_e( 'Last Major Update:', 'newsdesk-sitemap' ); ?> 
					<code><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_major ) ) ); ?></code>
				</small>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save editorial metadata
	 */
	public function save_meta_box( $post_id, $post ) {
		if ( ! isset( $_POST['nds_editorial_meta_nonce'] ) || ! wp_verify_nonce( $_POST['nds_editorial_meta_nonce'], 'nds_editorial_meta_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Save "Sitemap-Ready" status
		$ready = isset( $_POST['nds_sitemap_ready'] ) ? '1' : '0';
		update_post_meta( $post_id, '_nds_sitemap_ready', $ready );

		// Handle "Major Update" signal
		if ( isset( $_POST['nds_signal_major_update'] ) && '1' === $_POST['nds_signal_major_update'] ) {
			update_post_meta( $post_id, '_nds_last_major_update', current_time( 'mysql' ) );
		}
	}
}