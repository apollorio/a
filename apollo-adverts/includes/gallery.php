<?php
/**
 * Gallery System
 *
 * Handles image upload via plupload, gallery rendering, AJAX handlers.
 * Adapted from WPAdverts includes/gallery.php (~854 lines).
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render gallery field for classified form
 * Adapted from adverts_gallery_content() in WPAdverts
 *
 * @param int    $post_id   Post ID (0 for new ads)
 * @param string $field_name Field name
 */
function apollo_adverts_gallery_render( int $post_id = 0, string $field_name = '_classified_gallery' ): void {
	$max_images = apollo_adverts_config( 'max_images', APOLLO_ADVERTS_MAX_IMAGES );

	$images = array();
	if ( $post_id ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_parent'    => $post_id,
				'post_mime_type' => 'image',
				'posts_per_page' => $max_images,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		foreach ( $attachments as $att ) {
			$thumb    = wp_get_attachment_image_src( $att->ID, 'classified-thumb' );
			$full     = wp_get_attachment_image_src( $att->ID, 'full' );
			$images[] = array(
				'attach_id' => $att->ID,
				'thumb'     => $thumb ? $thumb[0] : '',
				'full'      => $full ? $full[0] : '',
				'caption'   => $att->post_excerpt,
				'featured'  => ( (int) get_post_thumbnail_id( $post_id ) === $att->ID ),
			);
		}
	}

	// Plupload config
	$plupload_config = array(
		'runtimes'            => 'html5,flash,silverlight,html4',
		'browse_button'       => 'apollo-gallery-browse',
		'container'           => 'apollo-gallery-container',
		'max_file_size'       => wp_max_upload_size() . 'b',
		'url'                 => admin_url( 'admin-ajax.php' ),
		'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
		'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
		'multipart_params'    => array(
			'action'  => 'apollo_adverts_gallery_upload',
			'post_id' => $post_id,
			'nonce'   => wp_create_nonce( 'apollo_adverts_gallery' ),
		),
		'filters'             => array(
			'mime_types' => array(
				array(
					'title'      => __( 'Imagens', 'apollo-adverts' ),
					'extensions' => 'jpg,jpeg,png,webp',
				),
			),
		),
	);

	?>
	<div class="apollo-gallery-wrap" id="apollo-gallery-container">
		<div class="apollo-gallery-items" id="apollo-gallery-items" data-max="<?php echo esc_attr( (string) $max_images ); ?>">
			<?php foreach ( $images as $img ) : ?>
				<div class="apollo-gallery-item<?php echo $img['featured'] ? ' is-featured' : ''; ?>" data-attach-id="<?php echo esc_attr( (string) $img['attach_id'] ); ?>">
					<img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" />
					<div class="apollo-gallery-item-actions">
						<button type="button" class="apollo-gallery-btn-featured" title="<?php esc_attr_e( 'Definir como principal', 'apollo-adverts' ); ?>">
							<span class="dashicons dashicons-star-filled"></span>
						</button>
						<button type="button" class="apollo-gallery-btn-delete" title="<?php esc_attr_e( 'Remover', 'apollo-adverts' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</button>
					</div>
					<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>[]" value="<?php echo esc_attr( (string) $img['attach_id'] ); ?>" />
				</div>
			<?php endforeach; ?>
		</div>

		<div class="apollo-gallery-upload">
			<button type="button" id="apollo-gallery-browse" class="button">
				<span class="dashicons dashicons-camera"></span>
				<?php esc_html_e( 'Adicionar Fotos', 'apollo-adverts' ); ?>
			</button>
			<p class="description">
				<?php
				printf(
					esc_html__( 'Máximo %d fotos. Formatos: JPG, PNG, WebP.', 'apollo-adverts' ),
					$max_images
				);
				?>
			</p>
		</div>

		<div class="apollo-gallery-progress" id="apollo-gallery-progress" style="display:none;">
			<div class="apollo-gallery-progress-bar"><div class="apollo-gallery-progress-fill"></div></div>
			<span class="apollo-gallery-progress-text"></span>
		</div>
	</div>

	<script>
		if ( typeof window.apolloGalleryConfig === 'undefined' ) {
			window.apolloGalleryConfig = <?php echo wp_json_encode( $plupload_config ); ?>;
		}
	</script>
	<?php
}

/**
 * Register gallery field type with the Form system
 */
function apollo_adverts_register_gallery_field_type(): void {
	\Apollo\Adverts\Form::register_field_type(
		'gallery',
		function ( $field, $error, $form ) {
			$post_id = 0;
			$values  = $form->get_values();
			if ( isset( $values['_post_id'] ) ) {
				$post_id = (int) $values['_post_id'];
			}

			printf( '<div class="apollo-field-wrap apollo-field-gallery %s">', $error ? 'has-error' : '' );
			printf( '<label>%s</label>', esc_html( $field['label'] ) );
			apollo_adverts_gallery_render( $post_id, $field['name'] );
			if ( $error ) {
				printf( '<span class="apollo-field-error">%s</span>', esc_html( $error ) );
			}
			echo '</div>';
		}
	);
}
add_action( 'init', 'apollo_adverts_register_gallery_field_type', 9 );

