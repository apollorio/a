<?php
/**
 * Apollo Home — Infra Section
 *
 * "Infraestrutura Cultural Digital" hero block with video iframe.
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();
?>

<section class="hero container" id="infra-section" style="margin-top:150px; background:#fff;">
	<div class="hero-grid">
		<div>
			<div class="reveal-up">
				<span class="hero-badge"><?php esc_html_e( 'Infraestrutura Cultural Digital', 'apollo-templates' ); ?></span>
			</div>
			<h1 class="reveal-up delay-100">
				<?php esc_html_e( 'Patrimônio Imaterial.', 'apollo-templates' ); ?><br>
				<span class="hero-text-gray"><?php esc_html_e( 'Futuro Digital.', 'apollo-templates' ); ?></span>
			</h1>
		</div>

		<div class="reveal-up delay-200">
			<p class="hero-description">
				<?php esc_html_e( 'Estruturando a cadeia produtiva da cultura carioca através de tecnologia modular e memória viva.', 'apollo-templates' ); ?>
			</p>
		</div>
	</div>

	<div class="hero-video reveal-up delay-300" style="margin-top: 3rem;">
		<iframe src="https://plano.apollo.rio.br/ij.html" title="<?php esc_attr_e( 'Apollo Plano Visualizer', 'apollo-templates' ); ?>" allowfullscreen loading="lazy"></iframe>
	</div>

	<br>

	<div class="reveal-up delay-300">
		<a href="https://plano.apollo.rio.br/" target="_blank" rel="noopener" style="text-decoration: none;">
			<div class="hub-link hub-link-primary" style="width: auto !important; min-width: 280px; display: inline-flex;">
				<span style="flex-grow: 1; text-align: left;">
					<b>Plano.px</b> — Apollo's Creative Studio
				</span>
				<i class="ri-arrow-right-up-long-line"></i>
			</div>
		</a>
	</div>
</section>
