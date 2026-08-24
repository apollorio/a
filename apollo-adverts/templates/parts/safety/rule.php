<?php

/**
 * Part: safety/rule — the one sentence the whole page turns on.
 *
 * Stated before the checks, not after, because the checks are meaningless
 * until you know what they have to add up to. Having mutuals is not the bar —
 * a scammer follows back whoever follows him and the overlap fills itself.
 * The bar is one of them saying, on the record, that they know this person.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<p class="ap-rule">
	<b><?php esc_html_e('Ter amigos em comum não prova nada.', 'apollo-adverts'); ?></b>
	<?php
	esc_html_e(
		'A conversa só abre se um deles confirmar que conhece essa pessoa — ou se a Apollo já verificou o perfil.',
		'apollo-adverts'
	);
	?>
</p>
