<?php
/**
 * Apollo — content seeder.
 *
 * Creates the locs, DJs, tracks and events described in apollo-seed-data.json.
 *
 * ── HOW TO RUN ───────────────────────────────────────────────────────────────
 *
 *   wp eval-file _inventory/seed/apollo-seed.php            # dry run, prints a plan
 *   wp eval-file _inventory/seed/apollo-seed.php -- --write # actually writes
 *
 * Or from wp-admin as an administrator:
 *
 *   /wp-admin/?apollo_seed=1          dry run
 *   /wp-admin/?apollo_seed=1&write=1  writes
 *
 * ── IDEMPOTENT BY DESIGN ─────────────────────────────────────────────────────
 *
 * Every entry carries a seed_key stored as _apollo_seed_key. The seeder looks a
 * post up by that key first and UPDATES it rather than inserting a second one.
 * Re-running is safe and is the expected way to apply corrections — edit the
 * JSON, re-run, done.
 *
 * DRY RUN IS THE DEFAULT. This folder deploys on save and a seeder that wrote on
 * import would be a content incident, not a feature. You have to ask for --write.
 *
 * ── WHAT IT WILL NOT DO ──────────────────────────────────────────────────────
 *
 *   · It never invents data. Fields absent from the JSON are simply not written,
 *     so a gap renders as empty rather than as a plausible lie.
 *   · It never publishes an unresolved event. The four Blueticket events could
 *     not be identified by fetch or by 20+ searches, so they are seeded as
 *     DRAFTS carrying only their URL and coupon flag, waiting for a human.
 *   · It never registers a CPT, taxonomy or meta key. Those belong to
 *     apollo-core; if `local`, `dj`, `track` or `event` is not registered when
 *     this runs, the seeder says so and stops.
 *
 * @package Apollo\Seed
 * @since   2026-08-17
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run the seeder.
 *
 * @param bool $write Actually persist. False = plan only.
 * @return array<string,mixed> Report.
 */
