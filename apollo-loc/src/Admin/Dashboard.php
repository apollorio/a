<?php
/**
 * Admin Dashboard — Apollo Local admin panel
 *
 * @package Apollo\Local
 */

declare(strict_types=1);

namespace Apollo\Local\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
	}

	/**
	 * Register admin submenu under Apollo.
	 */
	public function register_menu(): void {
		add_submenu_page(
			'apollo',
			__( 'Locais', 'apollo-local' ),
			__( 'Locais', 'apollo-local' ),
			'manage_options',
			'apollo-local',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_page(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Apollo Local — Gerenciar Locais', 'apollo-local' ) . '</h1>';
		echo '<p>' . esc_html__( 'Gerencie os locais cadastrados no sistema.', 'apollo-local' ) . '</p>';
		echo '</div>';
	}
}
