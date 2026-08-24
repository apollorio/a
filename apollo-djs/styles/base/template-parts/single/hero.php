<?php
/**
 * DJ Single — cell: hero
 *
 * OWNS: hero: padded copy + full-bleed figure as a bare header child
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
<header class="dj-hero" id="hero">
  <div class="dj-wrap">
    <div class="dj-hero-eyebrow">
      <span class="dj-lbl"><?php echo esc_html( $dj_eyebrow ); ?></span>
      <?php if ( '' !== $dj_status ) : ?><span class="dj-hero-live"><?php echo esc_html( $dj_status ); ?></span><?php endif; ?>
    </div>

    <h1 class="dj-display dj-hero-name" id="heroName" aria-label="<?php echo esc_attr( $dj_name ); ?>">
      <?php foreach ( $dj_name_lines as $i => $ln ) : ?>
      <span class="dj-hn-line<?php echo $i ? ' dj-is-ac' : ''; ?>" aria-hidden="true"><?php echo esc_html( $ln ); ?></span>
      <?php endforeach; ?>
    </h1>

    <div class="dj-hero-under">
      <p class="dj-hero-bio dj-rv"><strong>Leo Janeiro.</strong> Hard groove com raiz na Zona Norte do Rio — grooves pesados, breaks certeiros e um peak time que não perdoa. De warehouse em warehouse, o nome que os line-ups mais afiados do Rio chamam primeiro.</p>
      <div class="dj-hero-ctas dj-rv">
        <a class="dj-btn dj-btn-ink dj-pill" id="heroBooking" href="#"><i class="ri-mail-send-line"></i> Contato booking</a>
        <a class="dj-btn dj-btn-line dj-pill" href="#sound"><i class="ri-soundcloud-fill"></i> Ouvir DJ set</a>
      </div>
    </div>
  </div>

  <!-- full-bleed: direct child of <header class="dj-hero">, width:100%, zero gap top/left/right -->
  <figure class="dj-hero-figure" id="heroFig">
    <img id="heroImg" src="<?php echo esc_url( $dj_hero_image ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>">
    <div class="dj-hero-cue" aria-hidden="true">Deslize <i class="ri-arrow-down-s-line"></i></div>
    <div class="dj-hero-pills">
      <?php foreach ( $dj_pills as $pill ) : ?>
      <span class="dj-gpill dj-pill"><strong><?php echo esc_html( $pill['v'] ); ?></strong><span><?php echo esc_html( $pill['l'] ); ?></span></span>
      <?php endforeach; ?></div>
  </figure>
</header>
