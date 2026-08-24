<?php

/**
 * Mural: News Ticker (Airport style)
 *
 * @package Apollo\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variable: $ticker_items (array of strings from page-mural.php)
if ( empty( $ticker_items ) ) {
	return;
}
?>



<!-- ADJUST HTML BELOW FOR PRINTING CORRECT SOUNDS BY USER , FAVORITES BY USER, ADVERTS AND LISTING ALL APOLLO EVENTS -->
<style>
/* --- MARQUEE SECTIONS (Favorites & Vibe) --- */
.marquee-section {
	margin-bottom: 60px;
	overflow: hidden;
}

.marquee-header {
	padding: 0 24px 16px;
	font-family: var(--ff-mono);
	font-size: 12px;
	font-weight: 700;
	text-transform: uppercase;
	color: var(--txt-muted);
}

/* Marquee Container */
.marquee-track-container {
	width: 100%;
	overflow: hidden;
	display: flex;
	mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent);
}

.marquee-content {
	display: flex;
	gap: 20px;
	padding: 10px 0;
	/* Infinite Scroll Animation */
	animation: scroll-left 40s linear infinite;
}

.marquee-content.reverse {
	animation-direction: reverse;
}

.marquee-card {
	flex: 0 0 240px;
	/* Fixed width cards */
	background: #fff;
	border: 1px solid var(--border);
	padding: 12px;
	transition: 0.2s;
	cursor: pointer;
}

.marquee-card:hover {
	border-color: #000;
}

.mq-img {
	height: 120px;
	width: 100%;
	object-fit: cover;
	filter: grayscale(100%);
	transition: 0.3s;
	margin-bottom: 12px;
}

.marquee-card:hover .mq-img {
	filter: grayscale(0%);
}

.mq-title {
	font-size: 14px;
	font-weight: 700;
	margin-bottom: 4px;
	line-height: 1.2;
}

.mq-date {
	font-family: var(--ff-mono);
	font-size: 9px;
	color: var(--primary);
	text-transform: uppercase;
}

@keyframes scroll-left {
	0% {
		transform: translateX(0);
	}

	100% {
		transform: translateX(calc(-240px * 5 - 100px));
	}

	/* Adjust calculation based on content */
}


/* --- NEW APOLLO EVENT CARD (.a-eve-card) --- */
.events-section {
	margin-bottom: 80px;
}

.events-title {
	font-family: var(--ff-fun);
	font-size: 48px;
	color: #000;
	padding: 0 24px 24px;
	border-bottom: 1px solid var(--border);
	margin-bottom: 24px;
}

.events-grid-layout {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 32px;
	padding: 0 24px;
}

.a-eve-card {
	display: block;
	position: relative;
	width: 100%;
	text-decoration: none;
	cursor: pointer;
	transition: transform 0.4s ease;
	background: transparent;
	margin-bottom: 32px;
}

.a-eve-card:hover {
	transform: translateY(-5px);
}

/* Date Box */
.a-eve-date {
	position: absolute;
	top: 5px;
	left: 7px;
	width: 60px;
	height: 54px;
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	text-align: center;
	line-height: 1;
	z-index: 5;
	pointer-events: none;
}

.a-eve-date-day {
	font-size: 1.6rem;
	font-weight: 700;
	color: #111;
	display: block;
}

.a-eve-date-month {
	font-size: 0.9rem;
	font-weight: 600;
	text-transform: uppercase;
	color: var(--txt-muted);
}

