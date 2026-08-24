<?php

/**
 * HubAdmin — Painel admin do Apollo Hub
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HubAdmin {


	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_submenu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Adiciona tab/submenu no painel Apollo principal (apollo-admin)
		add_filter( 'apollo_admin_tabs', array( $this, 'register_admin_tab' ) );
	}

	/**
	 * Adiciona submenu no WP-admin.
	 */
	public function add_admin_submenu(): void {
		add_submenu_page(
			'apollo',
			__( 'Apollo Hub', 'apollo-hub' ),
			__( 'Hubs', 'apollo-hub' ),
			'manage_options',
			'edit.php?post_type=' . APOLLO_HUB_CPT
		);
	}

	/**
	 * Registra tab no painel unificado da apollo-admin.
	 *
	 * @param  array $tabs Tabs atuais.
	 * @return array
	 */
	public function register_admin_tab( array $tabs ): array {
		$tabs['hub'] = array(
			'label'    => __( 'Hubs', 'apollo-hub' ),
			'icon'     => 'ri-connector-line',
			'url'      => admin_url( 'edit.php?post_type=' . APOLLO_HUB_CPT ),
			'external' => true,
		);
		return $tabs;
	}

	/**
	 * Enfileira assets no admin.
	 *
	 * @param string $hook Hook de tela atual.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		$allowed_hooks = array( 'edit.php', 'post.php', 'post-new.php' );
		global $post_type;

		if ( ! in_array( $hook, $allowed_hooks, true ) || APOLLO_HUB_CPT !== $post_type ) {
			return;
		}

		wp_enqueue_style(
			'apollo-hub-admin',
			APOLLO_HUB_URL . 'assets/css/hub-admin.css',
			array(),
			APOLLO_HUB_VERSION
		);

		// Blocks admin JS — only on post edit screen
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_script(
				'apollo-hub-admin-blocks',
				APOLLO_HUB_URL . 'assets/js/hub-admin-blocks.js',
				array(),
				APOLLO_HUB_VERSION,
				true
			);
		}
	}
}
