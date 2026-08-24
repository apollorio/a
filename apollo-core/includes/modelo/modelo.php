<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * MODELO — the "Hello world!" of the Apollo CPTs
 * ═══════════════════════════════════════════════════════════════════════
 *
 * WordPress ships one post called "Hello world!" and it has survived twenty
 * years because it answers a question a blank install cannot: *what is this
 * supposed to look like when it works?* Apollo's CPTs are far richer than a
 * blog post — `dj` has 38 registered fields, `local` 27 — so the question is
 * far harder, and the answer far more useful.
 *
 * This module seeds one record per CPT at slug `modelo`:
 *
 *     /dj/modelo       every one of the 38 fields, populated
 *     /local/modelo    every one of the 27 fields, populated
 *
 * ⚠ ROUTE NOTE — the request was `/loc/modelo`. The registered rewrite is
 * `local/{slug}` (apollo-loc pages[1]). Nothing here changes routing; the
 * seed lands at `/local/modelo`. If `/loc/` is wanted as the public prefix
 * that is a rewrite change in apollo-loc, and it is a separate decision
 * because `namingRules` prefers `loc` while 32 shipped meta keys say `local`.
 *
 * ── Why this is modular, and what "modular" bought ────────────────────
 *   modelo.php    mechanism — find-or-create, apply, verify. No content.
 *   data-dj.php   content. No logic.
 *   data-loc.php  content. No logic.
 *
 * The seeder does not know what a DJ is. It reads `apollo_form_schema()`,
 * applies whatever the data file provides, and then reports which declared
 * keys it could NOT fill. So the moment somebody registers a 39th field,
 * `/dj/modelo` stops being 100% and says so out loud. A demo record that
 * silently falls behind the schema is worse than no demo record, because it
 * looks authoritative while lying.
 *
 * ── Idempotent, and safe to run on a live site ────────────────────────
 * Keyed on `post_name = 'modelo'` within the CPT. Re-running updates in
 * place; it never duplicates. It never touches a post it did not create
 * (checked via `_apollo_modelo` marker meta), so a real artist who happens
 * to use the slug `modelo` is left alone.
 *
 * ── Triggers ──────────────────────────────────────────────────────────
 *   · apollo-core activation
 *   · `do_action('apollo/modelo/seed')`
 *   · Tools → Apollo Modelo (admin, manage_options)
 *
 * @package Apollo\Core
 * @since   6.2.8
 * @see     includes/form-schema.php   the coverage source of truth
 */

if (! defined('ABSPATH')) {
    exit;
}

const APOLLO_MODELO_SLUG   = 'modelo';
const APOLLO_MODELO_MARKER = '_apollo_modelo';

/**
 * The CPTs that get a modelo, and where their payload lives.
 *
 * @return array<string,string> cpt slug => data file
 */
function apollo_modelo_map(): array
{
    return (array) apply_filters(
        'apollo/modelo/map',
        array(
            'dj'    => __DIR__ . '/data-dj.php',
            'local' => __DIR__ . '/data-loc.php',
        )
    );
}

/**
 * Seed (or re-seed) every modelo record.
 *
 * @param bool $force Re-apply even if the record already exists.
 * @return array<string,array<string,mixed>> Per-CPT report.
 */
function apollo_modelo_seed(bool $force = true): array
{
    $report = array();

    foreach (apollo_modelo_map() as $cpt => $file) {
        if (! post_type_exists($cpt)) {
            $report[$cpt] = array( 'status' => 'skipped', 'reason' => 'CPT not registered' );
            continue;
        }
        if (! is_readable($file)) {
            $report[$cpt] = array( 'status' => 'skipped', 'reason' => 'payload missing: ' . basename($file) );
            continue;
        }

        $data = require $file;
        $report[$cpt] = apollo_modelo_apply($cpt, (array) $data, $force);
    }

    /* Events last: they need both record IDs, which only exist now. */
    $dj_id    = (int) ($report['dj']['post_id'] ?? 0);
    $local_id = (int) ($report['local']['post_id'] ?? 0);
    if ($dj_id || $local_id) {
        $report['event'] = apollo_modelo_seed_events($dj_id, $local_id);
    }

    do_action('apollo/modelo/seeded', $report);

    return $report;
}

/**
 * Apply one payload to one CPT.
 *
 * @param string              $cpt   Post type.
 * @param array<string,mixed> $data  Payload from a data-*.php file.
 * @param bool                $force Re-apply to an existing record.
 * @return array<string,mixed>
 */
