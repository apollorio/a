<?php
/**
 * REST API: Triggers Controller
 *
 * Endpoints:
 *   GET  /membership/triggers — list available triggers
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TriggersController {

	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_MEMBERSHIP_REST_NAMESPACE;
	}

	public function register_routes(): void {

		register_rest_route(
			$this->namespace,
			'/membership/triggers',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_triggers' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'category' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function get_triggers( \WP_REST_Request $request ): \WP_REST_Response {
		$category = $request->get_param( 'category' );

		// Gatilhos WordPress
		$wordpress_triggers = array(
			'apollo_wp_login'        => 'Login no site',
			'apollo_wp_login_x_days' => 'Login por X dias consecutivos',
			'apollo_wp_not_login'    => 'Não fazer login por X dias',
			'apollo_new_comment'     => 'Publicar comentário',
			'apollo_new_post'        => 'Publicar post',
			'apollo_visit_a_post'    => 'Visitar um post',
			'apollo_visit_a_page'    => 'Visitar uma página',
			'user_register'          => 'Registrar conta',
			'apollo_daily_visit'     => 'Visita diária',
		);

		// Gatilhos Apollo
		$apollo_triggers = array(
			'apollo_points_on_birthday'    => 'Aniversário do usuário',
			'apollo_event_created'         => 'Criar evento',
			'apollo_event_attended'        => 'Participar de evento',
			'apollo_dj_followed'           => 'Seguir DJ',
			'apollo_group_joined'          => 'Entrar em grupo',
			'apollo_wow_reaction_received' => 'Receber reação Wow',
			'apollo_fav_added'             => 'Adicionar favorito',
			'apollo_profile_completed'     => 'Completar perfil',
			'apollo_quiz_passed'           => 'Passar no quiz de aptidão',
			'specific_achievement'         => 'Conquistar achievement específico',
			'any_achievement'              => 'Conquistar qualquer achievement',
			'all_achievements'             => 'Conquistar todos os achievements',
		);

		// Filtrar por categoria se fornecida
		if ( 'WordPress' === $category ) {
			$triggers = array( 'wordpress' => $wordpress_triggers );
		} elseif ( 'apollo' === $category ) {
			$triggers = array( 'apollo' => $apollo_triggers );
		} else {
			$triggers = array(
				'wordpress' => $wordpress_triggers,
				'apollo'    => $apollo_triggers,
			);
		}

		// Adicionar total count
		$total = 0;
		foreach ( $triggers as $group ) {
			$total += count( $group );
		}

		return new \WP_REST_Response(
			array(
				'triggers' => $triggers,
				'total'    => $total,
			)
		);
	}
}
