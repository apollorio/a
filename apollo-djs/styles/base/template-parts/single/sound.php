<?php
/**
 * DJ Single — cell: sound
 *
 * OWNS: "Out now!" — tracks + SoundCloud shelf
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
<section class="dj-sec-tight" id="sound">
  <div class="dj-wrap">
    <div class="dj-sh">
      <div><span class="dj-lbl">Trabalho selecionado</span><h2 class="dj-serif dj-sh-t">Out now!</h2></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="dj-btn dj-btn-line dj-pill" id="scFollow" href="#" target="_blank" rel="noopener"><i class="ri-soundcloud-line"></i> SoundCloud</a>
        <a class="dj-btn dj-btn-line dj-pill" id="bcFollow" href="#" target="_blank" rel="noopener"><i class="ri-bandcamp-line"></i> Bandcamp</a>
        <a class="dj-btn dj-btn-line dj-pill" id="spFollow" href="#" target="_blank" rel="noopener"><i class="ri-spotify-line"></i> Spotify</a>
      </div>
    </div>
    <div id="trkList" class="dj-rule-draw"></div>
    <div class="dj-sc-shelf" id="scShelf"><iframe id="scPlayer" title="Player SoundCloud" allow="autoplay" loading="lazy"></iframe></div>
  </div>
</section>
