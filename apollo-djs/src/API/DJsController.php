<?php
/**
 * REST Controller — DJs
 *
 * Endpoints conforme apollo-registry.json:
 * - GET|POST   /djs
 * - GET|PUT|DEL /djs/{id}
 * - GET         /djs/{id}/eventos
 * - GET         /djs/por-som/{sound}
 * - GET         /djs/buscar
 *
 * @package Apollo\DJs
 */

declare(strict_types=1);

namespace Apollo\DJs\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DJsController {

	private string $namespace;

	public function __construct() {
		$this->namespace = APOLLO_DJ_REST_NAMESPACE;
	}

	/**
	 * Registra todas as rotas
	 */
	public function register_routes(): void {
		// GET|POST /djs
		register_rest_route(
			$this->namespace,
			'/djs',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_djs' ),
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
							'default'           => 12,
							'minimum'           => 1,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
						),
						'sound'    => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'featured' => array(
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'orderby'  => array(
							'type'              => 'string',
							'default'           => 'title',
							'enum'              => array( 'title', 'date', 'rand' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						'order'    => array(
							'type'              => 'string',
							'default'           => 'ASC',
							'enum'              => array( 'ASC', 'DESC' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_dj' ),
					// Logged-in: the event form's "Cadastrar novo DJ" quick-add posts
					// here as the organizer. edit_posts blocked every subscriber-level
					// user with a silent 403. Status stays publish-if-capable/pending.
					'permission_callback' => array( $this, 'is_dj_creator' ),
				),
			)
		);

		// GET|PUT|DELETE /djs/{id}
		register_rest_route(
			$this->namespace,
			'/djs/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_dj' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_dj' ),
					'permission_callback' => array( $this, 'can_edit_dj' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_dj' ),
					'permission_callback' => array( $this, 'can_edit_dj' ),
				),
			)
		);

		// GET /djs/{id}/eventos
		register_rest_route(
			$this->namespace,
			'/djs/(?P<id>\d+)/eventos',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dj_events' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /djs/por-som/{sound}
		register_rest_route(
			$this->namespace,
			'/djs/por-som/(?P<sound>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_by_sound' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /djs/buscar
		register_rest_route(
			$this->namespace,
			'/djs/buscar',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_djs' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'        => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 10,
						'maximum'           => 50,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	// ─── Callbacks ──────────────────────────────────────────────────────

	/**
	 * GET /djs — Listar DJs
	 */
	public function get_djs( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$args = array(
			'post_type'      => APOLLO_DJ_CPT,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
			'orderby'        => $request->get_param( 'orderby' ),
			'order'          => $request->get_param( 'order' ),
		);

		$sound = $request->get_param( 'sound' );
		if ( $sound ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => APOLLO_DJ_TAX_SOUND,
					'field'    => 'slug',
					'terms'    => array_map( 'trim', explode( ',', $sound ) ),
				),
			);
		}

		$featured = $request->get_param( 'featured' );
		if ( $featured ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_dj_verified',
					'value' => '1',
				),
			);
		}

		$query = new \WP_Query( $args );
		$djs   = array();

		foreach ( $query->posts as $post ) {
			$djs[] = $this->prepare_dj( $post );
		}

		$response = new \WP_REST_Response( $djs, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * POST /djs — Criar DJ
	 */
	public function create_dj( \WP_REST_Request $request ): \WP_REST_Response {
		$data = $request->get_json_params();

		$post_id = wp_insert_post(
			array(
				'post_type'    => APOLLO_DJ_CPT,
				'post_title'   => sanitize_text_field( $data['title'] ?? '' ),
				'post_content' => wp_kses_post( $data['content'] ?? '' ),
				'post_status'  => current_user_can( 'publish_posts' ) ? 'publish' : 'pending',
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return new \WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 400 );
		}

		$this->save_meta( $post_id, $data );
		$this->save_taxonomies( $post_id, $data );

		// Ensure artistic name meta exists (cartão reads _dj_name).
		if ( empty( get_post_meta( $post_id, '_dj_name', true ) ) ) {
			$title = sanitize_text_field( $data['title'] ?? get_the_title( $post_id ) );
			if ( $title ) {
				update_post_meta( $post_id, '_dj_name', $title );
			}
		}

		do_action( 'apollo_dj_rest_created', $post_id, $data );

		return new \WP_REST_Response( $this->prepare_dj( get_post( $post_id ) ), 201 );
	}

	/**
	 * GET /djs/{id}
	 */
	public function get_dj( \WP_REST_Request $request ): \WP_REST_Response {
		$post = get_post( (int) $request['id'] );

		if ( ! $post || $post->post_type !== APOLLO_DJ_CPT ) {
			return new \WP_REST_Response( array( 'error' => 'DJ não encontrado' ), 404 );
		}

		return new \WP_REST_Response( $this->prepare_dj( $post ), 200 );
	}

	/**
	 * PUT /djs/{id}
	 */
	public function update_dj( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['id'];
		$data    = $request->get_json_params();

		$update = array( 'ID' => $post_id );

		if ( isset( $data['title'] ) ) {
			$update['post_title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['content'] ) ) {
			$update['post_content'] = wp_kses_post( $data['content'] );
		}

		wp_update_post( $update );
		$this->save_meta( $post_id, $data );
		$this->save_taxonomies( $post_id, $data );

		do_action( 'apollo_dj_rest_updated', $post_id, $data );

		return new \WP_REST_Response( $this->prepare_dj( get_post( $post_id ) ), 200 );
	}

	/**
	 * DELETE /djs/{id}
	 */
	public function delete_dj( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request['id'];

		do_action( 'apollo_dj_rest_before_delete', $post_id );

		$result = wp_delete_post( $post_id, true );

		if ( ! $result ) {
			return new \WP_REST_Response( array( 'error' => 'Falha ao deletar DJ' ), 500 );
		}

		return new \WP_REST_Response(
			array(
				'deleted' => true,
				'id'      => $post_id,
			),
			200
		);
	}

	/**
	 * GET /djs/{id}/eventos — Eventos do DJ
	 */
	public function get_dj_events( \WP_REST_Request $request ): \WP_REST_Response {
		$dj_id = (int) $request['id'];

		if ( ! get_post( $dj_id ) || get_post_type( $dj_id ) !== APOLLO_DJ_CPT ) {
			return new \WP_REST_Response( array( 'error' => 'DJ não encontrado' ), 404 );
		}

		$events = \apollo_dj_get_upcoming_events( $dj_id, 50 );
		$result = array();

		foreach ( $events as $event ) {
			$result[] = array(
				'id'         => $event->ID,
				'title'      => $event->post_title,
				'permalink'  => get_permalink( $event->ID ),
				'start_date' => get_post_meta( $event->ID, '_event_start_date', true ),
				'start_time' => get_post_meta( $event->ID, '_event_start_time', true ),
				'end_date'   => get_post_meta( $event->ID, '_event_end_date', true ),
				'status'     => get_post_meta( $event->ID, '_event_status', true ),
				'loc_id'     => (int) get_post_meta( $event->ID, '_event_loc_id', true ),
			);
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /djs/por-som/{sound}
	 */
	public function get_by_sound( \WP_REST_Request $request ): \WP_REST_Response {
		$sound = sanitize_text_field( $request['sound'] );

		$query = new \WP_Query(
			array(
				'post_type'      => APOLLO_DJ_CPT,
				'posts_per_page' => 50,
				'post_status'    => 'publish',
				'tax_query'      => array(
					array(
						'taxonomy' => APOLLO_DJ_TAX_SOUND,
						'field'    => 'slug',
						'terms'    => $sound,
					),
				),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$djs = array();
		foreach ( $query->posts as $post ) {
			$djs[] = $this->prepare_dj( $post );
		}

		return new \WP_REST_Response( $djs, 200 );
	}

	/**
	 * GET /djs/buscar?q=
	 */
	public function search_djs( \WP_REST_Request $request ): \WP_REST_Response {
		$q        = sanitize_text_field( $request->get_param( 'q' ) );
		$per_page = (int) $request->get_param( 'per_page' );

		$query = new \WP_Query(
			array(
				'post_type'      => APOLLO_DJ_CPT,
				'posts_per_page' => $per_page,
				'post_status'    => 'publish',
				's'              => $q,
			)
		);

		$djs = array();
		foreach ( $query->posts as $post ) {
			$djs[] = $this->prepare_dj( $post );
		}

		return new \WP_REST_Response( $djs, 200 );
	}

	// ─── Helpers ────────────────────────────────────────────────────────

	/**
	 * Prepara DJ para resposta REST
	 */
	private function prepare_dj( \WP_Post $post ): array {
		return array(
			'id'                    => $post->ID,
			'title'                 => $post->post_title,
			'slug'                  => $post->post_name,
			'content'               => apply_filters( 'the_content', $post->post_content ),
			'excerpt'               => wp_trim_words( $post->post_content, 30 ),
			'permalink'             => get_permalink( $post ),
			'image'                 => \apollo_dj_get_image( $post->ID ),
			'banner'                => \apollo_dj_get_banner( $post->ID ),
			'bio_short'             => get_post_meta( $post->ID, '_dj_bio_short', true ),
			'website'               => get_post_meta( $post->ID, '_dj_website', true ),
			'instagram'             => get_post_meta( $post->ID, '_dj_instagram', true ),
			'soundcloud'            => get_post_meta( $post->ID, '_dj_soundcloud', true ),
			'spotify'               => get_post_meta( $post->ID, '_dj_spotify', true ),
			'youtube'               => get_post_meta( $post->ID, '_dj_youtube', true ),
			'mixcloud'              => get_post_meta( $post->ID, '_dj_mixcloud', true ),
			'user_id'               => (int) get_post_meta( $post->ID, '_dj_user_id', true ),
			'verified'              => \apollo_dj_is_verified( $post->ID ),
			'sounds'                => \apollo_dj_get_sounds( $post->ID ),
			'links'                 => \apollo_dj_get_links( $post->ID ),
			'tracks'                => \apollo_dj_get_tracks( $post->ID ),
			'name'                  => get_post_meta( $post->ID, '_dj_name', true ) ?: $post->post_title,
			'bandcamp'              => get_post_meta( $post->ID, '_dj_bandcamp', true ),
			'booking'               => get_post_meta( $post->ID, '_dj_booking', true ),
			'media_kit_url'         => get_post_meta( $post->ID, '_dj_media_kit_url', true ),
			'statement'             => get_post_meta( $post->ID, '_dj_statement', true ),
			'about_photo'           => (int) get_post_meta( $post->ID, '_dj_about_photo', true ),
			'about_video'           => get_post_meta( $post->ID, '_dj_about_video', true ),
			'upcoming_events_count' => \apollo_dj_count_upcoming_events( $post->ID ),
			'author'                => array(
				'id'   => (int) $post->post_author,
				'name' => get_the_author_meta( 'display_name', $post->post_author ),
			),
			'date'                  => $post->post_date,
			'modified'              => $post->post_modified,
		);
	}

	/**
	 * Salva meta fields (admin REST + quick-add do formulário de evento).
	 */
	private function save_meta( int $post_id, array $data ): void {
		$text_map = array(
			'name'                 => '_dj_name',
			'bio_short'            => '_dj_bio_short',
			'statement'            => '_dj_statement',
			'bio'                  => '_dj_bio',
			'instagram'            => '_dj_instagram',
			'original_project_1'   => '_dj_original_project_1',
			'original_project_2'   => '_dj_original_project_2',
			'original_project_3'   => '_dj_original_project_3',
		);
		foreach ( $text_map as $key => $meta_key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}
			$raw = is_string( $data[ $key ] ) ? $data[ $key ] : (string) $data[ $key ];
			$fn  = in_array( $key, array( 'bio_short', 'statement', 'bio' ), true )
				? 'sanitize_textarea_field'
				: 'sanitize_text_field';
			update_post_meta( $post_id, $meta_key, $fn( $raw ) );
		}

		$url_map = array(
			'website'          => '_dj_website',
			'soundcloud'       => '_dj_soundcloud',
			'spotify'          => '_dj_spotify',
			'youtube'          => '_dj_youtube',
			'mixcloud'         => '_dj_mixcloud',
			'bandcamp'         => '_dj_bandcamp',
			'beatport'         => '_dj_beatport',
			'resident_advisor' => '_dj_resident_advisor',
			'facebook'         => '_dj_facebook',
			'twitter'          => '_dj_twitter',
			'tiktok'           => '_dj_tiktok',
			'set_url'          => '_dj_set_url',
			'mix_url'          => '_dj_mix_url',
			'media_kit_url'    => '_dj_media_kit_url',
			'rider_url'        => '_dj_rider_url',
			'about_video'      => '_dj_about_video',
		);
		foreach ( $url_map as $key => $meta_key ) {
			if ( array_key_exists( $key, $data ) ) {
				update_post_meta( $post_id, $meta_key, esc_url_raw( (string) $data[ $key ] ) );
			}
		}

		if ( array_key_exists( 'booking', $data ) ) {
			update_post_meta( $post_id, '_dj_booking', sanitize_email( (string) $data['booking'] ) );
		}

		$int_map = array(
			'user_id'     => '_dj_user_id',
			'image'       => '_dj_image',
			'banner'      => '_dj_banner',
			'about_photo' => '_dj_about_photo',
		);
		foreach ( $int_map as $key => $meta_key ) {
			if ( array_key_exists( $key, $data ) ) {
				update_post_meta( $post_id, $meta_key, absint( $data[ $key ] ) );
			}
		}

		if ( array_key_exists( 'verified', $data ) ) {
			update_post_meta( $post_id, '_dj_verified', ! empty( $data['verified'] ) ? '1' : '' );
		}

		/*
		 * Optional first Out now! track from quick-add.
		 *
		 * WAS SILENTLY BROKEN UNTIL 2026-08-17. This block built a schema-v1 row
		 * — {title, url, duration, year} — and handed it to update_post_meta().
		 * `_dj_tracks` is registered with apollo_dj_sanitize_tracks_meta() as its
		 * sanitize callback, WP core runs that on EVERY write (not only REST, as
		 * a since-corrected docblock in includes/functions.php claimed), and the
		 * v2 rules drop any row without `artists` and without at least one
		 * `url_*`. Every row this produced was discarded. The caller got a 200
		 * and the track vanished.
		 *
		 * Now it builds a v2 row:
		 *   · `artists` defaults to the DJ's own name — a DJ quick-adding their
		 *     release is the artist, and it is the only honest default available
		 *     from this form.
		 *   · the single `track_url` is routed to the platform key its host
		 *     implies, because v2 has no generic `url` field.
		 *   · `release_date` is synthesised from the year so the sort key exists.
		 *
		 * `duration` is still mandatory and still comes from the form. A row
		 * submitted without one is dropped — by the ONE function that decides
		 * what a valid row is, which is the correct place for that decision.
		 * This block deliberately does not re-implement those rules.
		 */
		if ( ! empty( $data['track_title'] ) || ! empty( $data['track_url'] ) ) {
			$existing = get_post_meta( $post_id, '_dj_tracks', true );
			if ( ! is_array( $existing ) ) {
				$existing = array();
			}

			$year = preg_replace( '/\D/', '', (string) ( $data['track_year'] ?? '' ) );
			if ( strlen( $year ) > 4 ) {
				$year = substr( $year, 0, 4 );
			}

			$row = array(
				'title'        => sanitize_text_field( (string) ( $data['track_title'] ?? '' ) ),
				'artists'      => sanitize_text_field(
					(string) ( $data['track_artists'] ?? get_the_title( $post_id ) )
				),
				'duration'     => sanitize_text_field( (string) ( $data['track_duration'] ?? '' ) ),
				'release_date' => '' !== $year ? $year . '-01-01' : '',
			);

			$url = esc_url_raw( (string) ( $data['track_url'] ?? '' ) );
			if ( '' !== $url ) {
				$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
				if ( false !== strpos( $host, 'soundcloud' ) ) {
					$row['url_soundcloud'] = $url;
				} elseif ( false !== strpos( $host, 'spotify' ) ) {
					$row['url_spotify'] = $url;
				} elseif ( false !== strpos( $host, 'bandcamp' ) ) {
					$row['url_bandcamp'] = $url;
				} else {
					$row['url_download'] = $url;
				}
			}

			$existing[] = $row;
			update_post_meta( $post_id, '_dj_tracks', $existing );
		}
	}

	/**
	 * Salva taxonomias
	 */
	private function save_taxonomies( int $post_id, array $data ): void {
		if ( isset( $data['sounds'] ) && is_array( $data['sounds'] ) ) {
			wp_set_object_terms( $post_id, $data['sounds'], APOLLO_DJ_TAX_SOUND );
			return;
		}
		// Quick-add may send comma-separated genre string
		if ( ! empty( $data['genres'] ) && is_string( $data['genres'] ) ) {
			$terms = array_filter( array_map( 'trim', preg_split( '/[,;]+/', $data['genres'] ) ?: array() ) );
			if ( ! empty( $terms ) ) {
				wp_set_object_terms( $post_id, $terms, APOLLO_DJ_TAX_SOUND );
			}
		}
	}

	// ─── Permissions ────────────────────────────────────────────────────

	public function can_edit( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Quick-add permission: any logged-in user may propose a DJ (goes to
	 * 'pending' unless they can publish_posts — see create_dj()).
	 */
	public function is_dj_creator( \WP_REST_Request $request ): bool {
		return is_user_logged_in();
	}

	public function can_edit_dj( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_post', (int) $request['id'] );
	}
}
