<?php

/**
 * REST API Controller — /apollo/v1/eventos
 *
 * Endpoints do registry:
 *   GET|POST   /eventos
 *   GET|PUT|DEL /eventos/{id}
 *   GET        /eventos/proximos
 *   GET        /eventos/passados
 *   GET        /eventos/hoje
 *   GET        /eventos/por-data/{date}
 *   GET        /eventos/por-local/{loc_id}
 *   GET        /eventos/por-dj/{dj_id}
 *   GET|POST|DEL /eventos/{id}/djs
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event\API;

use function Apollo\Event\apollo_event_get_banner;
use function Apollo\Event\apollo_event_get_djs;
use function Apollo\Event\apollo_event_get_loc;
use function Apollo\Event\apollo_event_is_gone;
use function Apollo\Event\apollo_event_is_valid_video_url;
use function Apollo\Event\apollo_event_kses_about;
use function Apollo\Event\apollo_event_option;
use function Apollo\Event\apollo_event_sanitize_video_url;
use function Apollo\Event\apollo_event_set_banner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EventsController {


	/** @var string */
	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_EVENT_REST_NAMESPACE;
	}

	/**
	 * Registra todas as rotas REST
	 */
	public function register_routes(): void {

		// GET|POST /eventos
		register_rest_route(
			$this->namespace,
			'/eventos',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_events' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_event' ),
					'permission_callback' => array( $this, 'can_edit' ),
					'args'                => $this->get_create_params(),
				),
			)
		);

		// GET|PUT|DELETE /eventos/{id}
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_event' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => array( $this, 'validate_event_id' ),
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_event' ),
					'permission_callback' => array( $this, 'can_edit_event' ),
					'args'                => $this->get_create_params(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_event' ),
					'permission_callback' => array( $this, 'can_delete_event' ),
				),
			)
		);

		// GET /eventos/proximos
		register_rest_route(
			$this->namespace,
			'/eventos/proximos',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_upcoming' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
			)
		);

		// GET /eventos/passados
		register_rest_route(
			$this->namespace,
			'/eventos/passados',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_past' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
			)
		);

		// GET /eventos/hoje
		register_rest_route(
			$this->namespace,
			'/eventos/hoje',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_today' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /eventos/por-data/{date}
		register_rest_route(
			$this->namespace,
			'/eventos/por-data/(?P<date>[\d-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_by_date' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'date' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value );
						},
					),
				),
			)
		);

		// GET /eventos/por-local/{loc_id}
		register_rest_route(
			$this->namespace,
			'/eventos/por-local/(?P<loc_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_by_loc' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'loc_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /eventos/por-dj/{dj_id}
		register_rest_route(
			$this->namespace,
			'/eventos/por-dj/(?P<dj_id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_by_dj' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'dj_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET|POST|DELETE /eventos/{id}/djs
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)/djs',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_event_djs' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_event_dj' ),
					'permission_callback' => array( $this, 'can_edit_event' ),
					'args'                => array(
						'dj_id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'slot'  => array(
							'type'     => 'object',
							'required' => false,
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_event_dj' ),
					'permission_callback' => array( $this, 'can_edit_event' ),
					'args'                => array(
						'dj_id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// GET /eventos/buscar
		register_rest_route(
			$this->namespace,
			'/eventos/buscar',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_events' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'        => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 12,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /eventos/calendario/{year}/{month}
		register_rest_route(
			$this->namespace,
			'/eventos/calendario/(?P<year>\d{4})/(?P<month>\d{1,2})',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_calendar_month' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'year'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'month' => array(
						'type'              => 'integer',
						'required'          => true,
						'minimum'           => 1,
						'maximum'           => 12,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /eventos/{id}/banner
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)/banner',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_event_banner' ),
					'permission_callback' => array( $this, 'can_edit_event' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_event_banner' ),
					'permission_callback' => array( $this, 'can_edit_event' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/eventos/validar-imagem',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_image_url' ),
				'permission_callback' => static function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'url' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);

		// POST /eventos/{id}/clonar
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)/clonar',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'clone_event' ),
				'permission_callback' => array( $this, 'can_edit_event' ),
			)
		);

		// GET /eventos/{id}/estatisticas — manage surface only (NO_EGO_COUNTERS:
		// vanity metrics are never public; also avoids leaking private-event RSVP data).
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)/estatisticas',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_event_stats' ),
				'permission_callback' => array( $this, 'can_edit_event' ),
			)
		);

		// GET /eventos/{id}/participantes + POST (RSVP) + DELETE (cancel)
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)/participantes',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_event_attendees' ),
					'permission_callback' => array( $this, 'can_edit_event' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rsvp_event' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
					'args'                => array(
						'id'     => array(
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => array( $this, 'validate_event_id' ),
						),
						'status' => array(
							'type'              => 'string',
							'default'           => 'going',
							'enum'              => array( 'going', 'interested', 'not_going' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_rsvp' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => array( $this, 'validate_event_id' ),
						),
					),
				),
			)
		);

		// GET /eventos/{id}/participantes/check-in — mod/admin only
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)/participantes/(?P<user_id>\d+)/check-in',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'check_in_attendee' ),
				// Author/co-author/admin of THIS event only — a bare edit_posts cap
				// would let any contributor check people in on someone else's event.
				'permission_callback' => array( $this, 'can_edit_event' ),
				'args'                => array(
					'id'      => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /eventos/{id}/notificar-warmup — toggle warm-up notifications
		register_rest_route(
			$this->namespace,
			'/eventos/(?P<id>\d+)/notificar-warmup',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_warmup_notify' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'id'    => array(
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => array( $this, 'validate_event_id' ),
					),
					'muted' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);

		// GET /eventos/meus — authenticated user's events
		register_rest_route(
			$this->namespace,
			'/eventos/meus',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_events' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 12,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /eventos/meus/rsvp — user's RSVP'd events
		register_rest_route(
			$this->namespace,
			'/eventos/meus/rsvp',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_rsvp_events' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 12,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'status'   => array(
						'type'              => 'string',
						'default'           => 'going',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// POST /eventos/lote — bulk operations
		register_rest_route(
			$this->namespace,
			'/eventos/lote',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'batch_events' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'action' => array(
						'type'              => 'string',
						'required'          => true,
						'enum'              => array( 'delete', 'publish', 'draft', 'cancel' ),
						'sanitize_callback' => 'sanitize_key',
					),
					'ids'    => array(
						'type'     => 'array',
						'required' => true,
						'items'    => array( 'type' => 'integer' ),
					),
				),
			)
		);

		/*
		 * THE /_agent_debug ROUTE WAS REMOVED HERE ON 2026-08-17, along with its
		 * two handlers. It was not merely debug clutter — it was a live hole:
		 *
		 *   · permission_callback was is_user_logged_in(), so ANY subscriber
		 *     could call it.
		 *   · POST wrote the caller's raw JSON body to disk, including
		 *     wp_upload_dir()/debug-c3f157.log — the uploads directory is
		 *     WEB-SERVED, so an authenticated user could append arbitrary
		 *     content to a publicly readable file, unbounded.
		 *   · GET returned the whole 100-entry ring buffer from the
		 *     apollo_agent_debug_c3f157 option to any logged-in user — i.e. one
		 *     user could read the debug payloads another user's browser sent.
		 *
		 * $apollo_rule.audit_verificator #8: REST permission_callback on every
		 * endpoint, never a write route open wider than it needs.
		 *
		 * The option row apollo_agent_debug_c3f157 may still exist on the live
		 * site; delete it with the rest of this cleanup.
		 */
	}

	/* agent_debug_log() and agent_debug_get() were deleted here on 2026-08-17
	   together with the /_agent_debug route. See the note in register_routes(). */

	// ─── Callbacks ─────────────────────────────────────────────────────

	/**
	 * GET /eventos — Listagem
	 */
	public function get_events( \WP_REST_Request $request ): \WP_REST_Response {
		$args  = $this->build_query_from_request( $request );
		$query = new \WP_Query( $args );

		return $this->paginated_response( $query, $request );
	}

	/**
	 * POST /eventos — Criar evento
	 */
	public function create_event( \WP_REST_Request $request ): \WP_REST_Response {
		$data = $request->get_json_params();
		if ( ! is_array( $data ) || empty( $data ) ) {
			$data = $request->get_params();
		}

		$data = $this->normalize_payload_shape( $data );

		$post_status = sanitize_text_field( (string) ( $data['post_status'] ?? 'draft' ) );
		if ( ! in_array( $post_status, array( 'publish', 'draft', 'pending' ), true ) ) {
			$post_status = 'draft';
		}
		// Non-publishers always get draft
		if ( ! current_user_can( 'publish_posts' ) ) {
			$post_status = 'draft';
		}

		$post_data = array(
			'post_type'    => APOLLO_EVENT_CPT,
			'post_status'  => $post_status,
			'post_author'  => get_current_user_id(),
			'post_title'   => sanitize_text_field( $data['title'] ?? '' ),
			'post_content' => apollo_event_kses_about( (string) ( $data['content'] ?? '' ) ),
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return new \WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 400 );
		}

		$this->save_event_meta( $post_id, $data );

		// Taxonomias
		$this->save_event_taxonomies( $post_id, $data );
		$this->save_event_coauthors( $post_id, $data );

		/**
		 * Ação após criar evento via REST (hook legado do apollo-events).
		 *
		 * @param int   $post_id ID do evento criado.
		 * @param array $data    Dados do request.
		 */
		do_action( 'apollo_event_rest_created', $post_id, $data );

		/**
		 * Hook do ecossistema Apollo — contrato ( int $post_id, string $action, array $payload ).
		 *
		 * O argumento #2 DEVE ser uma string: collectors de outros plugins
		 * (apollo-statistics HookCollector::on_content_action) declaram
		 * `string $action` e um array aqui derruba a request inteira com
		 * TypeError → HTTP 500. O payload vai no argumento #3.
		 *
		 * @param int    $post_id ID do evento.
		 * @param string $action  Nome da ação.
		 * @param array  $payload Dados do request.
		 */
		do_action( 'apollo/event/created', $post_id, 'created', $data );

		return new \WP_REST_Response( $this->prepare_event( $post_id ), 201 );
	}

	/**
	 * GET /eventos/{id} — Evento individual
	 */
	public function get_event( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );

		if ( ! $this->can_view_event( $id ) ) {
			return new \WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}

		return new \WP_REST_Response( $this->prepare_event( $id ) );
	}

	/**
	 * PUT /eventos/{id} — Atualizar evento
	 */
	public function update_event( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$data = $request->get_json_params();
		if ( ! is_array( $data ) || empty( $data ) ) {
			$data = $request->get_params();
		}

		// Same dual-shape tolerance as create_event() — a re-import must not
		// silently blank an event that imported correctly the first time.
		$data = $this->normalize_payload_shape( $data );

		$post_data = array( 'ID' => $id );

		if ( isset( $data['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['content'] ) ) {
			$post_data['post_content'] = apollo_event_kses_about( (string) $data['content'] );
		}
		$status_raw = $data['status'] ?? $data['post_status'] ?? null;
		if ( null !== $status_raw ) {
			$status = sanitize_text_field( (string) $status_raw );
			if ( in_array( $status, array( 'publish', 'draft', 'pending', 'private', 'future' ), true ) ) {
				if ( 'publish' === $status && ! current_user_can( 'publish_posts' ) ) {
					$status = 'draft';
				}
				$post_data['post_status'] = $status;
			}
		}

		wp_update_post( $post_data );

		$this->save_event_meta( $id, $data );
		$this->save_event_taxonomies( $id, $data );
		$this->save_event_coauthors( $id, $data );

		/**
		 * Ação após atualizar evento via REST (hook legado do apollo-events).
		 *
		 * @param int   $id   ID do evento.
		 * @param array $data Dados do request.
		 */
		do_action( 'apollo_event_rest_updated', $id, $data );

		/**
		 * Hook do ecossistema Apollo — contrato ( int $post_id, string $action, array $payload ).
		 * Ver nota em create_event(): argumento #2 é string, nunca array.
		 *
		 * @param int    $id      ID do evento.
		 * @param string $action  Nome da ação.
		 * @param array  $payload Dados do request.
		 */
		do_action( 'apollo/event/updated', $id, 'updated', $data );

		return new \WP_REST_Response( $this->prepare_event( $id ) );
	}

	/**
	 * DELETE /eventos/{id} — Excluir evento
	 */
	public function delete_event( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );

		/**
		 * Ação antes de excluir evento via REST
		 */
		do_action( 'apollo_event_rest_before_delete', $id );
		do_action( 'apollo/event/before-delete', $id );

		$result = wp_trash_post( $id );

		if ( ! $result ) {
			return new \WP_REST_Response( array( 'error' => 'Falha ao excluir evento.' ), 500 );
		}

		do_action( 'apollo/event/deleted', $id );

		return new \WP_REST_Response(
			array(
				'deleted' => true,
				'id'      => $id,
			)
		);
	}

	/**
	 * GET /eventos/proximos — Eventos futuros
	 */
	public function get_upcoming( \WP_REST_Request $request ): \WP_REST_Response {
		$args                 = $this->build_query_from_request( $request );
		$args['meta_query'][] = array(
			'key'     => '_event_start_date',
			'value'   => current_time( 'Y-m-d' ),
			'compare' => '>=',
			'type'    => 'DATE',
		);

		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/passados — Eventos passados
	 */
	public function get_past( \WP_REST_Request $request ): \WP_REST_Response {
		$args                 = $this->build_query_from_request( $request );
		$args['meta_query'][] = array(
			'key'     => '_event_start_date',
			'value'   => current_time( 'Y-m-d' ),
			'compare' => '<',
			'type'    => 'DATE',
		);
		$args['order']        = 'DESC';

		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/hoje — Eventos de hoje
	 */
	public function get_today( \WP_REST_Request $request ): \WP_REST_Response {
		$today = current_time( 'Y-m-d' );

		$args = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_time',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => $today,
					'compare' => '=',
				),
			),
		);

		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/por-data/{date} — Eventos por data
	 */
	public function get_by_date( \WP_REST_Request $request ): \WP_REST_Response {
		$date = sanitize_text_field( $request->get_param( 'date' ) );

		$args = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_time',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => $date,
					'compare' => '=',
				),
			),
		);

		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/por-local/{loc_id} — Eventos por local
	 */
	public function get_by_loc( \WP_REST_Request $request ): \WP_REST_Response {
		$loc_id = (int) $request->get_param( 'loc_id' );

		$args = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_loc_id',
					'value'   => $loc_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
		);

		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/por-dj/{dj_id} — Eventos por DJ
	 */
	public function get_by_dj( \WP_REST_Request $request ): \WP_REST_Response {
		$dj_id = (int) $request->get_param( 'dj_id' );

		// _event_dj_ids é serialized array — busca com LIKE
		$args = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_dj_ids',
					'value'   => sprintf( '"%d"', $dj_id ),
					'compare' => 'LIKE',
				),
			),
		);

		// Fallback: busca com i:{n};i:{dj_id}
		$args_alt                           = $args;
		$args_alt['meta_query'][0]['value'] = sprintf( 'i:%d;', $dj_id );

		$query = new \WP_Query( $args );

		// Se não encontrou, tentar formato serializado alternativo
		if ( ! $query->have_posts() ) {
			$query = new \WP_Query( $args_alt );
		}

		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/{id}/djs — DJs do evento
	 */
	public function get_event_djs( \WP_REST_Request $request ): \WP_REST_Response {
		$id  = (int) $request->get_param( 'id' );
		$djs = apollo_event_get_djs( $id );

		// Incluir slots
		$slots    = get_post_meta( $id, '_event_dj_slots', true ) ?: array();
		$enriched = array();

		foreach ( $djs as $dj ) {
			$dj_data = (array) $dj;
			// Procurar slot do DJ
			foreach ( $slots as $slot ) {
				if ( isset( $slot['dj_id'] ) && (int) $slot['dj_id'] === $dj['id'] ) {
					$dj_data['slot'] = $slot;
					break;
				}
			}
			$enriched[] = $dj_data;
		}

		return new \WP_REST_Response( $enriched );
	}

	/**
	 * POST /eventos/{id}/djs — Adicionar DJ ao evento
	 */
	public function add_event_dj( \WP_REST_Request $request ): \WP_REST_Response {
		$id    = (int) $request->get_param( 'id' );
		$dj_id = (int) $request->get_param( 'dj_id' );
		$slot  = $request->get_param( 'slot' );

		// Verificar se DJ existe
		$dj_post = get_post( $dj_id );
		if ( ! $dj_post || 'dj' !== $dj_post->post_type ) {
			return new \WP_REST_Response( array( 'error' => 'DJ não encontrado.' ), 404 );
		}

		// Adicionar DJ ao array
		$dj_ids = get_post_meta( $id, '_event_dj_ids', true ) ?: array();
		if ( ! is_array( $dj_ids ) ) {
			$dj_ids = array();
		}

		if ( ! in_array( $dj_id, $dj_ids, true ) ) {
			$dj_ids[] = $dj_id;
			update_post_meta( $id, '_event_dj_ids', $dj_ids );
		}

		// Salvar slot (se fornecido)
		if ( $slot && is_array( $slot ) ) {
			$slots = get_post_meta( $id, '_event_dj_slots', true ) ?: array();
			if ( ! is_array( $slots ) ) {
				$slots = array();
			}
			$slot['dj_id'] = $dj_id;
			$slots[]       = $slot;
			update_post_meta( $id, '_event_dj_slots', $slots );
		}

		/**
		 * Ação após adicionar DJ ao evento
		 */
		do_action( 'apollo_event_dj_added', $id, $dj_id, $slot );
		do_action( 'apollo/event/dj-added', $id, $dj_id, $slot );

		return new \WP_REST_Response(
			array(
				'added'    => true,
				'event_id' => $id,
				'dj_id'    => $dj_id,
			),
			201
		);
	}

	/**
	 * DELETE /eventos/{id}/djs — Remover DJ do evento
	 */
	public function remove_event_dj( \WP_REST_Request $request ): \WP_REST_Response {
		$id    = (int) $request->get_param( 'id' );
		$dj_id = (int) $request->get_param( 'dj_id' );

		// Remover do array de IDs
		$dj_ids = get_post_meta( $id, '_event_dj_ids', true ) ?: array();
		if ( is_array( $dj_ids ) ) {
			$dj_ids = array_values( array_filter( $dj_ids, fn( $d ) => (int) $d !== $dj_id ) );
			update_post_meta( $id, '_event_dj_ids', $dj_ids );
		}

		// Remover slot
		$slots = get_post_meta( $id, '_event_dj_slots', true ) ?: array();
		if ( is_array( $slots ) ) {
			$slots = array_values( array_filter( $slots, fn( $s ) => (int) ( $s['dj_id'] ?? 0 ) !== $dj_id ) );
			update_post_meta( $id, '_event_dj_slots', $slots );
		}

		/**
		 * Ação após remover DJ do evento
		 */
		do_action( 'apollo_event_dj_removed', $id, $dj_id );
		do_action( 'apollo/event/dj-removed', $id, $dj_id );

		return new \WP_REST_Response(
			array(
				'removed'  => true,
				'event_id' => $id,
				'dj_id'    => $dj_id,
			)
		);
	}

	// ─── New Callbacks ─────────────────────────────────────────────────

	/**
	 * GET /eventos/buscar
	 */
	public function search_events( \WP_REST_Request $request ): \WP_REST_Response {
		$q     = sanitize_text_field( (string) $request->get_param( 'q' ) );
		$args  = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => min( (int) ( $request->get_param( 'per_page' ) ?: 12 ), 100 ),
			'paged'          => max( (int) ( $request->get_param( 'page' ) ?: 1 ), 1 ),
			's'              => $q,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
		);
		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/calendario/{year}/{month}
	 * Returns events grouped by day for a given month.
	 */
	public function get_calendar_month( \WP_REST_Request $request ): \WP_REST_Response {
		$year  = (int) $request->get_param( 'year' );
		$month = (int) $request->get_param( 'month' );

		$date_from = sprintf( '%04d-%02d-01', $year, $month );
		$date_to   = date( 'Y-m-t', strtotime( $date_from ) );

		$args = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_event_start_date',
					'value'   => array( $date_from, $date_to ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$query  = new \WP_Query( $args );
		$by_day = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id         = get_the_ID();
				$start_date = get_post_meta( $id, '_event_start_date', true );
				$day        = (int) date( 'j', strtotime( $start_date ) );
				if ( ! isset( $by_day[ $day ] ) ) {
					$by_day[ $day ] = array();
				}
				$by_day[ $day ][] = array(
					'id'         => $id,
					'title'      => get_the_title(),
					'start_date' => $start_date,
					'start_time' => get_post_meta( $id, '_event_start_time', true ),
					'status'     => get_post_meta( $id, '_event_status', true ) ?: 'scheduled',
					'is_gone'    => apollo_event_is_gone( $id ),
					'banner'     => apollo_event_get_banner( $id, 'thumbnail' ),
					'permalink'  => get_permalink( $id ),
				);
			}
			wp_reset_postdata();
		}

		return new \WP_REST_Response(
			array(
				'year'   => $year,
				'month'  => $month,
				'total'  => $query->found_posts,
				'by_day' => $by_day,
			)
		);
	}

	/**
	 * POST /eventos/{id}/banner — Upload banner via multipart
	 */
	public function upload_event_banner( \WP_REST_Request $request ): \WP_REST_Response {
		$id    = (int) $request->get_param( 'id' );
		$files = $request->get_file_params();

		if ( empty( $files['banner']['tmp_name'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'No file uploaded.' ), 400 );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'banner', $id );
		if ( is_wp_error( $attachment_id ) ) {
			return new \WP_REST_Response( array( 'error' => $attachment_id->get_error_message() ), 500 );
		}

		apollo_event_set_banner( $id, (int) $attachment_id );
		apollo_event_heal_cover( $id );

		return new \WP_REST_Response(
			array(
				'banner_id'  => (int) $attachment_id,
				'banner_url' => wp_get_attachment_image_url( (int) $attachment_id, 'large' ),
				'thumbnail'  => (int) get_post_thumbnail_id( $id ),
			),
			201
		);
	}

	/**
	 * DELETE /eventos/{id}/banner
	 */
	public function delete_event_banner( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );
		apollo_event_set_banner( $id, '' );
		return new \WP_REST_Response( array( 'deleted' => true ) );
	}

	/**
	 * POST /eventos/validar-imagem — content-type image/* (not HTML).
	 */
	public function validate_image_url( \WP_REST_Request $request ) {
		$url = (string) $request->get_param( 'url' );
		$ok  = apollo_event_validate_remote_image( $url );
		if ( is_wp_error( $ok ) ) {
			return new \WP_REST_Response(
				array(
					'ok'    => false,
					'error' => $ok->get_error_message(),
				),
				400
			);
		}
		return new \WP_REST_Response(
			array(
				'ok'  => true,
				'url' => self::sanitize_image_ref( $url ),
			),
			200
		);
	}

	/**
	 * POST /eventos/{id}/clonar — Duplicates event, metadata & taxonomies
	 */
	public function clone_event( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );
		if ( ! $post || APOLLO_EVENT_CPT !== $post->post_type ) {
			return new \WP_REST_Response( array( 'error' => 'Event not found.' ), 404 );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => APOLLO_EVENT_CPT,
				'post_status'  => 'draft',
				'post_author'  => get_current_user_id(),
				'post_title'   => $post->post_title . ' (cópia)',
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			return new \WP_REST_Response( array( 'error' => $new_id->get_error_message() ), 500 );
		}

		// Copy all meta
		foreach ( APOLLO_EVENT_META_KEYS as $key ) {
			$val = get_post_meta( $id, $key, true );
			if ( '' !== $val ) {
				update_post_meta( $new_id, $key, $val );
			}
		}
		// Clear expiration flag on clone
		delete_post_meta( $new_id, '_event_is_gone' );

		// Copy taxonomies
		foreach ( array( APOLLO_EVENT_TAX_CATEGORY, APOLLO_EVENT_TAX_TYPE, APOLLO_EVENT_TAX_TAG, APOLLO_EVENT_TAX_SOUND, APOLLO_EVENT_TAX_SEASON ) as $tax ) {
			$terms = wp_get_object_terms( $id, $tax, array( 'fields' => 'slugs' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $new_id, $terms, $tax );
			}
		}

		do_action( 'apollo/event/cloned', $new_id, $id );

		return new \WP_REST_Response( $this->prepare_event( $new_id ), 201 );
	}

	/**
	 * GET /eventos/{id}/estatisticas
	 */
	public function get_event_stats( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$id    = (int) $request->get_param( 'id' );
		$table = $wpdb->prefix . 'apollo_event_rsvp';

		$views = (int) get_post_meta( $id, '_event_view_count', true );
		$fav   = (int) get_post_meta( $id, '_apollo_fav_count', true );
		$wow   = (int) get_post_meta( $id, '_apollo_wow_count', true );

		// RSVP counts
		$going      = 0;
		$interested = 0;
		$not_going  = 0;
		$checked_in = 0;

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$going      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_id=%d AND status='going'", $id ) );
			$interested = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_id=%d AND status='interested'", $id ) );
			$not_going  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_id=%d AND status='not_going'", $id ) );
			$checked_in = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE event_id=%d AND checked_in=1", $id ) );
		}

		return new \WP_REST_Response(
			array(
				'event_id'  => $id,
				'views'     => $views,
				'fav_count' => $fav,
				'wow_count' => $wow,
				'rsvp'      => array(
					'going'      => $going,
					'interested' => $interested,
					'not_going'  => $not_going,
					'checked_in' => $checked_in,
					'total'      => $going + $interested + $not_going,
				),
			)
		);
	}

	/**
	 * GET /eventos/{id}/participantes
	 */
	public function get_event_attendees( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$id    = (int) $request->get_param( 'id' );
		$table = $wpdb->prefix . 'apollo_event_rsvp';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return new \WP_REST_Response(
				array(
					'attendees' => array(),
					'total'     => 0,
				)
			);
		}

		$status = sanitize_text_field( (string) ( $request->get_param( 'status' ) ?: '' ) );
		$where  = $wpdb->prepare( 'WHERE event_id = %d', $id );
		if ( in_array( $status, array( 'going', 'interested', 'not_going' ), true ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}

		$rows = $wpdb->get_results( "SELECT user_id, status, checked_in, created_at FROM {$table} {$where} ORDER BY created_at DESC" );

		$attendees = array();
		foreach ( (array) $rows as $row ) {
			$user = get_userdata( (int) $row->user_id );
			if ( ! $user ) {
				continue;
			}
			$attendees[] = array(
				'user_id'    => (int) $row->user_id,
				'login'      => $user->user_login,
				'name'       => $user->display_name,
				'avatar'     => get_avatar_url( (int) $row->user_id ),
				'status'     => $row->status,
				'checked_in' => (bool) $row->checked_in,
				'rsvp_at'    => $row->created_at,
			);
		}

		return new \WP_REST_Response(
			array(
				'event_id'  => $id,
				'attendees' => $attendees,
				'total'     => count( $attendees ),
			)
		);
	}

	/**
	 * POST /eventos/{id}/participantes — RSVP to event
	 */
	public function rsvp_event( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$id     = (int) $request->get_param( 'id' );
		$uid    = get_current_user_id();
		$status = sanitize_text_field( (string) ( $request->get_param( 'status' ) ?: 'going' ) );
		if ( ! in_array( $status, array( 'going', 'interested', 'not_going' ), true ) ) {
			$status = 'going';
		}

		$table    = $wpdb->prefix . 'apollo_event_rsvp';
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE event_id=%d AND user_id=%d",
				$id,
				$uid
			)
		);

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 'status' => $status ),
				array(
					'event_id' => $id,
					'user_id'  => $uid,
				)
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'event_id'   => $id,
					'user_id'    => $uid,
					'status'     => $status,
					'checked_in' => 0,
					'created_at' => current_time( 'mysql' ),
				)
			);
		}

		do_action( 'apollo/event/rsvp', $id, $uid, $status );

		// Notify event author
		$author_id = (int) get_post_field( 'post_author', $id );
		if ( $author_id && $author_id !== $uid && function_exists( 'apollo_create_notification' ) ) {
			$user = get_userdata( $uid );
			apollo_create_notification(
				$author_id,
				'event_rsvp',
				sprintf( '%s marcou "%s" no seu evento.', $user ? $user->display_name : 'Alguém', get_the_title( $id ) ),
				get_avatar_url( $uid ),
				get_permalink( $id ),
				array(
					'event_id' => $id,
					'user_id'  => $uid,
					'status'   => $status,
				),
				array(
					'icon'    => 'ri-calendar-check-line',
					'channel' => 'apollo/event',
				)
			);
		}

		return new \WP_REST_Response(
			array(
				'rsvp'     => $status,
				'event_id' => $id,
			),
			200
		);
	}

	/**
	 * DELETE /eventos/{id}/participantes — Cancel RSVP
	 */
	public function cancel_rsvp( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$id  = (int) $request->get_param( 'id' );
		$uid = get_current_user_id();

		$wpdb->delete(
			$wpdb->prefix . 'apollo_event_rsvp',
			array(
				'event_id' => $id,
				'user_id'  => $uid,
			)
		);
		do_action( 'apollo/event/rsvp_cancelled', $id, $uid );

		return new \WP_REST_Response( array( 'cancelled' => true ) );
	}

	/**
	 * POST /eventos/{id}/notificar-warmup — Toggle warm-up mural notifications
	 *
	 * Stores muted event IDs in user meta _apollo_warmup_muted (array).
	 *
	 * @param \WP_REST_Request $request Request with id (event) and muted (bool).
	 * @return \WP_REST_Response
	 */
	public function toggle_warmup_notify( \WP_REST_Request $request ): \WP_REST_Response {
		$event_id = (int) $request->get_param( 'id' );
		$uid      = get_current_user_id();
		$muted    = (bool) $request->get_param( 'muted' );

		$muted_events = get_user_meta( $uid, '_apollo_warmup_muted', true );
		if ( ! is_array( $muted_events ) ) {
			$muted_events = array();
		}

		if ( $muted ) {
			if ( ! in_array( $event_id, $muted_events, true ) ) {
				$muted_events[] = $event_id;
			}
		} else {
			$muted_events = array_values( array_diff( $muted_events, array( $event_id ) ) );
		}

		update_user_meta( $uid, '_apollo_warmup_muted', $muted_events );

		return new \WP_REST_Response(
			array(
				'muted'    => $muted,
				'event_id' => $event_id,
			),
			200
		);
	}

	/**
	 * POST /eventos/{id}/participantes/{user_id}/check-in
	 */
	public function check_in_attendee( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$id      = (int) $request->get_param( 'id' );
		$user_id = (int) $request->get_param( 'user_id' );
		$table   = $wpdb->prefix . 'apollo_event_rsvp';

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE event_id=%d AND user_id=%d",
				$id,
				$user_id
			)
		);

		if ( ! $existing ) {
			// Auto-register as going on check-in (walk-ins)
			$wpdb->insert(
				$table,
				array(
					'event_id'   => $id,
					'user_id'    => $user_id,
					'status'     => 'going',
					'checked_in' => 1,
					'created_at' => current_time( 'mysql' ),
				)
			);
		} else {
			$wpdb->update(
				$table,
				array( 'checked_in' => 1 ),
				array(
					'event_id' => $id,
					'user_id'  => $user_id,
				)
			);
		}

		do_action( 'apollo/event/checked_in', $id, $user_id );

		return new \WP_REST_Response(
			array(
				'checked_in' => true,
				'event_id'   => $id,
				'user_id'    => $user_id,
			)
		);
	}

	/**
	 * GET /eventos/meus
	 */
	public function get_my_events( \WP_REST_Request $request ): \WP_REST_Response {
		$uid   = get_current_user_id();
		$args  = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'author'         => $uid,
			'posts_per_page' => min( (int) ( $request->get_param( 'per_page' ) ?: 12 ), 100 ),
			'paged'          => max( (int) ( $request->get_param( 'page' ) ?: 1 ), 1 ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * GET /eventos/meus/rsvp
	 */
	public function get_my_rsvp_events( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$uid    = get_current_user_id();
		$status = sanitize_text_field( (string) ( $request->get_param( 'status' ) ?: 'going' ) );
		if ( ! in_array( $status, array( 'going', 'interested', 'not_going' ), true ) ) {
			$status = 'going';
		}

		$table = $wpdb->prefix . 'apollo_event_rsvp';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return new \WP_REST_Response( array() );
		}

		$event_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT event_id FROM {$table} WHERE user_id=%d AND status=%s ORDER BY created_at DESC",
				$uid,
				$status
			)
		);

		if ( empty( $event_ids ) ) {
			$response = new \WP_REST_Response( array() );
			$response->header( 'X-WP-Total', 0 );
			$response->header( 'X-WP-TotalPages', 0 );
			return $response;
		}

		$per_page = min( (int) ( $request->get_param( 'per_page' ) ?: 12 ), 100 );
		$page     = max( (int) ( $request->get_param( 'page' ) ?: 1 ), 1 );

		$args  = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'post__in'       => array_map( 'intval', $event_ids ),
			'orderby'        => 'post__in',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		);
		$query = new \WP_Query( $args );
		return $this->paginated_response( $query, $request );
	}

	/**
	 * POST /eventos/lote
	 */
	public function batch_events( \WP_REST_Request $request ): \WP_REST_Response {
		$action = sanitize_key( (string) $request->get_param( 'action' ) );
		$ids    = array_map( 'intval', (array) $request->get_param( 'ids' ) );
		$done   = array();
		$failed = array();

		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( ! $post || APOLLO_EVENT_CPT !== $post->post_type ) {
				$failed[] = $id;
				continue;
			}
			if ( ! current_user_can( 'edit_post', $id ) ) {
				$failed[] = $id;
				continue;
			}
			switch ( $action ) {
				case 'delete':
					wp_trash_post( $id );
					$done[] = $id;
					break;
				case 'publish':
					wp_update_post(
						array(
							'ID'          => $id,
							'post_status' => 'publish',
						)
					);
					$done[] = $id;
					break;
				case 'draft':
					wp_update_post(
						array(
							'ID'          => $id,
							'post_status' => 'draft',
						)
					);
					$done[] = $id;
					break;
				case 'cancel':
					update_post_meta( $id, '_event_status', 'cancelled' );
					$done[] = $id;
					break;
				default:
					$failed[] = $id;
			}
		}

		return new \WP_REST_Response(
			array(
				'action' => $action,
				'done'   => $done,
				'failed' => $failed,
			)
		);
	}

	// ─── Permissions ───────────────────────────────────────────────────

	/**
	 * Pode criar eventos?
	 */
	public function can_edit( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Pode editar/excluir este evento?
	 */
	public function can_edit_event( \WP_REST_Request $request ): bool {
		$id = (int) $request->get_param( 'id' );
		if ( current_user_can( 'edit_post', $id ) ) {
			return true;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$post = get_post( $id );
		if ( ! $post || APOLLO_EVENT_CPT !== $post->post_type ) {
			return false;
		}
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}
		return function_exists( 'apollo_event_user_is_coauthor' )
			? apollo_event_user_is_coauthor( $id, $user_id )
			: false;
	}

	/**
	 * Pode excluir este evento? Autor ou moderador do site — equipe não pode excluir.
	 */
	public function can_delete_event( \WP_REST_Request $request ): bool {
		$id = (int) $request->get_param( 'id' );
		if ( current_user_can( 'apollo_moderate_content' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		$post = get_post( $id );
		if ( ! $post || APOLLO_EVENT_CPT !== $post->post_type ) {
			return false;
		}
		return (int) $post->post_author === $user_id;
	}

	/**
	 * Valida se o ID é de um evento
	 */
	public function validate_event_id( $value ): bool {
		$post = get_post( (int) $value );
		return $post && APOLLO_EVENT_CPT === $post->post_type;
	}

	// ─── Helpers ───────────────────────────────────────────────────────

	/**
	 * Prepara dados do evento para resposta REST
	 */
	private function prepare_event( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		$djs = apollo_event_get_djs( $post_id );
		$loc = apollo_event_get_loc( $post_id );

		$data = array(
			'id'           => $post_id,
			'title'        => $post->post_title,
			'slug'         => $post->post_name,
			'content'      => $post->post_content,
			'excerpt'      => $post->post_excerpt,
			'status'       => $post->post_status,
			'author'       => (int) $post->post_author,
			'permalink'    => get_permalink( $post_id ),
			'banner'       => apollo_event_get_banner( $post_id ),
			'bg_color'     => get_post_meta( $post_id, '_event_bg_color', true ) ?: '#0a0a0a',
			'thumbnail'    => get_the_post_thumbnail_url( $post_id, 'medium' ) ?: null,
			'start_date'   => get_post_meta( $post_id, '_event_start_date', true ),
			'end_date'     => get_post_meta( $post_id, '_event_end_date', true ),
			'start_time'   => get_post_meta( $post_id, '_event_start_time', true ),
			'end_time'     => get_post_meta( $post_id, '_event_end_time', true ),
			'dj_ids'       => get_post_meta( $post_id, '_event_dj_ids', true ) ?: array(),
			'djs'          => $djs,
			'dj_slots'     => get_post_meta( $post_id, '_event_dj_slots', true ) ?: array(),
			'loc_id'       => (int) get_post_meta( $post_id, '_event_loc_id', true ),
			'loc'          => $loc,
			'ticket_url'   => get_post_meta( $post_id, '_event_ticket_url', true ),
			'ticket_price' => get_post_meta( $post_id, '_event_ticket_price', true ),
			'coupon_code'  => get_post_meta( $post_id, '_event_coupon_code', true ),
			'list_url'     => get_post_meta( $post_id, '_event_list_url', true ),
			'video_url'    => get_post_meta( $post_id, '_event_video_url', true ),
			'audio_url'    => get_post_meta( $post_id, '_event_audio_url', true ),
			'ticket_status'    => get_post_meta( $post_id, '_event_ticket_status', true ) ?: 'available',
			'ticket_btn_style' => get_post_meta( $post_id, '_event_ticket_btn_style', true ) ?: 'main',
			'list_btn_style'   => get_post_meta( $post_id, '_event_list_btn_style', true ) ?: 'lista',
			'earlybird_enabled' => (string) get_post_meta( $post_id, '_event_earlybird_enabled', true ) === '1',
			'earlybird_name'    => (string) get_post_meta( $post_id, '_event_earlybird_name', true ),
			'earlybird_sub'     => (string) get_post_meta( $post_id, '_event_earlybird_sub', true ),
			'earlybird_url'     => (string) get_post_meta( $post_id, '_event_earlybird_url', true ),
			'lista_geral_enabled' => (string) get_post_meta( $post_id, '_event_lista_geral_enabled', true ) === '1',
			'lista_geral_sub'     => (string) get_post_meta( $post_id, '_event_lista_geral_sub', true ),
			'lista_fem_enabled'   => (string) get_post_meta( $post_id, '_event_lista_fem_enabled', true ) === '1',
			'lista_fem_sub'       => (string) get_post_meta( $post_id, '_event_lista_fem_sub', true ),
			'lista_cta_label'     => (string) get_post_meta( $post_id, '_event_lista_cta_label', true ),
			'gallery'      => (array) ( get_post_meta( $post_id, '_event_gallery', true ) ?: array() ),
			'privacy'      => get_post_meta( $post_id, '_event_privacy', true ) ?: 'public',
			'event_status' => get_post_meta( $post_id, '_event_status', true ) ?: 'scheduled',
			'is_gone'      => apollo_event_is_gone( $post_id ),
			'view_count'   => (int) get_post_meta( $post_id, '_event_view_count', true ),
			'categories'   => wp_get_post_terms( $post_id, APOLLO_EVENT_TAX_CATEGORY, array( 'fields' => 'all' ) ),
			'types'        => wp_get_post_terms( $post_id, APOLLO_EVENT_TAX_TYPE, array( 'fields' => 'all' ) ),
			'tags'         => wp_get_post_terms( $post_id, APOLLO_EVENT_TAX_TAG, array( 'fields' => 'all' ) ),
			'sounds'       => wp_get_post_terms( $post_id, APOLLO_EVENT_TAX_SOUND, array( 'fields' => 'all' ) ),
			'seasons'      => wp_get_post_terms( $post_id, APOLLO_EVENT_TAX_SEASON, array( 'fields' => 'all' ) ),
			'created'      => $post->post_date,
			'modified'     => $post->post_modified,
			'my_rsvp'      => $this->get_current_user_rsvp( $post_id ),
			'access'       => function_exists( 'apollo_event_build_access_payload' )
				? apollo_event_build_access_payload( $post_id )
				: array(),
		);

		// Co-authors + raw access-buttons repeater are edit/manage only — never
		// required for public single-page render (public uses 'access' above).
		if ( current_user_can( 'edit_post', $post_id ) || (int) $post->post_author === get_current_user_id() ) {
			$data['coauthors'] = function_exists( 'apollo_event_get_coauthor_ids' )
				? apollo_event_get_coauthor_ids( $post_id )
				: array();

			$raw_buttons = get_post_meta( $post_id, '_event_access_buttons', true );
			$data['access_buttons'] = is_array( $raw_buttons ) ? $raw_buttons : array();
		}

		/**
		 * Filtra dados do evento na resposta REST
		 *
		 * @param array $data    Dados preparados.
		 * @param int   $post_id ID do evento.
		 */
		return apply_filters( 'apollo_event_rest_data', $data, $post_id );
	}

	/**
	 * Monta query WP a partir dos parâmetros da requisição
	 */
	private function build_query_from_request( \WP_REST_Request $request ): array {
		$events_per_page = (int) apollo_event_option( 'events_per_page', 12 );
		$events_per_page = max( 1, min( 100, $events_per_page ) );

		$order_by = sanitize_text_field( (string) ( $request->get_param( 'orderby' ) ?: 'start_date' ) );
		$order    = strtoupper( sanitize_text_field( (string) ( $request->get_param( 'order' ) ?: 'ASC' ) ) );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'ASC';
		}

		$args = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => min( (int) ( $request->get_param( 'per_page' ) ?: $events_per_page ), 100 ),
			'paged'          => max( (int) ( $request->get_param( 'page' ) ?: 1 ), 1 ),
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => $order,
			'meta_query'     => array(),
		);

		switch ( $order_by ) {
			case 'title':
				$args['orderby'] = 'title';
				unset( $args['meta_key'] );
				break;

			case 'created':
				$args['orderby'] = 'date';
				unset( $args['meta_key'] );
				break;

			case 'start_time':
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = '_event_start_time';
				break;

			case 'start_date':
			default:
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = '_event_start_date';
				break;
		}

		// Busca por texto
		$search = $request->get_param( 'search' );
		if ( $search ) {
			$args['s'] = sanitize_text_field( $search );
		}

		// Filtros de taxonomia
		$tax_query = array();

		$category = $request->get_param( 'category' );
		if ( $category ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_EVENT_TAX_CATEGORY,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $category ),
			);
		}

		$type = $request->get_param( 'type' );
		if ( $type ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_EVENT_TAX_TYPE,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $type ),
			);
		}

		$sound = $request->get_param( 'sound' );
		if ( $sound ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_EVENT_TAX_SOUND,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $sound ),
			);
		}

		$season = $request->get_param( 'season' );
		if ( $season ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_EVENT_TAX_SEASON,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $season ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$date_from = sanitize_text_field( (string) ( $request->get_param( 'date_from' ) ?: '' ) );
		$date_to   = sanitize_text_field( (string) ( $request->get_param( 'date_to' ) ?: '' ) );

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$args['meta_query'][] = array(
				'key'     => '_event_start_date',
				'value'   => $date_from,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$args['meta_query'][] = array(
				'key'     => '_event_start_date',
				'value'   => $date_to,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		$loc_id = (int) ( $request->get_param( 'loc_id' ) ?: 0 );
		if ( $loc_id > 0 ) {
			$args['meta_query'][] = array(
				'key'     => '_event_loc_id',
				'value'   => $loc_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		$dj_id = (int) ( $request->get_param( 'dj_id' ) ?: 0 );
		if ( $dj_id > 0 ) {
			$args['meta_query'][] = array(
				'key'     => '_event_dj_ids',
				'value'   => sprintf( '"%d"', $dj_id ),
				'compare' => 'LIKE',
			);
		}

		$privacy = sanitize_text_field( (string) ( $request->get_param( 'privacy' ) ?: '' ) );
		if ( in_array( $privacy, array( 'public', 'private', 'invite' ), true ) ) {
			// Explicit privacy filter only for privileged callers.
			if ( 'public' === $privacy || current_user_can( 'edit_posts' ) ) {
				$args['meta_query'][] = array(
					'key'     => '_event_privacy',
					'value'   => $privacy,
					'compare' => '=',
				);
			} else {
				$args['meta_query'][] = array(
					'key'     => '_event_privacy',
					'value'   => 'public',
					'compare' => '=',
				);
			}
		} elseif ( ! current_user_can( 'edit_posts' ) ) {
			// Public listings default to public-only (missing meta treated as public).
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_event_privacy',
					'value'   => 'public',
					'compare' => '=',
				),
				array(
					'key'     => '_event_privacy',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_event_privacy',
					'value'   => '',
					'compare' => '=',
				),
			);
		}

		$event_status = sanitize_text_field( (string) ( $request->get_param( 'event_status' ) ?: '' ) );
		if ( in_array( $event_status, array( 'scheduled', 'cancelled', 'postponed', 'ongoing', 'finished' ), true ) ) {
			$args['meta_query'][] = array(
				'key'     => '_event_status',
				'value'   => $event_status,
				'compare' => '=',
			);
		}

		// Filtro: ocultar gone se configurado
		$include_gone = $request->get_param( 'include_gone' );
		$allow_gone   = apollo_event_option( 'show_gone_events', true );
		if ( null !== $include_gone ) {
			$allow_gone = filter_var( $include_gone, FILTER_VALIDATE_BOOLEAN );
		}

		if ( ! $allow_gone ) {
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_event_is_gone',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_event_is_gone',
					'value'   => '1',
					'compare' => '!=',
				),
			);
		}

		return $args;
	}

	/**
	 * Resposta paginada
	 */
	private function paginated_response( \WP_Query $query, \WP_REST_Request $request ): \WP_REST_Response {
		$events = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$events[] = $this->prepare_event( get_the_ID() );
			}
			wp_reset_postdata();
		}

		$response = new \WP_REST_Response( $events );
		$response->header( 'X-WP-Total', $query->found_posts );
		$response->header( 'X-WP-TotalPages', $query->max_num_pages );

		return $response;
	}

	/**
	 * Salva metas do evento
	 */
	/**
	 * Accept the wp_insert_post payload shape as well as this controller's flat one.
	 *
	 * BUGFIX (2026-08-05) — silently blank imported events.
	 * ----------------------------------------------------
	 * This endpoint reads FLAT keys: title, content, start_date, start_time,
	 * ticket_url, banner, … But the URL importer (templates/events-by-url.html,
	 * buildPayload()) posts the WordPress shape instead:
	 *
	 *     { post_type:'event', post_status:'draft', post_title:'…',
	 *       post_content:'…', meta_input:{ _event_start_date:'…', … } }
	 *
	 * Nothing errored: wp_insert_post() happily creates a post with an empty
	 * title, save_event_meta() found none of the keys it looks for, and the
	 * request returned 201. So the importer reported success while writing an
	 * event with no title, no date and no meta — which is exactly what the two
	 * test imports (one Shotgun, one BlueTicket) produced.
	 *
	 * Rather than only fixing the caller, the endpoint now accepts BOTH shapes.
	 * The WP shape is the obvious thing for any future client to send — an
	 * importer, a CLI job, a Zapier webhook — and having it fail silently is a
	 * trap worth removing permanently. Flat keys always win when both are
	 * present, so no existing caller changes behaviour.
	 *
	 * @param array<string,mixed> $data Raw request payload.
	 * @return array<string,mixed> Flat payload this controller understands.
	 */
	private function normalize_payload_shape( array $data ): array {
		// post_title / post_content → title / content
		if ( ! isset( $data['title'] ) && isset( $data['post_title'] ) ) {
			$data['title'] = $data['post_title'];
		}
		if ( ! isset( $data['content'] ) && isset( $data['post_content'] ) ) {
			$data['content'] = $data['post_content'];
		}

		// meta_input{_event_foo} → foo   (the map below is the inverse of
		// save_event_meta()'s, so the two can never drift apart silently).
		if ( ! empty( $data['meta_input'] ) && is_array( $data['meta_input'] ) ) {
			foreach ( $data['meta_input'] as $meta_key => $value ) {
				$flat = preg_replace( '~^_event_~', '', (string) $meta_key );
				if ( '' === $flat || isset( $data[ $flat ] ) ) {
					continue; // flat key already supplied — it wins.
				}
				$data[ $flat ] = $value;
			}
		}

		return $data;
	}

	private function save_event_meta( int $post_id, array $data ): void {
		/*
		 * Heal: URL importer once mapped the page URL into ticket_price
		 * (button title). Promote http(s) titles into ticket_url when the
		 * real ticket_url key is missing.
		 */
		if ( empty( $data['ticket_url'] ) && ! empty( $data['ticket_price'] )
			&& is_string( $data['ticket_price'] )
			&& preg_match( '~^https?://~i', $data['ticket_price'] ) ) {
			$data['ticket_url']   = $data['ticket_price'];
			$data['ticket_price'] = __( 'Ingressos do Evento', 'apollo-events' );
		} elseif ( ! empty( $data['ticket_url'] ) && empty( $data['ticket_price'] ) ) {
			$data['ticket_price'] = __( 'Ingressos do Evento', 'apollo-events' );
		} elseif ( ! empty( $data['ticket_price'] ) && is_string( $data['ticket_price'] )
			&& preg_match( '~^https?://~i', $data['ticket_price'] ) ) {
			$data['ticket_price'] = __( 'Ingressos do Evento', 'apollo-events' );
		}

		$meta_map = array(
			'start_date'           => '_event_start_date',
			'end_date'             => '_event_end_date',
			'start_time'           => '_event_start_time',
			'end_time'             => '_event_end_time',
			'loc_id'               => '_event_loc_id',
			/* banner handled by apollo_event_set_banner() — always = featured. */
			'bg_color'             => '_event_bg_color',
			'ticket_url'           => '_event_ticket_url',
			'ticket_price'         => '_event_ticket_price',
			'coupon_code'          => '_event_coupon_code',
			'list_url'             => '_event_list_url',
			'video_url'            => '_event_video_url',
			'audio_url'            => '_event_audio_url',
			'ticket_status'        => '_event_ticket_status',
			'ticket_btn_style'     => '_event_ticket_btn_style',
			'list_btn_style'       => '_event_list_btn_style',
			'privacy'              => '_event_privacy',
			'event_status'         => '_event_status',
			'earlybird_enabled'    => '_event_earlybird_enabled',
			'earlybird_name'       => '_event_earlybird_name',
			'earlybird_sub'        => '_event_earlybird_sub',
			'earlybird_url'        => '_event_earlybird_url',
			'lista_geral_enabled'  => '_event_lista_geral_enabled',
			'lista_geral_sub'      => '_event_lista_geral_sub',
			'lista_fem_enabled'    => '_event_lista_fem_enabled',
			'lista_fem_sub'        => '_event_lista_fem_sub',
			'lista_cta_label'      => '_event_lista_cta_label',
			'highlighted'          => '_event_highlighted',
		);

		foreach ( $meta_map as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) {
				update_post_meta( $post_id, $meta_key, $this->sanitize_meta_value( $meta_key, $data[ $key ] ) );
			}
		}

		// Arrays (always persist when key present — including empty to clear)
		if ( array_key_exists( 'dj_ids', $data ) ) {
			$ids = is_array( $data['dj_ids'] ) ? array_map( 'intval', $data['dj_ids'] ) : array();
			update_post_meta( $post_id, '_event_dj_ids', array_values( array_filter( $ids ) ) );
		}

		if ( array_key_exists( 'dj_slots', $data ) ) {
			$slots = is_array( $data['dj_slots'] ) ? $data['dj_slots'] : array();
			update_post_meta( $post_id, '_event_dj_slots', $this->sanitize_dj_slots( $slots ) );
		}

		// Early Bird e Listas — unified repeater (replaces the old fixed
		// earlybird/lista_geral/lista_fem toggles). Persisted even when empty
		// (metadata_exists() distinguishes "zero buttons" from "never migrated"
		// so apollo_event_build_access_payload() knows not to fall back to legacy).
		if ( array_key_exists( 'access_buttons', $data ) ) {
			$buttons = is_array( $data['access_buttons'] ) ? $data['access_buttons'] : array();
			update_post_meta( $post_id, '_event_access_buttons', $this->sanitize_access_buttons( $buttons ) );
		}

		/* Gallery entries follow the same int-or-URL rule as the banner. */
		if ( array_key_exists( 'gallery', $data ) ) {
			$gallery = is_array( $data['gallery'] ) ? $data['gallery'] : array();
			$gallery = array_map( array( self::class, 'sanitize_image_ref' ), $gallery );
			$gallery = array_values(
				array_filter(
					$gallery,
					static function ( $ref ): bool {
						return '' !== $ref && 0 !== $ref;
					}
				)
			);
			update_post_meta( $post_id, '_event_gallery', $gallery );
		}

		/*
		 * Banner === featured image (brutal). Attachment id OR remote URL;
		 * URLs are sideloaded so `_event_banner` and `_thumbnail_id` match.
		 */
		if ( array_key_exists( 'banner', $data ) ) {
			apollo_event_set_banner( $post_id, $data['banner'] );
			apollo_event_heal_cover( $post_id );
		}
	}

	/**
	 * Sanitiza valor de meta baseado na chave oficial do registry.
	 */
	private function sanitize_meta_value( string $meta_key, $value ) {
		switch ( $meta_key ) {
			case '_event_loc_id':
				return absint( $value );

			/*
			 * The banner is EITHER an attachment id (uploaded to the Apollo media
			 * library) OR an absolute https URL to an image hosted elsewhere —
			 * users without upload_files can only supply the latter.
			 */
			case '_event_banner':
				return self::sanitize_image_ref( $value );

			case '_event_bg_color':
				$hex = sanitize_hex_color( (string) $value );
				return $hex ?: '#0a0a0a';

			case '_event_ticket_url':
			case '_event_list_url':
			case '_event_audio_url':
			case '_event_earlybird_url':
				return esc_url_raw( (string) $value );

			case '_event_video_url':
				return apollo_event_sanitize_video_url( $value );

			case '_event_earlybird_enabled':
			case '_event_lista_geral_enabled':
			case '_event_lista_fem_enabled':
			case '_event_highlighted':
				$flag = sanitize_text_field( (string) $value );
				if ( in_array( $flag, array( '1', 'true', 'yes', 'on' ), true ) ) {
					return '1';
				}
				return '0';

			case '_event_ticket_status':
				$ticket_status = sanitize_text_field( (string) $value );
				return in_array( $ticket_status, array( 'free', 'available', 'soldout_soon', 'sold_out' ), true ) ? $ticket_status : 'available';

			case '_event_ticket_btn_style':
				$btn = sanitize_text_field( (string) $value );
				return in_array( $btn, array( 'main', 'soft', 'lista' ), true ) ? $btn : 'main';

			case '_event_list_btn_style':
				$list_btn = sanitize_text_field( (string) $value );
				return in_array( $list_btn, array( 'main', 'soft', 'lista', 'fem' ), true ) ? $list_btn : 'lista';

			case '_event_privacy':
				$privacy = sanitize_text_field( (string) $value );
				return in_array( $privacy, array( 'public', 'private', 'invite' ), true ) ? $privacy : 'public';

			case '_event_status':
				$event_status = sanitize_text_field( (string) $value );
				return in_array( $event_status, array( 'scheduled', 'cancelled', 'postponed', 'ongoing', 'finished' ), true ) ? $event_status : 'scheduled';

			/*
			 * Dates and times are format-validated, not just escaped. These feed
			 * strtotime() in the single page, the archive queries and the
			 * expiration cron — a malformed value there silently resolves to
			 * "now" and the event quietly behaves as if it were today.
			 */
			case '_event_start_date':
			case '_event_end_date':
				$date = sanitize_text_field( (string) $value );
				if ( '' === $date ) {
					return '';
				}
				$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date );
				return ( $dt && $dt->format( 'Y-m-d' ) === $date ) ? $date : '';

			case '_event_start_time':
			case '_event_end_time':
				$time = sanitize_text_field( (string) $value );
				if ( '' === $time ) {
					return '';
				}
				return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time ) ? $time : '';

			case '_event_coupon_code':
				/* Comma-separated codes; keep them uppercase and URL-safe. */
				$codes = array_filter(
					array_map(
						static function ( $code ): string {
							return strtoupper( preg_replace( '/[^A-Za-z0-9_-]/', '', trim( (string) $code ) ) ?? '' );
						},
						explode( ',', (string) $value )
					),
					static function ( string $code ): bool {
						return '' !== $code;
					}
				);
				return implode( ',', array_unique( $codes ) );

			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Sanitize an image reference that may be an attachment id OR a remote URL.
	 *
	 * Remote URLs are restricted to http(s) with an image-looking path so the
	 * field cannot be turned into a redirect/XSS vector or point at an internal
	 * host. Anything else collapses to '' rather than being stored half-valid.
	 *
	 * @param mixed $value Raw value.
	 * @return int|string Attachment id, image URL, or '' when unusable.
	 */
	public static function sanitize_image_ref( $value ) {
		if ( is_numeric( $value ) ) {
			return absint( $value );
		}

		if ( ! is_string( $value ) ) {
			return '';
		}

		$url = esc_url_raw( trim( $value ), array( 'http', 'https' ) );
		if ( '' === $url ) {
			return '';
		}

		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( '' === $host || false !== strpos( $host, '..' ) ) {
			return '';
		}

		/* Block loopback / link-local so this cannot be used to probe the host. */
		if ( preg_match( '/^(localhost|127\.|0\.0\.0\.0|10\.|192\.168\.|169\.254\.|172\.(1[6-9]|2\d|3[01])\.|\[?::1\]?)/i', $host ) ) {
			return '';
		}

		$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( ! preg_match( '/\.(jpe?g|png|gif|webp|avif|svg)$/', $path ) ) {
			/*
			 * Many CDNs serve extension-less image URLs. Allow those, but only
			 * when the URL carries no query-string trickery.
			 */
			if ( wp_parse_url( $url, PHP_URL_QUERY ) && ! preg_match( '/(jpe?g|png|gif|webp|avif)/', strtolower( $url ) ) ) {
				return '';
			}
		}

		return $url;
	}

	/**
	 * Sanitize DJ timetable slots to a strict shape.
	 *
	 * @param array<int,mixed> $slots Raw slots.
	 * @return array<int,array{dj_id:int,start_time:string,end_time:string,badge?:string}>
	 */
	private function sanitize_dj_slots( array $slots ): array {
		$clean = array();
		foreach ( $slots as $slot ) {
			if ( ! is_array( $slot ) ) {
				continue;
			}
			$dj_id = absint( $slot['dj_id'] ?? 0 );
			if ( $dj_id <= 0 ) {
				continue;
			}
			$row = array(
				'dj_id'      => $dj_id,
				'start_time' => sanitize_text_field( (string) ( $slot['start_time'] ?? '' ) ),
				'end_time'   => sanitize_text_field( (string) ( $slot['end_time'] ?? '' ) ),
			);
			$badge = sanitize_text_field( (string) ( $slot['badge'] ?? '' ) );
			if ( '' !== $badge ) {
				$row['badge'] = $badge;
			}
			$clean[] = $row;
		}
		return $clean;
	}

	/**
	 * Sanitize the Early Bird e Listas repeater ("+ Novo Ticket ou Lista").
	 * Each row needs a label + url to survive; kind/style are whitelisted.
	 */
	private function sanitize_access_buttons( array $buttons ): array {
		$clean = array();
		foreach ( $buttons as $btn ) {
			if ( ! is_array( $btn ) ) {
				continue;
			}
			$label = sanitize_text_field( (string) ( $btn['label'] ?? '' ) );
			if ( '' === $label ) {
				continue;
			}
			$url = esc_url_raw( (string) ( $btn['url'] ?? '' ) );
			$kind  = (string) ( $btn['kind'] ?? 'ticket' );
			$kind  = in_array( $kind, array( 'ticket', 'lista' ), true ) ? $kind : 'ticket';
			$style = (string) ( $btn['style'] ?? 'soft' );
			$style = in_array( $style, array( 'main', 'soft', 'lista', 'fem', 'cta' ), true ) ? $style : 'soft';

			$clean[] = array(
				'kind'  => $kind,
				'style' => $style,
				'label' => $label,
				'sub'   => sanitize_text_field( (string) ( $btn['sub'] ?? '' ) ),
				'url'   => $url,
			);
		}
		return $clean;
	}

	/**
	 * Public GET may only see public events unless author/coauthor/admin.
	 */
	private function can_view_event( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post || APOLLO_EVENT_CPT !== $post->post_type ) {
			return false;
		}

		$privacy = (string) ( get_post_meta( $post_id, '_event_privacy', true ) ?: 'public' );
		if ( 'public' === $privacy || '' === $privacy ) {
			return true;
		}

		if ( current_user_can( 'edit_post', $post_id ) || current_user_can( 'manage_options' ) ) {
			return true;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		return function_exists( 'apollo_event_user_is_coauthor' )
			? apollo_event_user_is_coauthor( $post_id, $user_id )
			: false;
	}

	/**
	 * Retorna o status RSVP do usuário logado para um evento.
	 */
	private function get_current_user_rsvp( int $post_id ): ?string {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_event_rsvp';

		// Verifica se a tabela existe
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT status, checked_in FROM {$table} WHERE event_id = %d AND user_id = %d",
				$post_id,
				get_current_user_id()
			)
		);

		if ( ! $row ) {
			return null;
		}

		return $row->status . ( $row->checked_in ? ':checked_in' : '' );
	}

	/**
	 * Persist co-authors from REST body (`coauthors` = int[]).
	 * Never exposed on the public single event page — form/edit only.
	 */
	private function save_event_coauthors( int $post_id, array $data ): void {
		if ( ! array_key_exists( 'coauthors', $data ) ) {
			return;
		}
		$ids = is_array( $data['coauthors'] ) ? $data['coauthors'] : array();
		if ( function_exists( 'apollo_event_save_coauthors' ) ) {
			apollo_event_save_coauthors( $post_id, $ids );
			return;
		}
		if ( function_exists( '\\Apollo\\Event\\apollo_event_save_coauthors' ) ) {
			\Apollo\Event\apollo_event_save_coauthors( $post_id, $ids );
		}
	}

	/**
	 * Salva taxonomias do evento
	 */
	private function save_event_taxonomies( int $post_id, array $data ): void {
		$tax_map = array(
			'categories' => APOLLO_EVENT_TAX_CATEGORY,
			'types'      => APOLLO_EVENT_TAX_TYPE,
			'tags'       => APOLLO_EVENT_TAX_TAG,
			'sounds'     => APOLLO_EVENT_TAX_SOUND,
			'seasons'    => APOLLO_EVENT_TAX_SEASON,
		);

		foreach ( $tax_map as $key => $taxonomy ) {
			if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				continue;
			}
			$terms = array();
			foreach ( $data[ $key ] as $term ) {
				if ( is_numeric( $term ) ) {
					$terms[] = absint( $term );
					continue;
				}
				$slug = sanitize_title( (string) $term );
				if ( '' !== $slug ) {
					$terms[] = $slug;
				}
			}
			wp_set_object_terms( $post_id, $terms, $taxonomy );
		}
	}

	/**
	 * Parâmetros de coleção (paginação + filtros)
	 */
	private function get_collection_params(): array {
		return array(
			'per_page'     => array(
				'type'              => 'integer',
				'default'           => 12,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'page'         => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'search'       => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'category'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'type'         => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'sound'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'season'       => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'date_from'    => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'date_to'      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'loc_id'       => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'dj_id'        => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'privacy'      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'event_status' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'include_gone' => array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'orderby'      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Parâmetros de criação/atualização
	 */
	private function get_create_params(): array {
		return array(
			'title'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content'      => array(
				'type'              => 'string',
				'sanitize_callback' => static function ( $value ) {
					return apollo_event_kses_about( (string) $value );
				},
			),
			'status'       => array(
				'type'              => 'string',
				'enum'              => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'post_status'  => array(
				'type'              => 'string',
				'enum'              => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'start_date'   => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'end_date'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'start_time'   => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'end_time'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'loc_id'       => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			/*
			 * Attachment id OR absolute image URL — users without upload_files
			 * may only reference an image hosted outside Apollo. The schema must
			 * stay permissive here; sanitize_image_ref() is the real gate.
			 */
			/*
			 * NOTE: 'banner' and 'gallery' are deliberately ABSENT from this
			 * schema.
			 *
			 * Both accept an attachment id OR an absolute image URL. Every
			 * schema shape tried here ('integer', 'string', and the union of
			 * both) saw WP's own rest_sanitize_value_from_schema() coerce the
			 * URL to 0 and silently drop it before any sanitize_callback ran.
			 * Leaving them undeclared means the raw value reaches
			 * save_event_meta(), where sanitize_image_ref() is the single,
			 * testable gate — it rejects javascript:, loopback and non-image
			 * URLs. Undeclared args are NOT unvalidated args here.
			 */
			'bg_color'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
			),
			'ticket_url'   => array(
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
			),
			'ticket_price' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'coupon_code'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'list_url'     => array(
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
			),
			'video_url'    => array(
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'Apollo\\Event\\apollo_event_sanitize_video_url',
				'validate_callback' => static function ( $value ) {
					if ( null === $value || '' === $value ) {
						return true;
					}
					if ( ! is_string( $value ) ) {
						return false;
					}
					if ( ! apollo_event_is_valid_video_url( $value ) ) {
						return new \WP_Error(
							'rest_invalid_param',
							__( 'Use um link do YouTube ou arquivo .mp4 / .webm / .mov', 'apollo-events' ),
							array( 'status' => 400 )
						);
					}
					return true;
				},
			),
			'audio_url'    => array(
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
			),
			'ticket_status' => array(
				'type'              => 'string',
				'enum'              => array( 'free', 'available', 'soldout_soon', 'sold_out' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'ticket_btn_style' => array(
				'type'              => 'string',
				'enum'              => array( 'main', 'soft', 'lista' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'list_btn_style' => array(
				'type'              => 'string',
				'enum'              => array( 'main', 'soft', 'lista', 'fem' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'earlybird_enabled' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'earlybird_name' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'earlybird_sub' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'earlybird_url' => array(
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
			),
			'lista_geral_enabled' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'lista_geral_sub' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'lista_fem_enabled' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'lista_fem_sub' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'lista_cta_label' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'privacy'      => array(
				'type'              => 'string',
				'enum'              => array( 'public', 'private', 'invite' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'event_status' => array(
				'type'              => 'string',
				'enum'              => array( 'scheduled', 'cancelled', 'postponed', 'ongoing', 'finished' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'dj_ids'       => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
			'dj_slots'     => array( 'type' => 'array' ),
			'coauthors'    => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
			'access_buttons' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'object' ),
			),
			'categories'   => array( 'type' => 'array' ),
			'types'        => array( 'type' => 'array' ),
			'tags'         => array( 'type' => 'array' ),
			'sounds'       => array( 'type' => 'array' ),
			'seasons'      => array( 'type' => 'array' ),
		);
	}
}
