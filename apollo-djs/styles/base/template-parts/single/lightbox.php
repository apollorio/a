<?php
/**
 * DJ Single — cell: lightbox
 *
 * OWNS: "Ver todos" track lightbox
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
<div class="dj-outnow-lb" id="outNowLb" role="dialog" aria-modal="true" aria-labelledby="outNowLbTitle" aria-hidden="true">
  <div class="dj-outnow-lb-panel">
    <div class="dj-outnow-lb-head">
      <div>
        <span class="dj-lbl">Trabalho selecionado</span>
        <h3 id="outNowLbTitle">Out now!</h3>
      </div>
      <button type="button" class="dj-outnow-lb-x dj-pill" id="outNowLbClose" aria-label="Fechar"><i class="ri-close-line"></i></button>
    </div>
    <div id="outNowLbList" class="dj-rule-draw"></div>
  </div>
</div>
