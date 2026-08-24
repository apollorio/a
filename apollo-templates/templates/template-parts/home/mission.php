<?php

/**
 * Apollo Home — Mission / Manifesto Section
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

$section_label = $args['label'] ?? __( 'A Missão', 'apollo-templates' );
$main_text     = $args['text'] ?? __( 'O Apollo::rio é um projeto estruturante e territorial. Atuamos como a ferramenta de cultura digital do Rio de Janeiro, orientados à economia criativa e à difusão do acesso.', 'apollo-templates' );

$cards = $args['cards'] ?? array(
	array(
		'title' => __( 'Suporte à Indústria', 'apollo-templates' ),
		'text'  => __( 'Infraestrutura digital modular para eventos, memória e inteligência aplicada. Uma plataforma social inclusiva para agentes de todos os portes.', 'apollo-templates' ),
	),
	array(
		'title' => __( 'Arquivo Vivo', 'apollo-templates' ),
		'text'  => __( 'Um registro em tempo real da cultura carioca com enfase da música eletrônica e clubber carioca, com vocação para replicação em outros territórios globais.', 'apollo-templates' ),
	),
);
?>

<section id="manifesto" class="manifesto container">
	<div class="manifesto-grid">
		<div class="reveal-up">
			<h3 class="section-label"><?php echo esc_html( $section_label ); ?></h3>
		</div>
		<div>
			<p class="manifesto-text reveal-up delay-100">
				<?php echo esc_html( $main_text ); ?>
			</p>
			<div class="manifesto-cards">
				<?php
				$delay = 200;
				foreach ( $cards as $card ) :
					?>
					<div class="reveal-up delay-<?php echo esc_attr( (string) $delay ); ?>">
						<h4 class="card-title"><?php echo esc_html( $card['title'] ); ?></h4>
						<p class="card-text"><?php echo esc_html( $card['text'] ); ?></p>
					</div>
					<?php
					$delay += 100;
				endforeach;
				?>
			</div>
		</div>
	</div>
</section>
