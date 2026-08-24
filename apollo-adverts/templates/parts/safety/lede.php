<?php

/**
 * Part: safety/lede — the admission.
 *
 * Apollo says the uncomfortable thing first and in its own voice: past this
 * screen we cannot protect you. A warning that opens by defending the platform
 * gets skimmed; one that opens by admitting a limit gets read.
 *
 * @var int $post_id
 * @var int $seller_id
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<header class="ap-lede">
	<span class="ap-kicker"><?php esc_html_e('Apollo · Segurança', 'apollo-adverts'); ?></span>

	<h1 id="apSafetyTitle">
		<?php esc_html_e('A partir daqui a Apollo não consegue te proteger', 'apollo-adverts'); ?>
	</h1>

	<p>
		<?php esc_html_e('Você está prestes a falar direto com essa pessoa, fora do nosso alcance.', 'apollo-adverts'); ?>
		<strong><?php esc_html_e('A Apollo não intermedia nem garante pagamento algum.', 'apollo-adverts'); ?></strong>
		<?php esc_html_e('Enquanto você lê isto, estamos verificando tudo que dá para verificar sobre ela.', 'apollo-adverts'); ?>
	</p>
</header>
