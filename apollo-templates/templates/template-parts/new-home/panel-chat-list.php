<?php
/**
 * Panel: Chat List — Conversations (Logged LEFT)
 *
 * Chat conversations listing. Slides LEFT from center.
 * data-panel="chat-list" data-dir="left"
 *
 * Clicking a conversation → slides LEFT deeper to chat-inbox.
 * Content provided by apollo-chat via hook.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → panels.chat-list
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id    = get_current_user_id();
$rest_url   = esc_url_raw( rest_url( 'apollo/v1/chat/' ) );
$nonce      = wp_create_nonce( 'wp_rest' );
$chat_count = function_exists( 'apollo_get_unread_message_count' ) ? apollo_get_unread_message_count( $user_id ) : 0;
?>
<section data-panel="chat-list" data-glyph="◄"
	<?php if ( $user_id ) : ?> data-a-user="<?php echo esc_attr( (string) $user_id ); ?>"<?php endif; ?>
	data-a-component="panel-chat-list">

	<!-- ── Header ── -->
	<header class="pnl-head">
		<button class="pnl-head__back" onclick="ApolloSlider&&ApolloSlider.back()" aria-label="Voltar">
			<i class="ri-arrow-left-s-line"></i>
		</button>
		<h2 class="pnl-head__title"><?php esc_html_e( 'Mensagens', 'apollo-templates' ); ?></h2>
		<div class="pnl-head__actions">
			<button class="pnl-btn pnl-btn--icon" id="chatNewBtn"
				aria-label="<?php esc_attr_e( 'Nova conversa', 'apollo-templates' ); ?>">
				<i class="ri-edit-2-line"></i>
			</button>
		</div>
	</header>

	<!-- ── Search ── -->
	<div class="pnl-search">
		<i class="ri-search-line pnl-search__icon"></i>
		<input type="search" class="apollo-input pnl-search__input" id="chatSearch"
			placeholder="<?php esc_attr_e( 'Buscar conversas...', 'apollo-templates' ); ?>"
			autocomplete="off">
	</div>

	<!-- ── Conversations ── -->
	<div class="pnl-body" id="chatListBody">

		<?php
		/**
		 * Hook: apollo/chat/render_list
		 * apollo-chat renders conversation cards here.
		 * Each card should include:
		 *   data-to="chat-inbox" data-dir="left" data-thread-id="{id}"
		 *
		 * @param int $user_id Current user
		 */
		do_action( 'apollo/chat/render_list', $user_id );
		?>

		<!-- Fallback if no plugin populates -->
		<div class="pnl-empty" id="chatListEmpty">
			<i class="ri-message-3-line pnl-empty__icon"></i>
			<p><?php esc_html_e( 'Nenhuma conversa ainda', 'apollo-templates' ); ?></p>
			<button class="pnl-btn pnl-btn--primary" id="chatStartBtn">
				<?php esc_html_e( 'Iniciar conversa', 'apollo-templates' ); ?>
			</button>
		</div>
	</div>
</section>

<script>
(function(){
	/* ── Search filter ── */
	var search = document.getElementById('chatSearch');
	if (search) {
		search.addEventListener('input', function(){
			var q = this.value.toLowerCase();
			var items = document.querySelectorAll('#chatListBody .pnl-card');
			items.forEach(function(card){
				var name = card.querySelector('.pnl-card__name');
				card.style.display = (!q || (name && name.textContent.toLowerCase().indexOf(q) > -1)) ? '' : 'none';
			});
		});
	}

	/* ── Hide empty state when conversations exist ── */
	document.addEventListener('apollo:chat:list-loaded', function(){
		var empty = document.getElementById('chatListEmpty');
		if (empty) empty.style.display = 'none';
	});
})();
</script>