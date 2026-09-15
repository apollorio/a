<?php

/**
 * New Home — Hero Section
 * Video background + headline + scroll-hint.
 * Matches working HTML at apollo.rio.br/test/ EXACTLY.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */
if (! defined('ABSPATH')) {
    exit;
}
?>

<?php
/*
 * Hero fallback is painted on the CONTAINER, not only on the <video poster>.
 *
 * The poster only shows while the video is pending; the moment the element
 * errors, is blocked by an autoplay policy, or the source 404s, the poster
 * is dropped and the hero renders as flat background — which is what a
 * visitor was seeing. A background-image on .nh-hero sits UNDER the video
 * for the whole life of the page and can never be dropped, so there is no
 * state in which the hero is empty.
 *
 * Relevant: v2.mp4 is 28 MB. On a slow connection the poster / background layer is
 * what the visitor actually looks at for several seconds.
 */
$nh_hero_fallback = 'https://assets.apollo.rio.br/img/bg/fallback.jpg';
?>
<style<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; ?>>
/* Fallback is the FLOOR, not a blend layer.
   .nh-hero-vid is opacity:.78 by design, so anything painted on the
   container shows THROUGH the video and mixes with it. Painting a photo
   there unconditionally turned the hero into two images averaged
   together. So the image is shown only while there is no playing video,
   and removed the instant one starts. The solid colour underneath stays
   as the final floor. */
#nhHero{background-color:rgb(10,10,10);background-image:url('<?php echo esc_url($nh_hero_fallback); ?>');background-size:cover;background-position:center;background-repeat:no-repeat;}
#nhHero.nh-hero--playing{background-image:none;}
</style>
<div class="nh-hero" id="nhHero">
    <video class="nh-hero-vid" id="nhHeroVid" autoplay muted loop playsinline preload="metadata" poster="<?php echo esc_url($nh_hero_fallback); ?>" aria-hidden="true">
        <?php /* v2.webm is missing on assets CDN (302 → /erro/404/). Start on mp4. */ ?>
        <source src="https://assets.apollo.rio.br/vid/v2.mp4" type="video/mp4">
    </video>
    <div class="nh-hero-overlay" aria-hidden="true"></div>
    <div class="nh-hero-content">
        <h1 class="nh-hero-title nh-hero-typer ai" id="nhHeroTitle">
            <span class="nh-hero-typer-view" data-nh-typer>Não Apenas<br>Veja.</span>
        </h1>
        <p class="nh-hero-sub ai" split-lines>The definitive guide to Rio's underground culture, soundscapes, and spaces.<br>2026 Edition.</p>
    </div>
    <div class="nh-scroll-hint ai" aria-hidden="true">
        <div class="scroll-line">
            <div class="scroll-line-fill"></div>
        </div>
    </div>
</div>

<!-- Force-play fallback: some browsers block autoplay even with muted attr -->
<script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
(function(){
    var v = document.getElementById('nhHeroVid');
    if (!v) return;

    /* Drop the fallback photo the moment real frames are on screen.
       .nh-hero-vid is opacity:.78, so a background image on #nhHero blends
       THROUGH the video instead of sitting behind it. The image is the floor
       for "no video yet / no video ever"; once the video plays it must go. */
    var hero = document.getElementById('nhHero');
    var showVideo = function(){ if (hero) hero.classList.add('nh-hero--playing'); };
    var showPhoto = function(){ if (hero) hero.classList.remove('nh-hero--playing'); };
    v.addEventListener('playing', showVideo);
    v.addEventListener('error', showPhoto);
    v.addEventListener('stalled', showPhoto);
    v.addEventListener('emptied', showPhoto);
    if (!v.paused && v.readyState > 2) showVideo();
    var tryPlay = function(){
        var p = v.play();
        if (p && p.catch) p.catch(function(){});
    };
    if (v.paused) tryPlay();
    v.addEventListener('suspend', tryPlay);
    document.addEventListener('visibilitychange', function(){
        if (!document.hidden && v.paused) tryPlay();
    });
})();
</script>