function apollo_modelo_apply(string $cpt, array $data, bool $force): array
{
    $existing = apollo_modelo_find($cpt);

    /* Never overwrite a post we did not create. Somebody's real venue may
       legitimately be at the slug `modelo`. */
    if ($existing && ! get_post_meta($existing, APOLLO_MODELO_MARKER, true)) {
        return array(
            'status' => 'skipped',
            'reason' => sprintf('post %d holds slug "%s" and is not ours', $existing, APOLLO_MODELO_SLUG),
        );
    }
    if ($existing && ! $force) {
        return array( 'status' => 'exists', 'post_id' => $existing );
    }

    $postarr = array_merge(
        (array) ($data['post'] ?? array()),
        array( 'post_type' => $cpt )
    );
    if ($existing) {
        $postarr['ID'] = $existing;
    }

    $post_id = wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($post_id)) {
        return array( 'status' => 'error', 'reason' => $post_id->get_error_message() );
    }

    update_post_meta($post_id, APOLLO_MODELO_MARKER, '1');

    /* Meta goes through the schema-aware writer so the registered
       sanitize_callback runs and an empty override CLEARS rather than
       stores '' — the templates read absent as "use the derivation". */
    $meta = (array) ($data['meta'] ?? array());

    /* Media keys are attachment IDs, not URLs — see apollo_modelo_sideload().
       Only when explicitly re-seeded from the admin screen: media_sideload_image()
       makes an outbound HTTP request per image and must never run on a
       front-end init. */
    if ($force && is_admin() && function_exists('apollo_modelo_sideload')) {
        foreach (apollo_modelo_media_keys($cpt) as $mk) {
            if (! empty($meta[$mk]) && is_string($meta[$mk])) {
                $meta[$mk] = apollo_modelo_sideload($meta[$mk], $post_id);
            }
        }
    } else {
        /* Unattended seed: drop URL-valued media keys rather than storing a
           string that will cast to 0 and render as the placeholder. An absent
           value at least lets the template's own fallback run honestly. */
        foreach (apollo_modelo_media_keys($cpt) as $mk) {
            if (isset($meta[$mk]) && is_string($meta[$mk])) {
                unset($meta[$mk]);
            }
        }
    }
    if (function_exists('apollo_form_save')) {
        apollo_form_save($post_id, $cpt, $meta);
    } else {
        foreach ($meta as $k => $v) {
            ('' === $v || array() === $v) ? delete_post_meta($post_id, $k) : update_post_meta($post_id, $k, $v);
        }
    }

    /* Taxonomies — create the terms if they are not there yet. A modelo that
       needs a human to pre-create terms is not a working demo. */
    foreach ((array) ($data['terms'] ?? array()) as $tax => $names) {
        if (! taxonomy_exists($tax)) {
            continue;
        }
        $ids = array();
        foreach ((array) $names as $name) {
            $term = term_exists($name, $tax);
            if (! $term) {
                $term = wp_insert_term($name, $tax);
            }
            if (! is_wp_error($term) && isset($term['term_id'])) {
                $ids[] = (int) $term['term_id'];
            }
        }
        if ($ids) {
            wp_set_object_terms($post_id, $ids, $tax, false);
        }
    }

    return array_merge(
        array( 'status' => $existing ? 'updated' : 'created', 'post_id' => $post_id ),
        apollo_modelo_coverage($cpt, $post_id)
    );
}

/**
 * Find the modelo post for a CPT, published or not.
 *
 * @param string $cpt Post type.
 * @return int|null
 */
function apollo_modelo_find(string $cpt): ?int
{
    $q = new WP_Query(array(
        'post_type'      => $cpt,
        'name'           => APOLLO_MODELO_SLUG,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));

    return $q->posts ? (int) $q->posts[0] : null;
}

/**
 * How complete is this modelo, against the schema?
 *
 * This is the whole reason the module reads `apollo_form_schema()` instead of
 * carrying its own field list. Register a new key and the number drops here
 * first — before anybody discovers the gap on a rendered page.
 *
 * `filled` counts a key as filled when it holds a non-empty value OR is a
 * declared override that is intentionally blank (blank IS its correct state —
 * it means "use the derivation").
 *
 * @param string $cpt     Post type.
 * @param int    $post_id Modelo post.
 * @return array{declared:int,filled:int,pct:float,empty:string[]}
 */
function apollo_modelo_coverage(string $cpt, int $post_id): array
{
    if (! function_exists('apollo_form_schema')) {
        return array( 'declared' => 0, 'filled' => 0, 'pct' => 0.0, 'empty' => array() );
    }

    $schema = apollo_form_schema($cpt, 'rest');
    $empty  = array();
    $filled = 0;

    foreach ($schema as $key => $field) {
        $v = get_post_meta($post_id, $key, true);
        $has = ! ('' === $v || null === $v || array() === $v || false === $v);

        if ($has || ! empty($field['is_override'])) {
            $filled++;
            continue;
        }
        $empty[] = $key;
    }

    $n = count($schema);

    return array(
        'declared' => $n,
        'filled'   => $filled,
        'pct'      => $n ? round(100 * $filled / $n, 1) : 0.0,
        'empty'    => $empty,
    );
}


