<?php

/**
 * Template Part: GPS Archive — Hero Section
 *
 * @package Apollo\Local
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_found = $total_found ?? 0;
?>
<section class="gps-hero">
	<div class="gps-hero__inner">

		<nav class="gps-hero__breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span>/</span>
			<span>GPS</span>
		</nav>

		<h1 class="gps-hero__title">GPS</h1>

		<p class="gps-hero__sub">
			Mapeando os <strong>melhores espaços</strong> da cena carioca.
			Clubs, bares, rooftops e mais.
		</p>

		<?php if ( $total_found > 0 ) : ?>
			<div class="gps-hero__stat">
				<span class="gps-hero__stat-num"><?php echo esc_html( $total_found ); ?></span>
				<span class="gps-hero__stat-label">locais mapeados</span>
			</div>
		<?php endif; ?>

	</div>
</section>
