<?php
/**
 * DJ Single — cell: footer
 *
 * OWNS: footer: name + full-bleed image as last node
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
<footer class="dj-foot">
  <div class="dj-wrap">
    <div class="dj-foot-name" id="footName"><?php echo esc_html( $dj_name_upper ); ?></div>
    <div class="dj-foot-grid">
      <div class="dj-socials">
        <a class="dj-soc dj-pill" id="footSc" href="#" target="_blank" rel="noopener" aria-label="SoundCloud"><i class="ri-soundcloud-line"></i></a>
        <a class="dj-soc dj-pill" id="footBc" href="#" target="_blank" rel="noopener" aria-label="Bandcamp"><i class="ri-bandcamp-line"></i></a>
        <a class="dj-soc dj-pill" id="footSp" href="#" target="_blank" rel="noopener" aria-label="Spotify"><i class="ri-spotify-line"></i></a>
        <a class="dj-soc dj-pill" href="#" aria-label="Instagram"><i class="ri-instagram-line"></i></a>
        <button class="dj-soc dj-pill" data-share="profile" aria-label="Compartilhar"><i class="ri-share-forward-box-line"></i></button>
      </div>
      <span class="dj-foot-copy">© 2026 apollo.rio.br — cartão de artista via Apollo</span>
    </div>
  </div>
  <!-- full-bleed footer image — último nó do <footer>, width:100% flush, é o bottom:0 literal da página -->
  <figure class="dj-foot-img" id="footImgWrap">
    <img id="footImg" src="<?php echo esc_url( $dj_foot_image ); ?>" alt="Multidão da Apollo ao amanhecer">
    <div class="dj-foot-img-ov"><small>O Rio não fecha</small></div>
  </figure>
</footer>
