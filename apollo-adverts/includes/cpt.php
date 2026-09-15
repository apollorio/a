<?php
/**
 * CPT, Taxonomy, Meta Registration (Fallback)
 *
 * Apollo Core's CPTRegistry should register CPT `classified` from master-registry.
 * This file provides fallback registration + taxonomy + meta registration + post statuses.
 *
 * Adapted from WPAdverts init/module_cpt_register patterns.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fallback CPT registration (if apollo-core didn't register it)
 * Adapted from WPAdverts adverts_register_post_type
 */
function apollo_adverts_register_cpt(): void {

	// Only register if core hasn't already
	if ( post_type_exists( APOLLO_CPT_CLASSIFIED ) ) {
		return;
	}

	$labels = array(
		'name'               => __( 'Anúncios', 'apollo-adverts' ),
		'singular_name'      => __( 'Anúncio', 'apollo-adverts' ),
		'add_new'            => __( 'Novo Anúncio', 'apollo-adverts' ),
		'add_new_item'       => __( 'Adicionar Anúncio', 'apollo-adverts' ),
		'edit_item'          => __( 'Editar Anúncio', 'apollo-adverts' ),
		'new_item'           => __( 'Novo Anúncio', 'apollo-adverts' ),
		'view_item'          => __( 'Ver Anúncio', 'apollo-adverts' ),
		'search_items'       => __( 'Buscar Anúncios', 'apollo-adverts' ),
		'not_found'          => __( 'Nenhum anúncio encontrado', 'apollo-adverts' ),
		'not_found_in_trash' => __( 'Nenhum anúncio na lixeira', 'apollo-adverts' ),
		'menu_name'          => __( 'Anúncios', 'apollo-adverts' ),
	);

	register_post_type(
		APOLLO_CPT_CLASSIFIED,
		array(
			'labels'          => $labels,
			'public'          => true,
			'has_archive'     => true,
			'show_in_rest'    => true,
			'rest_base'       => 'classifieds',
			'rewrite'         => array(
				'slug'       => 'anuncio',
				'with_front' => false,
			),
			// 'comments' powers Depoimentos on accommodation adverts. Support
			// is declared for the whole CPT (WP has no per-post-meta support
			// switch), then narrowed to accommodations only by the
			// apollo_adverts_depoimentos_open() filter further down.
			'supports'        => array( 'title', 'editor', 'thumbnail', 'author', 'excerpt', 'comments' ),
			'menu_icon'       => 'dashicons-megaphone',
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'apollo_adverts_register_cpt', 5 );

/**
 * Register taxonomies (fallback if core didn't)
 * Adapted from WPAdverts taxonomy registration
 */
function apollo_adverts_register_taxonomies(): void {

	// classified_domain (Tipo de Anúncio)
	if ( ! taxonomy_exists( APOLLO_TAX_CLASSIFIED_DOMAIN ) ) {
		register_taxonomy(
			APOLLO_TAX_CLASSIFIED_DOMAIN,
			APOLLO_CPT_CLASSIFIED,
			array(
				'labels'            => array(
					'name'          => __( 'Categorias', 'apollo-adverts' ),
					'singular_name' => __( 'Categoria', 'apollo-adverts' ),
					'search_items'  => __( 'Buscar Categorias', 'apollo-adverts' ),
					'all_items'     => __( 'Todas as Categorias', 'apollo-adverts' ),
					'edit_item'     => __( 'Editar Categoria', 'apollo-adverts' ),
					'add_new_item'  => __( 'Nova Categoria', 'apollo-adverts' ),
					'menu_name'     => __( 'Categorias', 'apollo-adverts' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'tipo-anuncio',
					'with_front' => false,
				),
			)
		);
	}

	// classified_intent (Intenção)
	if ( ! taxonomy_exists( APOLLO_TAX_CLASSIFIED_INTENT ) ) {
		register_taxonomy(
			APOLLO_TAX_CLASSIFIED_INTENT,
			APOLLO_CPT_CLASSIFIED,
			array(
				'labels'            => array(
					'name'          => __( 'Intenções', 'apollo-adverts' ),
					'singular_name' => __( 'Intenção', 'apollo-adverts' ),
					'search_items'  => __( 'Buscar Intenções', 'apollo-adverts' ),
					'all_items'     => __( 'Todas as Intenções', 'apollo-adverts' ),
					'edit_item'     => __( 'Editar Intenção', 'apollo-adverts' ),
					'add_new_item'  => __( 'Nova Intenção', 'apollo-adverts' ),
					'menu_name'     => __( 'Intenções', 'apollo-adverts' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'intencao',
					'with_front' => false,
				),
			)
		);
	}
}
add_action( 'init', 'apollo_adverts_register_taxonomies', 5 );

/**
 * Register custom post statuses
 * Adapted from WPAdverts adverts_register_status
 */
function apollo_adverts_register_post_statuses(): void {

	register_post_status(
		'expired',
		array(
			'label'                     => __( 'Expirado', 'apollo-adverts' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Expirado <span class="count">(%s)</span>', 'Expirados <span class="count">(%s)</span>', 'apollo-adverts' ),
		)
	);

	register_post_status(
		'classified_tmp',
		array(
			'label'                     => __( 'Temporário', 'apollo-adverts' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		)
	);
}
add_action( 'init', 'apollo_adverts_register_post_statuses', 6 );

/**
 * Register post meta THROUGH apollo-core MetaRegistry.
 *
 * This plugin must not call register_post_meta() directly.
 * Adapted config is merged into core map via filter.
 *
 * @param array $meta_config Existing post meta map from apollo-core.
 * @return array
 */
function apollo_adverts_register_meta( array $meta_config ): array {
	if ( ! isset( $meta_config[ APOLLO_CPT_CLASSIFIED ] ) || ! is_array( $meta_config[ APOLLO_CPT_CLASSIFIED ] ) ) {
		$meta_config[ APOLLO_CPT_CLASSIFIED ] = array();
	}

	$meta_keys = APOLLO_ADVERTS_META_KEYS;

	foreach ( $meta_keys as $key => $config ) {
		$type = 'string';
		if ( $config['type'] === 'float' ) {
			$type = 'number';
		} elseif ( $config['type'] === 'bool' ) {
			$type = 'boolean';
		} elseif ( $config['type'] === 'int' ) {
			$type = 'integer';
		}

		// Editorial keys are wp-admin only. The blanket edit_posts callback
		// below is enough for a seller's own listing data, but NOT for the
		// hostel switch: that flag decides whether a listing bypasses the
		// auth gate entirely, so a contributor ("cena") being able to PATCH
		// it through the CPT's generic REST meta endpoint would let anyone
		// self-declare their spare room a public business and unmask the
		// gate. Staff-only, checked at the write boundary rather than merely
		// omitted from the UI.
		$is_editorial = in_array( $key, APOLLO_ADVERTS_ADMIN_ONLY_META, true );

		$meta_config[ APOLLO_CPT_CLASSIFIED ][ $key ] = array(
			'type'          => $type,
			'single'        => true,
			'show_in_rest'  => true,
			'sanitize'      => 'apollo_adverts_sanitize_meta_' . $config['type'],
			'auth_callback' => $is_editorial
				? function () {
					return current_user_can( 'manage_options' );
				}
				: function () {
					return current_user_can( 'edit_posts' );
				},
		);
	}

	return $meta_config;
}
add_filter( 'apollo_core_register_meta', 'apollo_adverts_register_meta', 10 );

/**
 * Meta sanitization callbacks
 */
function apollo_adverts_sanitize_meta_float( $value ): float {
	return (float) $value;
}

function apollo_adverts_sanitize_meta_string( $value ): string {
	return sanitize_text_field( (string) $value );
}

function apollo_adverts_sanitize_meta_bool( $value ): string {
	return $value ? '1' : '';
}

function apollo_adverts_sanitize_meta_int( $value ): int {
	return (int) $value;
}

function apollo_adverts_sanitize_meta_date( $value ): string {
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ) {
		return (string) $value;
	}
	return '';
}

function apollo_adverts_sanitize_meta_url( $value ): string {
	return esc_url_raw( trim( (string) $value ) );
}

/**
 * Canonicalise an advert type to the single spelling that gets stored.
 *
 * ticket_sell → ticket, rent_space → accommodation. Anything already
 * canonical (or unrecognised) passes through untouched; an empty value falls
 * back to 'general', matching the meta's declared default.
 *
 * Every write path must run values through here, so the DB never holds two
 * spellings for one kind and the marketplace read queries stay valid.
 *
 * @param string $type Raw type from a form, REST param or importer.
 */
function apollo_adverts_canonical_type( string $type ): string {
	$type = sanitize_text_field( $type );
	if ( '' === $type ) {
		return 'general';
	}
	$type = APOLLO_ADVERTS_TYPE_CANONICAL[ $type ] ?? $type;
	$allowed = APOLLO_ADVERTS_META_KEYS['_classified_type']['values'] ?? array();
	return in_array( $type, $allowed, true ) ? $type : 'general';
}

/**
 * Is this advert an accommodation (a stay), rather than a ticket/general ad?
 *
 * Accepts both spellings so historic rows written before
 * apollo_adverts_canonical_type() existed still resolve correctly.
 *
 * @param int $post_id Advert post ID.
 */
function apollo_adverts_is_accommodation( int $post_id ): bool {
	$type = (string) get_post_meta( $post_id, '_classified_type', true );
	return in_array( $type, APOLLO_ADVERTS_ACCOMMODATION_TYPES, true );
}

/**
 * Is this advert a ticket resale?
 *
 * @param int $post_id Advert post ID.
 */
function apollo_adverts_is_ticket( int $post_id ): bool {
	$type = (string) get_post_meta( $post_id, '_classified_type', true );
	return in_array( $type, APOLLO_ADVERTS_TICKET_TYPES, true );
}

/**
 * Snapshot an Apollo `event` onto a resale advert.
 *
 * The card templates read _classified_event_title/_date/_loc rather than
 * joining to the event on every render, so the relation (_classified_event_id)
 * is stored alongside a denormalised copy of the three fields the card needs.
 * Re-running this refreshes the snapshot if the event later moves or is
 * renamed.
 *
 * @param int $post_id  Advert post ID.
 * @param int $event_id Linked event post ID (0 clears the link).
 */
function apollo_adverts_link_event( int $post_id, int $event_id ): void {
	if ( $event_id <= 0 ) {
		delete_post_meta( $post_id, '_classified_event_id' );
		return;
	}

	$event = get_post( $event_id );
	if ( ! $event || 'event' !== $event->post_type || 'publish' !== $event->post_status ) {
		return;
	}

	update_post_meta( $post_id, '_classified_event_id', $event_id );
	update_post_meta( $post_id, '_classified_event_title', $event->post_title );

	$start = (string) get_post_meta( $event_id, '_event_start_date', true );
	if ( $start ) {
		update_post_meta( $post_id, '_classified_event_date', $start );
	}

	// Venue name via the events plugin's own resolver when available, so the
	// loc/local naming convention stays owned by one place.
	$loc_name = '';
	if ( function_exists( 'apollo_event_get_loc' ) ) {
		$loc = apollo_event_get_loc( $event_id );
		$loc_name = $loc['title'] ?? '';
	}
	if ( $loc_name ) {
		update_post_meta( $post_id, '_classified_event_loc', $loc_name );
	}
}

/**
 * Keep every resale advert's event snapshot current.
 *
 * apollo_adverts_link_event() above promises, in its own docblock, that
 * "re-running this refreshes the snapshot if the event later moves or is
 * renamed". Nothing re-ran it. It fires on ClassifiedsController.php:496 and
 * Plugin.php:883 — both of which are the ADVERT being saved, never the event.
 *
 * So an event could be renamed, moved to another venue or shifted to a new
 * date, and every resale advert pointing at it kept showing the old copy,
 * silently, forever. The relation was correct; only the refresh was missing.
 *
 * Why a snapshot at all rather than reading the event at render time: the
 * cards are listed twelve at a time on /anuncios, and joining to the event on
 * every card on every request is a query per card. The denormalised copy is
 * deliberate. This function is the invalidation the design always assumed.
 *
 * Bounded on purpose. An event with more than APOLLO_ADVERTS_EVENT_RESYNC_MAX
 * resale adverts is not a real scenario today, and an unbounded loop inside a
 * save_post handler is how an admin screen starts timing out. The cap is
 * filterable so it never becomes a silent ceiling.
 *
 * Known limit, stated rather than hidden: this fires on save_post_event. An
 * event edited purely through a REST meta write that does not touch the post
 * row will not trigger it. Re-saving the advert still refreshes it, exactly as
 * before, so the worst case is the behaviour that shipped until today.
 *
 * @since 1.1.9
 * @param int      $event_id Event post ID.
 * @param \WP_Post|null $post  The event post, as passed by save_post_event.
 * @return void
 */
function apollo_adverts_resync_event_adverts( int $event_id, $post = null ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $event_id ) || wp_is_post_autosave( $event_id ) ) {
		return;
	}

	$post = $post instanceof WP_Post ? $post : get_post( $event_id );
	if ( ! $post || 'event' !== $post->post_type ) {
		return;
	}

	/**
	 * How many resale adverts one event may refresh in a single save.
	 *
	 * @param int $max Default 500.
	 */
	$max = (int) apply_filters( 'apollo_adverts_event_resync_max', 500 );

	$adverts = get_posts(
		array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'any',
			'posts_per_page' => $max,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_classified_event_id',
					'value' => (string) $event_id,
				),
			),
		)
	);

	foreach ( $adverts as $advert_id ) {
		apollo_adverts_link_event( (int) $advert_id, $event_id );
	}

	/**
	 * Fires after an event's resale adverts have been refreshed.
	 *
	 * @param int   $event_id Event post ID.
	 * @param int[] $adverts  Advert IDs refreshed.
	 */
	do_action( 'apollo/adverts/event_resynced', $event_id, $adverts );
}
add_action( 'save_post_event', 'apollo_adverts_resync_event_adverts', 20, 2 );

