<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * FORM SCHEMA — one schema, three surfaces
 * ═══════════════════════════════════════════════════════════════════════
 *
 * PHASE 2.1 · 2026-08-11.
 *
 * The problem this exists to end: `[apollo_add_dj]` hand-listed 6 inputs
 * against 38 registered keys (16 % coverage) and `[apollo_add_loc]` 6
 * against 27 (22 %). Nobody noticed, because a hand-listed form has no way
 * to notice. The metabox drifted the same way in the other direction, and
 * `apollo-loc`'s REST create endpoint wrote no meta at all.
 *
 * A field now exists in exactly one place — `MetaRegistry::load_definitions()`
 * — and every surface derives from it:
 *
 *     MetaRegistry  ──▶  apollo_form_schema( $cpt )  ──▶  frontend form
 *                                                    ├──▶  REST create/update
 *                                                    └──▶  admin metabox
 *
 * Add a key to MetaRegistry and it appears on all three. Forget to add it
 * and `apollo_form_schema_coverage()` fails the gate. There is no third
 * option, which is the entire point.
 *
 * ── What this file does NOT do ────────────────────────────────────────
 * It renders no markup and enqueues nothing. It answers one question —
 * "what fields does this CPT have, and how should each be edited?" — and
 * leaves presentation to the surface. A shared renderer that tried to look
 * right in a metabox AND on a luxury public form would end up looking
 * wrong in both.
 *
 * @package Apollo\Core
 * @since   6.2.7
 * @see     _inventory/PLAN-CPT-SINGLE-PAGES-2026-08-11.md  §3 Phase 2
 * @see     _inventory/registry/21-mockup-field-contract.json
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Map a registered meta `type` onto the input control that should edit it.
 *
 * Deliberately conservative: an unrecognised type becomes `text`, never
 * nothing. A field the author cannot see is worse than one styled plainly.
 *
 * @param array<string,mixed> $def Meta definition from MetaRegistry.
 * @param string              $key Meta key — the naming convention is a signal.
 * @return string One of the apollo-lux-panels Panel.php field types.
 */
function apollo_form_control_for(array $def, string $key): string
{
    if (! empty($def['enum'])) {
        return 'select';
    }

    $type = isset($def['type']) ? (string) $def['type'] : 'string';

    if ('array' === $type) {
        /* A structured repeater declares object items; a flat one declares
           string items. The REST schema already tells us which. */
        $items = $def['show_in_rest']['schema']['items']['type'] ?? 'string';
        return ('object' === $items) ? 'repeater_card' : 'repeater';
    }
    if ('integer' === $type || 'number' === $type) {
        /* By convention `_*_image*` and `_*_id` integers are attachment IDs. */
        return (bool) preg_match('/(image|banner|photo|gallery|cover)/', $key) ? 'media_single' : 'number';
    }
    if ('boolean' === $type) {
        return 'toggle';
    }

    /* String subtypes read off the key, which is the only hint we have and a
       reliable one — the naming convention predates this file by years. */
    if (preg_match('/(_url|_website|_instagram|_facebook|_soundcloud|_spotify|_youtube|_mixcloud|_bandcamp|_beatport|_tiktok|_twitter|_resident_advisor|_mix|_set|_rider|_media_kit|_video|_photo)/', $key)) {
        return 'url';
    }
    if (preg_match('/(_email|_booking)$/', $key)) {
        return 'email';
    }
    if (preg_match('/(_bio|_statement|_description|_about)/', $key)) {
        return 'textarea';
    }
    if (preg_match('/_(lat|lng)$/', $key)) {
        return 'number';
    }
    if (preg_match('/_hours$/', $key)) {
        return 'hours';
    }

    return 'text';
}

/**
 * Keys that must never appear on a public "add new" form.
 *
 * Not a security boundary — the REST permission callbacks are. This keeps
 * system plumbing out of a form a member fills in, which is a usability
 * decision, and keeps `_dj_verified` out of the hands of the person who
 * would most like to set it, which is not.
 *
 * @param string $cpt Post type.
 * @return string[]
 */
function apollo_form_admin_only_keys(string $cpt): array
{
    $common = array(
        '_dj_user_id',
        '_local_user_id',
        '_dj_verified',      // a self-serve verified badge is not a badge
        '_local_visit_count',
        '_local_region',     // derived from city/state
        '_local_testimonials', // no writer yet — registry gap D3
    );

    /**
     * Filter the keys hidden from public forms for a CPT.
     *
     * @param string[] $keys
     * @param string   $cpt
     */
    return (array) apply_filters('apollo/form/admin_only_keys', $common, $cpt);
}

/**
 * Build the canonical field descriptor list for a CPT.
 *
 * @param string $cpt     Post type slug.
 * @param string $surface 'form' (public) | 'metabox' | 'rest'.
 * @return array<string,array<string,mixed>> Keyed by meta key.
 */
