<?php
/**
 * DJ Single — cell: statement
 *
 * OWNS: word-fill scrub statement
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
<section class="dj-stmt dj-wrap" id="stmt">
  <p class="dj-stmt-txt" id="stmtTxt"><?php if ( '' !== $dj_statement ) : ?><?php echo wp_kses_post( $dj_statement ); ?><?php else : ?>Ele não domestica o groove. Ele <em class="dj-gold">solta</em>.<?php endif; ?></p>
</section>
