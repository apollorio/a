<?php
/**
 * DJ Single — cell: topbar
 *
 * OWNS: fixed artist-card bar — back · name · share
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
<div class="dj-ev-top" id="evTop">
  <button type="button" class="dj-ev-chip" aria-label="Voltar" onclick="history.back()"><i class="ri-arrow-left-s-line"></i></button>
  <div class="dj-ev-top-id" aria-hidden="true"><b id="evTopName"><?php echo esc_html( $dj_name ); ?></b><span>Cartão de artista</span></div>
  <button type="button" class="dj-ev-chip" data-share="profile" aria-label="Compartilhar cartão"><i class="ri-share-forward-box-line"></i></button>
</div>
