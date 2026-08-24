<?php
/**
 * Partial: Hero — Full-screen video hero with overlay.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="nh-hero">
    <video class="nh-hero-vid" autoplay muted loop playsinline preload="auto" aria-hidden="true">
        <source src="https://assets.apollo.rio.br/vid/v2.webm" type="video/webm"/>
        <source src="https://assets.apollo.rio.br/vid/v2.mp4" type="video/mp4"/>
    </video>
    <div class="nh-hero-overlay" aria-hidden="true"></div>
    <div class="nh-hero-content">
        <h1 class="nh-hero-title ai">Não Apenas<br/>Veja.</h1>
        <p class="nh-hero-sub ai">The definitive guide to Rio's underground culture, soundscapes, and spaces.<br>2026 Edition.</p>
    </div>
    <div class="nh-scroll-hint ai" aria-hidden="true">
        <div class="nh-scroll-line"></div>
        <i class="ri-arrow-down-wide-line"></i>
    </div>
</div>
