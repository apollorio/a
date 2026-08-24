<?php
/**
 * Event visibility guard.
 *
 * `_event_privacy` was only ever enforced on GET /eventos/{id}. Every listing
 * endpoint — /eventos, /proximos, /passados, /hoje, /buscar, /por-data,
 * /por-local, /por-dj, /calendario — plus archives and shortcodes returned
 * private events to anyone. This closes that in ONE place instead of patching
 * a dozen WP_Query call sites.
 *
 * A private event stays visible to its author, its co-authors and moderators.
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event ids the CURRENT viewer must not see.
 *
 * Computed once per request. Returns [] for moderators (they see everything)
 * and when no private events exist.
 *
 * @return int[]
 */
function apollo_event_hidden_ids(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	if ( current_user_can( 'manage_options' ) || current_user_can( 'apollo_moderate_content' ) ) {
		$cache = array();
		return $cache;
	}

	$cpt = defined( 'APOLLO_EVENT_CPT' ) ? APOLLO_EVENT_CPT : 'event';

	/* Every restricted event, regardless of author. */
	$restricted = get_posts(
		array(
			'post_type'              => $cpt,
			'post_status'            => 'any',
			'posts_per_page'         => 500,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
			'apollo_skip_privacy'    => true,
			'meta_query'             => array(
				array(
					'key'     => '_event_privacy',
					'value'   => array( 'private', 'invite' ),
					'compare' => 'IN',
				),
			),
		)
	);

	if ( empty( $restricted ) ) {
		$cache = array();
		return $cache;
	}

	$user_id = get_current_user_id();
	$hidden  = array();

	foreach ( $restricted as $event_id ) {
		$event_id = (int) $event_id;

		if ( $user_id ) {
			$post = get_post( $event_id );
			if ( $post && (int) $post->post_author === $user_id ) {
				continue;
			}
			if ( function_exists( 'apollo_event_user_is_coauthor' )
				&& apollo_event_user_is_coauthor( $event_id, $user_id ) ) {
				continue;
			}
			if ( current_user_can( 'edit_post', $event_id ) ) {
				continue;
			}
		}

		$hidden[] = $event_id;
	}

	$cache = $hidden;
	return $cache;
}

/**
 * Exclude restricted events from every front-end / REST event query.
 *
 * Opt out on a single query with:
 *   $q->set( 'apollo_skip_privacy', true );
 *
 * @param WP_Query $query Query being prepared.
 * @return void
 */
function apollo_event_apply_privacy( $query ): void {
	if ( ! $query instanceof WP_Query ) {
		return;
	}

	/* wp-admin list tables keep full visibility — capability checks apply there. */
	if ( is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( $query->get( 'apollo_skip_privacy' ) ) {
		return;
	}

	$cpt        = defined( 'APOLLO_EVENT_CPT' ) ? APOLLO_EVENT_CPT : 'event';
	$query_type = $query->get( 'post_type' );

	$targets_events = ( $cpt === $query_type )
		|| ( is_array( $query_type ) && in_array( $cpt, $query_type, true ) );

	if ( ! $targets_events ) {
		return;
	}

	/* Singular requests are gated by can_view_event() / can_view checks. */
	if ( $query->is_singular() ) {
		return;
	}

	$hidden = apollo_event_hidden_ids();
	if ( empty( $hidden ) ) {
		return;
	}

	$existing = (array) $query->get( 'post__not_in' );
	$query->set( 'post__not_in', array_values( array_unique( array_merge( $existing, $hidden ) ) ) );
}
add_action( 'pre_get_posts', 'apollo_event_apply_privacy', 20 );