/**
 * Is this accommodation a hostel?
 *
 * The single switch that decides whether an accommodation listing is public.
 * A hostel is a registered, worldwide-listed business, so showing it openly
 * exposes nothing that isn't already on Booking/Hostelworld. Every other
 * accommodation is a private home and stays locked behind auth — same rule as
 * resale tickets.
 *
 * Deliberately reads the meta on ANY post ID rather than checking post type
 * first: /casa's Acomoda::Rio strip lists the `local` CPT rather than
 * `classified`, and this way that surface honours the same switch the moment
 * the flag exists on those posts. Absent meta → false → locked, which is the
 * safe default for a privacy gate.
 *
 * @param int $post_id Advert (or local) post ID.
 */
function apollo_adverts_is_hostel( int $post_id ): bool {
	return '1' === (string) get_post_meta( $post_id, '_classified_hostel', true );
}

/**
 * Should this listing's identity/contact be locked for the current viewer?
 *
 * Locked when: the viewer is a guest AND the listing is not a public hostel.
 * Members always see through; hostels are always open to everyone.
 *
 * @param int $post_id Listing post ID.
 */
function apollo_adverts_is_identity_locked( int $post_id ): bool {
	if ( is_user_logged_in() ) {
		return false;
	}
	return ! apollo_adverts_is_hostel( $post_id );
}

