<?php

/**
 * REST API: Classifieds Controller
 *
 * Endpoints: /classifieds (GET,POST), /classifieds/{id} (GET,PUT,DELETE), /classifieds/my (GET)
 * Adapted from WPAdverts REST patterns + WP_REST_Controller base.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ClassifiedsController extends \WP_REST_Controller {


	protected $namespace = APOLLO_ADVERTS_REST_NAMESPACE;
	protected $rest_base = 'classifieds';

	/**
	 * Register routes
	 * Registry spec: /classifieds (GET,POST), /classifieds/{id} (GET,PUT,DELETE), /classifieds/my (GET)
	 */
	public function register_routes(): void {

		// /classifieds — list + create
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_create_params(),
				),
			)
		);

		// /classifieds/{id} — single CRUD
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'id' => array(
							'type'     => 'integer',
							'required' => true,
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);

		// /classifieds/my — authenticated user's ads
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/my',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_my_items' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * GET /classifieds — List
	 */
	public function get_items( $request ): \WP_REST_Response {
		$args = array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ) ?: APOLLO_ADVERTS_POSTS_PER_PAGE,
			'paged'          => $request->get_param( 'page' ) ?: 1,
			'orderby'        => $request->get_param( 'orderby' ) ?: 'date',
			'order'          => $request->get_param( 'order' ) ?: 'DESC',
		);

		// Taxonomy filters
		$tax_query = array();
		$domain    = $request->get_param( 'domain' );
		if ( $domain ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_TAX_CLASSIFIED_DOMAIN,
				'field'    => 'slug',
				'terms'    => $domain,
			);
		}
		$intent = $request->get_param( 'intent' );
		if ( $intent ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_TAX_CLASSIFIED_INTENT,
				'field'    => 'slug',
				'terms'    => $intent,
			);
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		// Featured filter
		$featured = $request->get_param( 'featured' );
		if ( $featured ) {
			$args['meta_query'][] = array(
				'key'   => '_classified_featured',
				'value' => '1',
			);
		}

		$query = new \WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = $this->prepare_item( $post );
		}

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * GET /classifieds/{id} — Single
	 */
	public function get_item( $request ): \WP_REST_Response|\WP_Error {
		$post = get_post( $request->get_param( 'id' ) );

		if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
			return new \WP_Error( 'not_found', __( 'Anúncio não encontrado.', 'apollo-adverts' ), array( 'status' => 404 ) );
		}

		if ( $post->post_status !== 'publish' ) {
			$can_view = is_user_logged_in() && ( (int) $post->post_author === get_current_user_id() || current_user_can( 'manage_options' ) );
			if ( ! $can_view ) {
				return new \WP_Error( 'not_found', __( 'Anúncio não encontrado.', 'apollo-adverts' ), array( 'status' => 404 ) );
			}
		}

		return rest_ensure_response( $this->prepare_item( $post ) );
	}

	/**
	 * POST /classifieds — Create
	 */
	public function create_item( $request ): \WP_REST_Response|\WP_Error {
		$config = apollo_adverts_config();
		$status = $config['moderation'] === 'manual' ? 'pending' : 'publish';

		$post_data = array(
			'post_type'    => APOLLO_CPT_CLASSIFIED,
			'post_status'  => $status,
			'post_title'   => sanitize_text_field( $request->get_param( 'title' ) ?? '' ),
			// `description` is what all three frontend forms send; `content`
			// is the API-native name. Accept either.
			'post_content' => sanitize_textarea_field(
				$request->get_param( 'content' ) ?? $request->get_param( 'description' ) ?? ''
			),
			'post_author'  => get_current_user_id(),
		);

		$post_id = wp_insert_post( $post_data, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// ── Advert kind ───────────────────────────────────────────────────
		// Every frontend form has always POSTed classified_type, and this
		// method has always ignored it — so frontend-created adverts carried
		// NO _classified_type at all, while every marketplace query filters on
		// it. Result: nothing submitted through the site ever appeared in the
		// ticket carousel or the accommodation grid. Canonicalised on the way
		// in (ticket_sell→ticket, rent_space→accommodation) so the stored
		// vocabulary matches what those queries look for.
		update_post_meta(
			$post_id,
			'_classified_type',
			apollo_adverts_canonical_type( (string) ( $request->get_param( 'classified_type' ) ?? '' ) )
		);

		// Save meta
		$this->save_meta_from_request( $post_id, $request );

		// Set expiration
		apollo_adverts_set_expiration( $post_id );
		update_post_meta( $post_id, '_classified_currency', 'BRL' );

		// Assign taxonomies
		$domain = $request->get_param( 'domain' );
		if ( $domain ) {
			wp_set_object_terms( $post_id, $domain, APOLLO_TAX_CLASSIFIED_DOMAIN );
		}
		$intent = $request->get_param( 'intent' );
		if ( $intent ) {
			wp_set_object_terms( $post_id, $intent, APOLLO_TAX_CLASSIFIED_INTENT );
		}

		do_action( 'apollo/classifieds/created', $post_id, $request->get_params() );

		$post = get_post( $post_id );
		return rest_ensure_response( $this->prepare_item( $post ) );
	}

	/**
	 * PUT /classifieds/{id} — Update
	 */
	public function update_item( $request ): \WP_REST_Response|\WP_Error {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
			return new \WP_Error( 'not_found', __( 'Anúncio não encontrado.', 'apollo-adverts' ), array( 'status' => 404 ) );
		}

		$update = array( 'ID' => $post_id );

		$title = $request->get_param( 'title' );
		if ( $title !== null ) {
			$update['post_title'] = sanitize_text_field( $title );
		}
		$content = $request->get_param( 'content' ) ?? $request->get_param( 'description' );
		if ( $content !== null ) {
			$update['post_content'] = sanitize_textarea_field( $content );
		}

		// Type is editable on update too — an advert mis-typed at creation
		// (or created before _classified_type was persisted at all) can be
		// corrected without a wp-admin round trip.
		$new_type = $request->get_param( 'classified_type' );
		if ( $new_type !== null ) {
			update_post_meta( $post_id, '_classified_type', apollo_adverts_canonical_type( (string) $new_type ) );
		}

		wp_update_post( $update );
		$this->save_meta_from_request( $post_id, $request );

		// Taxonomies
		$domain = $request->get_param( 'domain' );
		if ( $domain !== null ) {
			wp_set_object_terms( $post_id, $domain, APOLLO_TAX_CLASSIFIED_DOMAIN );
		}
		$intent = $request->get_param( 'intent' );
		if ( $intent !== null ) {
			wp_set_object_terms( $post_id, $intent, APOLLO_TAX_CLASSIFIED_INTENT );
		}

		do_action( 'apollo/classifieds/updated', $post_id, $request->get_params() );

		return rest_ensure_response( $this->prepare_item( get_post( $post_id ) ) );
	}

	/**
	 * DELETE /classifieds/{id}
	 */
	public function delete_item( $request ): \WP_REST_Response|\WP_Error {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
			return new \WP_Error( 'not_found', __( 'Anúncio não encontrado.', 'apollo-adverts' ), array( 'status' => 404 ) );
		}

		wp_trash_post( $post_id );
		do_action( 'apollo/classifieds/deleted', $post_id );

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $post_id,
			)
		);
	}

	/**
	 * GET /classifieds/my — Current user's ads
	 */
	public function get_my_items( $request ): \WP_REST_Response {
		$args = array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => array( 'publish', 'pending', 'draft', 'expired' ),
			'author'         => get_current_user_id(),
			'posts_per_page' => $request->get_param( 'per_page' ) ?: APOLLO_ADVERTS_POSTS_PER_PAGE,
			'paged'          => $request->get_param( 'page' ) ?: 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new \WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = $this->prepare_item( $post );
		}

		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * Prepare item for response
	 */
	protected function prepare_item( \WP_Post $post ): array {
		/*
		 * ── GUEST REDACTION (2026-08-17) ─────────────────────────────────────
		 *
		 * THIS METHOD WAS A LIVE PII LEAK. The loop below returns every key in
		 * APOLLO_ADVERTS_META_KEYS, that set contains _classified_contact_phone
		 * and _classified_contact_whatsapp, and GET /apollo/v1/classifieds is
		 * permission_callback => '__return_true' (see :40). So an anonymous
		 * request returned every seller's phone number and WhatsApp, plus their
		 * user id and display name from the 'author'/'author_name' fields below.
		 *
		 * That is exactly what $seller_privacy_leak_fix_2026_07_28 set out to
		 * stop. It hardened five templates — card-accommodation, card-ticket,
		 * list-item, single-classified, single — and never touched the API. A
		 * template is one consumer of this data; the API is every other one.
		 *
		 * SHAPE IS PRESERVED ON PURPOSE. Redacted values are blanked, not
		 * removed: '' for meta and author_name, 0 for author. A consumer that
		 * reads $item['author_name'] keeps working and simply gets nothing,
		 * where a missing key would throw. Guests could never render identity
		 * anyway — the templates already withhold it.
		 *
		 * NOT hostel-exempt. A hostel listing is deliberately public, but that
		 * means its title, price, image and booking URL. It never means a
		 * person's phone number.
		 */
		$is_member = is_user_logged_in();

		$meta = array();
		foreach ( APOLLO_ADVERTS_META_KEYS as $key => $config ) {
			if ( ! $is_member && in_array( $key, APOLLO_ADVERTS_MEMBER_ONLY_META, true ) ) {
				$meta[ $key ] = '';
				continue;
			}
			$meta[ $key ] = get_post_meta( $post->ID, $key, true );
		}

		$domains = wp_get_object_terms( $post->ID, APOLLO_TAX_CLASSIFIED_DOMAIN, array( 'fields' => 'all' ) );
		$intents = wp_get_object_terms( $post->ID, APOLLO_TAX_CLASSIFIED_INTENT, array( 'fields' => 'all' ) );

		$image = apollo_adverts_get_main_image( $post->ID, 'classified-medium' );

		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'content'     => $post->post_content,
			'excerpt'     => get_the_excerpt( $post ),
			'status'      => $post->post_status,
			// Seller identity is member-only. See the GUEST REDACTION note above.
			'author'      => $is_member ? (int) $post->post_author : 0,
			'author_name' => $is_member ? get_the_author_meta( 'display_name', $post->post_author ) : '',
			'date'        => $post->post_date,
			'modified'    => $post->post_modified,
			'link'        => get_permalink( $post->ID ),
			'image'       => $image,
			'price'       => apollo_adverts_get_the_price( $post->ID ),
			'meta'        => $meta,
			'domains'     => is_wp_error( $domains ) ? array() : array_map(
				function ( $t ) {
					return array(
						'slug' => $t->slug,
						'name' => $t->name,
					);
				},
				$domains
			),
			'intents'     => is_wp_error( $intents ) ? array() : array_map(
				function ( $t ) {
					return array(
						'slug' => $t->slug,
						'name' => $t->name,
					);
				},
				$intents
			),
			'is_expired'  => apollo_adverts_is_expired( $post->ID ),
			'is_featured' => apollo_adverts_is_featured( $post->ID ),
			'views'       => (int) get_post_meta( $post->ID, '_classified_views', true ),
		);
	}

	/**
	 * Save meta from REST request
	 */
	protected function save_meta_from_request( int $post_id, \WP_REST_Request $request ): void {
		// Seller-owned fields — safe to accept from whoever owns the listing.
		//
		// NOTE: 'featured' was removed from this map. It writes
		// _classified_featured, which promotes a listing across the
		// marketplace — an editorial decision, not a seller preference. Any
		// logged-in user could previously POST featured:1 and self-promote.
		// It now lives in APOLLO_ADVERTS_ADMIN_ONLY_META and is settable only
		// from wp-admin, same as the hostel switch.
		$map = array(
			'price'      => '_classified_price',
			'currency'   => '_classified_currency',
			'negotiable' => '_classified_negotiable',
			'condition'  => '_classified_condition',
			'location'   => '_classified_loc',
			'phone'      => '_classified_contact_phone',
			'whatsapp'   => '_classified_contact_whatsapp',
			'expires_at' => '_classified_expires_at',
		);

		foreach ( $map as $param => $meta_key ) {
			$value = $request->get_param( $param );
			if ( $value !== null ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $value ) );
			}
		}

		// ── Stay rules (accommodation adverts) ────────────────────────────
		// Sellers DO own these — how long someone may stay and when the place
		// is free is exactly the host's call — so unlike the hostel switch
		// they are accepted here and rendered on the frontend form.
		//
		// Previously 'avail_start' was posted by the rent_space form and
		// silently dropped (no entry in the map above), while 'avail_end' was
		// smuggled in as 'expires_at' — writing the availability window's end
		// into the advert's EXPIRY date, so a room free until December made
		// the whole listing disappear in December. Both now map to their real
		// keys.
		$min_nights = $request->get_param( 'min_nights' );
		if ( $min_nights !== null ) {
			update_post_meta( $post_id, '_classified_min_nights', max( 0, (int) $min_nights ) );
		}

		$max_days = $request->get_param( 'max_days' );
		if ( $max_days !== null ) {
			update_post_meta( $post_id, '_classified_max_days', max( 0, (int) $max_days ) );
		}

		foreach ( array( 'avail_start' => '_classified_avail_start', 'avail_end' => '_classified_avail_end' ) as $param => $meta_key ) {
			$value = $request->get_param( $param );
			if ( $value === null ) {
				continue;
			}
			$raw = sanitize_text_field( (string) $value );
			// Strict Y-m-d only — same guard as the metabox and
			// apollo_adverts_sanitize_meta_date(). Anything else stores empty
			// rather than a half-parsed date.
			update_post_meta( $post_id, $meta_key, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '' );
		}

		// ── Ticket resale ─────────────────────────────────────────────────
		// The sell form has always posted `quantity` and `event_id`; neither
		// had a handler here, and `quantity` had no meta key to land in at
		// all. So a seller picked their event and said "2 tickets", and the
		// resulting card rendered with an empty event block and no count.
		$quantity = $request->get_param( 'quantity' );
		if ( $quantity !== null ) {
			update_post_meta( $post_id, '_classified_quantity', max( 1, (int) $quantity ) );
		}

		$event_id = $request->get_param( 'event_id' );
		if ( $event_id !== null ) {
			// Stores the relation AND refreshes the denormalised
			// _classified_event_title/_date/_loc snapshot the cards read.
			apollo_adverts_link_event( $post_id, (int) $event_id );
		}
	}

	/**
	 * Permission checks
	 */
	public function create_item_permissions_check( $request ): bool|\WP_Error {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'unauthorized', __( 'Faça login para criar anúncios.', 'apollo-adverts' ), array( 'status' => 401 ) );
		}
		return true;
	}

	public function update_item_permissions_check( $request ): bool|\WP_Error {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'unauthorized', __( 'Faça login primeiro.', 'apollo-adverts' ), array( 'status' => 401 ) );
		}
		$post = get_post( $request->get_param( 'id' ) );
		if ( ! $post ) {
			return new \WP_Error( 'not_found', __( 'Anúncio não encontrado.', 'apollo-adverts' ), array( 'status' => 404 ) );
		}
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'forbidden', __( 'Permissão negada.', 'apollo-adverts' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public function delete_item_permissions_check( $request ): bool|\WP_Error {
		return $this->update_item_permissions_check( $request );
	}

	/**
	 * Collection params schema
	 */
	public function get_collection_params(): array {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => APOLLO_ADVERTS_POSTS_PER_PAGE,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'orderby'  => array(
				'type'              => 'string',
				'default'           => 'date',
				'enum'              => array( 'date', 'title', 'modified', 'meta_value_num' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'    => array(
				'type'              => 'string',
				'default'           => 'DESC',
				'enum'              => array( 'ASC', 'DESC' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'domain'   => array(
				'type'              => 'string',
				'description'       => 'Filter by classified_domain slug',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'intent'   => array(
				'type'              => 'string',
				'description'       => 'Filter by classified_intent slug',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'featured' => array(
				'type'              => 'boolean',
				'description'       => 'Only featured ads',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
		);
	}

	/**
	 * Create params schema
	 */
	protected function get_create_params(): array {
		return array(
			'title'      => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			// Body text. NOT required, and aliased.
			//
			// This was `required => true` while every frontend form posts the
			// body as `description`, never `content` — so the REST layer
			// rejected EVERY submission from the site with a 400 before any
			// handler ran. That is why none of the meta bugs below had ever
			// been noticed: no advert created through the UI reached the DB at
			// all. Requiredness is also wrong on its own terms — a classified
			// with a clear title and no body is perfectly valid, and all three
			// forms treat the description field as optional.
			'content'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			),
			'description' => array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			),
			'domain'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'intent'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'price'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'negotiable' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'condition'  => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'location'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'phone'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'whatsapp'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),

			// Advert kind. Both spellings of each pair are accepted and
			// canonicalised on write (see apollo_adverts_canonical_type).
			'classified_type' => array(
				'type'              => 'string',
				'enum'              => APOLLO_ADVERTS_META_KEYS['_classified_type']['values'],
				'sanitize_callback' => 'sanitize_text_field',
			),

			// Ticket resale.
			'event_id' => array( 'type' => 'integer', 'minimum' => 0, 'sanitize_callback' => 'absint' ),
			'quantity' => array( 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint' ),

			// Stay rules — accommodation adverts. Declared so the frontend
			// form's values survive REST's arg filtering; save_meta_from_request()
			// does the real clamping/format guard.
			'min_nights'  => array( 'type' => 'integer', 'minimum' => 0, 'sanitize_callback' => 'absint' ),
			'max_days'    => array( 'type' => 'integer', 'minimum' => 0, 'sanitize_callback' => 'absint' ),
			'avail_start' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'avail_end'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),

			// NOT declared here, deliberately: 'hostel' / 'hostel_url'.
			// The hostel switch is staff-verified (see
			// APOLLO_ADVERTS_ADMIN_ONLY_META in includes/constants.php) — it
			// has no create/update param, no field-map entry, and its
			// register_meta auth_callback requires manage_options, so the
			// CPT's generic REST meta endpoint rejects it too.
		);
	}
}
