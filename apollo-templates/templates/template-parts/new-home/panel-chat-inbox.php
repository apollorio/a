<?php
/**
 * Panel: Chat Inbox — Single Thread (Logged LEFT deeper)
 *
 * Single conversation view. Slides LEFT from chat-list.
 * data-panel="chat-inbox" data-dir="left"
 *
 * Messages loaded via REST when a conversation is opened.
 * Compose bar at bottom for sending messages.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → panels.chat-inbox
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id  = get_current_user_id();
$rest_url = esc_url_raw( rest_url( 'apollo/v1/chat/' ) );
$nonce    = wp_create_nonce( 'wp_rest' );
?>
<section data-panel="chat-inbox" data-glyph="✉"
	<?php if ( $user_id ) : ?> data-a-user="<?php echo esc_attr( (string) $user_id ); ?>"<?php endif; ?>
	data-a-component="panel-chat-inbox">

	<!-- ── Header ── -->
	<header class="pnl-head">
		<button class="pnl-head__back" onclick="ApolloSlider&&ApolloSlider.back()" aria-label="Voltar">
			<i class="ri-arrow-left-s-line"></i>
		</button>
		<div class="pnl-head__user" id="inboxUserInfo">
			<span class="pnl-card__avatar pnl-card__avatar--sm" id="inboxAvatar"></span>
			<div>
				<span class="pnl-head__title" id="inboxName"></span>
				<span class="pnl-head__status" id="inboxStatus"></span>
			</div>
		</div>
		<div class="pnl-head__actions">
			<button class="pnl-btn pnl-btn--icon" id="inboxInfoBtn" aria-label="Info">
				<i class="ri-information-line"></i>
			</button>
		</div>
	</header>

	<!-- ── Messages ── -->
	<div class="pnl-body pnl-body--chat" id="inboxMessages">

		<div class="pnl-loader" id="inboxLoader">
			<div class="pnl-loader__ring"></div>
		</div>

		<?php
		/**
		 * Hook: apollo/chat/render_thread
		 * apollo-chat renders messages here.
		 *
		 * @param int $user_id Current user
		 */
		do_action( 'apollo/chat/render_thread', $user_id );
		?>
	</div>

	<!-- ── Compose ── -->
	<div class="pnl-compose" id="inboxCompose">
		<button class="pnl-btn pnl-btn--icon" id="inboxAttach" aria-label="Anexar">
			<i class="ri-attachment-2"></i>
		</button>
		<input type="text" class="apollo-input pnl-compose__input" id="inboxInput"
			placeholder="<?php esc_attr_e( 'Escreva uma mensagem...', 'apollo-templates' ); ?>"
			autocomplete="off">
		<button class="pnl-compose__send" id="inboxSend" aria-label="Enviar">
			<i class="ri-send-plane-fill"></i>
		</button>
	</div>
</section>

<script>
(function(){
	var REST     = <?php echo wp_json_encode( $rest_url ); ?>;
	var NONCE    = <?php echo wp_json_encode( $nonce ); ?>;
	var USER_ID  = <?php echo (int) $user_id; ?>;
	var messages = document.getElementById('inboxMessages');
	var loader   = document.getElementById('inboxLoader');
	var input    = document.getElementById('inboxInput');
	var sendBtn  = document.getElementById('inboxSend');
	var nameEl   = document.getElementById('inboxName');
	var avatarEl = document.getElementById('inboxAvatar');
	var threadId = null;

	/* ── Open thread from chat-list ── */
	document.addEventListener('click', function(e){
		var trigger = e.target.closest('[data-to="chat-inbox"]');
		if (!trigger) return;
		threadId = trigger.getAttribute('data-thread-id');
		var name = trigger.getAttribute('data-thread-name') || '';
		if (nameEl) nameEl.textContent = name;
		if (avatarEl) avatarEl.textContent = name.substring(0, 2).toUpperCase();
		loadThread(threadId);
	});

	function loadThread(id){
		if (!id) return;
		loader.style.display = '';
		fetch(REST + id + '/messages', { headers: { 'X-WP-Nonce': NONCE } })
			.then(function(r){ return r.ok ? r.json() : Promise.reject(r.status); })
			.then(function(data){
				loader.style.display = 'none';
				renderMessages(data);
				scrollBottom();
			})
			.catch(function(){
				loader.style.display = 'none';
			});
	}

	function renderMessages(msgs){
		var existing = messages.querySelectorAll('.pnl-msg');
		existing.forEach(function(m){ m.remove(); });
		if (!Array.isArray(msgs)) return;
		msgs.forEach(function(msg){
			var div = document.createElement('div');
			div.className = 'pnl-msg' + (msg.sender_id == USER_ID ? ' pnl-msg--self' : '');
			div.innerHTML = '<div class="pnl-msg__bubble">' + (msg.content || '') + '</div>';
			messages.appendChild(div);
		});
	}

	function scrollBottom(){
		messages.scrollTop = messages.scrollHeight;
	}

	/* ── Send message ── */
	function send(){
		var text = input.value.trim();
		if (!text || !threadId) return;
		input.value = '';

		/* Optimistic UI */
		var div = document.createElement('div');
		div.className = 'pnl-msg pnl-msg--self';
		div.innerHTML = '<div class="pnl-msg__bubble">' + text.replace(/</g,'&lt;') + '</div>';
		messages.appendChild(div);
		scrollBottom();

		fetch(REST + threadId + '/messages', {
			method: 'POST',
			headers: { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' },
			body: JSON.stringify({ content: text })
		}).catch(function(err){ console.warn('[Apollo Chat] Send failed:', err); });
	}

	if (sendBtn) sendBtn.addEventListener('click', send);
	if (input) input.addEventListener('keydown', function(e){
		if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
	});
})();
</script>