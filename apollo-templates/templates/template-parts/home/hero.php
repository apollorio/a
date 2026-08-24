<?php

/**
 * Apollo Home Hero Section
 *
 * Video background hero with title and CTA
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

$hero_title    = $args['title'] ?? __( 'Não apenas veja.', 'apollo-templates' );
$hero_subtitle = $args['subtitle'] ?? __( 'Venha viver junto!', 'apollo-templates' );
$hero_text     = $args['text'] ?? __( 'Sua nova ferramenta de cultura digital e de cadeia produtiva da cultura do Rio de Janeiro. Com caráter inovador, é orientado à economia criativa e à difusão do acesso à arte, música e cultura carioca.', 'apollo-templates' );
$cta_text      = $args['cta_text'] ?? __( 'Explorar', 'apollo-templates' );
$cta_url       = $args['cta_url'] ?? '#events';
$video_webm    = $args['video_webm'] ?? 'https://assets.apollo.rio.br/vid/v2.webm';
$video_mp4     = $args['video_mp4'] ?? 'https://assets.apollo.rio.br/vid/v2.mp4';
$video_poster  = $args['video_poster'] ?? '';
$login_url     = home_url( '/acesso' );
?>
<style>
html,
body {
	padding-top: 0px !important;
}

.a-hero-aprio-hero-title {
	color: white !important
}
</style>

<header class="a-hero-aprio-hero-header inverso"
	style="z-index:999999!important; opacity:.55; color:white!important; mix-blend-mode:difference!imporant;font-weight:300!important;">
	<div class="a-hero-aprio-hero-brand"
		style="z-index:999999!important; color:white!important; mix-blend-mode:difference!imporant;font-weight:300!important;">
		<i class="apollo i-logo-hero" style="position:absolute;top:6px;left:17px;"></i><a class="txt-logo-hero"
			href="<?php echo esc_url( home_url( '/' ) ); ?>"
			style="color:white!important; mix-blend-mode:difference!imporant;">apollo<span class="ap-logo-squ">::</span
				style="color:white!important; mix-blend-mode:difference!imporant;font-weight:300!important;">rio</a>
	</div>

</header>
<!-- HERO -->
<section class="a-hero-aprio-hero">
	<video class="a-hero-hero-video" autoplay muted loop playsinline preload="auto"
		<?php
		if ( $video_poster ) :
			?>
			poster="<?php echo esc_url( $video_poster ); ?>" 
			<?php
		endif;
		?>
		>
		<source src="<?php echo esc_url( $video_webm ); ?>" type="video/webm">
		<source src="<?php echo esc_url( $video_mp4 ); ?>" type="video/mp4">
	</video>
	<h1 class="a-hero-aprio-hero-title reveal-up">
	<?php
	echo esc_html( $hero_title );
	?>
													<br><span class="vetxt">
													<?php
													echo esc_html( $hero_subtitle );
													?>
													</span></h1>
	<p class="a-hero-aprio-hero-text reveal-up" style="transition-delay:0.1s;">
	<?php
	echo esc_html( $hero_text );
	?>
	</p>
	<div class="reveal-up" style="transition-delay:0.2s;"><a href="<?php echo esc_url( $cta_url ); ?>"
			class="a-hero-aprio-hero-btn">
			<?php
			echo esc_html( $cta_text );
			?>
			</a></div>
</section>