/**
 * Public outbound URL for a hostel listing (empty for anything else).
 *
 * When present, the card's chat CTA is replaced by a link to this URL —
 * a hostel takes bookings on its own site, so routing guests into Apollo's
 * peer-to-peer chat would be the wrong door.
 *
 * @param int $post_id Advert post ID.
 */
function apollo_adverts_hostel_url( int $post_id ): string {
	if ( ! apollo_adverts_is_hostel( $post_id ) ) {
		return '';
	}
	return (string) get_post_meta( $post_id, '_classified_hostel_url', true );
}

// ═══════════════════════════════════════════════════════════════════════════
// DEPOIMENTOS (testimonials) — accommodation adverts only
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Guarantee 'comments' support even when apollo-core (or anything else)
 * registered the CPT first — apollo_adverts_register_cpt() self-skips in that
 * case, so its supports array would never apply. Runs after every plausible
 * registration priority.
 */
function apollo_adverts_add_comment_support(): void {
	if ( post_type_exists( APOLLO_CPT_CLASSIFIED ) ) {
		add_post_type_support( APOLLO_CPT_CLASSIFIED, 'comments' );
	}
}
add_action( 'init', 'apollo_adverts_add_comment_support', 20 );

/**
 * Depoimentos are for stays, not for ticket resale.
 *
 * WordPress has no per-post-type-and-meta comment switch, so support is
 * declared CPT-wide above and narrowed here: a `classified` accepts
 * depoimentos only when it's an accommodation. Ticket/general adverts are
 * short-lived one-off transactions — a testimonial thread on them would be
 * noise, and worse, a public channel between two people who are supposed to
 * be talking privately.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function apollo_adverts_depoimentos_open( $open, $post_id ) {
	if ( APOLLO_CPT_CLASSIFIED !== get_post_type( $post_id ) ) {
		return $open;
	}
	return apollo_adverts_is_accommodation( (int) $post_id );
}
add_filter( 'comments_open', 'apollo_adverts_depoimentos_open', 10, 2 );

/**
 * Relabel the comment surface as "Depoimentos" across wp-admin.
 *
 * Naming convention (registry 15-conventions): comment/review → depoimento.
 * The frontend string is the pt-BR word for "testimonials", which is what the
 * label reads as to a Brazilian user — same word, no separate translation.
 */
