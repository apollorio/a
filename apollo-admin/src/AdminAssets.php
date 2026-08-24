<?php

/**
 * Apollo Admin Assets — enqueue CSS/JS only on the Apollo admin page.
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminAssets {


	private static ?AdminAssets $instance = null;

	public static function get_instance(): AdminAssets {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue styles & scripts only on admin.php?page=apollo
	 */
	public function enqueue( string $hook ): void {
		// Only load on our own page
		if ( 'toplevel_page_apollo' !== $hook ) {
			return;
		}

		$version = APOLLO_ADMIN_VERSION;

		// ── RemixIcon (used throughout CPanel) ──
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.min.css',
			array(),
			'3.5.0'
		);

		// ── Google Fonts (Space Grotesk, Space Mono, Shrikhand) ──
		wp_enqueue_style(
			'apollo-cpanel-fonts',
			'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Syne:wght@400..800&display=swap',
			array(),
			$version
		);

		// ── Suppress WP admin chrome so CPanel renders full-width ──
		$hide_wp_chrome = '
			#adminmenuwrap, #adminmenuback, #adminmenumain,
			#wpfooter, #screen-meta, #screen-meta-links {
				display: none !important;
			}
			#wpcontent, #wpbody, #wpbody-content {
				margin-left: 0 !important;
				padding: 0 !important;
			}
			#wpadminbar {
				display: none !important;
			}
			html.wp-toolbar {
				padding-top: 0 !important;
			}
			.apollo-cpanel-wrap {
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				z-index: 99999;
				overflow: hidden;
			}
			.notice:not(.apollo-notice),
			.update-nag,
			.updated {
				display: none !important;
			}
		';
		wp_add_inline_style( 'remixicon', $hide_wp_chrome );

		// ── Localize ajaxurl & nonce for the save handler ──
		wp_enqueue_script( 'jquery' );
		wp_add_inline_script(
			'jquery',
			'var apolloAdmin = ' . wp_json_encode(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'apollo_cpanel_save' ),
					'i18n'    => array(
						'saved'   => __( 'Configurações salvas!', 'apollo-admin' ),
						'error'   => __( 'Erro ao salvar.', 'apollo-admin' ),
						'confirm' => __( 'Tem certeza?', 'apollo-admin' ),
					),
				)
			) . ';',
			'before'
		);
	}
}