/**
 * Turn a remote image URL into a real attachment ID.
 *
 * FOUND ON THE LIVE PAGE, 2026-08-11: /dj/modelo rendered the placeholder SVG
 * instead of the seeded hero. Cause — `_dj_image`, `_dj_banner` and
 * `_local_image_1..5` are registered as `integer` (attachment ID), and
 * apollo_dj_get_banner() does `(int) get_post_meta(...)` then
 * wp_get_attachment_image_url(). A URL string casts to 0 and the template
 * falls through to its fallback. The seed was storing URLs. That is the
 * seed's bug, not the template's — the template is right.
 *
 * So the modelo sideloads its media into the library once and stores IDs,
 * which is also what a real record does. Network I/O never happens on a
 * front-end request: this runs only from Tools → Apollo Modelo.
 *
 * @param string $url    Remote image URL.
 * @param int    $parent Post to attach to.
 * @return int Attachment ID, or 0 on failure.
 */
function apollo_modelo_sideload(string $url, int $parent = 0): int
{
    if ('' === $url || ! preg_match('#^https?://#i', $url)) {
        return 0;
    }

    /* Re-use an attachment already sideloaded for this exact source, so
       re-running the seeder does not fill the library with duplicates. */
    $existing = get_posts(array(
        'post_type'      => 'attachment',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_apollo_modelo_src',   // phpcs:ignore WordPress.DB.SlowDBQuery
        'meta_value'     => $url,                   // phpcs:ignore WordPress.DB.SlowDBQuery
    ));
    if ($existing) {
        return (int) $existing[0];
    }

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $id = media_sideload_image($url, $parent, 'Modelo — mídia de demonstração', 'id');
    if (is_wp_error($id)) {
        return 0;
    }

    update_post_meta((int) $id, '_apollo_modelo_src', $url);
    update_post_meta((int) $id, APOLLO_MODELO_MARKER, '1');

    return (int) $id;
}

/**
 * Keys whose registered type is `integer` but whose payload carries a URL.
 *
 * @param string $cpt Post type.
 * @return string[]
 */
function apollo_modelo_media_keys(string $cpt): array
{
    $out = array();
    if (! function_exists('apollo_form_schema')) {
        return $out;
    }
    foreach (apollo_form_schema($cpt, 'rest') as $key => $f) {
        if ('integer' === ($f['type'] ?? '') && 'media_single' === ($f['control'] ?? '')) {
            $out[] = $key;
        }
    }
    return $out;
}


/**
 * Seed the cross-linked modelo events.
 *
 * Runs AFTER both modelo records exist, because the link keys need their
 * post IDs and those cannot be known while authoring a data file. This is
 * the piece that turns "0 Eventos · 0 Cidades" from a bug-looking zero into
 * a real derived count — the numbers were always honest, there was simply
 * nothing on the other end of the relation.
 *
 * Idempotent on the same terms as everything else here: keyed by slug,
 * marked with `_apollo_modelo`, never touching a post it did not create.
 *
 * @param int $dj_id    Modelo dj post ID.
 * @param int $local_id Modelo local post ID.
 * @return array{created:int,updated:int,skipped:int,ids:int[]}
 */
function apollo_modelo_seed_events(int $dj_id, int $local_id): array
{
    $file = __DIR__ . '/data-event.php';
    $out  = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'ids' => array() );

    if (! post_type_exists('event') || ! is_readable($file)) {
        return $out;
    }

    $data = (array) require $file;
    $link = (array) ($data['link'] ?? array());

    foreach ((array) ($data['rows'] ?? array()) as $row) {
        $slug = (string) ($row['post']['post_name'] ?? '');
        if ('' === $slug) {
            continue;
        }

        $q = new WP_Query(array(
            'post_type'      => 'event',
            'name'           => $slug,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));
        $existing = $q->posts ? (int) $q->posts[0] : 0;

        /* Somebody else's event may hold this slug. Leave it alone. */
        if ($existing && ! get_post_meta($existing, APOLLO_MODELO_MARKER, true)) {
            $out['skipped']++;
            continue;
        }

        $postarr = array_merge((array) $row['post'], array( 'post_type' => 'event' ));
        if ($existing) {
            $postarr['ID'] = $existing;
        }

        $id = wp_insert_post(wp_slash($postarr), true);
        if (is_wp_error($id)) {
            $out['skipped']++;
            continue;
        }
        $existing ? $out['updated']++ : $out['created']++;
        $out['ids'][] = (int) $id;

        update_post_meta($id, APOLLO_MODELO_MARKER, '1');

        $meta = (array) ($row['meta'] ?? array());

        /* Banner is an attachment ID like every other media key — same
           lesson as the hero placeholder. Sideload only from the admin
           screen; otherwise drop it rather than store a URL that casts to 0. */
        if (! empty($meta['_event_banner']) && is_string($meta['_event_banner'])) {
            $meta['_event_banner'] = ( is_admin() && function_exists('apollo_modelo_sideload') )
                ? apollo_modelo_sideload($meta['_event_banner'], (int) $id)
                : null;
            if (null === $meta['_event_banner']) {
                unset($meta['_event_banner']);
            }
        }

        /* The cross-links — the entire point of this function. */
        if (! empty($link['local']) && $local_id) {
            $meta[ $link['local'] ] = $local_id;
            /* Two key spellings are in the wild: _event_loc_id (6 call sites)
               and _event_local_id (3). Write both so neither template misses
               the relation. Unifying them is a separate decision. */
            $meta['_event_local_id'] = $local_id;
        }
        if (! empty($link['dj']) && $dj_id) {
            $meta[ $link['dj'] ] = array( $dj_id );
        }

        foreach ($meta as $k => $v) {
            ('' === $v || array() === $v) ? delete_post_meta($id, $k) : update_post_meta($id, $k, $v);
        }

        foreach ((array) ($row['terms'] ?? array()) as $tax => $names) {
            if (! taxonomy_exists($tax)) {
                continue;
            }
            $ids = array();
            foreach ((array) $names as $name) {
                $t = term_exists($name, $tax) ?: wp_insert_term($name, $tax);
                if (! is_wp_error($t) && isset($t['term_id'])) {
                    $ids[] = (int) $t['term_id'];
                }
            }
            if ($ids) {
                wp_set_object_terms((int) $id, $ids, $tax, false);
            }
        }
    }

    return $out;
}