function apollo_adverts_depoimentos_admin_labels(): void {
	global $post_type;
	if ( APOLLO_CPT_CLASSIFIED !== $post_type ) {
		return;
	}
	add_filter(
		'gettext',
		function ( $translated, $original, $domain ) {
			if ( 'default' !== $domain ) {
				return $translated;
			}
			$map = array(
				'Comments'      => __( 'Depoimentos', 'apollo-adverts' ),
				'Comment'       => __( 'Depoimento', 'apollo-adverts' ),
				'Allow comments' => __( 'Permitir depoimentos', 'apollo-adverts' ),
			);
			return $map[ $original ] ?? $translated;
		},
		10,
		3
	);
}
add_action( 'admin_head', 'apollo_adverts_depoimentos_admin_labels' );

/**
 * Add "Expired" status to admin dropdown
 * Adapted from WPAdverts admin status display
 */
function apollo_adverts_admin_status_display(): void {
	global $post;
	if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
		return;
	}

	if ( $post->post_status === 'expired' ) {
		echo '<script>
			jQuery(document).ready(function($){
				$("select#post_status").append(\'<option value="expired" selected="selected">' . esc_js( __( 'Expirado', 'apollo-adverts' ) ) . '</option>\');
				$(".misc-pub-section #post-status-display").text("' . esc_js( __( 'Expirado', 'apollo-adverts' ) ) . '");
			});
		</script>';
	}
}
add_action( 'admin_footer-post.php', 'apollo_adverts_admin_status_display' );
add_action( 'admin_footer-post-new.php', 'apollo_adverts_admin_status_display' );
