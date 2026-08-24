<?php

/**
 * Part: safety/note — the liability line, stated plainly.
 *
 * Kept short and kept last. The long legal paragraph the old modal carried was
 * the reason nobody read the modal.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<p class="ap-note">
	<i aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20ZM11 7H13V9H11V7ZM11 11H13V17H11V11Z"/></svg>
	</i>
	<?php
	esc_html_e(
		'A Apollo não processa o pagamento, não retém valores e não garante a entrega. Nunca pague adiantado para um perfil que ninguém reconhece — prefira combinar a transferência pelo app oficial do ingresso, com as duas partes presentes.',
		'apollo-adverts'
	);
	?>
</p>
