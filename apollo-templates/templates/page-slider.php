<?php

/**
 * Template Name: Apollo Full-Screen Slider
 * Template Post Type: page
 *
 * Blank Canvas — Apollo CDN loads GSAP bundle (ScrollTrigger, SplitText,
 * ScrollSmoother, CustomEase, Observer). No wp_head/wp_footer.
 *
 * Slide data: populate via ACF repeater field "apollo_slides"
 * (image, title, description) or falls back to static demo set.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Slide data — ACF repeater or static fallback ──────────────────────────
$slides = array();

if ( function_exists( 'get_field' ) ) {
	$acf_slides = get_field( 'apollo_slides' );
	if ( is_array( $acf_slides ) && count( $acf_slides ) ) {
		foreach ( $acf_slides as $s ) {
			$slides[] = array(
				'image' => esc_url( $s['image']['url'] ?? '' ),
				'alt'   => esc_attr( $s['image']['alt'] ?? '' ),
				'title' => esc_html( $s['title'] ?? '' ),
				'descr' => esc_html( $s['description'] ?? '' ),
			);
		}
	}
}

// Static demo fallback (two slides).
if ( empty( $slides ) ) {
	$slides = array(
		array(
			'image' => 'https://zajno-storage0.s3.us-west-1.amazonaws.com/dev/codepen/tle/luminous.jpg',
			'alt'   => 'Slide 1',
			'title' => 'Luminous Vista',
			'descr' => 'Apollo Collection',
		),
		array(
			'image' => 'https://zajno-storage0.s3.us-west-1.amazonaws.com/dev/codepen/tle/celestial.jpg',
			'alt'   => 'Slide 2',
			'title' => 'Celestial Symphony',
			'descr' => 'Apollo Collection',
		),
	);
}

$total         = count( $slides );
$page_title    = esc_html( get_the_title() ?: get_bloginfo( 'name' ) );
$page_subtitle = esc_html( get_bloginfo( 'description' ) );

$ver = defined( 'APOLLO_TEMPLATES_VERSION' ) ? APOLLO_TEMPLATES_VERSION : '6.0.0';
$css = defined( 'APOLLO_TEMPLATES_URL' ) ? APOLLO_TEMPLATES_URL . 'assets/css/slider.css?v=' . $ver : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
	<meta name="theme-color" content="#ffffff">
	<title><?php echo $page_title; ?></title>

	<!-- Apollo CDN — GSAP bundle + ScrollSmoother + SplitText + Observer -->
	<script src="<?php echo esc_url( defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL . 'core.min.js' : 'https://cdn.apollo.rio.br/v1.0.0/core.min.js' ); ?>" fetchpriority="high"></script>

	<?php if ( $css ) : ?>
		<link rel="stylesheet" href="<?php echo esc_url( $css ); ?>">
	<?php endif; ?>

	<style id="apollo-slider-styles">
		/* ── Diamond Design tokens ──────────────────────────────────── */
		:root {
			--font-main:  -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			--dark:       #131313;
			--white:      #ffffff;
			--primary:    #FF9820;
			--radius:     20px;
			/* Viewport-relative scale: 1rem = vw/14.4 (keeps original proportions) */
		}

		html { font-size: 6.9444444444vw; }

		/* ── Body — clip-path wipe entrance ──────────────────────────── */
		body {
			font-size: 16px;
			font-family: var(--font-main);
			color: var(--dark);
			background: var(--white);
			overflow: hidden;
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			clip-path: polygon(
				0% 0%, 6.25% 0%, 6.25% 100%, 6.25% 0%,
				12.5% 0%, 12.5% 100%, 12.5% 0%,
				18.75% 0%, 18.75% 100%, 18.75% 0%,
				25% 0%, 25% 100%, 25% 0%,
				31.25% 0%, 31.25% 100%, 31.25% 0%,
				37.5% 0%, 37.5% 100%, 37.5% 0%,
				43.75% 0%, 43.75% 100%, 43.75% 0%,
				50% 0%, 50% 100%, 50% 0%,
				56.25% 0%, 56.25% 100%, 56.25% 0%,
				62.5% 0%, 62.5% 100%, 62.5% 0%,
				68.75% 0%, 68.75% 100%, 68.75% 0%,
				75% 0%, 75% 100%, 75% 0%,
				81.25% 0%, 81.25% 100%, 81.25% 0%,
				87.5% 0%, 87.5% 100%, 87.5% 0%,
				93.75% 0%, 93.75% 100%, 93.75% 0%,
				100% 0%, 100% 100%, 0% 100%
			);
			transition: 0s;
		}

		body.hide {
			clip-path: polygon(
				6.25% 0%, 6.25% 0%, 6.25% 100%, 6.25% 100%,
				12.5% 100%, 12.5% 0%, 12.5% 0%, 12.5% 100%,
				18.75% 100%, 18.75% 0%, 18.75% 0%, 18.75% 100%,
				25% 100%, 25% 0%, 25% 0%, 25% 100%,
				31.25% 100%, 31.25% 0%, 31.25% 0%, 31.25% 100%,
				37.5% 100%, 37.5% 0%, 37.5% 0%, 37.5% 100%,
				43.75% 100%, 43.75% 0%, 43.75% 0%, 43.75% 100%,
				50% 100%, 50% 0%, 50% 0%, 50% 100%,
				56.25% 100%, 56.25% 0%, 56.25% 0%, 56.25% 100%,
				62.5% 100%, 62.5% 0%, 62.5% 0%, 62.5% 100%,
				68.75% 100%, 68.75% 0%, 68.75% 0%, 68.75% 100%,
				75% 100%, 75% 0%, 75% 0%, 75% 100%,
				81.25% 100%, 81.25% 0%, 81.25% 0%, 81.25% 100%,
				87.5% 100%, 87.5% 0%, 87.5% 0%, 87.5% 100%,
				93.75% 100%, 93.75% 0%, 93.75% 0%, 93.75% 100%,
				100% 100%, 100% 0%, 100% 0%, 100% 100%, 6.25% 100%
			);
			transition: clip-path 1.5s;
		}

		*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

		img, svg, video { display: block; max-width: 100%; width: 100%; }
		img {
			user-drag: none; user-select: none;
			-webkit-user-drag: none; -webkit-user-select: none;
		}
		button { user-select: none; }

		#smooth-content {
			border-top: 1px solid transparent;
			border-bottom: 1px solid transparent;
		}

		/* ── Split text animation classes ────────────────────────────── */
		.split {
			font-kerning: none;
			text-rendering: optimizeSpeed;
			-webkit-transform: translateZ(0);
			transform: translateZ(0);
			line-height: 1.2;
		}

		.fade-overflow { overflow: hidden; }
		.fade-el { display: block; transform: translateY(110%); }

		/* ── Preloader ────────────────────────────────────────────────── */
		.preloader {
			position: fixed;
			inset: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			background: var(--white);
			z-index: 999;
		}

		.preloader-main { width: 1.58rem; height: auto; overflow: hidden; }
		.preloader-wrapp { width: 100%; height: auto; }
		.preloader-img {
			position: absolute;
			top: 50%; left: 50%;
			width: 100%;
			max-width: 1.58rem;
			transform: translate(-50%, -50%);
			animation-duration: 0.8s;
			animation-iteration-count: infinite;
		}
		.preloader-img svg { width: 100%; height: auto; }

		#preloader-1 { position: relative; top: 0; left: 0; opacity: 1; transform: none; animation-name: preloaderAnim1; }
		#preloader-2 { opacity: 0; animation-name: preloaderAnim2; }
		#preloader-3 { opacity: 0; animation-name: preloaderAnim3; }
		#preloader-4 { opacity: 0; animation-name: preloaderAnim4; }

		@keyframes preloaderAnim1 {
			1%,25% { opacity: 1; }
			25.01%,0%,100% { opacity: 0; }
		}
		@keyframes preloaderAnim2 {
			0%,25%,51%,100% { opacity: 0; }
			26%,50% { opacity: 1; }
		}
		@keyframes preloaderAnim3 {
			0%,50%,76%,100% { opacity: 0; }
			51%,75% { opacity: 1; }
		}
		@keyframes preloaderAnim4 {
			1%,75%,100% { opacity: 0; }
			76%,99% { opacity: 1; }
		}

		/* ── Header ────────────────────────────────────────────────────── */
		.header {
			position: fixed;
			top: 0; left: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			z-index: 100;
		}

		.header-wrapp {
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			align-items: baseline;
			gap: 0.2rem;
			width: 100%;
			padding: 0.13rem 0.2rem;
			margin: 0.06rem;
			transform: translateY(-1rem);
		}

		.header-logo {
			display: flex;
			align-items: center;
			gap: 0.04rem;
			font-weight: 700;
			letter-spacing: -0.02em;
		}

		.header-logo .logo-icon {
			width: 0.13rem;
			height: 0.13rem;
			flex-shrink: 0;
		}

		.header-menu {
			display: flex;
			flex-direction: column;
			gap: 0.04rem;
			justify-self: flex-end;
		}

		.header-link { color: var(--dark); text-decoration: none; }
		.header-contact { justify-self: flex-end; }
		.header-cart { justify-self: flex-end; }

		/* ── Banner ────────────────────────────────────────────────────── */
		.banner {
			position: relative;
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			grid-gap: 0.2rem;
			padding: 3.19rem 0 0.69rem;
			margin: 0 0.2rem;
		}

		.banner-mask {
			position: absolute;
			top: 0; left: 50%;
			width: 100vw;
			height: 100%;
			background: var(--dark);
			transform: translateX(-50%);
			opacity: 0;
			pointer-events: none;
			user-select: none;
			z-index: 1;
		}

		.banner-text { max-width: 2rem; text-transform: uppercase; }
		.banner-title {
			grid-column-start: span 3;
			font-size: 0.93rem;
			line-height: 1;
			margin-top: -0.16rem;
		}
		.banner-descr { text-transform: uppercase; }

		/* ── Collection + Slider ──────────────────────────────────────── */
		.collection {
			position: relative;
			width: 100%;
			height: 100vh;
			height: 100dvh;
			padding: 0 0.22rem;
			overflow: hidden;
			z-index: 1;
		}

		.collection-mask {
			position: absolute;
			inset: 0;
			background: var(--dark);
			opacity: 0;
		}

		.slider {
			height: 100vh;
			height: 100dvh;
			border-radius: 0.22rem;
			transform: translateY(50%);
			overflow: hidden;
		}

		.slider-wrapp {
			position: relative;
			width: 100%;
			height: 100%;
		}

		.slider-gradient {
			position: absolute;
			bottom: 0; left: 0;
			width: 100%;
			height: 38vh;
			background: linear-gradient(180deg, rgba(53,51,47,0) 9.24%, #3D3830 100%);
		}

		/* Inactive slide — clip-path "venetian blind" wipe */
		.slider-img {
			position: absolute;
			inset: 0;
			opacity: 0;
			clip-path: polygon(
				0% 0%, 6.25% 0%, 6.25% 100%, 6.25% 100%,
				6.25% 0%, 12.5% 0%, 12.5% 100%, 12.5% 100%,
				12.5% 0%, 18.75% 0%, 18.75% 100%, 18.75% 100%,
				18.75% 0%, 25% 0%, 25% 100%, 25% 100%,
				25% 0%, 31.25% 0%, 31.25% 100%, 31.25% 100%,
				31.25% 0%, 37.5% 0%, 37.5% 100%, 37.5% 100%,
				37.5% 0%, 43.75% 0%, 43.75% 100%, 43.75% 100%,
				43.75% 0%, 50% 0%, 50% 100%, 50% 100%,
				50% 0%, 56.25% 0%, 56.25% 100%, 56.25% 100%,
				56.25% 0%, 62.5% 0%, 62.5% 100%, 62.5% 100%,
				62.5% 0%, 68.75% 0%, 68.75% 100%, 68.75% 100%,
				68.75% 0%, 75% 0%, 75% 100%, 75% 100%,
				75% 0%, 81.25% 0%, 81.25% 100%, 81.25% 100%,
				81.25% 0%, 87.5% 0%, 87.5% 100%, 87.5% 100%,
				87.5% 0%, 93.75% 0%, 93.75% 100%, 93.75% 100%,
				93.75% 0%, 100% 0%, 100% 100%, 0% 100%
			);
			transform: scale(1.1) translateX(-4%);
			transition: 1.5s;
		}

		.slider-img.active {
			opacity: 1;
			transform: scale(1.1);
			transition:
				clip-path 1.5s cubic-bezier(0.55,0.00,0.49,1.00),
				transform  2s  cubic-bezier(0.17,0.17,0.49,1.00);
		}

		.slider-img.hide {
			clip-path: polygon(
				6.25% 0%, 6.25% 0%, 6.25% 100%, 6.25% 100%,
				12.5% 100%, 12.5% 0%, 12.5% 0%, 12.5% 100%,
				18.75% 100%, 18.75% 0%, 18.75% 0%, 18.75% 100%,
				25% 100%, 25% 0%, 25% 0%, 25% 100%,
				31.25% 100%, 31.25% 0%, 31.25% 0%, 31.25% 100%,
				37.5% 100%, 37.5% 0%, 37.5% 0%, 37.5% 100%,
				43.75% 100%, 43.75% 0%, 43.75% 0%, 43.75% 100%,
				50% 100%, 50% 0%, 50% 0%, 50% 100%,
				56.25% 100%, 56.25% 0%, 56.25% 0%, 56.25% 100%,
				62.5% 100%, 62.5% 0%, 62.5% 0%, 62.5% 100%,
				68.75% 100%, 68.75% 0%, 68.75% 0%, 68.75% 100%,
				75% 100%, 75% 0%, 75% 0%, 75% 100%,
				81.25% 100%, 81.25% 0%, 81.25% 0%, 81.25% 100%,
				87.5% 100%, 87.5% 0%, 87.5% 0%, 87.5% 100%,
				93.75% 100%, 93.75% 0%, 93.75% 0%, 93.75% 100%,
				100% 100%, 100% 0%, 100% 0%, 100% 100%, 6.25% 100%
			);
			transform: scale(1.1) translateX(4%);
			transition:
				clip-path 1.5s cubic-bezier(0.55,0.00,0.49,1.00),
				transform  1.5s cubic-bezier(0.55,0.00,0.83,0.83);
		}

		.slider-img img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			object-position: center;
		}

		/* Slide labels */
		.slider-descr {
			position: absolute;
			left: 0.4rem;
			bottom: 0.28rem;
			max-width: 1.9rem;
			text-transform: uppercase;
			color: var(--white);
		}

		.slider-title {
			position: absolute;
			left: 3.09rem;
			bottom: 0.2rem;
			height: 1.3em;
			display: flex;
			flex-direction: column;
			justify-content: flex-end;
			font-size: 0.94rem;
			color: var(--white);
			overflow: hidden;
		}

		.slider-title__item { height: 1.3em; min-height: 1.3em; }

		.slider-numeric {
			position: absolute;
			right: 0.4rem;
			bottom: 0.28rem;
			display: flex;
			gap: 0.5em;
			font-size: 0.18rem;
			text-transform: uppercase;
			color: var(--white);
		}

		.slider-numeric__active { height: 1.3em; overflow: hidden; }
		.slider-numeric__item   { height: 1.3em; }

		/* ── Next section placeholder ─────────────────────────────────── */
		.next-section {
			height: 100vh;
			height: 100dvh;
			background: var(--dark);
			color: var(--white);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 2rem;
		}

		/* ── Mobile-first: collapse grid columns on small viewports ───── */
		@media (max-width: 640px) {
			.header-wrapp    { grid-template-columns: 1fr 1fr; gap: 0.4rem; }
			.header-search,
			.header-menu     { display: none; }
			.banner          { grid-template-columns: 1fr; padding-top: 4rem; }
			.banner-title    { grid-column-start: auto; }
			.slider-title    { left: 0.4rem; bottom: 0.56rem; }
			.slider-numeric  { right: 0.4rem; font-size: 0.22rem; }
		}
	</style>
