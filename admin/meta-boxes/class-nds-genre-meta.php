<?php
/**
 * Genre Meta Box
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/meta-boxes
 */

/**
 * SOURCE: Part-5 of Complete Technical Implementation Guide (Meta Box Implementation logic)
 * IMPLEMENTATION: NDS_Genre_Meta for Google News <news:genres> tag.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Genre_Meta {

	/**
	 * Register the meta box for allowed post types
	 * [cite: 456-467]
	 */
	public function add_meta_box() {
		$post_types = get_option( 'nds_included_post_types', array( 'post' ) );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'nds_genre',
				__( 'NewsDesk: Genre', 'newsdesk-sitemap' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the selection dropdown with Google News valid genres
	 * [cite: 511-540]
	 */
	public function render( $post ) {
		wp_nonce_field( 'nds_save_genre', 'nds_genre_nonce' );
		$current = get_post_meta( $post->ID, '_nds_genre', true );
		
		$genres = array( 
			'PressRelease'  => __( 'Press Release', 'newsdesk-sitemap' ), 
			'Satire'        => __( 'Satire', 'newsdesk-sitemap' ), 
			'Blog'          => __( 'Blog', 'newsdesk-sitemap' ), 
			'OpEd'          => __( 'Opinion/Editorial', 'newsdesk-sitemap' ), 
			'Opinion'       => __( 'Opinion', 'newsdesk-sitemap' ), 
			'UserGenerated' => __( 'User Generated', 'newsdesk-sitemap' ) 
		);
		?>
		<div class="nds-meta-field">
			<label for="nds_genre_field" style="display:block; margin-bottom: 5px;">
				<strong><?php esc_html_e( 'Article Genre:', 'newsdesk-sitemap' ); ?></strong>
			</label>
			<select id="nds_genre_field" name="nds_genre" style="width:100%">
				<option value=""><?php esc_html_e( '-- Default (Blog) --', 'newsdesk-sitemap' ); ?></option>
				<?php foreach ( $genres as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php esc_html_e( 'Helps Google categorize your content accurately.', 'newsdesk-sitemap' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Save the selected genre with whitelist validation
	 [cite_start]* [cite: 626-631]
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['nds_genre_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nds_genre_nonce'] ), 'nds_save_genre' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		
		if ( isset( $_POST['nds_genre'] ) ) {
			$allowed_genres = array( 'PressRelease', 'Satire', 'Blog', 'OpEd', 'Opinion', 'UserGenerated' );
			$genre = in_array( $_POST['nds_genre'], $allowed_genres, true ) ? sanitize_text_field( $_POST['nds_genre'] ) : '';
			update_post_meta( $post_id, '_nds_genre', $genre );
		}
	}
}