<?php
/**
 * Profile Handler Component
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users\Components;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile Handler class
 */
class ProfileHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		// AJAX handlers
		add_action( 'wp_ajax_apollo_update_profile', array( $this, 'ajax_update_profile' ) );
		add_action( 'wp_ajax_apollo_upload_avatar', array( $this, 'ajax_upload_avatar' ) );
		add_action( 'wp_ajax_apollo_upload_cover', array( $this, 'ajax_upload_cover' ) );
		add_action( 'wp_ajax_apollo_delete_avatar', array( $this, 'ajax_delete_avatar' ) );
		add_action( 'wp_ajax_apollo_delete_cover', array( $this, 'ajax_delete_cover' ) );
	}

	/**
	 * AJAX: Update profile
	 *
	 * @return void
	 */
	public function ajax_update_profile(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado.' ) ) );
		}

		$user_id = get_current_user_id();

		// Sanitize and update fields
		$fields = array(
			'_apollo_social_name'     => sanitize_text_field( $_POST['social_name'] ?? '' ),
			'_apollo_bio'             => sanitize_textarea_field( $_POST['bio'] ?? '' ),
			'_apollo_website'         => esc_url_raw( $_POST['website'] ?? '' ),
			'_apollo_youtube_url'     => esc_url_raw( $_POST['youtube_url'] ?? '' ),
			'_apollo_soundcloud_url'  => esc_url_raw( $_POST['soundcloud_url'] ?? '' ),
			'_apollo_phone'           => sanitize_text_field( $_POST['phone'] ?? '' ),
			'user_location'           => sanitize_text_field( $_POST['location'] ?? '' ),
			'instagram'               => wp_get_current_user()->user_login, // Always set to username
			'_apollo_privacy_profile' => sanitize_text_field( $_POST['privacy_profile'] ?? 'public' ),
			'_apollo_privacy_email'   => ! empty( $_POST['privacy_email'] ),
		);

		foreach ( $fields as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		// Update display_name if social_name provided
		if ( ! empty( $fields['_apollo_social_name'] ) ) {
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $fields['_apollo_social_name'],
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Perfil atualizado com sucesso!' ),
			)
		);
	}

	/**
	 * AJAX: Upload avatar
	 *
	 * @return void
	 */
	public function ajax_upload_avatar(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado.' ) ) );
		}

		if ( empty( $_FILES['avatar'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Nenhum arquivo enviado.' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$user_id = get_current_user_id();

		// Upload file
		$attachment_id = media_handle_upload( 'avatar', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		// Delete old avatar
		$old_avatar = get_user_meta( $user_id, 'custom_avatar', true );
		if ( $old_avatar ) {
			wp_delete_attachment( $old_avatar, true );
		}

		// Save new avatar
		update_user_meta( $user_id, 'custom_avatar', $attachment_id );
		update_user_meta( $user_id, 'avatar_thumb', wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) );

		wp_send_json_success(
			array(
				'message'    => __( 'Avatar atualizado!' ),
				'avatar_url' => wp_get_attachment_image_url( $attachment_id, 'medium' ),
			)
		);
	}

	/**
	 * AJAX: Upload cover image
	 *
	 * @return void
	 */
	public function ajax_upload_cover(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado.' ) ) );
		}

		if ( empty( $_FILES['cover'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Nenhum arquivo enviado.' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$user_id = get_current_user_id();

		// Upload file
		$attachment_id = media_handle_upload( 'cover', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		// Delete old cover
		$old_cover = get_user_meta( $user_id, 'cover_image', true );
		if ( $old_cover ) {
			wp_delete_attachment( $old_cover, true );
		}

		// Save new cover
		update_user_meta( $user_id, 'cover_image', $attachment_id );

		wp_send_json_success(
			array(
				'message'   => __( 'Capa atualizada!' ),
				'cover_url' => wp_get_attachment_image_url( $attachment_id, 'full' ),
			)
		);
	}

	/**
	 * AJAX: Delete avatar
	 *
	 * @return void
	 */
	public function ajax_delete_avatar(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado.' ) ) );
		}

		$user_id = get_current_user_id();

		$avatar_id = get_user_meta( $user_id, 'custom_avatar', true );
		if ( $avatar_id ) {
			wp_delete_attachment( $avatar_id, true );
		}

		delete_user_meta( $user_id, 'custom_avatar' );
		delete_user_meta( $user_id, 'avatar_thumb' );

		wp_send_json_success(
			array(
				'message' => __( 'Avatar removido.' ),
			)
		);
	}

	/**
	 * AJAX: Delete cover
	 *
	 * @return void
	 */
	public function ajax_delete_cover(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado.' ) ) );
		}

		$user_id = get_current_user_id();

		$cover_id = get_user_meta( $user_id, 'cover_image', true );
		if ( $cover_id ) {
			wp_delete_attachment( $cover_id, true );
		}

		delete_user_meta( $user_id, 'cover_image' );

		wp_send_json_success(
			array(
				'message' => __( 'Capa removida.' ),
			)
		);
	}
}
