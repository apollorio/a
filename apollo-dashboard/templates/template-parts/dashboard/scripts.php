<?php

/**
 * Dashboard Scripts — V2
 *
 * GSAP + ScrollTrigger init, page loader, post stagger, sidebar fade,
 * tab system with history.replaceState(), char counter with ring,
 * toggleWow, post submit, joinComuna, saveSettings.
 *
 * Variables expected: $active_tab, $avatar_url, $display_name, $username, $user_id
 *
 * @package Apollo\Dashboard
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
	(function() {
		'use strict';

		/* ── Tab map (slug → panel id) ── */
		const TAB_MAP = {
			'feed': 'panel-feed',
			'favoritos': 'panel-favs',
			'comunas': 'panel-comunas',
			'eventos': 'panel-events',
			'configuracoes': 'panel-settings'
		};

		const URL_MAP = {
			'feed': '/painel',
			'favoritos': '/painel/favoritos',
			'comunas': '/painel/comunas',
			'eventos': '/painel/eventos',
			'configuracoes': '/painel/configuracoes'
		};

		/* ── Active tab from PHP ── */
		let activeTab = '<?php echo esc_js( $active_tab ); ?>';

		/* ── DOM Ready ── */
		document.addEventListener('DOMContentLoaded', () => {

			/* ── Page Loader ── */
			const loader = document.getElementById('pageLoader');
			if (loader && typeof gsap !== 'undefined') {
				gsap.to(loader, {
					scaleY: 0,
					duration: 0.8,
					ease: 'power4.inOut',
					delay: 0.6,
					onComplete: () => {
						loader.style.display = 'none';
					}
				});
			} else if (loader) {
				setTimeout(() => {
					loader.style.display = 'none';
				}, 1000);
			}

			/* ── Activate initial tab ── */
			activateTab(activeTab, false);

			/* ── Tab Bar Clicks ── */
			document.querySelectorAll('.tab-item').forEach(tab => {
				tab.addEventListener('click', (e) => {
					e.preventDefault();
					const slug = tab.dataset.tab;
					if (slug) activateTab(slug, true);
				});
			});

			/* ── Compose: Character Counter ── */
			const composeInput = document.getElementById('composeInput');
			const charCount = document.getElementById('charCount');
			const charRing = document.getElementById('charRing');
			const btnPost = document.getElementById('btnPost');
			const MAX_CHARS = 280;
			const CIRCUMFERENCE = 62.83;

			if (composeInput) {
				composeInput.addEventListener('input', () => {
					const len = composeInput.value.length;
					const remaining = MAX_CHARS - len;
					if (charCount) charCount.textContent = remaining;
					if (charRing) {
						const offset = CIRCUMFERENCE - (len / MAX_CHARS) * CIRCUMFERENCE;
						charRing.style.strokeDashoffset = Math.max(0, offset);
						charRing.style.stroke = remaining < 0 ? '#ef4444' : remaining < 20 ? '#f97316' : '#f97316';
					}
					if (btnPost) btnPost.disabled = (len === 0 || len > MAX_CHARS);
				});
			}

			/* ── Post Submit ── */
			if (btnPost) {
				btnPost.addEventListener('click', () => {
					const text = composeInput?.value?.trim();
					if (!text) return;

					btnPost.disabled = true;
					btnPost.innerHTML = '<i class="ri-loader-4-line ri-spin"></i>';

					fetch('/wp-json/apollo/v1/social/posts', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
							},
							body: JSON.stringify({
								content: text
							})
						})
						.then(r => r.json())
						.then(data => {
							if (data && !data.code) {
								/* Optimistic: prepend post to feed */
								const feedCol = document.getElementById('feedColumn');
								const compose = feedCol?.querySelector('.compose');
								if (feedCol && compose) {
									const article = document.createElement('article');
									article.className = 'post-article';
									article.style.opacity = '0';
									article.innerHTML = `
								<div class="ap-avatar" style="background-image:url('<?php echo esc_url( $avatar_url ); ?>')"></div>
								<div class="ap-card">
									<section class="ap-user">
										<p><span class="ap-username"><?php echo esc_js( $display_name ); ?></span> <span class="ap-badge apollo">Apollo</span></p>
										<p class="ap-handle"><span class="uid">@<?php echo esc_js( $username ); ?></span> · agora</p>
									</section>
									<p class="ap-text">${escapeHTML(text)}</p>
									<div class="ap-actions">
										<button class="ap-act wow" onclick="toggleWow(this)"><i class="ri-brain-line"></i> <span>0</span></button>
										<button class="ap-act"><i class="ri-chat-4-line"></i> <span>0</span></button>
										<button class="ap-act"><i class="ri-share-forward-line"></i> <span>0</span></button>
									</div>
								</div>`;
									compose.insertAdjacentElement('afterend', article);
									gsap.to(article, {
										opacity: 1,
										y: 0,
										duration: 0.5,
										ease: 'power2.out'
									});
								}
								composeInput.value = '';
								if (charCount) charCount.textContent = MAX_CHARS;
								if (charRing) charRing.style.strokeDashoffset = CIRCUMFERENCE;
							}
						})
						.catch(console.error)
						.finally(() => {
							btnPost.disabled = true;
							btnPost.innerHTML = '<span>Postar</span>';
						});
				});
			}

			/* ── GSAP Animations ── */
			if (typeof gsap !== 'undefined') {
				/* Stagger posts on load */
				gsap.from('.post-article', {
					opacity: 0,
					y: 30,
					duration: 0.5,
					stagger: 0.08,
					ease: 'power2.out',
					delay: 0.8
				});

				/* Sidebar fade in */
				gsap.from('.sidebar-column .sb-card', {
					opacity: 0,
					x: 20,
					duration: 0.5,
					stagger: 0.12,
					ease: 'power2.out',
					delay: 1.0
				});

				/* Compose slide */
				gsap.from('.compose', {
					opacity: 0,
					y: -15,
					duration: 0.4,
					ease: 'power2.out',
					delay: 0.6
				});
			}
		});

		/* ── Tab Activation ── */
		function activateTab(slug, pushState) {
			/* Normalize slug */
			if (!TAB_MAP[slug]) slug = 'feed';

			/* Update activeTab */
			activeTab = slug;

			/* Hide all panels */
			document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

			/* Show target panel */
			const targetId = TAB_MAP[slug];
			const targetPanel = document.getElementById(targetId);
			if (targetPanel) targetPanel.classList.add('active');

			/* Update tab bar */
			document.querySelectorAll('.tab-item').forEach(a => {
				a.classList.toggle('active', a.dataset.tab === slug);
			});

			/* Update URL */
			if (pushState && URL_MAP[slug]) {
				history.replaceState(null, '', URL_MAP[slug]);
			}

			/* Re-run GSAP for the newly visible panel */
			if (typeof gsap !== 'undefined' && targetPanel) {
				const items = targetPanel.querySelectorAll('.post-article, .fav-card, .ev-card, .li-item, .stat-strip, .s-group');
				if (items.length) {
					gsap.from(items, {
						opacity: 0,
						y: 20,
						duration: 0.4,
						stagger: 0.06,
						ease: 'power2.out'
					});
				}
			}
		}

		/* ── Toggle Wow (Brain icon) ── */
		window.toggleWow = function(btn) {
			const icon = btn.querySelector('i');
			const span = btn.querySelector('span');
			const isActive = btn.classList.contains('active');
			let count = parseInt(span?.textContent) || 0;

			if (isActive) {
				btn.classList.remove('active');
				icon.className = 'ri-brain-line';
				count = Math.max(0, count - 1);
			} else {
				btn.classList.add('active');
				icon.className = 'ri-brain-fill';
				count++;
				/* Pulse animation */
				if (typeof gsap !== 'undefined') {
					gsap.fromTo(btn, {
						scale: 1
					}, {
						scale: 1.25,
						duration: 0.15,
						yoyo: true,
						repeat: 1,
						ease: 'power2.out'
					});
				}
			}
			if (span) span.textContent = count;

			/* API call */
			const postId = btn.dataset?.postId;
			if (postId) {
				fetch('/wp-json/apollo/v1/wow/toggle', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify({
						post_id: postId,
						type: 'wow'
					})
				}).catch(console.error);
			}
		};

		/* ── Join Comuna ── */
		window.joinComuna = function(communityId) {
			fetch('/wp-json/apollo/v1/groups/' + communityId + '/join', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					}
				})
				.then(r => r.json())
				.then(data => {
					if (data && !data.code) {
						/* Optimistic: change button */
						event.target.textContent = 'Entrou ✓';
						event.target.style.background = '#10b981';
						event.target.style.color = '#fff';
						event.target.disabled = true;
					}
				})
				.catch(console.error);
		};

		/* ── Save Settings ── */
		window.saveSettings = function() {
			const btn = document.getElementById('btnSaveSettings');
			if (btn) {
				btn.textContent = 'Salvando...';
				btn.disabled = true;
			}

			const payload = {
				display_name: document.getElementById('settingsName')?.value || '',
				email: document.getElementById('settingsEmail')?.value || '',
				location: document.getElementById('settingsLocation')?.value || '',
				genre: document.getElementById('settingsGenre')?.value || '',
				bio: document.getElementById('settingsBio')?.value || '',
				preferences: {
					dark_mode: document.getElementById('prefDark')?.classList.contains('on') ? 'on' : 'off',
					notifications: document.getElementById('prefNotif')?.classList.contains('on') ? 'on' : 'off',
					sound: document.getElementById('prefSound')?.classList.contains('on') ? 'on' : 'off',
					email_notif: document.getElementById('prefEmailNotif')?.classList.contains('on') ? 'on' : 'off',
					profile_visible: document.getElementById('prefVisible')?.classList.contains('on') ? 'on' : 'off'
				}
			};

			fetch('/wp-json/apollo/v1/users/me/settings', {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify(payload)
				})
				.then(r => r.json())
				.then(data => {
					if (btn) {
						btn.textContent = 'Salvo ✓';
						btn.style.background = '#10b981';
						setTimeout(() => {
							btn.textContent = 'Salvar alterações';
							btn.style.background = '';
							btn.disabled = false;
						}, 2000);
					}
				})
				.catch(err => {
					console.error(err);
					if (btn) {
						btn.textContent = 'Erro. Tente novamente';
						btn.style.background = '#ef4444';
						btn.disabled = false;
					}
				});
		};

		/* ── Escape HTML helper ── */
		function escapeHTML(str) {
			const div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		}

	})();
</script>
