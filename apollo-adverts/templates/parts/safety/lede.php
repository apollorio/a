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
	<span class="ap-seal" id="apSeal" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L20.2169 2.82598C20.6745 2.92766 21 3.33347 21 3.80217V13.7889C21 15.795 19.9974 17.6684 18.3282 18.7812L12 23L5.6718 18.7812C4.00261 17.6684 3 15.795 3 13.7889V3.80217C3 3.33347 3.32553 2.92766 3.78307 2.82598L12 1ZM12 3.04879L5 4.60434V13.7889C5 15.1263 5.6684 16.3752 6.7812 17.1171L12 20.5963L17.2188 17.1171C18.3316 16.3752 19 15.1263 19 13.7889V4.60434L12 3.04879ZM11 15H13V17H11V15ZM11 7H13V13H11V7Z"/></svg>
	</span>
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
