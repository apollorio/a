#!/usr/bin/env php
<?php
/**
 * Migrate `_dj_tracks` post meta into `track` CPT posts.  (plan T6-1)
 *
 * WHY THIS EXISTS
 * ---------------
 * Two stores hold the same concept and nothing bridges them.
 *
 *   · `_dj_tracks`  — a repeater on the `dj` post, edited in apollo-lux-panels'
 *                     "Faixas (Out Now)" field. Read by apollo_dj_get_tracks()
 *                     (apollo-djs/includes/functions.php:380), which renders the
 *                     DJ's OWN profile page and nothing else.
 *   · `track` CPT   — registered in apollo-core/config/cpts.php:107, queried by
 *                     apollo_track_query() (apollo-djs/includes/tracks.php:39),
 *                     which is the ONLY source the /casa "Out Now" rail reads
 *                     (apollo-templates/.../new-home/tracks.php:38).
 *
 * So a DJ fills in their tracks, sees them on their profile, and they never
 * appear on the home page. Not a bug in either file — the migration between
 * them was planned as T6 and never run. `_track_migrated_from` is already
 * registered in MetaRegistry for exactly this purpose and nothing writes it.
 *
 * WHAT IT DOES
 * ------------
 * Dry-run by default. It reports, and it writes NOTHING, until you pass --live.
 * Idempotent: a row already migrated is detected by `_track_migrated_from` plus
 * a title match and is skipped, so re-running is safe.
 *
 * USAGE
 *   php wp-content/plugins/apollo-djs/bin/migrate-dj-tracks.php            # report only
 *   php wp-content/plugins/apollo-djs/bin/migrate-dj-tracks.php --verbose  # every row
 *   php wp-content/plugins/apollo-djs/bin/migrate-dj-tracks.php --dj=123   # one DJ
 *   php wp-content/plugins/apollo-djs/bin/migrate-dj-tracks.php --live     # actually write
 *
 * Env:
 *   APOLLO_WP_LOAD — path to wp-load.php (auto-detected if omitted)
 *
 * @package Apollo\DJs
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

/* ─────────────────────────── arguments ─────────────────────────── */

$argvv   = $argv ?? array();
$live    = in_array('--live', $argvv, true);
$verbose = in_array('--verbose', $argvv, true);
$only_dj = 0;
foreach ($argvv as $a) {
    if (strpos($a, '--dj=') === 0) {
        $only_dj = (int) substr($a, 5);
    }
}

/* ─────────────────────────── bootstrap ─────────────────────────── */

$wp_load = getenv('APOLLO_WP_LOAD') ?: '';
if ('' === $wp_load) {
    foreach (array(
        dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'wp-load.php',
        dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'wp-load.php',
    ) as $path) {
        if (is_readable($path)) {
            $wp_load = $path;
            break;
        }
    }
}

if ('' === $wp_load || ! is_readable($wp_load)) {
    fwrite(STDERR, "Could not find wp-load.php. Set APOLLO_WP_LOAD.\n");
    exit(1);
}

define('WP_USE_THEMES', false);
require $wp_load;

if (! post_type_exists('track')) {
    fwrite(STDERR, "The `track` post type is not registered. Is apollo-core active?\n");
    exit(1);
}

/* ────────────────── the mapping, declared once ────────────────── */

/**
 * `_dj_tracks` row key => `track` meta key.
 *
 * Derived from apollo_dj_sanitize_tracks_meta() (apollo-djs/includes/functions.php:295),
 * which is the only writer of the v2 shape, checked against the `_track_*` keys
 * registered in apollo-core/src/Core/MetaRegistry.php. `title` is deliberately
 * absent: it becomes post_title, not meta.
 */
const FIELD_MAP = array(
    'artists'        => '_track_artists',
    'duration'       => '_track_duration',
    'bpm'            => '_track_bpm',
    'release_date'   => '_track_release_date',
    'album'          => '_track_album',
    'label'          => '_track_label',
    'genre'          => '_track_genre_legacy',
    'cover_url'      => '_track_cover_url',
    'url_soundcloud' => '_track_url_soundcloud',
    'url_spotify'    => '_track_url_spotify',
    'url_bandcamp'   => '_track_url_bandcamp',
    'url_download'   => '_track_url_download',
);

/* ─────────────────────────── scan ─────────────────────────── */

$dj_args = array(
    'post_type'      => 'dj',
    'post_status'    => array('publish', 'draft', 'pending', 'private'),
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
    'meta_query'     => array(
        array('key' => '_dj_tracks', 'compare' => 'EXISTS'),
    ),
);
if ($only_dj > 0) {
    $dj_args['p'] = $only_dj;
    unset($dj_args['meta_query']);
}

$dj_ids = get_posts($dj_args);

$stats = array(
    'djs_with_meta' => 0,
    'rows_total'    => 0,
    'rows_skipped'  => 0,
    'rows_existing' => 0,
    'rows_ready'    => 0,
    'rows_written'  => 0,
);
$plan = array();

