<?php
/**
 * DJ Single — cell: about
 *
 * OWNS: "O artista" — figure + bio + tags
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
<section class="dj-sec-tight">
  <div class="dj-wrap dj-about">
    <!-- vídeo (loop mudo) tem prioridade; JS decide o miolo conforme APOLLO_DJ.videoUrl -->
    <figure class="dj-about-img dj-rv" id="aboutFig"></figure>
    <div>
      <span class="dj-lbl" style="display:block;margin-bottom:18px">Sobre</span>
      <h2 class="dj-serif" style="font-size:clamp(44px,7vw,92px);margin-bottom:26px">O artista</h2>
      <p class="dj-about-p dj-rv"><?php if ( '' !== $dj_bio ) : ?><?php echo wp_kses_post( $dj_bio ); ?><?php else : ?><strong><?php echo esc_html( $dj_name ); ?>.</strong><?php endif; ?></p>
      <div class="dj-tags dj-rv"><?php foreach ( $dj_sounds as $tag ) : ?><span class="dj-tag"><?php echo esc_html( $tag ); ?></span><?php endforeach; ?></div>
    </div>
  </div>
</section>
