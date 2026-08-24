<?php

/**
 * Apollo Home — Tools Accordion Section
 *
 * DJ Global Roster and platform tools showcase.
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$tools_data = array(
	array(
		'title'       => "Plano, apollo's image creative studio",
		'description' => __( 'Estúdio criativo para produção de material visual. Crie flyers, capas e identidades visuais para seus eventos e releases.', 'apollo-templates' ),
		'images'      => array(
			'https://apollo.rio.br/v2/pln1.png',
			'https://apollo.rio.br/v2/pln2.png',
		),
		'iframe'      => 'https://plano.apollo.rio.br/ij.html',
		'hide_media'  => false,
	),
	array(
		'title'       => 'Doc & Assina::rio, seu contrato fácil',
		'description' => __( 'Contratos digitais simplificados para a indústria criativa. Assinatura eletrônica válida juridicamente.', 'apollo-templates' ),
		'images'      => array(
			'https://apollo.rio.br/v2/ass1.png',
			'https://apollo.rio.br/v2/ass2.png',
		),
		'iframe'      => 'https://apollo.rio.br/v2/sign.html',
		'hide_media'  => false,
	),
	array(
		'title'       => 'Cena::rio, ferramentas da indústria cultural',
		'description' => __( 'Suite completa de ferramentas para produtores, promoters e artistas. Gestão de eventos, bilheteria e analytics.', 'apollo-templates' ),
		'images'      => array(
			'https://images.unsplash.com/photo-1514525253440-b393452e8d2e?q=80&w=600&auto=format&fit=crop',
			'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=600&auto=format&fit=crop',
		),
		'iframe'      => '',
		'hide_media'  => true,
	),
	array(
		'title'       => 'Repasses de Ingressos & Acomodações',
		'description' => __( 'Marketplace seguro para revenda de ingressos e hospedagem compartilhada durante eventos.', 'apollo-templates' ),
		'images'      => array(
			'https://images.unsplash.com/photo-1540541338287-41700207dee6?q=80&w=600&auto=format&fit=crop',
			'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?q=80&w=600&auto=format&fit=crop',
		),
		'iframe'      => '',
		'hide_media'  => true,
	),
);
?>

<section id="roster" class="tools-section container">
	<div class="tools-grid">

		<div class="tools-intro reveal-up">
			<div class="hub-status" style="margin-bottom:16px;">
				<span class="hub-pulse"></span>
				<span class="hub-status-text"><?php esc_html_e( 'Ferramenta', 'apollo-templates' ); ?></span>
			</div>
			<h2><?php esc_html_e( 'DJ Global Roster', 'apollo-templates' ); ?></h2>
			<p class="card-text">
				<?php esc_html_e( 'Conectando sons locais a plataformas globais. Curadoria internacional sem intermediários.', 'apollo-templates' ); ?>
			</p>
		</div>

		<div class="reveal-up delay-100">
			<h3 class="section-label" style="margin-bottom:24px;"><?php esc_html_e( 'Ferramentas', 'apollo-templates' ); ?></h3>

			<div class="accordion">
				<?php
				foreach ( $tools_data as $item ) :
					$media_style = $item['hide_media'] ? ' style="display:none"' : '';
					?>
					<div class="accordion-item">
						<button class="accordion-trigger" type="button" aria-expanded="false">
							<span class="accordion-title"><?php echo esc_html( $item['title'] ); ?></span>
							<span class="accordion-icon"></span>
						</button>

						<div class="accordion-content">
							<div class="accordion-inner">
								<p><?php echo esc_html( $item['description'] ); ?></p>

								<?php if ( ! empty( $item['images'] ) ) : ?>
									<div class="accordion-images">
										<?php foreach ( $item['images'] as $img_url ) : ?>
											<img src="<?php echo esc_url( $img_url ); ?>"
												alt="<?php echo esc_attr( $item['title'] ); ?>"
												<?php
												echo $media_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
												?>
												>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if ( ! empty( $item['iframe'] ) ) : ?>
									<div class="accordion-iframe">
										<iframe src="<?php echo esc_url( $item['iframe'] ); ?>"
											allowfullscreen
											<?php
											echo $media_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
											?>
											>
										</iframe>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

	</div>
</section>

<script>
	(function() {
		var triggers = document.querySelectorAll('.accordion-trigger');
		triggers.forEach(function(trigger) {
			trigger.addEventListener('click', function() {
				var item = this.parentElement;
				var isActive = item.classList.contains('active');

				document.querySelectorAll('.accordion-item').forEach(function(i) {
					i.classList.remove('active');
					var btn = i.querySelector('.accordion-trigger');
					if (btn) btn.setAttribute('aria-expanded', 'false');
				});

				if (!isActive) {
					item.classList.add('active');
					this.setAttribute('aria-expanded', 'true');
				}
			});
		});
	})();
</script>
