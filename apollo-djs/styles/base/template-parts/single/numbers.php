<?php
/**
 * DJ Single — cell: numbers
 *
 * OWNS: "Em números" — counter band
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
<section class="dj-sec-tight dj-band" id="numbers">
  <div class="dj-wrap">
    <div class="dj-sh"><div><span class="dj-lbl">Retrospecto</span><h2 class="dj-serif dj-sh-t">Em números</h2></div></div>
    <div class="dj-nums">
      <?php foreach ( $dj_numbers as $n ) : ?>
      <div class="dj-num dj-rv"><div class="dj-num-v"><span data-count="<?php echo esc_attr( (string) $n['v'] ); ?>">0</span></div><p class="dj-num-l"><?php echo esc_html( $n['l'] ); ?></p></div>
      <?php endforeach; ?></div>
  </div>
</section>
