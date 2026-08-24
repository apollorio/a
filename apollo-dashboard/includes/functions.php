<?php
/**
 * Helper Functions
 *
 * @package Apollo\Dashboard
 */

declare(strict_types=1);

namespace Apollo\Dashboard;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get user dashboard layout
 *
 * @param int $user_id User ID.
 * @return array
 */
function get_user_dashboard_layout( int $user_id = 0 ): array {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	return get_user_meta( $user_id, '_apollo_dashboard_layout', true ) ?: array();
}

/**
 * Save user dashboard layout
 *
 * @param array $layout  Layout data.
 * @param int   $user_id User ID.
 * @return bool
 */
function save_user_dashboard_layout( array $layout, int $user_id = 0 ): bool {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	return (bool) update_user_meta( $user_id, '_apollo_dashboard_layout', $layout );
}

/**
 * Get available dashboard widgets
 *
 * @return array
 */
function get_available_widgets(): array {
	return apply_filters(
		'apollo_dashboard_widgets',
		array(
			'events'        => array(
				'id'          => 'events',
				'name'        => __( 'Meus Eventos', 'apollo-dashboard' ),
				'description' => __( 'Lista de eventos criados', 'apollo-dashboard' ),
			),
			'favs'          => array(
				'id'          => 'favs',
				'name'        => __( 'Favoritos', 'apollo-dashboard' ),
				'description' => __( 'Posts favoritados', 'apollo-dashboard' ),
			),
			'groups'        => array(
				'id'          => 'groups',
				'name'        => __( 'Grupos', 'apollo-dashboard' ),
				'description' => __( 'Grupos que participa', 'apollo-dashboard' ),
			),
			'notifications' => array(
				'id'          => 'notifications',
				'name'        => __( 'Notificações', 'apollo-dashboard' ),
				'description' => __( 'Notificações recentes', 'apollo-dashboard' ),
			),
			'stats'         => array(
				'id'          => 'stats',
				'name'        => __( 'Estatísticas', 'apollo-dashboard' ),
				'description' => __( 'Estatísticas do perfil', 'apollo-dashboard' ),
			),
		)
	);
}
