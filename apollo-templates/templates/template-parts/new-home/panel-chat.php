<?php

/**
 * New Home — Panel: Chat
 *
 * Slides in from LEFT via page-layout.js engine.
 * Hook: do_action('apollo/chat/render_panel') for full chat UI.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section data-panel="chat" data-glyph="C">
	<div class="container" style="padding-top:calc(80px + var(--safe-top));">
		<button data-back="1" class="return-back" aria-label="<?php esc_attr_e( 'Voltar', 'apollo-templates' ); ?>">
			<i class="ri-corner-up-right-line"></i>
		</button>
		<?php
		/**
		 * Apollo Chat panel content.
		 * When apollo-chat is active, it renders the full chat interface here.
		 */
		do_action( 'apollo/chat/render_panel' );

		if ( ! has_action( 'apollo/chat/render_panel' ) ) :
			?>
			<p class="ai font-mono text-sm text-muted">HERE /*chat page</p>
		<?php endif; ?>
	</div>
</section>
