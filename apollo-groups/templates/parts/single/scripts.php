<?php
/**
 * Single Part — Scripts
 *
 * GSAP animations, drawer toggle, join/leave, wow toggle, composer,
 * channel tabs, thread toggle.
 * Expects: $rest_url, $nonce, $group_id, $is_member
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<script>
(function() {
	'use strict';

	const REST    = '<?php echo esc_url( $rest_url ); ?>';
	const NONCE   = '<?php echo esc_js( $nonce ); ?>';
	const GID     = <?php echo (int) $group_id; ?>;
	const IS_MBR  = <?php echo $is_member ? 'true' : 'false'; ?>;

	const h = () => ({ 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' });

	/* ═══ Loader ═══ */
	document.addEventListener('DOMContentLoaded', () => {
		const loader = document.getElementById('pageLoader');
		if (loader && typeof gsap !== 'undefined') {
			gsap.to(loader, {
				scaleY: 0, duration: .8, ease: 'power4.inOut', delay: .4,
				transformOrigin: 'bottom',
				onComplete: () => loader.style.display = 'none'
			});
		} else if (loader) {
			setTimeout(() => loader.style.display = 'none', 600);
		}

		/* GSAP fade-ins */
		if (typeof gsap !== 'undefined') {
			gsap.registerPlugin(ScrollTrigger);

			gsap.utils.toArray('.g-fade').forEach(el => {
				gsap.to(el, {
					opacity: 1, y: 0, duration: .6, ease: 'power2.out',
					scrollTrigger: { trigger: el, start: 'top 92%', once: true }
				});
			});
		}
	});

	/* ═══ Drawer ═══ */
	const overlay = document.getElementById('drawerOverlay');
	const openBtn = document.getElementById('btnDrawerOpen');
	const closeBtn = document.getElementById('drawerClose');

	if (openBtn && overlay) {
		openBtn.addEventListener('click', () => overlay.classList.add('open'));
	}
	if (closeBtn && overlay) {
		closeBtn.addEventListener('click', () => overlay.classList.remove('open'));
		overlay.addEventListener('click', (e) => {
			if (e.target === overlay) overlay.classList.remove('open');
		});
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && overlay.classList.contains('open')) {
				overlay.classList.remove('open');
			}
		});
	}

	/* ═══ Join / Leave ═══ */
	document.querySelectorAll('#btnJoin, .drawer #btnJoin').forEach(btn => {
		if (!btn) return;
		btn.addEventListener('click', async () => {
			const state = btn.dataset.state;
			try {
				if (state === 'join') {
					await fetch(REST + '/' + GID + '/join', {
						method: 'POST', headers: h(), credentials: 'same-origin'
					});
					btn.dataset.state = 'joined';
					btn.classList.add('joined');
					btn.innerHTML = '<i class="ri-check-line"></i> Participando';
				} else {
					await fetch(REST + '/' + GID + '/leave', {
						method: 'DELETE', headers: h(), credentials: 'same-origin'
					});
					btn.dataset.state = 'join';
					btn.classList.remove('joined');
					btn.innerHTML = '<i class="ri-add-line"></i> Participar';
				}
			} catch (e) { console.error('join/leave:', e); }
		});
	});

	/* ═══ Channel Tabs ═══ */
	document.querySelectorAll('#channelTabs .channel').forEach(ch => {
		ch.addEventListener('click', () => {
			document.querySelectorAll('#channelTabs .channel').forEach(c => c.classList.remove('active'));
			ch.classList.add('active');
			// TODO: filter feed by channel (ch.dataset.channel)
		});
	});

	/* ═══ Thread Toggle ═══ */
	document.addEventListener('click', (e) => {
		const toggle = e.target.closest('.thread-toggle');
		if (!toggle) return;

		const replies = toggle.nextElementSibling;
		if (!replies) return;

		const isOpen = toggle.dataset.open === 'true';
		toggle.dataset.open = isOpen ? 'false' : 'true';
		toggle.classList.toggle('open', !isOpen);
		replies.style.display = isOpen ? 'none' : '';
	});

	/* ═══ Wow Toggle ═══ */
	document.addEventListener('click', (e) => {
		const btn = e.target.closest('[data-action="wow"]');
		if (!btn) return;

		const postId = btn.dataset.postId;
		btn.classList.toggle('active');

		fetch('<?php echo esc_url( rest_url( 'apollo/v1/wow/toggle' ) ); ?>', {
			method: 'POST', headers: h(), credentials: 'same-origin',
			body: JSON.stringify({
				content_type: 'social_post',
				content_id: parseInt(postId)
			})
		}).catch(e => console.error('wow:', e));
	});

	/* ═══ Composer ═══ */
	const composerSend = document.getElementById('composerSend');
	const composerInput = document.getElementById('composerInput');

	if (composerSend && composerInput) {
		/* Auto-resize textarea */
		composerInput.addEventListener('input', function() {
			this.style.height = 'auto';
			this.style.height = this.scrollHeight + 'px';
		});

		composerSend.addEventListener('click', async () => {
			const content = composerInput.value.trim();
			if (!content) return;

			composerSend.disabled = true;
			composerSend.textContent = '...';

			try {
				const res = await fetch('<?php echo esc_url( rest_url( 'apollo/v1/social/posts' ) ); ?>', {
					method: 'POST', headers: h(), credentials: 'same-origin',
					body: JSON.stringify({
						content: content,
						group_id: GID,
						type: 'text'
					})
				});

				if (res.ok) {
					composerInput.value = '';
					composerInput.style.height = 'auto';
					// Reload to show new post
					window.location.reload();
				}
			} catch (e) {
				console.error('post:', e);
			} finally {
				composerSend.disabled = false;
				composerSend.textContent = 'Postar';
			}
		});
	}

	/* ═══ Invite / Share ═══ */
	document.addEventListener('click', (e) => {
		const btn = e.target.closest('[data-action="share"]');
		if (!btn) return;

		const url = window.location.href;
		if (navigator.share) {
			navigator.share({ title: document.title, url: url });
		} else if (navigator.clipboard) {
			navigator.clipboard.writeText(url).then(() => {
				btn.innerHTML = '<i class="ri-check-line"></i> Copiado';
				setTimeout(() => btn.innerHTML = '<i class="ri-share-forward-line"></i>', 2000);
			});
		}
	});

})();
</script>

</body>
</html>
