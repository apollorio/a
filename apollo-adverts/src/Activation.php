<?php
/**
 * Activation Handler
 *
 * Sets default options, seeds terms, registers image sizes.
 * Adapted from WPAdverts activate() + Apollo Core Activation pattern.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	/**
	 * Run activation tasks
	 */
	public static function activate(): void {
		self::set_defaults();
		self::seed_intent_terms();
		self::seed_domain_terms();
		self::maybe_create_pages();
		flush_rewrite_rules();
	}

	/**
	 * Set default plugin options
	 * Adapted from WPAdverts defaults
	 */
	private static function set_defaults(): void {
		$defaults = array(
			'expiration_days'      => APOLLO_ADVERTS_DEFAULT_EXPIRATION,
			'max_images'           => APOLLO_ADVERTS_MAX_IMAGES,
			'posts_per_page'       => APOLLO_ADVERTS_POSTS_PER_PAGE,
			'currency_code'        => 'BRL',
			'currency_sign'        => 'R$',
			'currency_decimals'    => 2,
			'moderation'           => 'auto',     // auto|manual
			'allow_guest'          => false,
			'notify_admin_new'     => true,
			'notify_admin_expire'  => true,
			'notify_author_expire' => true,
			'expiring_days_before' => 3,
			'bp_profile_tab'       => true,
			'fav_enabled'          => true,
			'wow_enabled'          => true,
		);

		$existing = get_option( 'apollo_adverts_settings', array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		// Only set defaults for keys that don't exist
		$merged = wp_parse_args( $existing, $defaults );
		update_option( 'apollo_adverts_settings', $merged, true );

		// Set DB version
		update_option( 'apollo_adverts_version', Plugin::$version, true );
	}

	/**
	 * Seed classified_intent taxonomy terms
	 * Registry spec: vendo, compro, troco, alugo, procuro
	 */
	private static function seed_intent_terms(): void {
		if ( ! taxonomy_exists( APOLLO_TAX_CLASSIFIED_INTENT ) ) {
			return;
		}

		$terms = APOLLO_ADVERTS_INTENTS;
		foreach ( $terms as $slug => $label ) {
			if ( ! term_exists( $slug, APOLLO_TAX_CLASSIFIED_INTENT ) ) {
				wp_insert_term( $label, APOLLO_TAX_CLASSIFIED_INTENT, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Seed classified_domain taxonomy with initial terms
	 */
	private static function seed_domain_terms(): void {
		if ( ! taxonomy_exists( APOLLO_TAX_CLASSIFIED_DOMAIN ) ) {
			return;
		}

		$defaults = array(
			'equipamento' => __( 'Equipamento', 'apollo-adverts' ),
			'servico'     => __( 'Serviço', 'apollo-adverts' ),
			'espaco'      => __( 'Espaço', 'apollo-adverts' ),
			'veiculo'     => __( 'Veículo', 'apollo-adverts' ),
			'imovel'      => __( 'Imóvel', 'apollo-adverts' ),
			'outro'       => __( 'Outro', 'apollo-adverts' ),
		);

		foreach ( $defaults as $slug => $label ) {
			if ( ! term_exists( $slug, APOLLO_TAX_CLASSIFIED_DOMAIN ) ) {
				wp_insert_term( $label, APOLLO_TAX_CLASSIFIED_DOMAIN, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Create default pages if they don't exist
	 * Registry spec: anuncios (archive), criar-anuncio (form)
	 */
	private static function maybe_create_pages(): void {
		// Archive page
		$archive_id = get_option( 'apollo_adverts_archive_page_id', 0 );
		if ( ! $archive_id || ! get_post( $archive_id ) ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Anúncios', 'apollo-adverts' ),
					'post_name'    => 'anuncios',
					'post_content' => '[apollo_classifieds]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => get_current_user_id() ?: 1,
				)
			);
			if ( ! is_wp_error( $page_id ) ) {
				update_option( 'apollo_adverts_archive_page_id', $page_id, true );
			}
		}

		// Submit page
		$submit_id = get_option( 'apollo_adverts_submit_page_id', 0 );
		if ( ! $submit_id || ! get_post( $submit_id ) ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Criar Anúncio', 'apollo-adverts' ),
					'post_name'    => 'criar-anuncio',
					'post_content' => '[apollo_classified_form]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => get_current_user_id() ?: 1,
				)
			);
			if ( ! is_wp_error( $page_id ) ) {
				update_option( 'apollo_adverts_submit_page_id', $page_id, true );
			}
		}
	}
}
