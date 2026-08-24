<?php
/**
 * DJ Single — cell: data
 *
 * OWNS: window.APOLLO_DJ — the runtime payload. Data only: no markup, no behaviour
 *
 * GENERATED — do not hand-edit. Sliced from the approved mockup
 * (_sandbox/dj-single-page.mockup.html) by _sandbox/build-dj-cells.py, which
 * also namespaces every class to `dj-`. Edit the mockup, re-run the generator,
 * review the diff. Hand-editing here is how the page and the design drift.
 *
 * Dynamic values arrive as $dj_* in scope from apollo_dj_single_context().
 * Anything the runtime fills client-side is left as the empty container it
 * expects to find.
 *
 * @package Apollo\DJs
 * @since   1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script id="apollo-dj-data">
/* The runtime's whole data contract, emitted by PHP. Field-for-field the
   shape the mockup hard-codes; every key traces to a registry meta key or
   taxonomy — see the @field annotations in the mockup and
   apollo_dj_single_context(). wp_json_encode does the escaping. */
window.APOLLO_DJ = <?php echo wp_json_encode( $dj_payload ); ?>;
</script>