</head>

<body>
	<!-- ── Preloader ────────────────────────────────────────────────── -->
	<div id="preloader" class="preloader">
		<div class="preloader-main">
			<div class="preloader-wrapp">
				<div class="preloader-img" id="preloader-1">
					<svg width="159" height="36" viewBox="0 0 159 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<rect width="36" height="36" fill="#131313"/>
						<path d="M18 0L25.1276 10.8724L36 18L25.1276 25.1276L18 36L10.8724 25.1276L0 18L10.8724 10.8724L18 0Z" fill="#FFFAF6"/>
						<g clip-path="url(#a)"><rect x="41" width="36" height="36" fill="#131313"/>
						<path d="M71.7279 5.27L77 18L71.7279 30.728L59 36L46.2721 30.728L41 18L46.2721 5.27L59 0L71.7279 5.27Z" fill="#FFFAF6"/></g>
						<g clip-path="url(#b)"><rect x="82" width="36" height="36" fill="#FFFAF6"/>
						<rect x="82" width="6" height="6" fill="black"/>
						<rect x="112" width="6" height="6" fill="black"/>
						<rect x="112" y="30" width="6" height="6" fill="black"/>
						<rect x="82" y="30" width="6" height="6" fill="black"/></g>
						<rect x="123" width="36" height="36" fill="#131313"/>
						<circle cx="141" cy="18" r="17" fill="#FFFAF6"/>
						<defs>
							<clipPath id="a"><rect width="36" height="36" fill="white" transform="translate(41)"/></clipPath>
							<clipPath id="b"><rect width="36" height="36" fill="white" transform="translate(82)"/></clipPath>
						</defs>
					</svg>
				</div>
				<div class="preloader-img" id="preloader-2">
					<svg width="159" height="36" viewBox="0 0 159 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<rect x="41" width="36" height="36" fill="#131313"/>
						<path d="M59 0L66.1276 10.8724L77 18L66.1276 25.1276L59 36L51.8724 25.1276L41 18L51.8724 10.8724L59 0Z" fill="#FFFAF6"/>
						<g clip-path="url(#c)"><rect width="36" height="36" fill="#131313"/>
						<path d="M30.7279 5.27L36 18L30.7279 30.728L18 36L5.27208 30.728L0 18L5.27208 5.27L18 0L30.7279 5.27Z" fill="#FFFAF6"/></g>
						<g clip-path="url(#d)"><rect x="123" width="36" height="36" fill="#FFFAF6"/>
						<rect x="123" width="6" height="6" fill="black"/>
						<rect x="153" width="6" height="6" fill="black"/>
						<rect x="153" y="30" width="6" height="6" fill="black"/>
						<rect x="123" y="30" width="6" height="6" fill="black"/></g>
						<rect x="82" width="36" height="36" fill="#131313"/>
						<circle cx="100" cy="18" r="17" fill="#FFFAF6"/>
						<defs>
							<clipPath id="c"><rect width="36" height="36" fill="white"/></clipPath>
							<clipPath id="d"><rect width="36" height="36" fill="white" transform="translate(123)"/></clipPath>
						</defs>
					</svg>
				</div>
				<div class="preloader-img" id="preloader-3">
					<svg width="159" height="36" viewBox="0 0 159 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<rect x="123" width="36" height="36" fill="#131313"/>
						<path d="M141 0L148.128 10.8724L159 18L148.128 25.1276L141 36L133.872 25.1276L123 18L133.872 10.8724L141 0Z" fill="#FFFAF6"/>
						<g clip-path="url(#e)"><rect x="82" width="36" height="36" fill="#131313"/>
						<path d="M112.728 5.27L118 18L112.728 30.728L100 36L87.2721 30.728L82 18L87.2721 5.27L100 0L112.728 5.27Z" fill="#FFFAF6"/></g>
						<g clip-path="url(#f)"><rect width="36" height="36" fill="#FFFAF6"/>
						<rect width="6" height="6" fill="black"/>
						<rect x="30" width="6" height="6" fill="black"/>
						<rect x="30" y="30" width="6" height="6" fill="black"/>
						<rect y="30" width="6" height="6" fill="black"/></g>
						<rect x="41" width="36" height="36" fill="#131313"/>
						<circle cx="59" cy="18" r="17" fill="#FFFAF6"/>
						<defs>
							<clipPath id="e"><rect width="36" height="36" fill="white" transform="translate(82)"/></clipPath>
							<clipPath id="f"><rect width="36" height="36" fill="white"/></clipPath>
						</defs>
					</svg>
				</div>
				<div class="preloader-img" id="preloader-4">
					<svg width="159" height="36" viewBox="0 0 159 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<rect x="82" width="36" height="36" fill="#131313"/>
						<path d="M100 0L107.128 10.8724L118 18L107.128 25.1276L100 36L92.8724 25.1276L82 18L92.8724 10.8724L100 0Z" fill="#FFFAF6"/>
						<g clip-path="url(#g)"><rect x="123" width="36" height="36" fill="#131313"/>
						<path d="M153.728 5.27L159 18L153.728 30.728L141 36L128.272 30.728L123 18L128.272 5.27L141 0L153.728 5.27Z" fill="#FFFAF6"/></g>
						<g clip-path="url(#h)"><rect x="41" width="36" height="36" fill="#FFFAF6"/>
						<rect x="41" width="6" height="6" fill="black"/>
						<rect x="71" width="6" height="6" fill="black"/>
						<rect x="71" y="30" width="6" height="6" fill="black"/>
						<rect x="41" y="30" width="6" height="6" fill="black"/></g>
						<rect width="36" height="36" fill="#131313"/>
						<circle cx="18" cy="18" r="17" fill="#FFFAF6"/>
						<defs>
							<clipPath id="g"><rect width="36" height="36" fill="white" transform="translate(123)"/></clipPath>
							<clipPath id="h"><rect width="36" height="36" fill="white" transform="translate(41)"/></clipPath>
						</defs>
					</svg>
				</div>
			</div>
		</div>
	</div>

	<!-- ── Header ───────────────────────────────────────────────────── -->
	<header class="header" role="banner">
		<div class="header-wrapp">
			<div class="header-logo">
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
				<svg class="logo-icon" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M6.5 0H0V6.5V13H6.5H13V6.5V0H6.5ZM6.5 0L3.92613 3.92613L0 6.5L3.92613 9.07387L6.5 13L9.07387 9.07387L13 6.5L9.07387 3.92613L6.5 0Z" fill="#131313"/>
				</svg>
			</div>
			<div class="header-search" aria-hidden="true">Search</div>
			<nav class="header-menu" aria-label="<?php esc_attr_e( 'Main navigation', 'apollo-templates' ); ?>">
				<a href="#" class="header-menu__item header-link">Events</a>
				<a href="#" class="header-menu__item header-link">DJs</a>
				<a href="#" class="header-menu__item header-link">About</a>
			</nav>
			<a href="<?php echo esc_url( home_url( '/contato' ) ); ?>" class="header-contact header-link">Contact</a>
			<a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="header-cart header-link">
				<?php echo is_user_logged_in() ? esc_html__( 'My Profile', 'apollo-templates' ) : esc_html__( 'Sign In', 'apollo-templates' ); ?>
			</a>
		</div>
	</header>

	<!-- ── Main content ─────────────────────────────────────────────── -->
	<main id="main" role="main">
		<div id="smooth-wrapper">
			<div id="smooth-content">

				<!-- Banner section -->
				<section class="banner" aria-label="<?php esc_attr_e( 'Hero banner', 'apollo-templates' ); ?>">
					<div class="banner-mask" aria-hidden="true"></div>
					<div class="banner-text split"><?php echo esc_html( $page_subtitle ?: 'Apollo Platform' ); ?></div>
					<div class="banner-title split"><?php echo esc_html( $page_title ); ?></div>
					<div class="banner-descr split">
						<?php echo esc_html( get_the_excerpt() ?: __( 'Scroll to explore our collection.', 'apollo-templates' ) ); ?>
					</div>
				</section>

				<!-- Collection / Slider section -->
				<section class="collection" aria-label="<?php esc_attr_e( 'Collection slider', 'apollo-templates' ); ?>">
					<div class="collection-mask" aria-hidden="true"></div>
					<div class="slider" role="region" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Image slider', 'apollo-templates' ); ?>">
						<div class="slider-wrapp">
							<?php foreach ( $slides as $i => $slide ) : ?>
								<div
									class="slider-img<?php echo ( $i === $total - 1 ) ? ' active' : ''; ?>"
									role="group"
									aria-roledescription="slide"
									aria-label="<?php echo esc_attr( ( $i + 1 ) . ' of ' . $total ); ?>"
								>
									<img
										src="<?php echo esc_url( $slide['image'] ); ?>"
										alt="<?php echo esc_attr( $slide['alt'] ?: $slide['title'] ); ?>"
										loading="<?php echo $i === $total - 1 ? 'eager' : 'lazy'; ?>"
									>
									<div class="slider-gradient" aria-hidden="true"></div>
								</div>
							<?php endforeach; ?>
						</div>

						<div class="slider-descr" aria-hidden="true">
							<?php echo esc_html( $slides[0]['descr'] ?? '' ); ?>
						</div>

						<div class="slider-title" aria-live="polite">
							<?php foreach ( $slides as $slide ) : ?>
								<div class="slider-title__item"><?php echo esc_html( $slide['title'] ); ?></div>
							<?php endforeach; ?>
							<!-- Extra blank item for slide-out animation room -->
							<div class="slider-title__item" aria-hidden="true"></div>
						</div>

						<div class="slider-numeric" aria-hidden="true">
							<div class="slider-numeric__active">
								<?php foreach ( $slides as $i => $slide ) : ?>
									<div class="slider-numeric__item"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
								<?php endforeach; ?>
							</div>
							<span>–</span>
							<div class="slider-numeric__total"><?php echo esc_html( str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) ); ?></div>
						</div>
					</div>
				</section>

				<!-- Next section — replace with actual Apollo content -->
				<section class="next-section" id="next-section">
					<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				</section>

			</div><!-- /smooth-content -->
		</div><!-- /smooth-wrapper -->
	</main>

	<!-- ── Apollo CDN already in <head> — GSAP available globally ─── -->
	<script>
	(function () {
		'use strict';

		// Bail if GSAP bundle not loaded (graceful degradation).
		if ( typeof gsap === 'undefined' ) { return; }

		gsap.registerPlugin( ScrollTrigger, SplitText, ScrollSmoother, CustomEase, Observer );

		CustomEase.create( 'preloaderEase', '0.48,0.00,0.83,0.83' );
		CustomEase.create( 'headerEase',    '0.17,0.17,0.52,1.00' );
		CustomEase.create( 'titleEase',     '0.17,0.17,0.49,1.00' );
		CustomEase.create( 'titleEaseHide', '0.55,0.00,0.83,0.83' );

		window.addEventListener( 'load', function () {

			// ── SplitText ──────────────────────────────────────────────
			function initSplitText( selector ) {
				var wrappers = document.querySelectorAll( selector );
				if ( ! wrappers.length ) { return; }

				new SplitText( selector, { type: 'lines', linesClass: 'fade-overflow' } );

				wrappers.forEach( function ( wrapper ) {
					wrapper.querySelectorAll( '.fade-overflow' ).forEach( function ( line ) {
						var text = line.innerHTML;
						line.innerText = '';
						line.innerHTML = '<div class="fade-el">' + text + '</div>';
					} );
				} );
			}

			initSplitText( '.split' );

			// ── ScrollSmoother ────────────────────────────────────────
			var smoother = ScrollSmoother.create( {
				wrapper:         document.getElementById( 'smooth-wrapper' ),
				content:         document.getElementById( 'smooth-content' ),
				smooth:          1.5,
				normalizeScroll: true,
				effects:         true,
			} );

			var activeIndexSlider;

			// ── Page init + preloader exit ────────────────────────────
			function initPage() {
				smoother.paused( true );
				smoother.scrollTo( 0 );

				var slideImgs = document.querySelectorAll( '.slider-img' );
				activeIndexSlider = slideImgs.length - 1;

				slideImgs.forEach( function ( el, i ) {
					el.classList.remove( 'hide', 'active' );
					if ( i === activeIndexSlider ) { el.classList.add( 'active' ); }
				} );

				gsap.set( '.slider-title__item',   { y: 0 } );
				gsap.set( '.slider-numeric__item',  { y: 0 } );

				gsap.timeline( { onComplete: function () { smoother.paused( false ); } } )
					.to( '.preloader-wrapp', { y: '100%', duration: 0.7, ease: 'preloaderEase' }, '>2' )
					.set( '.preloader', { display: 'none' } )
					.fromTo( '.header-wrapp',      { y: '-1rem' }, { y: 0, duration: 0.85, ease: 'headerEase' } )
					.fromTo( '.banner-text .fade-el',  { y: '100%' }, { y: 0, duration: 0.5, stagger: 0.2, ease: 'headerEase' }, '<' )
					.fromTo( '.banner-title .fade-el', { y: '100%' }, { y: 0, duration: 1,   stagger: 0.2, ease: 'headerEase' }, '<' )
					.fromTo( '.banner-descr .fade-el', { y: '100%' }, { y: 0, duration: 1,   stagger: 0.1, ease: 'headerEase' }, '<' )
					.fromTo( '.slider',         { y: '50%' }, { y: 0, duration: 1.05, ease: 'headerEase' }, '<' )
					.fromTo( '.slider-img img', { scale: 1.7 }, { scale: 1.3, duration: 1 }, '<' );
			}

			initPage();

			// ── Next slide / scroll-through ───────────────────────────
			var playAnimation = false;

			function nextSlide() {
				playAnimation = true;

				if ( activeIndexSlider > 0 ) {
					document.querySelector( '.slider-img.active' ).classList.add( 'hide' );
					document.querySelectorAll( '.slider-img' )[ activeIndexSlider - 1 ].classList.add( 'active' );

					gsap.to( '.slider-numeric__item', {
						y: '-=100%', duration: 1.4, ease: 'titleEase',
					} );

					gsap.timeline( { onComplete: function () { playAnimation = false; } } )
						.to( '.slider-title__item', { y: '+=100%', duration: 0.7, ease: 'titleEaseHide' } )
						.to( '.slider-title__item', { y: '+=100%', duration: 0.7, ease: 'titleEase' } );

					activeIndexSlider--;
				} else {
					sliderObserver.disable();
					smoother.scrollTo( '#next-section' );
					playAnimation = false;
				}

				smoother.paused( false );
			}

			// ── Observer — wheel + touch ───────────────────────────────
			var sliderObserver = ScrollTrigger.observe( {
				target:      '.collection',
				type:        'wheel,touch,scroll,pointer',
				wheelSpeed:  1,
				tolerance:   1,
				preventDefault: true,
				onDown: function () {
					if ( ! playAnimation ) { nextSlide(); }
				},
				onWheel: function ( self ) {
					if ( self.deltaY > 0 && ! playAnimation ) { nextSlide(); }
				},
			} );

			sliderObserver.disable();

			// Enable/disable observer based on scroll position.
			ScrollTrigger.create( {
				trigger: '.collection',
				start:   'top top+=2px',
				end:     'bottom bottom',
				scrub:   true,
				onEnter:     function () { sliderObserver.enable(); },
				onEnterBack: function () { sliderObserver.enable(); },
			} );

			// ── Banner scroll parallax + header color transition ────────
			gsap.timeline( {
				defaults: { ease: 'none' },
				scrollTrigger: {
					trigger:     document.querySelector( '.banner' ),
					start:       'top top',
					end:         'bottom top',
					scrub:       true,
					onEnter:     function () { sliderObserver.disable(); },
					onEnterBack: function () { sliderObserver.disable(); },
				},
			} )
				.to( '.collection',    { padding: 0, duration: 1 } )
				.to( '.slider',        { borderRadius: 0, duration: 1 }, '<' )
				.to( '.banner-mask',   { alpha: 1, duration: 1 }, '<' )
				.to( '.collection-mask', { alpha: 1, duration: 1 }, '<' )
				.fromTo( '.slider-img img', { scale: 1.3 }, { scale: 1, duration: 1 }, '<' )
				.to( '.header',        { color: '#ffffff', duration: 0.3 }, '<0.3' )
				.to( '.header-link',   { color: '#ffffff', duration: 0.3 }, '<' )
				.to( '.header-logo .logo-icon', { filter: 'invert(1)', duration: 0.3 }, '<' );

		} ); // end window.load
	}());
	</script>

</body>
</html>
