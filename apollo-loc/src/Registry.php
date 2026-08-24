<?php
/**
 * REMOVED 2026-08-17 — this file held a dead duplicate of apollo-loc's entire
 * registration layer.
 *
 * WHAT WAS HERE
 * -------------
 * class Apollo\Local\Registry, containing:
 *   · register_post_type( 'local' )      — duplicate of src/CPT/CPTRegistrar.php
 *   · register_taxonomy( 'local_type' )  — duplicate of src/CPT/TaxonomyRegistrar.php
 *   · register_taxonomy( 'local_area' )  — duplicate of the same
 *   · add_meta_box + render_metabox      — duplicate of src/Admin/Metabox/*
 *
 * WHY IT WENT
 * -----------
 * Nothing ever constructed it. src/Plugin.php builds CPT\CPTRegistrar,
 * Shortcodes\ShortcodeRegistry and Admin\Metabox\MetaboxManager — never this
 * class. Verified by grepping `new Registry` across the whole plugin: the only
 * hit was `new Shortcodes\ShortcodeRegistry()`.
 *
 * It was not harmless dead weight. Its args DISAGREED with the live registrar:
 *
 *   live  (CPT/CPTRegistrar.php:29)  has_archive => 'locais'
 *   here                             has_archive => 'local'
 *
 * and its own docblock claimed rewrite="gps", archive="gps", rest_base="locals"
 * — three values that appeared nowhere in its body. So the file documented one
 * thing, contained another, and contradicted the code that actually runs. Wiring
 * it up by accident would have changed the venue archive URL and re-registered
 * two taxonomies with different arguments.
 *
 * That is the cardinal sin from CLAUDE.md at plugin scale: two owners for one
 * declaration, dormant only because one of them was never called.
 *
 * The file is kept as this tombstone rather than deleted outright so the reason
 * survives in the tree. Delete it whenever convenient — nothing loads it.
 *
 * @package Apollo\Local
 * @see     src/CPT/CPTRegistrar.php       the live CPT registration
 * @see     src/CPT/TaxonomyRegistrar.php  the live taxonomy registration
 * @see     src/Admin/Metabox/             the live metabox cells
 * @see     _inventory/PLAN-cpt-and-admin-panels.md §1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
