<?php

/**
 * Template Part: DJ Archive — Hero Section
 *
 * Dark gradient hero with Shrikhand title, breadcrumb, subtitle, stat badge.
 *
 * Expects: $total_found (int) — total DJs count.
 *
 * @package Apollo\DJs
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_found = $total_found ?? 0;
?>
<section class="dj-hero">
	<div class="dj-hero__inner">

		<!-- Breadcrumb -->
		<nav class="dj-hero__breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span>/</span>
			<span>DJs</span>
		</nav>

		<!-- Title -->
		<h1 class="dj-hero__title">DJs</h1>

		<!-- Subtitle -->
		<p class="dj-hero__sub">
			Os artistas que movem a <strong>cena noturna</strong> do Rio.
			Descubra os talentos que fazem a pista vibrar.
		</p>

		<!-- Stat Badge -->
		<?php if ( $total_found > 0 ) : ?>
			<div class="dj-hero__stat">
				<span class="dj-hero__stat-num"><?php echo esc_html( $total_found ); ?></span>
				<span class="dj-hero__stat-label">DJs na plataforma</span>
			</div>
		<?php endif; ?>

	</div>
</section>
