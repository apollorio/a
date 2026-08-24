<?php
/**
 * Apollo CoAuthor — Constants.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── REST ─────────────────────────────────────────────────────────── */
define( 'APOLLO_COAUTHOR_REST_NAMESPACE', 'apollo/v1' );

/* ── Taxonomy ─────────────────────────────────────────────────────── */
define( 'APOLLO_COAUTHOR_TAX', 'coauthor' );

/*
 * ── Supported Post Types ───────────────────────────────────────────
 *
 * `local`, NOT `loc` (fixed 2026-08-17).
 *
 * This listed 'loc', which is not a registered post type — the CPT slug is
 * `local` (apollo-loc/includes/constants.php, apollo-core/config/cpts.php).
 * So the coauthor metabox was never added to venues and
 * register_taxonomy_for_object_type( 'coauthor', 'loc' ) in
 * src/Components/Taxonomy.php pointed at nothing. Silent, because attaching a
 * taxonomy to a non-existent type is not an error.
 *
 * The confusion is understandable and widespread: `loc` is the REQUIRED
 * vocabulary per 15-conventions namingRules (venue/location → loc), and it is
 * the surface key used by the lightbox contract. But the POST TYPE has always
 * been `local`. apollo-core/config/taxonomies.php gets this right; this file
 * did not. See registry 09-plugins/apollo-core.json
 * $cpt_registration_ground_truth_audit_2026_08_17.
 */
define(
	'APOLLO_COAUTHOR_POST_TYPES',
	array( 'event', 'dj', 'classified', 'doc', 'local', 'post' )
);

/* ── Meta Keys ────────────────────────────────────────────────────── */
define( 'APOLLO_COAUTHOR_META_KEY', '_coauthors' );

/* ── Cache ─────────────────────────────────────────────────────────── */
define( 'APOLLO_COAUTHOR_CACHE_GROUP', 'apollo_coauthor' );
