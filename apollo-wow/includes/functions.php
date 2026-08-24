<?php
/**
 * Apollo WOW — Helper Functions
 *
 * @package Apollo\Wow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Available WOW reaction types (emoji-based, NO likes/hearts).
 */
function apollo_get_wow_types(): array {
	return apply_filters(
		'apollo/wow/types',
		array(
			'wow'    => array(
				'emoji' => '🤩',
				'label' => 'Wow!',
			),
			'fire'   => array(
				'emoji' => '🔥',
				'label' => 'Fogo',
			),
			'clap'   => array(
				'emoji' => '👏',
				'label' => 'Aplausos',
			),
			'mind'   => array(
				'emoji' => '🤯',
				'label' => 'Insano',
			),
			'vibe'   => array(
				'emoji' => '🎵',
				'label' => 'Vibe',
			),
			'rocket' => array(
				'emoji' => '🚀',
				'label' => 'Foguete',
			),
		)
	);
}

/**
 * Add a WOW reaction.
 */
function apollo_add_wow( int $user_id, string $object_type, int $object_id, string $reaction_type = 'wow' ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_wow_reactions';

	$types = apollo_get_wow_types();
	if ( ! isset( $types[ $reaction_type ] ) ) {
		return false;
	}

	$result = $wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO {$table} (user_id, object_type, object_id, reaction_type, created_at) VALUES (%d, %s, %d, %s, %s)",
			$user_id,
			$object_type,
			$object_id,
			$reaction_type,
			current_time( 'mysql' )
		)
	);

	if ( $result ) {
		// Update post meta counts
		if ( $object_type === 'post' || post_type_exists( $object_type ) ) {
			apollo_update_wow_counts( $object_type, $object_id );
		}

		do_action( 'apollo/wow/added', $user_id, $object_type, $object_id, $reaction_type );
	}

	return $result > 0;
}

/**
 * Remove a WOW reaction.
 */
function apollo_remove_wow( int $user_id, string $object_type, int $object_id, string $reaction_type = 'wow' ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_wow_reactions';

	$deleted = $wpdb->delete(
		$table,
		array(
			'user_id'       => $user_id,
			'object_type'   => $object_type,
			'object_id'     => $object_id,
			'reaction_type' => $reaction_type,
		)
	);

	if ( $deleted ) {
		if ( $object_type === 'post' || post_type_exists( $object_type ) ) {
			apollo_update_wow_counts( $object_type, $object_id );
		}
		do_action( 'apollo/wow/removed', $user_id, $object_type, $object_id, $reaction_type );
	}

	return (bool) $deleted;
}

/**
 * Toggle a WOW reaction (add if not exists, remove if exists).
 */
function apollo_toggle_wow( int $user_id, string $object_type, int $object_id, string $reaction_type = 'wow' ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_wow_reactions';

	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND object_type = %s AND object_id = %d AND reaction_type = %s",
			$user_id,
			$object_type,
			$object_id,
			$reaction_type
		)
	);

	if ( $exists ) {
		apollo_remove_wow( $user_id, $object_type, $object_id, $reaction_type );
		return array(
			'action' => 'removed',
			'active' => false,
		);
	} else {
		apollo_add_wow( $user_id, $object_type, $object_id, $reaction_type );
		return array(
			'action' => 'added',
			'active' => true,
		);
	}
}

/**
 * Get WOW counts for an object.
 */
function apollo_get_wow_counts( string $object_type, int $object_id ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_wow_reactions';

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT reaction_type, COUNT(*) as count FROM {$table} WHERE object_type = %s AND object_id = %d GROUP BY reaction_type",
			$object_type,
			$object_id
		),
		ARRAY_A
	);

	$counts = array();
	$total  = 0;
	foreach ( $rows as $row ) {
		$counts[ $row['reaction_type'] ] = (int) $row['count'];
		$total                          += (int) $row['count'];
	}

	return array(
		'total' => $total,
		'types' => $counts,
	);
}

/**
 * Update cached WOW counts in post meta.
 */
function apollo_update_wow_counts( string $object_type, int $object_id ): void {
	$counts = apollo_get_wow_counts( $object_type, $object_id );
	// Store on the post if it's a WP post type
	if ( get_post( $object_id ) ) {
		update_post_meta( $object_id, '_wow_count', $counts['total'] );
		update_post_meta( $object_id, '_wow_counts', $counts['types'] );
	}
}

/**
 * Get user's WOW reactions on an object.
 */
function apollo_get_user_wows( int $user_id, string $object_type, int $object_id ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'apollo_wow_reactions';

	return $wpdb->get_col(
		$wpdb->prepare(
			"SELECT reaction_type FROM {$table} WHERE user_id = %d AND object_type = %s AND object_id = %d",
			$user_id,
			$object_type,
			$object_id
		)
	) ?: array();
}

/**
 * Generate WOW chart data (bar/donut) in pure PHP.
 */
function apollo_wow_chart_data( string $object_type, int $object_id ): array {
	$counts = apollo_get_wow_counts( $object_type, $object_id );
	$types  = apollo_get_wow_types();

	$chart = array();
	foreach ( $types as $key => $info ) {
		$chart[] = array(
			'type'  => $key,
			'emoji' => $info['emoji'],
			'label' => $info['label'],
			'count' => $counts['types'][ $key ] ?? 0,
		);
	}

	return array(
		'total'     => $counts['total'],
		'breakdown' => $chart,
	);
}
