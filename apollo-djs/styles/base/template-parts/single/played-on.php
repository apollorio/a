<?php
/**
 * DJ Single — cell: played-on
 *
 * OWNS: "Tocou em" — event carousel
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
<section class="dj-sec dj-po" id="playedOn">
  <div class="dj-wrap dj-sh">
    <div><span class="dj-lbl"><span class="dj-dot"></span>Histórico ao vivo</span><h2 class="dj-serif dj-sh-t">Tocou em</h2></div>
    <span class="dj-sh-side">Fonte · eventos apollo.rio</span>
  </div>
  <div class="dj-po-stage" id="poStage">
    <div class="dj-po-track" id="poTrack"></div>
    <div class="dj-po-bar" aria-hidden="true"><i id="poBar"></i></div>
  </div>
</section>