/* Media */
.a-eve-media {
	height: 400px;
	position: relative;
	overflow: hidden;
	border-radius: 12px;
	border: 1px solid var(--border);
	box-shadow: 0 10px 30px rgba(var(--rgb-d), 0.05);
	transition: box-shadow 0.4s ease;
	background: #e0e0e0;

	/* Cutout Mask */
	--r: 12px;
	--s: 12px;
	--x: 48px;
	--y: 42px;
	--_m: /calc(2*var(--r)) calc(2*var(--r)) radial-gradient(#000 70%, #0000 72%);
	--_g: conic-gradient(at var(--r) var(--r), #000 75%, #0000 0);
	--_d: (var(--s) + var(--r));
	mask: calc(var(--_d) + var(--x)) 0 var(--_m), 0 calc(var(--_d) + var(--y)) var(--_m), radial-gradient(var(--s) at 0 0, #0000 99%, #000 calc(100% + 1px)) calc(var(--r) + var(--x)) calc(var(--r) + var(--y)), var(--_g) calc(var(--_d) + var(--x)) 0, var(--_g) 0 calc(var(--_d) + var(--y));
	mask-repeat: no-repeat;
	-webkit-mask: calc(var(--_d) + var(--x)) 0 var(--_m), 0 calc(var(--_d) + var(--y)) var(--_m), radial-gradient(var(--s) at 0 0, #0000 99%, #000 calc(100% + 1px)) calc(var(--r) + var(--x)) calc(var(--r) + var(--y)), var(--_g) calc(var(--_d) + var(--x)) 0, var(--_g) 0 calc(var(--_d) + var(--y));
	-webkit-mask-repeat: no-repeat;
}

.a-eve-card:hover .a-eve-media {
	box-shadow: 0 15px 40px rgba(var(--rgb-d), 0.1);
}

.a-eve-media img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.4s ease;
	display: block;
	filter: grayscale(100%);
}

.a-eve-card:hover .a-eve-media img {
	transform: scale(1.05);
	filter: grayscale(0%);
}

/* Tags */
.a-eve-tags {
	position: absolute;
	bottom: 10px;
	right: 10px;
	display: flex;
	gap: 8px;
	z-index: 3;
	pointer-events: none;
}

.a-eve-tag {
	padding: 4px 10px;
	border-radius: 4px;
	border: 1px solid rgba(var(--rgb-t), 0.2);
	background: linear-gradient(30deg, rgba(var(--rgb-t), 0.1) -49%, rgba(var(--rgb-t), 0.35) 160%);
	backdrop-filter: blur(4px);
	font-size: 0.65rem;
	color: #fff;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

/* Content */
.a-eve-content {
	padding: 1.25em 0.5rem;
	width: 100%;
}

.a-eve-title {
	font-size: 1.2rem;
	font-weight: 700;
	color: #111;
	line-height: 1.3;
	margin-bottom: 0.5rem;
	font-family: var(--ff-main);
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
}

.a-eve-meta {
	color: var(--txt-muted);
	font-size: 0.85rem;
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 0.4rem;
}

.a-eve-meta i {
	font-size: 0.95rem;
	flex-shrink: 0;
	opacity: 0.8;
}

.a-eve-meta span {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

/* Specific Opacity Request */
.a-eve-meta.sounds {
	opacity: 0.5;
}

/* --- CLASSIFIEDS --- */
.classified-section {
	border-top: 1px solid var(--border);
	padding-top: 40px;
}

.class-group-title {
	padding: 0 24px;
	font-family: var(--ff-mono);
	font-size: 12px;
	font-weight: 700;
	text-transform: uppercase;
	color: var(--txt-muted);
	margin-bottom: 16px;
}

.class-row {
	display: grid;
	grid-template-columns: 40px 2fr 1fr auto;
	align-items: center;
	padding: 16px 24px;
	border-bottom: 1px solid var(--border);
	transition: 0.2s;
	cursor: pointer;
}

.class-row:hover {
	background: #fafafa;
}

.class-icon {
	font-size: 18px;
	color: #999;
}

.class-main {
	display: flex;
	flex-direction: column;
}

.class-h {
	font-size: 14px;
	font-weight: 600;
}

.class-sub {
	font-size: 12px;
	color: #666;
}

.class-tag {
	font-family: var(--ff-mono);
	font-size: 10px;
	text-transform: uppercase;
	color: #999;
}

.class-price {
	font-family: var(--ff-mono);
	font-size: 12px;
	font-weight: 700;
	color: var(--primary);
}

/* Mobile */
@media (max-width: 768px) {
	.news-grid {
		grid-template-columns: 1fr;
	}

	.landing-hero {
		flex-direction: column;
	}

	.weather-widget-wrapper {
		position: relative;
		width: 100% !important;
		max-width: 100%;
		top: 0;
		right: 0;
		margin-bottom: 20px;
	}

	.a-fore-card {
		width: 100%;
	}

	.events-grid-layout {
		grid-template-columns: 1fr;
	}

	.class-row {
		grid-template-columns: 1fr auto;
		gap: 12px;
	}

	.class-icon,
	.class-tag {
		display: none;
	}
}

/* ABSOLUTE WEATHER CARD WRAPPER */
.weather-widget-wrapper {
	/* Positioned relative to hero on desktop */
	position: absolute;
	right: 0;
	top: 0;
	width: clamp(290px, 25vw, 310px);
	min-height: 150px;
	z-index: 10;
	overflow: hidden;
	border-radius: 33px;
	corner-shape: squircle;
}

/* --- WEATHER CARD STYLES (Provided & Adapted) --- */
.a-fore-card {
	width: 100%;
	height: 150px;
	position: relative;
	overflow: hidden;
	border-radius: 33px;
	corner-shape: squircle;
	background: var(--black-2);
	box-shadow: 0 20px 60px -10px rgba(var(--rgb-d), 0.073);
	color: white;
	display: flex;
	flex-direction: column;
	isolation: isolate;
}

.a-fore-video-layer {
	position: absolute;
	inset: 0;
	z-index: 0;
	overflow: hidden;
	pointer-events: none;
}

.a-fore-video-layer iframe {
	position: absolute;
	width: 140%;
	height: 140%;
	top: 50%;
	left: 60%;
	transform: translate(-50%, -50%);
	border: 0;
	opacity: 0.9;
	filter: saturate(1.1);
}

.a-fore-overlay {
	position: absolute;
	inset: 0;
	z-index: 1;
	background: linear-gradient(to bottom, rgba(var(--rgb-d), 0.3) 0%, rgba(var(--rgb-d), 0) 40%, rgba(var(--rgb-d), 0.6) 100%);
}

.a-fore-content {
	position: relative;
	z-index: 2;
	height: 100%;
	padding: 24px;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
}

.a-fore-header {
	display: flex;
	align-items: center;
	gap: 12px;
}

.a-fore-icon-box {
	width: 44px;
	height: 44px;
	display: grid;
	place-items: center;
	background: rgba(var(--rgb-t), 0.15);
	backdrop-filter: blur(12px);
	border-radius: 50%;
	border: 1px solid rgba(var(--rgb-t), 0.2);
	color: #FFD700;
	font-size: 22px;
}

.a-fore-meta {
	display: flex;
	flex-direction: column;
}

.a-fore-label {
	font-family: var(--ff-mono);
	font-size: 10px;
	text-transform: uppercase;
	letter-spacing: 0.05em;
	font-weight: 700;
	opacity: 0.8;
}

.a-fore-location {
	font-family: var(--ff-main);
	font-size: 16px;
	font-weight: 600;
}

.a-fore-footer {
	display: flex;
	align-items: flex-end;
	justify-content: space-between;
}

.a-fore-temp {
	font-family: var(--ff-main);
	font-size: 64px;
	line-height: 0.9;
	font-weight: 500;
	letter-spacing: -0.04em;
}

.a-fore-condition {
	font-family: var(--ff-mono);
	font-size: 11px;
	background: rgba(var(--rgb-d), 0.4);
	padding: 6px 12px;
	border-radius: 100px;
	backdrop-filter: blur(4px);
	border: 1px solid rgba(var(--rgb-t), 0.1);
	color: rgba(var(--rgb-t), 0.9);
	text-transform: uppercase;
}
</style>

<?php
/*
 * ══════════════════════════════════════════════════════════════
 * NEWS TICKER — Airport-style marquee of latest post headlines.
 * Each section below (sounds, favorites, same-vibe, events,
 * classifieds) is now rendered by its own template part, called
 * from page-mural.php.  This file ONLY handles the ticker.
 * ══════════════════════════════════════════════════════════════
 */
?>

<!-- NEWS TICKER (Airport Board Style) -->
<section class="ticker-section">
	<div class="ticker-track">
		<div class="ticker-belt">
			<?php
			// $ticker_items comes from page-mural.php — array of headlines
			$all = array_merge( $ticker_items, $ticker_items ); // duplicate for infinite scroll
			foreach ( $all as $item ) :
				?>
				<span class="ticker-item"><?php echo esc_html( $item ); ?></span>
				<span class="ticker-sep">◆</span>
			<?php endforeach; ?>
		</div>
	</div>
</section>