function apollo_form_schema(string $cpt, string $surface = 'form'): array
{
    if (! class_exists('\Apollo\Core\MetaRegistry') && ! function_exists('apollo_core_meta_registry')) {
        /* MetaRegistry is the only source. Without it we return nothing rather
           than guessing — a partial schema is how the drift started. */
        return array();
    }

    $registry = null;
    if (class_exists('\Apollo\Core\MetaRegistry')) {
        $registry = \Apollo\Core\MetaRegistry::get_instance();
    } elseif (function_exists('apollo_core_meta_registry')) {
        $registry = apollo_core_meta_registry();
    }

    $defs = ($registry && method_exists($registry, 'get_cpt_meta'))
        ? (array) $registry->get_cpt_meta($cpt)
        : array();

    if (empty($defs)) {
        return array();
    }

    $hidden = ('form' === $surface) ? apollo_form_admin_only_keys($cpt) : array();
    $out    = array();

    foreach ($defs as $key => $def) {
        if (in_array($key, $hidden, true)) {
            continue;
        }

        $def = (array) $def;

        $out[$key] = array(
            'key'         => $key,
            'control'     => apollo_form_control_for($def, $key),
            'type'        => isset($def['type']) ? (string) $def['type'] : 'string',
            'label'       => apollo_form_label_for($key, $def),
            'hint'        => isset($def['description']) ? (string) $def['description'] : '',
            'enum'        => isset($def['enum']) ? (array) $def['enum'] : array(),
            'default'     => $def['default'] ?? null,
            'rest'        => ! empty($def['show_in_rest']),
            'sanitize'    => $def['sanitize'] ?? null,
            /* An override field is one whose description says so. The templates
               fall back to a derivation when it is empty, so the form must not
               present it as required. */
            'is_override' => (bool) preg_match('/EMPTY BY DEFAULT|override/i', (string) ($def['description'] ?? '')),
        );
    }

    /**
     * Filter the assembled schema.
     *
     * @param array  $out     Descriptors keyed by meta key.
     * @param string $cpt     Post type.
     * @param string $surface form | metabox | rest.
     */
    return (array) apply_filters('apollo/form/schema', $out, $cpt, $surface);
}

/**
 * Human label for a meta key.
 *
 * Falls back to a de-prefixed, title-cased key rather than printing `_dj_bio`
 * at a person. A generated label is not as good as a written one, but it is
 * infinitely better than a raw meta key, and it means adding a key never
 * ships an unlabelled input.
 *
 * @param string              $key Meta key.
 * @param array<string,mixed> $def Definition.
 * @return string
 */
function apollo_form_label_for(string $key, array $def = array()): string
{
    /**
     * Filter to supply a curated label. apollo-djs / apollo-loc register their
     * translated labels here; anything they miss still gets a readable one.
     *
     * @param string|null $label
     * @param string      $key
     */
    $curated = apply_filters('apollo/form/label', null, $key, $def);
    if (is_string($curated) && '' !== $curated) {
        return $curated;
    }

    $s = (string) preg_replace('/^_(dj|local|event|classified)_/', '', $key);
    $s = str_replace('_', ' ', $s);

    return ucfirst(trim($s));
}

/**
 * Coverage gate — G2 in the plan.
 *
 * Compares what a surface actually offers against what the schema declares.
 * Returns the missing keys, so a test or a WP-CLI command can fail on it
 * instead of somebody noticing eighteen months later that a form collects
 * six of thirty-eight fields.
 *
 * @param string   $cpt    Post type.
 * @param string[] $offered Keys the surface presents.
 * @return array{declared:int,offered:int,missing:string[],extra:string[],pct:float}
 */
function apollo_form_schema_coverage(string $cpt, array $offered): array
{
    $declared = array_keys(apollo_form_schema($cpt, 'form'));
    $offered  = array_values(array_unique($offered));

    $missing = array_values(array_diff($declared, $offered));
    $extra   = array_values(array_diff($offered, $declared));
    $n       = count($declared);

    return array(
        'declared' => $n,
        'offered'  => count(array_intersect($declared, $offered)),
        'missing'  => $missing,
        'extra'    => $extra,
        'pct'      => $n ? round(100 * count(array_intersect($declared, $offered)) / $n, 1) : 0.0,
    );
}

/**
 * Sanitize + persist a whole submission against the schema.
 *
 * The single write path. Every surface — public form, REST create, REST update
 * — routes through here, so a key can never be saved by one and silently
 * skipped by another. Sanitization is whatever MetaRegistry declared; this
 * function does not invent its own, because two sanitizers for one key is the
 * exact defect that gave `_dj_tracks` two competing schemas.
 *
 * @param int                 $post_id Target post.
 * @param string              $cpt     Post type.
 * @param array<string,mixed> $input   Raw input keyed by meta key.
 * @return array{saved:string[],skipped:string[]}
 */
function apollo_form_save(int $post_id, string $cpt, array $input): array
{
    $schema  = apollo_form_schema($cpt, 'rest');
    $saved   = array();
    $skipped = array();

    foreach ($schema as $key => $field) {
        if (! array_key_exists($key, $input)) {
            $skipped[] = $key;
            continue;
        }

        $value = $input[$key];

        /* An empty override must CLEAR the meta, not store '' — the template
           reads "empty" as "use the derivation". Storing an empty string and
           storing nothing are different states downstream. */
        if ($field['is_override'] && ('' === $value || array() === $value)) {
            delete_post_meta($post_id, $key);
            $saved[] = $key;
            continue;
        }

        /* update_post_meta runs the registered sanitize_callback through
           sanitize_meta(), so no sanitizing happens here by design. */
        update_post_meta($post_id, $key, $value);
        $saved[] = $key;
    }

    /**
     * Fires after a schema-driven save.
     *
     * @param int    $post_id
     * @param string $cpt
     * @param array  $saved
     */
    do_action('apollo/form/saved', $post_id, $cpt, $saved);

    return array('saved' => $saved, 'skipped' => $skipped);
}
