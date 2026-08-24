<?php
/**
 * Shortcode: [apollo_classified_form]
 *
 * Displays the ad creation/edit form.
 * Adapted from WPAdverts shortcode_adverts_add() form submission pattern.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [apollo_classified_form] shortcode handler
 * Adapted from WPAdverts shortcode_adverts_add()
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function apollo_adverts_shortcode_form( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'edit_id' => 0,
		),
		$atts,
		'apollo_classified_form'
	);

	wp_enqueue_style( 'apollo-adverts' );
	wp_enqueue_script( 'apollo-adverts' );
	wp_enqueue_script( 'apollo-adverts-gallery' );

	// Auth check
	if ( ! is_user_logged_in() ) {
		$login_url = function_exists( 'apollo_login_url' ) ? apollo_login_url() : wp_login_url( get_permalink() );
		return sprintf(
			'<p class="apollo-adverts-notice">%s <a href="%s">%s</a></p>',
			esc_html__( 'Você precisa estar logado para criar um anúncio.', 'apollo-adverts' ),
			esc_url( $login_url ),
			esc_html__( 'Entrar', 'apollo-adverts' )
		);
	}

	// Determine if editing
	$edit_id = absint( $atts['edit_id'] );
	if ( ! $edit_id && isset( $_GET['edit'] ) ) {
		$edit_id = absint( $_GET['edit'] );
	}

	// If editing, verify ownership
	$editing_post = null;
	if ( $edit_id ) {
		$editing_post = get_post( $edit_id );
		if ( ! $editing_post || $editing_post->post_type !== APOLLO_CPT_CLASSIFIED ) {
			return '<p class="apollo-adverts-notice">' . esc_html__( 'Anúncio não encontrado.', 'apollo-adverts' ) . '</p>';
		}
		if ( (int) $editing_post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return '<p class="apollo-adverts-notice">' . esc_html__( 'Você não tem permissão para editar este anúncio.', 'apollo-adverts' ) . '</p>';
		}
	}

	// Load form
	$form = \Apollo\Adverts\Form::load( 'publish' );

	// Handle success redirect
	if ( isset( $_GET['success'] ) ) {
		$msg = $edit_id
			? __( 'Anúncio atualizado com sucesso!', 'apollo-adverts' )
			: __( 'Anúncio criado com sucesso!', 'apollo-adverts' );

		$config = apollo_adverts_config();
		if ( $config['moderation'] === 'manual' && ! $edit_id ) {
			$msg .= ' ' . __( 'Ele passará por moderação antes de ser publicado.', 'apollo-adverts' );
		}

		return '<div class="apollo-adverts-success">' . esc_html( $msg ) . '</div>';
	}

	$errors  = array();
	$message = '';

	// Process form submission
	// Adapted from WPAdverts shortcode_adverts_add() POST handling
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['apollo_adverts_form_nonce'] ) && wp_verify_nonce( $_POST['apollo_adverts_form_nonce'], 'apollo_adverts_submit' ) ) {

		$form->bind( wp_unslash( $_POST ) );

		if ( $form->validate() ) {
			$values = $form->get_filtered_values();
			$status = ( apollo_adverts_config( 'moderation', 'auto' ) === 'manual' && ! $edit_id )
				? 'pending'
				: 'publish';

			$post_data = array(
				'post_type'    => APOLLO_CPT_CLASSIFIED,
				'post_title'   => $values['post_title'] ?? '',
				'post_content' => $values['post_content'] ?? '',
				'post_status'  => $status,
				'post_author'  => get_current_user_id(),
			);

			if ( $edit_id ) {
				$post_data['ID'] = $edit_id;
				$result          = wp_update_post( $post_data, true );
			} else {
				$result = wp_insert_post( $post_data, true );
			}

			if ( is_wp_error( $result ) ) {
				$message = $result->get_error_message();
			} else {
				$post_id = (int) $result;

				// Save meta fields
				$meta_fields = array(
					'_classified_price',
					'_classified_negotiable',
					'_classified_condition',
					'_classified_loc',
					'_classified_contact_phone',
					'_classified_contact_whatsapp',
				);

				foreach ( $meta_fields as $key ) {
					if ( isset( $values[ $key ] ) ) {
						update_post_meta( $post_id, $key, $values[ $key ] );
					}
				}

				// Set expiration
				if ( ! $edit_id ) {
					apollo_adverts_set_expiration( $post_id );
					update_post_meta( $post_id, '_classified_currency', 'BRL' );
				}

				// Assign taxonomy terms
				if ( ! empty( $values[ APOLLO_TAX_CLASSIFIED_DOMAIN ] ) ) {
					wp_set_object_terms( $post_id, sanitize_text_field( $values[ APOLLO_TAX_CLASSIFIED_DOMAIN ] ), APOLLO_TAX_CLASSIFIED_DOMAIN );
				}
				if ( ! empty( $values[ APOLLO_TAX_CLASSIFIED_INTENT ] ) ) {
					wp_set_object_terms( $post_id, sanitize_text_field( $values[ APOLLO_TAX_CLASSIFIED_INTENT ] ), APOLLO_TAX_CLASSIFIED_INTENT );
				}

				// Handle gallery attachments — reassign temp post attachments
				if ( isset( $_POST['_classified_gallery'] ) && is_array( $_POST['_classified_gallery'] ) ) {
					$gallery_ids = array_map( 'absint', $_POST['_classified_gallery'] );
					foreach ( $gallery_ids as $idx => $attach_id ) {
						if ( $attach_id && get_post( $attach_id ) ) {
							wp_update_post(
								array(
									'ID'          => $attach_id,
									'post_parent' => $post_id,
									'menu_order'  => $idx,
								)
							);
						}
					}
					// Set first as thumbnail if none
					if ( ! has_post_thumbnail( $post_id ) && ! empty( $gallery_ids[0] ) ) {
						set_post_thumbnail( $post_id, $gallery_ids[0] );
					}
				}

				// Fire hooks
				$hook = $edit_id ? 'apollo/classifieds/updated' : 'apollo/classifieds/created';
				do_action( $hook, $post_id, $values );

				// Log
				apollo_adverts_log(
					$edit_id ? 'classified_updated' : 'classified_created',
					array(
						'post_id' => $post_id,
						'user_id' => get_current_user_id(),
					)
				);

				// Redirect to success
				$redirect = add_query_arg(
					array(
						'success' => 1,
						'edit'    => $edit_id ? $edit_id : '',
					),
					get_permalink()
				);
				wp_safe_redirect( $redirect );
				exit;
			}
		} else {
			$errors = $form->get_errors();
		}
	} elseif ( $edit_id && $editing_post ) {
		// Pre-fill form with existing data
		$form->bind_from_post( $edit_id );
	}

	ob_start();

	$template_data = array(
		'form'    => $form,
		'edit_id' => $edit_id,
		'errors'  => $errors,
		'message' => $message,
		'post'    => $editing_post,
	);

	apollo_adverts_load_template( 'form.php', $template_data );

	return ob_get_clean();
}
