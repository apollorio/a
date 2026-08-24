<?php
/**
 * Directory Part — Hero
 *
 * Editorial hero with parallax bg-text, label, title, description, stats strip.
 * Expects: $page_title, $page_desc, $is_nucleos, $stats (array)
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Stats defaults
$stats         = $stats ?? array();
$total_comunas = $stats['total_comunas'] ?? 0;
$total_nucleos = $stats['total_nucleos'] ?? 0;
$total_members = $stats['total_members'] ?? 0;
$total_active  = $stats['total_active'] ?? 0;
?>
<section class="hero g-fade">
	<div class="hero-bg-text" id="heroBgText"><?php echo $is_nucleos ? 'NÚCLEOS' : 'COMUNAS'; ?></div>
	<div class="wrap">
		<div class="hero-inner">
			<div class="hero-label">diretório</div>
			<h1 class="hero-title">
				<?php echo esc_html( $page_title ); ?>
				<span class="thin"><?php echo $is_nucleos ? 'da cena' : 'do Rio'; ?></span>
			</h1>
			<p class="hero-desc"><?php echo esc_html( $page_desc ); ?></p>

			<?php require __DIR__ . '/stats-strip.php'; ?>
		</div>
	</div>
</section>
