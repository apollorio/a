<?php
/**
 * REMOVED 2026-08-17 — dead duplicate of the live metabox cells.
 *
 * WHAT WAS HERE
 * -------------
 * class Apollo\Local\Admin\Metabox — a monolith registering four boxes
 * (apollo-loc-address, apollo-loc-contact, apollo-loc-details, gallery) and
 * saving the whole `_local_*` set.
 *
 * WHY IT WENT
 * -----------
 * Never instantiated. src/Plugin.php:44 builds Admin\Metabox\MetaboxManager,
 * which wires the four SEPARATE cells in src/Admin/Metabox/ — AddressMetabox,
 * ContactMetabox, DetailsMetabox, GalleryMetabox — with saving centralised in
 * MetaboxSaver on save_post_local:20.
 *
 * The duplication was dangerous rather than merely redundant: this class used
 * THE SAME box ids and THE SAME nonce pair (apollo_loc_metabox_nonce /
 * apollo_loc_save_meta) as the live cells. Had anything constructed it, WordPress
 * would have rendered two boxes per id and two writers would have raced on every
 * `_local_*` key, with the winner decided by hook order.
 *
 * Three loc metabox implementations existed in this plugin; exactly one ran.
 * This was the second. The third was in src/Registry.php, also now a tombstone.
 *
 * Kept as a tombstone so the reason survives; nothing loads it.
 *
 * @package Apollo\Local
 * @see     src/Admin/Metabox/MetaboxManager.php  the live wiring
 * @see     src/Admin/Metabox/MetaboxSaver.php    the single save path
 * @see     _inventory/PLAN-cpt-and-admin-panels.md §3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
