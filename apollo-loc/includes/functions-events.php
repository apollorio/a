<?php
/**
 * Funções de eventos do Apollo Local
 *
 * Helpers para buscar e contar eventos associados a um local.
 * Só funciona quando apollo-events está ativo.
 *
 * @package Apollo\Local
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna eventos futuros vinculados ao local.
 *
 * @param int $local_id Post ID do local.
 * @param int $limit    Máximo de eventos. -1 para todos.
 * @return \WP_Post[] Lista de posts de eventos.
 */
function apollo_local_get_upcoming_events( int $local_id, int $limit = 5 ): array {
	if ( ! post_type_exists( 'event' ) ) {
		return array();
	}

	$cache_key = 'local_events_' . $local_id . '_' . $limit;
	$cached    = wp_cache_get( $cache_key, APOLLO_LOCAL_CACHE_GROUP );
	if ( $cached !== false ) {
		return $cached;
	}

	$args = array(
		'post_type'      => 'event',
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => '_event_local_id',
				'value'   => $local_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			),
			array(
				'key'     => '_event_start_date',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
		'orderby'        => 'meta_value',
		'meta_key'       => '_event_start_date',
		'order'          => 'ASC',
	);

	$posts = get_posts( $args );
	wp_cache_set( $cache_key, $posts, APOLLO_LOCAL_CACHE_GROUP, APOLLO_LOCAL_CACHE_TTL );

	return $posts;
}

/**
 * Conta eventos futuros vinculados ao local.
 *
 * @param int $local_id Post ID do local.
 * @return int Número de eventos futuros.
 */
function apollo_local_count_upcoming_events( int $local_id ): int {
	$cache_key = 'local_event_count_' . $local_id;
	$cached    = wp_cache_get( $cache_key, APOLLO_LOCAL_CACHE_GROUP );
	if ( $cached !== false ) {
		return (int) $cached;
	}

	$count = count( apollo_local_get_upcoming_events( $local_id, -1 ) );
	wp_cache_set( $cache_key, $count, APOLLO_LOCAL_CACHE_GROUP, APOLLO_LOCAL_CACHE_TTL );

	return $count;
}

/**
 * Retorna array estruturado de upcoming events para uso em templates.
 *
 * @param int $local_id Post ID do local.
 * @param int $limit    Máximo de eventos.
 * @return array<int, array{id:int, title:string, url:string, date:string, image:string, tags:string[]}> Eventos formatados.
 */
function apollo_local_get_events_data( int $local_id, int $limit = 6 ): array {
	$posts  = apollo_local_get_upcoming_events( $local_id, $limit );
	$result = array();

	foreach ( $posts as $post ) {
		$ev_date  = get_post_meta( $post->ID, '_event_date', true );
		$ev_image = get_the_post_thumbnail_url( $post->ID, 'medium' );
		$sounds   = get_the_terms( $post->ID, 'sound' );
		$tags     = array();

		if ( ! is_wp_error( $sounds ) && ! empty( $sounds ) ) {
			$tags = wp_list_pluck( array_slice( $sounds, 0, 3 ), 'name' );
		}

		$result[] = array(
			'id'    => $post->ID,
			'title' => get_the_title( $post->ID ),
			'url'   => get_permalink( $post->ID ),
			'date'  => $ev_date,
			'image' => $ev_image ?: '',
			'tags'  => $tags,
		);
	}

	return $result;
}
