<?php
/**
 * DJ v3 Partial: Hero
 *
 * Full-viewport hero with banner background, badge, name, tagline, CTA.
 * Variables from single-dj-v3.php: $dj_id, $dj_name, $dj_name_formatted,
 * $dj_banner_url, $dj_bio_short, $dj_verified, $dj_sounds.
 *
 * Meta: _dj_banner, _dj_name, _dj_bio_short, _dj_verified
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;
?>

<header class="dj-hero">
	<div class="hero-bg-text" id="bgText"><?php echo esc_html( strtoupper( $dj_name ) ); ?></div>

	<div class="dj-hero-bg">
		<img
			src="<?php echo esc_url( $dj_banner_url ); ?>"
			alt="<?php echo esc_attr( $dj_name ); ?> live"
			loading="eager"
			width="2000"
			height="1200"
		>
		<div class="dj-hero-bg-overlay"></div>
	</div>

	<div class="dj-hero-content">

		<?php if ( $dj_verified ) : ?>
			<div class="dj-hero-badge ai">
				<i class="ri-verified-badge-fill"></i>
				<?php esc_html_e( 'DJ Verificado', 'apollo-djs' ); ?>
			</div>
		<?php else : ?>
			<div class="dj-hero-badge ai">
				<i class="ri-fingerprint-line"></i>
				<?php esc_html_e( 'DJ Global Passport', 'apollo-djs' ); ?>
			</div>
		<?php endif; ?>

		<h1 class="dj-hero-title ai"><?php echo wp_kses_post( $dj_name_formatted ); ?></h1>

		<?php if ( ! empty( $dj_bio_short ) ) : ?>
			<p class="dj-hero-sub ai">
				<?php echo esc_html( $dj_bio_short ); ?>
			</p>
		<?php endif; ?>

		<div class="dj-hero-actions ai">
			<?php if ( ! empty( $dj_sounds ) ) : ?>
				<a href="#sonoridades" class="btn btn-primary">
					<i class="ri-play-fill"></i> <?php esc_html_e( 'Ouvir agora', 'apollo-djs' ); ?>
				</a>
			<?php endif; ?>
			<a href="#bio" class="dj-play-circle" aria-label="<?php esc_attr_e( 'Ir para bio', 'apollo-djs' ); ?>">
				<i class="ri-arrow-down-s-line"></i>
			</a>
		</div>
	</div>
</header>
