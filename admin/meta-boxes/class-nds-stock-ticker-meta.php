<?php
/**
 * Stock Ticker Meta Box
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/meta-boxes
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (Meta Box Implementation logic)
 * IMPLEMENTATION: NDS_Stock_Ticker_Meta for Google News <news:stock_tickers> tag.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Stock_Ticker_Meta {

	/**
	 * Register the meta box for allowed post types
	 * [cite: 456-467]
	 */
	public function add_meta_box() {
		$post_types = get_option( 'nds_included_post_types', array( 'post' ) );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'nds_stock_tickers',
				__( 'NewsDesk: Stock Tickers', 'newsdesk-sitemap' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the text input for stock tickers
	 * [cite: 541-555]
	 */
	public function render( $post ) {
		wp_nonce_field( 'nds_save_stock_tickers', 'nds_stock_tickers_nonce' );
		$value = get_post_meta( $post_id = $post->ID, '_nds_stock_tickers', true );
		?>
		<div class="nds-meta-field">
			<label for="nds_stock_tickers_field" style="display:block; margin-bottom: 5px;">
				<strong><?php esc_html_e( 'Stock Tickers:', 'newsdesk-sitemap' ); ?></strong>
			</label>
			<input type="text" id="nds_stock_tickers_field" name="nds_stock_tickers" value="<?php echo esc_attr( $value ); ?>" class="widefat" placeholder="NASDAQ:GOOG, NYSE:IBM">
			<p class="description">
				<?php esc_html_e( 'Comma-separated list of stock tickers mentioned in this article.', 'newsdesk-sitemap' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save and sanitize stock ticker input
	 [cite_start]* [cite: 632-636]
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['nds_stock_tickers_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nds_stock_tickers_nonce'] ), 'nds_save_stock_tickers' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['nds_stock_tickers'] ) ) {
			// Sanitize tickers: uppercase letters, colons, and commas allowed
			$tickers = sanitize_text_field( wp_unslash( $_POST['nds_stock_tickers'] ) );
			update_post_meta( $post_id, '_nds_stock_tickers', strtoupper( $tickers ) );
		}
	}
}