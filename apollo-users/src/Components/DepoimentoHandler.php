<?php

/**
 * Depoimento Handler Component
 *
 * Testimonials stored as WordPress comments (comment_type = 'apollo_depoimento').
 * comment_parent = target user ID.
 * One depoimento per user per target.
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users\Components;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DepoimentoHandler {


	public const COMMENT_TYPE = 'apollo_depoimento';

	public function __construct() {
		// AJAX handlers
		add_action( 'wp_ajax_apollo_submit_depoimento', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_apollo_delete_depoimento', array( $this, 'ajax_delete' ) );

		// Exclude from normal comment queries
		add_action( 'pre_get_comments', array( $this, 'exclude_from_queries' ) );
	}

	/**
	 * AJAX: Submit a depoimento
	 */
	public function ajax_submit(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		$author_id = get_current_user_id();
		$target_id = absint( $_POST['target_id'] ?? 0 );
		$text      = sanitize_textarea_field( $_POST['text'] ?? '' );

		if ( ! $author_id ) {
			wp_send_json_error( array( 'message' => 'Login necessário.' ) );
		}
		if ( $author_id === $target_id ) {
			wp_send_json_error( array( 'message' => 'Não é possível escrever depoimento para si mesmo.' ) );
		}
		if ( empty( $text ) || strlen( $text ) < 10 ) {
			wp_send_json_error( array( 'message' => 'Mínimo de 10 caracteres.' ) );
		}
		if ( strlen( $text ) > 500 ) {
			wp_send_json_error( array( 'message' => 'Máximo de 500 caracteres.' ) );
		}

		// Check existing
		$existing = get_comments(
			array(
				'type'    => self::COMMENT_TYPE,
				'parent'  => $target_id,
				'user_id' => $author_id,
				'count'   => true,
			)
		);
		if ( $existing > 0 ) {
			wp_send_json_error( array( 'message' => 'Você já escreveu um depoimento para este usuário.' ) );
		}

		$author     = get_userdata( $author_id );
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => 0,
				'comment_parent'       => $target_id,
				'user_id'              => $author_id,
				'comment_author'       => $author->display_name,
				'comment_author_email' => $author->user_email,
				'comment_content'      => $text,
				'comment_type'         => self::COMMENT_TYPE,
				'comment_approved'     => 1,
			)
		);

		if ( ! $comment_id ) {
			wp_send_json_error( array( 'message' => 'Erro ao salvar.' ) );
		}

		// Build HTML for JS prepend
		$avatar_url = function_exists( 'Apollo\Users\apollo_get_user_avatar_url' )
			? \Apollo\Users\apollo_get_user_avatar_url( $author_id, 'thumb' )
			: get_avatar_url( $author_id, array( 'size' => 96 ) );

		$role_label = 'Membro';
		if ( function_exists( 'apollo_membership_get_user_badge' ) ) {
			$info = apollo_membership_get_badge_info( apollo_membership_get_user_badge( $author_id ) );
			$role_label = $info['label'] ?? $role_label;
		}

		$html = sprintf(
			'<div class="depo-card" data-depo-id="%d">
				<p class="depo-quote">%s</p>
				<div class="depo-author">
					<img src="%s" class="depo-avatar" alt="%s">
					<div class="depo-info">
						<div class="depo-name">%s</div>
						<div class="depo-role">%s</div>
					</div>
					<button class="depo-delete-btn" data-depo-id="%d" title="Remover">
						<i class="ri-close-fill"></i>
					</button>
				</div>
			</div>',
			$comment_id,
			esc_html( $text ),
			esc_url( $avatar_url ),
			esc_attr( $author->display_name ),
			esc_html( $author->display_name ),
			esc_html( $role_label ),
			$comment_id
		);

		wp_send_json_success(
			array(
				'html' => $html,
				'id'   => $comment_id,
			)
		);
	}

	/**
	 * AJAX: Delete a depoimento
	 */
	public function ajax_delete(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		$depo_id = absint( $_POST['depo_id'] ?? 0 );
		$comment = get_comment( $depo_id );

		if ( ! $comment || $comment->comment_type !== self::COMMENT_TYPE ) {
			wp_send_json_error( array( 'message' => 'Depoimento não encontrado.' ) );
		}

		$current_user = get_current_user_id();
		$is_author    = (int) $comment->user_id === $current_user;
		$is_target    = (int) $comment->comment_parent === $current_user;
		$is_admin     = current_user_can( 'manage_options' );

		if ( ! $is_author && ! $is_target && ! $is_admin ) {
			wp_send_json_error( array( 'message' => 'Sem permissão.' ) );
		}

		wp_delete_comment( $depo_id, true );

		wp_send_json_success( array( 'message' => 'Removido.' ) );
	}

	/**
	 * Exclude depoimentos from standard comment queries
	 */
	public function exclude_from_queries( $query ): void {
		if ( is_admin() ) {
			return;
		}

		$not_in = $query->query_vars['type__not_in'] ?? array();
		if ( ! is_array( $not_in ) ) {
			$not_in = array( $not_in );
		}
		if ( ! in_array( self::COMMENT_TYPE, $not_in, true ) ) {
			$not_in[]                          = self::COMMENT_TYPE;
			$query->query_vars['type__not_in'] = $not_in;
		}
	}
}
