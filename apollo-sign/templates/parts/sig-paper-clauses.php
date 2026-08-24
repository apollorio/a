<?php

/**
 * Apollo Sign — Paper Clauses Block
 * Part: sig-paper-clauses.php
 *
 * Renders the textual body of the legal document
 * (all clauses). Shared across preview, sign and done panels
 * via the $clauses array.
 *
 * Variables expected:
 *   $doc       — WP_Post or stdClass with doc data
 *   $clauses   — array of clause arrays:
 *                  ['label' => '1. Do Objeto', 'text' => '...', 'items' => []]
 *   $compact   — bool — when true, only essential clauses shown (sign/done panels)
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fallback default clauses (used when no real $clauses provided)
$clauses = $clauses ?? array(
	array(
		'label' => '1. Do Objeto',
		'text'  => 'O presente instrumento formaliza as responsabilidades operacionais, de segurança e de convivência aplicáveis ao evento cadastrado na plataforma Apollo::rio, conforme dados inseridos pelo GESTOR responsável, doravante denominado simplesmente RESPONSÁVEL.',
		'items' => array(),
	),
	array(
		'label' => '2. Das Obrigações do Responsável',
		'text'  => 'O RESPONSÁVEL declara ciência e plena concordância com as normas vigentes, comprometendo-se irrevogavelmente a:',
		'items' => array(
			'Garantir a integridade física e o bem-estar de todos os participantes do evento;',
			'Respeitar os limites de pressão sonora estabelecidos pela legislação municipal e estadual vigente;',
			'Assegurar a limpeza, organização e devolução do local ao estado original pós-evento;',
			'Responsabilizar-se civilmente perante terceiros por quaisquer danos materiais ou imateriais decorrentes do evento;',
			'Cumprir integralmente as exigências do Corpo de Bombeiros, Vigilância Sanitária e demais órgãos competentes.',
		),
	),
	array(
		'label' => '3. Da Validade Jurídica Digital',
		'text'  => 'A assinatura eletrônica aposta neste instrumento gera hash criptográfico único registrado na infraestrutura blockchain Apollo, conferindo plena validade jurídica nos termos da Medida Provisória nº 2.200-2/2001 e da Lei nº 14.063/2020, sendo equivalente à assinatura manuscrita para todos os efeitos legais previstos no ordenamento jurídico brasileiro.',
		'items' => array(),
	),
	array(
		'label' => '4. Das Penalidades e Sanções',
		'text'  => 'O descumprimento de qualquer cláusula do presente instrumento sujeitará o RESPONSÁVEL às penalidades previstas nos Termos de Uso Apollo, bem como à responsabilidade civil e, conforme o caso, penal perante a legislação vigente, incluindo suspensão imediata de conta e registro nos órgãos competentes.',
		'items' => array(),
	),
	array(
		'label' => '5. Da Aceitação e Manifestação de Vontade',
		'text'  => 'Ao assinar eletronicamente o presente instrumento, o RESPONSÁVEL declara expressamente que leu, compreendeu e aceita integralmente todos os termos e condições aqui estipulados, dispensando quaisquer formalidades adicionais para sua plena eficácia.',
		'items' => array(),
	),
	array(
		'label' => '6. Do Foro',
		'text'  => 'Fica eleito o foro da Comarca do Rio de Janeiro, Estado do Rio de Janeiro, para dirimir quaisquer questões oriundas do presente instrumento, com renúncia expressa a qualquer outro, por mais privilegiado que seja.',
		'items' => array(),
	),
);

$compact = $compact ?? false;

// In compact mode (sign/done panels) collapse multi-item clauses to a summary
foreach ( $clauses as $clause ) :
	if ( $compact && ! empty( $clause['compact_text'] ) ) {
		// Use a shorter compact version if defined
		$display_text  = $clause['compact_text'];
		$display_items = array();
	} else {
		$display_text  = $clause['text'];
		$display_items = $clause['items'] ?? array();
	}
	?>
	<div class="paper-sec">
		<div class="paper-sec-lbl"><?php echo esc_html( $clause['label'] ); ?></div>
		<p class="paper-txt"><?php echo esc_html( $display_text ); ?></p>
		<?php if ( ! empty( $display_items ) ) : ?>
			<ul class="paper-list">
				<?php foreach ( $display_items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
endforeach;