/* ── triggers ───────────────────────────────────────────────────────── */

add_action('apollo/modelo/seed', 'apollo_modelo_seed');

/**
 * Seed once, late enough that CPTs and taxonomies exist.
 *
 * Guarded by an option rather than an activation hook alone: apollo-core may
 * already be active when this module ships, and an activation hook that has
 * already fired never fires again.
 */
add_action('init', function (): void {
    if ('1' === get_option('apollo_modelo_seeded')) {
        return;
    }
    if (! post_type_exists('dj') && ! post_type_exists('local')) {
        return; // owner plugins not up yet — try on a later request
    }
    apollo_modelo_seed(false);
    update_option('apollo_modelo_seeded', '1', false);
}, 30);

/**
 * Tools → Apollo Modelo — re-seed and read the coverage report.
 */
add_action('admin_menu', function (): void {
    add_management_page(
        'Apollo Modelo',
        'Apollo Modelo',
        'manage_options',
        'apollo-modelo',
        'apollo_modelo_admin_page'
    );
});

/**
 * Render the admin screen.
 */
function apollo_modelo_admin_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Sem permissão.', 'apollo-core'));
    }

    $report = null;
    if (isset($_POST['apollo_modelo_run']) && check_admin_referer('apollo_modelo')) {
        $report = apollo_modelo_seed(true);
    }

    echo '<div class="wrap"><h1>Apollo Modelo</h1>';
    echo '<p>Registro de demonstração por CPT, no slug <code>modelo</code>. '
        . 'Preenche 100% dos campos declarados para mostrar como a página se comporta completa. '
        . 'Reexecutar atualiza no lugar — nunca duplica.</p>';

    echo '<form method="post">';
    wp_nonce_field('apollo_modelo');
    submit_button('Recriar registros modelo', 'primary', 'apollo_modelo_run');
    echo '</form>';

    foreach (apollo_modelo_map() as $cpt => $_file) {
        $id  = apollo_modelo_find($cpt);
        $cov = $id ? apollo_modelo_coverage($cpt, $id) : array( 'pct' => 0, 'filled' => 0, 'declared' => 0, 'empty' => array() );

        echo '<h2>' . esc_html($cpt) . '</h2><p>';
        if ($id) {
            printf(
                'Post <code>#%d</code> — <a href="%s" target="_blank" rel="noopener">ver página</a> · <a href="%s">editar</a><br>Cobertura: <strong>%s%%</strong> (%d/%d campos)',
                (int) $id,
                esc_url((string) get_permalink($id)),
                esc_url((string) get_edit_post_link($id)),
                esc_html((string) $cov['pct']),
                (int) $cov['filled'],
                (int) $cov['declared']
            );
            if (! empty($cov['empty'])) {
                echo '<br><span style="color:#b32d2e">Vazios: <code>'
                    . esc_html(implode('</code> <code>', $cov['empty'])) . '</code></span>';
            }
        } else {
            echo '<em>ainda não criado</em>';
        }
        echo '</p>';
    }

    if (null !== $report) {
        echo '<h2>Última execução</h2><pre style="background:#fff;padding:12px;overflow:auto">'
            . esc_html((string) wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            . '</pre>';
    }

    echo '</div>';
}
