<?php

/**
 * Portal de Eventos — window.APOLLO_EVENTS bridge
 *
 * Injects the real WordPress event rows built by archive-event.php as the
 * window.APOLLO_EVENTS global the portal runtime reads. This is the ONLY
 * place the mockup's simulated.data.js shape is honoured -- the file itself is
 * never ported; only the contract is.
 *
 * PHASE 002: split out of the single 1258-line portal-scripts.php so each
 * concern is independently readable, replaceable and themeable. Behaviour is
 * byte-identical to what was there before — this is a split, not a rewrite.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$portal_events = isset( $portal_events ) && is_array( $portal_events ) ? $portal_events : array();

/*
 * SECURITY FIX (2026-07-30) — stored XSS via inline JSON.
 *
 * This was `echo wp_json_encode( $portal_events )` with no flags. Inside a
 * <script> block that is NOT safe: wp_json_encode() escapes for JSON, not for
 * HTML, so it leaves `<`, `>` and `&` untouched. Every string in
 * $portal_events is author-supplied content read straight from the DB by
 * archive-event.php — post_title, get_the_excerpt(), the loc title/address,
 * DJ post titles, and event_tag/event_type/event_category term names.
 *
 * So any user who can create or edit an event (the /eventos/novo frontend
 * form is open to promoters, i.e. contributor-level and up) could put
 *     </script><script>…</script>
 * in an event title and have it execute in every visitor's browser on the
 * public /eventos and /portal pages — including a logged-in administrator's,
 * which turns a contributor account into full site takeover.
 *
 * JSON_HEX_TAG/AMP/APOS/QUOT emit <, &, ', " instead, so
 * the payload can no longer close the script element or open a tag. The
 * decoded JS values are byte-identical, so the portal runtime is unaffected —
 * this changes the transport encoding only, not the data.
 *
 * The encoding itself lives in apollo_json_for_script() (apollo-core/
 * includes/document-head.php) so every other inline-JSON site in the
 * ecosystem can adopt the same one-call fix instead of repeating the flags.
 */
?>
<script>

	window.APOLLO_EVENTS = <?php
	echo function_exists( 'apollo_json_for_script' )
		? apollo_json_for_script( $portal_events )
		: wp_json_encode( $portal_events, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	?>;
</script>
