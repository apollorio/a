<?php

/**
 * Shortcodes — Apollo Hub
 *
 * [apollo_hub username="johndoe"]  — exibe hub público de um usuário
 * [apollo_hub_builder]             — editor frontend (somente usuário logado)
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {


	public function __construct() {
		add_shortcode( 'apollo_hub', array( $this, 'shortcode_hub' ) );
		add_shortcode( 'apollo_hub_builder', array( $this, 'shortcode_builder' ) );
	}

	/**
	 * [apollo_hub username="johndoe"]
	 *
	 * @param  array|string $atts Atributos do shortcode.
	 * @return string HTML
	 */
	public function shortcode_hub( $atts ): string {
		$atts = shortcode_atts(
			array( 'username' => '' ),
			$atts,
			'apollo_hub'
		);

		$username = sanitize_user( $atts['username'] );
		if ( ! $username ) {
			return '';
		}

		$hub = apollo_hub_get_by_username( $username );
		if ( ! $hub ) {
			return '<p class="apollo-hub-not-found">' . esc_html__( 'Hub não encontrado.', 'apollo-hub' ) . '</p>';
		}

		ob_start();
		wp_enqueue_style( 'apollo-hub' );
		wp_enqueue_script( 'apollo-hub' );

		$template = ( new TemplateLoader() )->locate( 'single-hub.php' );
		if ( $template ) {
			// Injeta globals necessários para o template
			global $post;
            $post = $hub; // phpcs:ignore
			setup_postdata( $post );
			include $template;
			wp_reset_postdata();
		}

		return ob_get_clean();
	}

	/**
	 * [apollo_hub_builder]
	 *
	 * @return string HTML
	 */
	public function shortcode_builder(): string {
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( home_url( '/' . APOLLO_HUB_EDIT_SLUG ) );
			return '<p class="apollo-hub-login-required"><a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Faça login para editar seu Hub.', 'apollo-hub' ) . '</a></p>';
		}

		wp_enqueue_style( 'apollo-hub' );
		wp_enqueue_script( 'apollo-hub' );
		wp_enqueue_script( 'apollo-hub-builder' );

		ob_start();
		$template = ( new TemplateLoader() )->locate( 'edit-hub.php' );
		if ( $template ) {
			include $template;
		}
		return ob_get_clean();
	}
}
