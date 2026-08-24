<?php

/**
 * Template Part: Events Archive — Hero Section
 *
 * Dark gradient hero with Shrikhand title, breadcrumb, subtitle, and stat badge.
 * GSAP-animated elements (opacity:0 + translateY set in CSS, JS animates in).
 *
 * Expects: $total_found (int) — total events count from parent template.
 *
 * @package Apollo\Event
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_found = $total_found ?? 0;
?>
<section class="ev-hero">
	<div class="ev-hero__inner">

		<!-- Breadcrumb -->
		<nav class="ev-hero__breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span>/</span>
			<span>Eventos</span>
		</nav>

		<!-- Title -->
		<h1 class="ev-hero__title">Eventos</h1>

		<!-- Subtitle -->
		<p class="ev-hero__sub">
			A cena noturna do <strong>Rio de Janeiro</strong> reunida em um só lugar.
			Festas, shows, encontros e mais.
		</p>

		<!-- Stat Badge -->
		<?php if ( $total_found > 0 ) : ?>
			<div class="ev-hero__stat">
				<span class="ev-hero__stat-num"><?php echo esc_html( $total_found ); ?></span>
				<span class="ev-hero__stat-label">eventos ativos</span>
			</div>
		<?php endif; ?>

	</div>
</section>
