<?php
/**
 * Genre Meta Box
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Genre_Meta {

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

	public function render( $post ) {
		wp_nonce_field( 'nds_save_genre', 'nds_genre_nonce' );
		$current = get_post_meta( $post->ID, '_nds_genre', true );
		$genres = array( 'PressRelease', 'Satire', 'Blog', 'OpEd', 'Opinion', 'UserGenerated' );
		?>
		<select name="nds_genre" style="width:100%">
			<option value=""><?php _e( 'Default (Blog)', 'newsdesk-sitemap' ); ?></option>
			<?php foreach ( $genres as $genre ) : ?>
				<option value="<?php echo esc_attr( $genre ); ?>" <?php selected( $current, $genre ); ?>>
					<?php echo esc_html( $genre ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['nds_genre_nonce'] ) || ! wp_verify_nonce( $_POST['nds_genre_nonce'], 'nds_save_genre' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		
		if ( isset( $_POST['nds_genre'] ) ) {
			update_post_meta( $post_id, '_nds_genre', sanitize_text_field( $_POST['nds_genre'] ) );
		}
	}
}