/**
 * AJAX: Upload image
 * Adapted from WPAdverts adverts_gallery_upload AJAX handler
 */
function apollo_adverts_ajax_gallery_upload(): void {
	check_ajax_referer( 'apollo_adverts_gallery', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Faça login primeiro.', 'apollo-adverts' ) ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	// Validate post ownership (if editing)
	if ( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-adverts' ) ) );
		}
	}

	// Check image count
	$max_images = apollo_adverts_config( 'max_images', APOLLO_ADVERTS_MAX_IMAGES );
	if ( $post_id ) {
		$existing = (int) get_children(
			array(
				'post_parent'    => $post_id,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'numberposts'    => -1,
				'fields'         => 'ids',
			)
		);
		if ( is_array( $existing ) ) {
			$existing = count( $existing );
		}
		if ( $existing >= $max_images ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Máximo de %d fotos atingido.', 'apollo-adverts' ), $max_images ) ) );
		}
	}

	// If no post_id, create a temp classified post
	if ( ! $post_id ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => APOLLO_CPT_CLASSIFIED,
				'post_status' => 'classified_tmp',
				'post_title'  => __( 'Anúncio temporário', 'apollo-adverts' ),
				'post_author' => get_current_user_id(),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Erro ao criar rascunho.', 'apollo-adverts' ) ) );
		}
	}

	// Handle file upload
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$attach_id = media_handle_upload( 'file', $post_id );

	if ( is_wp_error( $attach_id ) ) {
		wp_send_json_error( array( 'message' => $attach_id->get_error_message() ) );
	}

	// Set as featured if first image
	if ( ! has_post_thumbnail( $post_id ) ) {
		set_post_thumbnail( $post_id, $attach_id );
	}

	$thumb = wp_get_attachment_image_src( $attach_id, 'classified-thumb' );
	$full  = wp_get_attachment_image_src( $attach_id, 'full' );

	wp_send_json_success(
		array(
			'attach_id' => $attach_id,
			'post_id'   => $post_id,
			'thumb'     => $thumb ? $thumb[0] : '',
			'full'      => $full ? $full[0] : '',
		)
	);
}
add_action( 'wp_ajax_apollo_adverts_gallery_upload', 'apollo_adverts_ajax_gallery_upload' );

/**
 * AJAX: Delete image
 * Adapted from WPAdverts adverts_gallery_delete AJAX handler
 */
function apollo_adverts_ajax_gallery_delete(): void {
	check_ajax_referer( 'apollo_adverts_gallery', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Faça login primeiro.', 'apollo-adverts' ) ) );
	}

	$attach_id = isset( $_POST['attach_id'] ) ? absint( $_POST['attach_id'] ) : 0;
	if ( ! $attach_id ) {
		wp_send_json_error( array( 'message' => __( 'ID inválido.', 'apollo-adverts' ) ) );
	}

	$attachment = get_post( $attach_id );
	if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
		wp_send_json_error( array( 'message' => __( 'Anexo não encontrado.', 'apollo-adverts' ) ) );
	}

	// Check ownership
	$parent = $attachment->post_parent ? get_post( $attachment->post_parent ) : null;
	if ( $parent && (int) $parent->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-adverts' ) ) );
	}

	wp_delete_attachment( $attach_id, true );

	wp_send_json_success( array( 'attach_id' => $attach_id ) );
}
add_action( 'wp_ajax_apollo_adverts_gallery_delete', 'apollo_adverts_ajax_gallery_delete' );

/**
 * AJAX: Reorder images
 * Adapted from WPAdverts adverts_gallery_update_order
 */
function apollo_adverts_ajax_gallery_reorder(): void {
	check_ajax_referer( 'apollo_adverts_gallery', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Faça login primeiro.', 'apollo-adverts' ) ) );
	}

	$order = isset( $_POST['order'] ) ? array_map( 'absint', (array) $_POST['order'] ) : array();
	if ( empty( $order ) ) {
		wp_send_json_error( array( 'message' => __( 'Ordem inválida.', 'apollo-adverts' ) ) );
	}

	foreach ( $order as $index => $attach_id ) {
		wp_update_post(
			array(
				'ID'         => $attach_id,
				'menu_order' => $index,
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_apollo_adverts_gallery_reorder', 'apollo_adverts_ajax_gallery_reorder' );

/**
 * AJAX: Set featured image
 * Adapted from WPAdverts gallery featured pattern
 */
function apollo_adverts_ajax_gallery_set_featured(): void {
	check_ajax_referer( 'apollo_adverts_gallery', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Faça login primeiro.', 'apollo-adverts' ) ) );
	}

	$attach_id = isset( $_POST['attach_id'] ) ? absint( $_POST['attach_id'] ) : 0;
	$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	if ( ! $attach_id || ! $post_id ) {
		wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'apollo-adverts' ) ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
		wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'apollo-adverts' ) ) );
	}

	set_post_thumbnail( $post_id, $attach_id );

	wp_send_json_success( array( 'attach_id' => $attach_id ) );
}
add_action( 'wp_ajax_apollo_adverts_gallery_set_featured', 'apollo_adverts_ajax_gallery_set_featured' );