function apollo_seed_run( bool $write = false ): array {
	$report = array( 'mode' => $write ? 'WRITE' : 'DRY RUN', 'lines' => array(), 'created' => 0, 'updated' => 0, 'skipped' => 0 );

	$say = static function ( string $s ) use ( &$report ) {
		$report['lines'][] = $s;
	};

	$json = __DIR__ . '/apollo-seed-data.json';
	if ( ! is_readable( $json ) ) {
		$say( 'ABORT — apollo-seed-data.json not readable at ' . $json );
		return $report;
	}
	$data = json_decode( (string) file_get_contents( $json ), true );
	if ( ! is_array( $data ) ) {
		$say( 'ABORT — apollo-seed-data.json is not valid JSON' );
		return $report;
	}

	/*
	 * Fail closed on missing post types. Seeding into a CPT that is not
	 * registered creates orphan rows WordPress will not show anywhere — worse
	 * than not seeding, because the mess is invisible until someone queries the
	 * table directly.
	 */
	foreach ( array( 'local', 'dj', 'event' ) as $pt ) {
		if ( ! post_type_exists( $pt ) ) {
			$say( 'ABORT — post type "' . $pt . '" is not registered. Is apollo-core active? Have permalinks been flushed?' );
			return $report;
		}
	}
	$has_track = post_type_exists( 'track' );
	if ( ! $has_track ) {
		$say( 'NOTE  — post type "track" is not registered; DJ releases will be skipped.' );
	}

	/**
	 * Find an existing seeded post, or 0.
	 *
	 * @param string $key Seed key.
	 * @return int
	 */
	$find = static function ( string $key ): int {
		$q = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'meta_key'         => '_apollo_seed_key',
				'meta_value'       => $key,
				'suppress_filters' => false,
			)
		);
		return $q ? (int) $q[0] : 0;
	};

	/**
	 * Create or update one seeded post.
	 *
	 * @param string               $type   Post type.
	 * @param array<string,mixed>  $entry  Entry from the JSON.
	 * @param array<string,int>    $locs   seed_key => post ID, for relations.
	 * @return int Post ID, or 0.
	 */
	$upsert = static function ( string $type, array $entry, array $locs ) use ( $write, $say, $find, &$report ): int {
		$key = (string) ( $entry['seed_key'] ?? '' );
		if ( '' === $key ) {
			return 0;
		}

		$existing = $find( $key );
		$status   = (string) ( $entry['status'] ?? 'publish' );
		$title    = (string) ( $entry['title'] ?? $key );

		$verb = $existing ? 'UPDATE' : 'CREATE';
		$say( sprintf( '  %-6s %-8s %-28s %s', $verb, $type, $key, $title ) );

		if ( ! $write ) {
			$existing ? $report['updated']++ : $report['created']++;
			return $existing;
		}

		$postarr = array(
			'ID'           => $existing ?: 0,
			'post_type'    => $type,
			'post_title'   => $title,
			'post_content' => (string) ( $entry['content'] ?? '' ),
			'post_status'  => $status,
		);
		$id = $existing ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );

		if ( is_wp_error( $id ) ) {
			$say( '    ERROR ' . $id->get_error_message() );
			$report['skipped']++;
			return 0;
		}
		$id = (int) $id;
		update_post_meta( $id, '_apollo_seed_key', $key );
		$existing ? $report['updated']++ : $report['created']++;

		// Meta — only what the JSON actually carries. Absent stays absent.
		foreach ( (array) ( $entry['meta'] ?? array() ) as $mk => $mv ) {
			update_post_meta( $id, (string) $mk, $mv );
		}

		// Relation to a seeded loc, resolved by seed key rather than by title.
		if ( ! empty( $entry['loc_seed_key'] ) && isset( $locs[ $entry['loc_seed_key'] ] ) ) {
			update_post_meta( $id, '_event_loc_id', (int) $locs[ $entry['loc_seed_key'] ] );
		}

		// Taxonomies — terms are created if missing, which is correct for a
		// seeder: a genre that no post uses yet still has to exist to be applied.
		foreach ( (array) ( $entry['taxonomies'] ?? array() ) as $tax => $terms ) {
			if ( taxonomy_exists( (string) $tax ) ) {
				wp_set_object_terms( $id, (array) $terms, (string) $tax, false );
			}
		}

		/*
		 * Lineup names are stored as plain text, NOT resolved to dj posts.
		 * Matching "thales" or "bossa" to a roster entry by string is how you
		 * credit the wrong artist. _event_dj_ids is left for a human to set, and
		 * the names remain readable in the meantime.
		 */
		if ( ! empty( $entry['lineup_names'] ) ) {
			update_post_meta( $id, '_apollo_seed_lineup_names', implode( ', ', (array) $entry['lineup_names'] ) );
		}

		return $id;
	};

	// ── 1. Locs first: events relate to them. ────────────────────────────────
	$say( '' );
	$say( 'LOCS' );
	$locs = array();
	foreach ( (array) ( $data['locs'] ?? array() ) as $loc ) {
		$id = $upsert( 'local', $loc, array() );
		if ( $id ) {
			$locs[ (string) $loc['seed_key'] ] = $id;
		}
		if ( ! empty( $loc['gaps'] ) ) {
			$say( '         gaps: ' . implode( ', ', (array) $loc['gaps'] ) );
		}
	}

	// ── 2. DJs, then their releases as `track` posts. ────────────────────────
	$say( '' );
	$say( 'DJS' );
	foreach ( (array) ( $data['djs'] ?? array() ) as $dj ) {
		$dj_id = $upsert( 'dj', $dj, array() );
		if ( ! empty( $dj['gaps'] ) ) {
			$say( '         gaps: ' . implode( ', ', (array) $dj['gaps'] ) );
		}

		if ( ! $has_track || empty( $dj['tracks'] ) ) {
			continue;
		}
		foreach ( (array) $dj['tracks'] as $i => $t ) {
			$tkey  = $dj['seed_key'] . '-track-' . ( (int) $i + 1 );
			$tmeta = array( '_track_artists' => (string) ( $t['artists'] ?? $dj['title'] ) );
			foreach ( array( 'release_date', 'album', 'label', 'duration', 'url_soundcloud', 'url_bandcamp', 'url_spotify', 'url_youtube' ) as $f ) {
				if ( ! empty( $t[ $f ] ) ) {
					$tmeta[ '_track_' . $f ] = (string) $t[ $f ];
				}
			}
			if ( $dj_id ) {
				$tmeta['_track_dj_ids'] = array( $dj_id );
			}
			$upsert(
				'track',
				array(
					'seed_key'   => $tkey,
					'title'      => (string) ( $t['title'] ?? 'Faixa' ),
					'status'     => 'publish',
					'meta'       => $tmeta,
					'taxonomies' => $dj['taxonomies'] ?? array(),
				),
				array()
			);
		}
	}

	// ── 3. Events. ───────────────────────────────────────────────────────────
	$say( '' );
	$say( 'EVENTS' );
	foreach ( (array) ( $data['events'] ?? array() ) as $ev ) {
		$upsert( 'event', $ev, $locs );
	}

	$say( '' );
	$say( 'EVENTS — UNRESOLVED (seeded as DRAFT, never published)' );
	foreach ( (array) ( $data['events_unresolved'] ?? array() ) as $ev ) {
		$ev['title']  = 'Blueticket ' . str_replace( 'ev-blueticket-', '', (string) $ev['seed_key'] ) . ' — a identificar';
		$ev['status'] = 'draft';
		$ev['content'] = 'Evento não identificado. A página do Blueticket é renderizada por JavaScript e o ID não aparece em nenhuma busca. Preencha título, data, local e lineup à mão — o link e o cupom já estão salvos.';
		$upsert( 'event', $ev, $locs );
	}

	if ( ! empty( $data['excluded'] ) ) {
		$say( '' );
		$say( 'EXCLUDED' );
		foreach ( (array) $data['excluded'] as $x ) {
			$say( '  ' . (string) ( $x['title'] ?? '?' ) . ' — ' . (string) ( $x['reason'] ?? '' ) );
		}
	}

	return $report;
}

/**
 * Format the report for a console or a browser.
 *
 * @param array<string,mixed> $r Report.
 * @return string
 */
function apollo_seed_format( array $r ): string {
	$out  = "\n=== APOLLO SEED — " . $r['mode'] . " ===\n";
	$out .= implode( "\n", (array) $r['lines'] );
	$out .= sprintf(
		"\n\n  created %d · updated %d · skipped %d\n",
		(int) $r['created'],
		(int) $r['updated'],
		(int) $r['skipped']
	);
	if ( 'DRY RUN' === $r['mode'] ) {
		$out .= "  Nothing was written. Re-run with --write (or &write=1) to apply.\n";
	}
	return $out;
}

// ── Entry points ─────────────────────────────────────────────────────────────

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$argv  = isset( $args ) && is_array( $args ) ? $args : array();
	$write = in_array( '--write', (array) $argv, true );
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	echo apollo_seed_format( apollo_seed_run( $write ) );
} else {
	add_action(
		'admin_init',
		static function () {
			if ( empty( $_GET['apollo_seed'] ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$write = ! empty( $_GET['write'] );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html( apollo_seed_format( apollo_seed_run( $write ) ) );
			exit;
		}
	);
}
