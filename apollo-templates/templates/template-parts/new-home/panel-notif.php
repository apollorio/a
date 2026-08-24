<?php

/**
 * New Home — Panel: Notifications
 *
 * Slides in from TOP via page-layout.js engine.
 * Hook: do_action('apollo/notif/render_panel') for notifications UI.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section data-panel="notif" data-glyph="N">
	<div class="container" style="padding-top:calc(80px + var(--safe-top));">
		<button data-back="1" class="return-back" style="margin-bottom:28px;" aria-label="<?php esc_attr_e( 'Fechar notificações', 'apollo-templates' ); ?>">
			<i class="ri-corner-up-right-line"></i>
		</button>
		<?php
		/**
		 * Apollo Notifications panel content.
		 * When apollo-notif is active, it renders the notifications list here.
		 */
		do_action( 'apollo/notif/render_panel' );

		if ( ! has_action( 'apollo/notif/render_panel' ) ) :
			?>
			<p class="ai font-mono text-sm text-muted">/notificacoes</p>
		<?php endif; ?>
	</div>
</section>
