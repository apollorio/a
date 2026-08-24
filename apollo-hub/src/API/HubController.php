<?php

/**
 * REST Controller — Apollo Hub
 *
 * Endpoints conforme apollo-registry.json:
 * - GET         /hubs              — lista hubs públicos
 * - GET|PUT     /hubs/{username}   — ler/atualizar hub
 * - GET|PUT     /hubs/{username}/links — ler/atualizar links (legacy)
 * - GET|PUT     /hubs/{username}/blocks — ler/atualizar blocos
 *
 * Adicional (não listado no registry, necessário para UX):
 * - GET         /hubs/{username}/share/{post_id} — share URLs de evento
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HubController {


	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_HUB_REST_NAMESPACE;
	}

	/**
	 * Registra todas as rotas REST.
	 */
	public function register_routes(): void {

		// GET /hubs
		register_rest_route(
			$this->namespace,
			'/hubs',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_hubs' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 20,
						'minimum'           => 1,
						'maximum'           => 50,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET|PUT /hubs/{username}
		register_rest_route(
			$this->namespace,
			'/hubs/(?P<username>[a-zA-Z0-9._-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_hub' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'username' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_user',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_hub' ),
					'permission_callback' => array( $this, 'can_edit_hub' ),
					'args'                => $this->get_update_args(),
				),
			)
		);

		// GET|PUT /hubs/{username}/links
		register_rest_route(
			$this->namespace,
			'/hubs/(?P<username>[a-zA-Z0-9._-]+)/links',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_links' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'username' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_user',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_links' ),
					'permission_callback' => array( $this, 'can_edit_hub' ),
					'args'                => array(
						'username' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_user',
						),
						'links'    => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array(
								'type'       => 'object',
								'properties' => array(
									'title'  => array( 'type' => 'string' ),
									'url'    => array(
										'type'   => 'string',
										'format' => 'uri',
									),
									'icon'   => array( 'type' => 'string' ),
									'active' => array(
										'type'    => 'boolean',
										'default' => true,
									),
								),
							),
						),
					),
				),
			)
		);

		// GET|PUT /hubs/{username}/blocks — blocos tipados
		register_rest_route(
			$this->namespace,
			'/hubs/(?P<username>[a-zA-Z0-9._-]+)/blocks',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_blocks' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'username' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_user',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_blocks' ),
					'permission_callback' => array( $this, 'can_edit_hub' ),
					'args'                => array(
						'username' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_user',
						),
						'blocks'   => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array(
								'type'       => 'object',
								'properties' => array(
									'type'   => array(
										'type'     => 'string',
										'required' => true,
									),
									'id'     => array( 'type' => 'string' ),
									'active' => array(
										'type'    => 'boolean',
										'default' => true,
									),
									'data'   => array( 'type' => 'object' ),
								),
							),
						),
					),
				),
			)
		);

		// GET /hubs/{username}/share/{post_id} — share URLs nativas
		register_rest_route(
			$this->namespace,
			'/hubs/(?P<username>[a-zA-Z0-9._-]+)/share/(?P<post_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_share_urls' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_user',
					),
					'post_id'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /hubs/me — cria/retorna hub do usuário logado
		register_rest_route(
			$this->namespace,
			'/hubs/me',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_hub' ),
				'permission_callback' => array( $this, 'is_logged_in' ),
			)
		);
	}

	// ──────────────────────────────────────────────────────────────────────────
	// CALLBACKS
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * GET /hubs — lista paginada de hubs públicos.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response
	 */
	public function get_hubs( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$query = new \WP_Query(
			array(
				'post_type'      => APOLLO_HUB_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$hubs = array();
		foreach ( $query->posts as $post ) {
			$hubs[] = $this->format_hub( $post );
		}

		$response = rest_ensure_response( $hubs );
		$response->header( 'X-Apollo-Total', (string) $query->found_posts );
		$response->header( 'X-Apollo-Pages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * GET /hubs/{username} — retorna hub público.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_hub( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$username = sanitize_user( $request->get_param( 'username' ) );
		$hub      = apollo_hub_get_by_username( $username );

		if ( ! $hub ) {
			return new \WP_Error( 'hub_not_found', __( 'Hub não encontrado.', 'apollo-hub' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $this->format_hub( $hub, true ) );
	}

	/**
	 * PUT /hubs/{username} — atualiza hub do usuário logado.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_hub( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$username = sanitize_user( $request->get_param( 'username' ) );
		$hub      = apollo_hub_get_by_username( $username );

		// Se não existe, auto-provision para o usuário atual
		if ( ! $hub ) {
			$post_id = apollo_hub_ensure_current_user_hub();
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			$hub = get_post( $post_id );
		}

		$bio = $request->get_param( 'bio' );
		if ( null !== $bio ) {
			update_post_meta( $hub->ID, '_hub_bio', substr( sanitize_textarea_field( $bio ), 0, APOLLO_HUB_BIO_MAX_LEN ) );
		}

		$theme = $request->get_param( 'theme' );
		if ( $theme && array_key_exists( $theme, APOLLO_HUB_THEMES ) ) {
			update_post_meta( $hub->ID, '_hub_theme', sanitize_key( $theme ) );
		}

		$avatar = $request->get_param( 'avatar' );
		if ( null !== $avatar ) {
			update_post_meta( $hub->ID, '_hub_avatar', absint( $avatar ) );
		}

		$avatar_type = $request->get_param( 'avatar_type' );
		if ( $avatar_type && in_array( $avatar_type, array( 'normal', 'morphism' ), true ) ) {
			update_post_meta( $hub->ID, '_hub_avatar_type', sanitize_key( $avatar_type ) );
		}

		$cover = $request->get_param( 'cover' );
		if ( null !== $cover ) {
			update_post_meta( $hub->ID, '_hub_cover', absint( $cover ) );
		}

		$socials = $request->get_param( 'socials' );
		if ( is_array( $socials ) ) {
			update_post_meta( $hub->ID, '_hub_socials', wp_json_encode( $this->sanitize_socials( $socials ) ) );
		}

		$custom_css = $request->get_param( 'custom_css' );
		if ( null !== $custom_css ) {
			update_post_meta( $hub->ID, '_hub_custom_css', wp_strip_all_tags( $custom_css ) );
		}

		// Handle blocks if provided via main update
		$blocks = $request->get_param( 'blocks' );
		if ( is_array( $blocks ) ) {
			apollo_hub_save_blocks( $hub->ID, $blocks );
		}

		apollo_hub_bust_cache( $hub->ID );
		do_action( 'apollo/hub/updated', $hub->ID );

		$hub = get_post( $hub->ID );
		return rest_ensure_response( $this->format_hub( $hub, true ) );
	}

	/**
	 * GET /hubs/{username}/blocks — retorna blocos tipados do hub.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_blocks( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$username = sanitize_user( $request->get_param( 'username' ) );
		$hub      = apollo_hub_get_by_username( $username );

		if ( ! $hub ) {
			return new \WP_Error( 'hub_not_found', __( 'Hub não encontrado.', 'apollo-hub' ), array( 'status' => 404 ) );
		}

		$blocks = apollo_hub_get_blocks( $hub->ID );
		return rest_ensure_response( $blocks );
	}

	/**
	 * PUT /hubs/{username}/blocks — substitui blocos do hub.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_blocks( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$username = sanitize_user( $request->get_param( 'username' ) );
		$hub      = apollo_hub_get_by_username( $username );

		if ( ! $hub ) {
			$post_id = apollo_hub_ensure_current_user_hub();
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			$hub = get_post( $post_id );
		}

		$blocks = (array) $request->get_param( 'blocks' );
		apollo_hub_save_blocks( $hub->ID, $blocks );

		do_action( 'apollo/hub/blocks_updated', $hub->ID, $blocks );

		return rest_ensure_response( apollo_hub_get_blocks( $hub->ID ) );
	}

	/**
	 * GET /hubs/{username}/links — retorna links do hub (legacy).
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_links( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$username = sanitize_user( $request->get_param( 'username' ) );
		$hub      = apollo_hub_get_by_username( $username );

		if ( ! $hub ) {
			return new \WP_Error( 'hub_not_found', __( 'Hub não encontrado.', 'apollo-hub' ), array( 'status' => 404 ) );
		}

		$data = apollo_hub_get_data( $hub->ID );
		return rest_ensure_response( $data['links'] );
	}

	/**
	 * PUT /hubs/{username}/links — substitui links do hub.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_links( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$username = sanitize_user( $request->get_param( 'username' ) );
		$hub      = apollo_hub_get_by_username( $username );

		if ( ! $hub ) {
			// Auto-provision
			$post_id = apollo_hub_ensure_current_user_hub();
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			$hub = get_post( $post_id );
		}

		$links = (array) $request->get_param( 'links' );

		// Limita a APOLLO_HUB_LINKS_MAX links
		$links = array_slice( $links, 0, APOLLO_HUB_LINKS_MAX );

		// Sanitiza cada link
		$clean_links = array_map(
			function ( $link ) {
				return array(
					'title'  => sanitize_text_field( $link['title'] ?? '' ),
					'url'    => esc_url_raw( $link['url'] ?? '' ),
					'icon'   => sanitize_text_field( $link['icon'] ?? 'ri-link-m' ),
					'active' => (bool) ( $link['active'] ?? true ),
				);
			},
			$links
		);

		// Remove links sem URL
		$clean_links = array_filter( $clean_links, fn( $l ) => ! empty( $l['url'] ) );
		$clean_links = array_values( $clean_links );

		update_post_meta( $hub->ID, '_hub_links', wp_json_encode( $clean_links ) );
		apollo_hub_bust_cache( $hub->ID );

		do_action( 'apollo/hub/links_updated', $hub->ID, $clean_links );

		return rest_ensure_response( $clean_links );
	}

	/**
	 * GET /hubs/{username}/share/{post_id} — URLs de compartilhamento nativas.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_share_urls( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$post    = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return new \WP_Error( 'post_not_found', __( 'Post não encontrado.', 'apollo-hub' ), array( 'status' => 404 ) );
		}

		$urls = apollo_hub_event_share_urls( $post_id );

		return rest_ensure_response(
			array(
				'post_id'    => $post_id,
				'post_title' => get_the_title( $post_id ),
				'post_url'   => get_permalink( $post_id ),
				'share_urls' => $urls,
			)
		);
	}

	/**
	 * GET /hubs/me — retorna hub do usuário logado (auto-provision se não existir).
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_my_hub( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$post_id = apollo_hub_ensure_current_user_hub();

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$hub = get_post( $post_id );
		return rest_ensure_response( $this->format_hub( $hub, true ) );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// PERMISSIONS
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Verifica se usuário logado pode editar o hub do {username}.
	 *
	 * @param  \WP_REST_Request $request Requisição.
	 * @return bool|\WP_Error
	 */
	public function can_edit_hub( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'rest_forbidden', __( 'Faça login para editar seu Hub.', 'apollo-hub' ), array( 'status' => 401 ) );
		}

		$username     = sanitize_user( $request->get_param( 'username' ) );
		$current_user = wp_get_current_user();

		// Admin pode editar qualquer hub
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Usuário só pode editar o próprio hub
		if ( $current_user->user_login !== $username ) {
			return new \WP_Error( 'rest_forbidden', __( 'Você só pode editar seu próprio Hub.', 'apollo-hub' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Verifica se usuário está logado.
	 *
	 * @return bool|\WP_Error
	 */
	public function is_logged_in(): bool|\WP_Error {
		if ( is_user_logged_in() ) {
			return true;
		}
		return new \WP_Error( 'rest_forbidden', __( 'Autenticação requerida.', 'apollo-hub' ), array( 'status' => 401 ) );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// HELPERS
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Formata post hub para resposta REST.
	 *
	 * @param  \WP_Post $post      Post hub.
	 * @param  bool     $full      Inclui dados completos (links, sociais, CSS)?
	 * @return array
	 */
	private function format_hub( \WP_Post $post, bool $full = false ): array {
		$data    = apollo_hub_get_data( $post->ID );
		$author  = get_userdata( $post->post_author );
		$hub_url = get_permalink( $post->ID );

		// Avatar: prioridade attachment > user meta Instagram > Gravatar
		$avatar_url = '';
		if ( $data['avatar'] ) {
			$avatar_url = (string) wp_get_attachment_image_url( $data['avatar'], 'medium' );
		}
		if ( ! $avatar_url && $author ) {
			$avatar_url = (string) get_user_meta( $author->ID, '_apollo_avatar_url', true );
		}
		if ( ! $avatar_url && $author ) {
			$avatar_url = get_avatar_url( $author->ID, array( 'size' => 200 ) );
		}

		$cover_url = $data['cover'] ? (string) wp_get_attachment_image_url( $data['cover'], 'large' ) : '';

		$result = array(
			'id'         => $post->ID,
			'username'   => $post->post_name,
			'name'       => get_the_title( $post->ID ),
			'bio'        => $data['bio'],
			'theme'      => $data['theme'],
			'avatar_url' => $avatar_url,
			'cover_url'  => $cover_url,
			'hub_url'    => $hub_url,
			'edit_url'   => home_url( '/' . APOLLO_HUB_EDIT_SLUG ),
		);

		if ( $full ) {
			$result['blocks']     = $data['blocks'];
			$result['links']      = $data['links'];
			$result['socials']    = $data['socials'];
			$result['custom_css'] = '';  // CSS não é exposto publicamente no REST

			// Eventos do usuário (integração com apollo-events)
			if ( $post->post_author ) {
				$result['events'] = $this->get_user_events( (int) $post->post_author );
			}
		}

		return apply_filters( 'apollo/hub/rest_response', $result, $post, $full );
	}

	/**
	 * Busca eventos publicados do usuário (integração apollo-events).
	 *
	 * @param  int $user_id User ID.
	 * @return array
	 */
	private function get_user_events( int $user_id ): array {
		if ( ! post_type_exists( 'event' ) ) {
			return array();
		}

		$events = get_posts(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 5,
				'orderby'        => 'meta_value',
				'meta_key'       => '_event_date_start',
				'order'          => 'DESC',
			)
		);

		return array_map(
			function ( $event ) {
				return array(
					'id'        => $event->ID,
					'title'     => get_the_title( $event->ID ),
					'url'       => get_permalink( $event->ID ),
					'date'      => get_post_meta( $event->ID, '_event_date_start', true ),
					'thumbnail' => get_the_post_thumbnail_url( $event->ID, 'medium' ),
					'share'     => apollo_hub_event_share_urls( $event->ID ),
				);
			},
			$events
		);
	}

	/**
	 * Sanitiza array de redes sociais.
	 *
	 * @param  array $socials Array de sociais.
	 * @return array
	 */
	private function sanitize_socials( array $socials ): array {
		$allowed_networks = array_keys( APOLLO_HUB_SOCIAL_ICONS );
		$clean            = array();

		foreach ( $socials as $item ) {
			$network = sanitize_key( $item['network'] ?? '' );
			$url     = esc_url_raw( $item['url'] ?? '' );

			if ( $network && $url && in_array( $network, $allowed_networks, true ) ) {
				$clean[] = array(
					'network' => $network,
					'url'     => $url,
					'icon'    => APOLLO_HUB_SOCIAL_ICONS[ $network ],
				);
			}
		}

		return $clean;
	}

	/**
	 * Argumentos de atualização do hub.
	 *
	 * @return array
	 */
	private function get_update_args(): array {
		return array(
			'username'   => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_user',
			),
			'bio'        => array(
				'type'              => 'string',
				'maxLength'         => APOLLO_HUB_BIO_MAX_LEN,
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'theme'      => array(
				'type'              => 'string',
				'enum'              => array_keys( APOLLO_HUB_THEMES ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'avatar'     => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'cover'      => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'socials'    => array( 'type' => 'array' ),
			'custom_css' => array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_strip_all_tags',
			),
			'blocks'     => array( 'type' => 'array' ),
		);
	}
}