foreach ($dj_ids as $dj_id) {
    $rows = get_post_meta((int) $dj_id, '_dj_tracks', true);
    if (! is_array($rows) || ! $rows) {
        continue;
    }

    $stats['djs_with_meta']++;
    $dj_title = get_the_title((int) $dj_id);

    foreach ($rows as $i => $row) {
        $stats['rows_total']++;

        if (! is_array($row)) {
            $stats['rows_skipped']++;
            $plan[] = array('dj' => $dj_id, 'i' => $i, 'state' => 'SKIP', 'why' => 'row is not an array', 'title' => '');
            continue;
        }

        $title = trim((string) ($row['title'] ?? ''));
        if ('' === $title) {
            $stats['rows_skipped']++;
            $plan[] = array('dj' => $dj_id, 'i' => $i, 'state' => 'SKIP', 'why' => 'no title', 'title' => '');
            continue;
        }

        // Already migrated? Match on provenance AND title, so a DJ with two
        // tracks does not collapse into one, and a re-run creates nothing.
        $existing = get_posts(array(
            'post_type'      => 'track',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'title'          => $title,
            'meta_query'     => array(
                array('key' => '_track_migrated_from', 'value' => (string) $dj_id),
            ),
        ));

        if ($existing) {
            $stats['rows_existing']++;
            $plan[] = array('dj' => $dj_id, 'i' => $i, 'state' => 'HAVE', 'why' => 'track #' . $existing[0], 'title' => $title);
            continue;
        }

        $stats['rows_ready']++;
        $plan[] = array('dj' => $dj_id, 'i' => $i, 'state' => 'NEW', 'why' => $dj_title, 'title' => $title, 'row' => $row);
    }
}

/* ─────────────────────────── write ─────────────────────────── */

if ($live) {
    foreach ($plan as $item) {
        if ('NEW' !== $item['state']) {
            continue;
        }

        $row   = $item['row'];
        $dj_id = (int) $item['dj'];

        $track_id = wp_insert_post(array(
            'post_type'   => 'track',
            'post_status' => 'publish',
            'post_title'  => $item['title'],
            'post_author' => (int) get_post_field('post_author', $dj_id),
        ), true);

        if (is_wp_error($track_id)) {
            fwrite(STDERR, sprintf("  FAILED dj=%d row=%d — %s\n", $dj_id, $item['i'], $track_id->get_error_message()));
            continue;
        }

        foreach (FIELD_MAP as $from => $to) {
            $value = $row[$from] ?? '';
            if ('' === $value || null === $value) {
                continue;
            }
            update_post_meta($track_id, $to, $value);
        }

        // The credit, and the provenance that makes this script idempotent.
        update_post_meta($track_id, '_track_dj_ids', array($dj_id));
        update_post_meta($track_id, '_track_migrated_from', (string) $dj_id);

        $stats['rows_written']++;
    }
}

/* ─────────────────────────── report ─────────────────────────── */

$mode = $live ? 'LIVE — posts were created' : 'DRY RUN — nothing was written';

echo "\n";
echo "  apollo-djs · _dj_tracks → track CPT   (plan T6-1)\n";
echo "  " . $mode . "\n";
echo "  " . str_repeat('─', 72) . "\n\n";

printf("  %-34s %d\n", 'dj posts carrying _dj_tracks', $stats['djs_with_meta']);
printf("  %-34s %d\n", 'rows found', $stats['rows_total']);
printf("  %-34s %d\n", 'already migrated (skipped)', $stats['rows_existing']);
printf("  %-34s %d\n", 'unusable (skipped)', $stats['rows_skipped']);
printf("  %-34s %d\n", $live ? 'created' : 'would create', $live ? $stats['rows_written'] : $stats['rows_ready']);
echo "\n";

if ($verbose || (! $live && $stats['rows_ready'] > 0 && $stats['rows_ready'] <= 40)) {
    echo "  Rows:\n";
    foreach ($plan as $item) {
        // printf pads by BYTES, and these titles are full of accents — "Fundição"
        // is 9 characters and 10 bytes, so %-44s silently loses a column per accent.
        $label = mb_substr($item['title'] ?: '(untitled)', 0, 44);
        $label .= str_repeat(' ', max(0, 44 - mb_strlen($label)));
        printf("    [%-4s] dj#%-6d %s %s\n", $item['state'], $item['dj'], $label, $item['why']);
    }
    echo "\n";
} elseif (! $live && $stats['rows_ready'] > 40) {
    echo "  " . $stats['rows_ready'] . " rows would be created. Re-run with --verbose to list them.\n\n";
}

if (! $live && $stats['rows_ready'] > 0) {
    echo "  Nothing was written. These posts publish to the /casa Out Now rail\n";
    echo "  the moment they exist, so read the list above first, then:\n\n";
    echo "      php " . basename(__FILE__) . " --live\n\n";
}

exit(0);
