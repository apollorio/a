<?php
/**
 * Template Part: Chat::Rio — Premium GSAP Animations
 *
 * Luxury-grade motion design: page entrance, header cascade,
 * thread stagger reveals, message bubble micro-interactions,
 * aurora glow, modal transitions, compose bar reveals.
 *
 * @package Apollo\Chat
 * @since   2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
?>
<script>
/* global MutationObserver, gsap, ApolloTextFX */
(function() {
	'use strict';

	var observers = [];

	/* ── Kick the initial loading text cycle on .ac-thread-list ── */
	function initLoadingTextFX() {
		if (typeof ApolloTextFX === 'undefined' || typeof gsap === 'undefined') return;
		var txtEl = document.querySelector('.ac-thread-list .ac-loading-txt');
		if (!txtEl) return;
		ApolloTextFX.loading(txtEl, [
			'Carregando conversas...',
			'Conectando ao Bate-Papo::rio...',
			'Carregando contatos...',
			'Sincronizando mensagens...'
		], { interval: 1.35, fadeTime: 0.18 });
	}

	/* ── Watch for typing indicator visibility changes ── */
	function initTypingIndicatorFX() {
		if (typeof gsap === 'undefined') return;

		var moTyping = new MutationObserver(function(mutations) {
			mutations.forEach(function(m) {
				if (m.type === 'attributes' && m.attributeName === 'class') {
					var el   = m.target;
					var dots = el.querySelectorAll('.ac-dot');
					if (el.classList.contains('show') && dots.length) {
						/* Pulse the dots in a continuous bounce loop */
						gsap.killTweensOf(dots);
						gsap.to(dots, {
							y:        -5,
							duration: 0.45,
							ease:     'sine.inOut',
							stagger:  { each: 0.15, repeat: -1, yoyo: true }
						});
					} else {
						gsap.killTweensOf(dots);
						gsap.to(dots, { y: 0, duration: 0.15 });
					}
				}
			});
		});

		/* Observe the typing indicator once chat renders it */
		function watchTypingEl() {
			var typingEl = document.getElementById('ac-typing');
			if (typingEl) {
				moTyping.observe(typingEl, { attributes: true });
				observers.push(moTyping);
			} else {
				/* Not in DOM yet — wait for chat.js to inject it */
				var bodyMo = new MutationObserver(function(muts) {
					muts.forEach(function(m) {
						m.addedNodes.forEach(function(node) {
							if (node.nodeType === 1) {
								var found = node.id === 'ac-typing'
									? node
									: node.querySelector && node.querySelector('#ac-typing');
								if (found) {
									bodyMo.disconnect();
									moTyping.observe(found, { attributes: true });
									observers.push(moTyping);
								}
							}
						});
					});
				});
				bodyMo.observe(document.body, { childList: true, subtree: true });
				observers.push(bodyMo);
			}
		}
		watchTypingEl();
	}

	function initChatAnimations() {
		if (typeof gsap === 'undefined') return;

		/* ═══════════════════════════════════════════════════════════════
			0. ENTRANCE — Sidebar header cascade
			═══════════════════════════════════════════════════════════════ */
		var tl = gsap.timeline({
			delay: 0.1,
			defaults: {
				ease: 'power3.out'
			}
		});

		tl.fromTo('.ac-sidebar-header', {
				opacity: 0,
				y: -15
			}, {
				opacity: 1,
				y: 0,
				duration: 0.5
			})
			.fromTo('.ac-thread-list', {
				opacity: 0
			}, {
				opacity: 1,
				duration: 0.4
			}, '-=0.2');

		/* ═══════════════════════════════════════════════════════════════
			1. THREAD LIST — Observe new items and stagger-reveal
			═══════════════════════════════════════════════════════════════ */
		var threadList = document.querySelector('.ac-thread-list');
		if (threadList) {
			var threadObs = new MutationObserver(function(mutations) {
				mutations.forEach(function(mut) {
					var newThreads = [];
					mut.addedNodes.forEach(function(node) {
						if (node.nodeType === 1 && node.classList && node.classList
							.contains('ac-thread')) {
							gsap.set(node, {
								opacity: 0,
								y: 14
							});
							newThreads.push(node);
						}
					});
					if (newThreads.length > 0) {
						gsap.to(newThreads, {
							opacity: 1,
							y: 0,
							duration: 0.4,
							stagger: 0.04,
							ease: 'power3.out',
							clearProps: 'transform'
						});
					}
				});
			});
			threadObs.observe(threadList, {
				childList: true
			});
			observers.push(threadObs);
		}

		/* Message entrance handled in chat.js (slide-up from below) */

		/* ═══════════════════════════════════════════════════════════════
			3. CHAT HEADER — Slide in when thread opens
			═══════════════════════════════════════════════════════════════ */
		var chatHeader = document.querySelector('.ac-chat-header');
		if (chatHeader) {
			var headerObs = new MutationObserver(function() {
				if (chatHeader.style.display !== 'none') {
					gsap.fromTo(chatHeader, {
						opacity: 0,
						y: -12
					}, {
						opacity: 1,
						y: 0,
						duration: 0.35,
						ease: 'power2.out'
					});
				}
			});
			headerObs.observe(chatHeader, {
				attributes: true,
				attributeFilter: ['style']
			});
			observers.push(headerObs);
		}

		/* ═══════════════════════════════════════════════════════════════
			4. COMPOSE BAR — Reveal when thread opens
			═══════════════════════════════════════════════════════════════ */
		var compose = document.querySelector('.ac-compose');
		if (compose) {
			var composeObs = new MutationObserver(function() {
				if (compose.style.display !== 'none') {
					gsap.fromTo(compose, {
						opacity: 0,
						y: 14
					}, {
						opacity: 1,
						y: 0,
						duration: 0.4,
						ease: 'power3.out',
						delay: 0.08
					});
				}
			});
			composeObs.observe(compose, {
				attributes: true,
				attributeFilter: ['style']
			});
			observers.push(composeObs);
		}

		/* ═══════════════════════════════════════════════════════════════
			5. SEND BUTTON — Micro-interaction pulse
			═══════════════════════════════════════════════════════════════ */
		var sendBtn = document.querySelector('.ac-send-btn');
		if (sendBtn && !(window.ApolloChatMotion && !window.ApolloChatMotion.reduced)) {
			sendBtn.addEventListener('click', function() {
				gsap.fromTo(sendBtn, {
					scale: 1
				}, {
					scale: 0.85,
					duration: 0.1,
					yoyo: true,
					repeat: 1,
					ease: 'power2.inOut'
				});
			});
		}

		/* ═══════════════════════════════════════════════════════════════
			6. MODAL — Premium open/close transitions
			═══════════════════════════════════════════════════════════════ */
		var modalOverlay = document.querySelector('.ac-modal-overlay');
		if (modalOverlay) {
			var modal = modalOverlay.querySelector('.ac-modal');
			if (modal) {
				var modalObs = new MutationObserver(function() {
					if (modalOverlay.classList.contains('show')) {
						gsap.fromTo(modal, {
							opacity: 0,
							y: 30,
							scale: 0.95
						}, {
							opacity: 1,
							y: 0,
							scale: 1,
							duration: 0.4,
							ease: 'power3.out'
						});
					}
				});
				modalObs.observe(modalOverlay, {
					attributes: true,
					attributeFilter: ['class']
				});
				observers.push(modalObs);
			}
		}

		/* Starfield / sparkle spawners removed — light Apple/Figma chat shell */

	} /* end initChatAnimations */

	/* ── Reaction emoji life: entrance + random idle (4–20s) ── */
	var reactionTimers = [];
	var reducedMotion = false;
	try {
		reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	} catch (eRM) {}

	function clearReactionTimers() {
		reactionTimers.forEach(function (t) { clearTimeout(t); });
		reactionTimers = [];
	}

	function randomIdleEffect(el) {
		if (!el || !el.isConnected || typeof gsap === 'undefined') return;
		var pick = Math.floor(Math.random() * 6);
		gsap.killTweensOf(el);
		if (pick === 0) {
			gsap.to(el, { y: -5, duration: 0.28, yoyo: true, repeat: 1, ease: 'power2.out' });
		} else if (pick === 1) {
			gsap.to(el, { rotation: 14, duration: 0.18, yoyo: true, repeat: 3, ease: 'sine.inOut' });
		} else if (pick === 2) {
			gsap.to(el, { scale: 1.22, duration: 0.22, yoyo: true, repeat: 1, ease: 'back.out(2)' });
		} else if (pick === 3) {
			gsap.fromTo(el, { x: 0 }, { x: 3, duration: 0.08, yoyo: true, repeat: 5, ease: 'power1.inOut' });
		} else if (pick === 4) {
			gsap.to(el, { y: -3, rotation: -8, duration: 0.35, yoyo: true, repeat: 1, ease: 'sine.inOut' });
		} else {
			gsap.fromTo(el, { scale: 1 }, { scale: 1.12, duration: 0.45, yoyo: true, repeat: 1, ease: 'elastic.out(1, 0.45)' });
		}
	}

	function scheduleReactionLife(el) {
		if (reducedMotion || !el || !el.isConnected) return;
		var delay = 4000 + Math.random() * 16000;
		var t = setTimeout(function () {
			randomIdleEffect(el);
			scheduleReactionLife(el);
		}, delay);
		reactionTimers.push(t);
	}

	function animateReactionEnter(el) {
		if (!el || typeof gsap === 'undefined') return;
		if (reducedMotion) {
			gsap.set(el, { opacity: 1, scale: 1, y: 0 });
			return;
		}
		gsap.fromTo(el, {
			opacity: 0,
			scale: 0.35,
			y: 10,
			rotation: -12
		}, {
			opacity: 1,
			scale: 1,
			y: 0,
			rotation: 0,
			duration: 0.45,
			ease: 'back.out(1.7)',
			onComplete: function () {
				scheduleReactionLife(el);
			}
		});
		/* #region agent log */
		fetch('http://127.0.0.1:7754/ingest/da9d552b-a038-4061-bf95-e47d2c529b38', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '161c5c' },
			body: JSON.stringify({
				sessionId: '161c5c',
				runId: 'react-fx-208',
				hypothesisId: 'R1',
				location: 'scripts.php:animateReactionEnter',
				message: 'reaction entrance',
				data: {
					emoji: el.getAttribute('data-emoji') || '',
					left: el.parentElement ? getComputedStyle(el.parentElement).left : null,
					bottom: el.parentElement ? getComputedStyle(el.parentElement).bottom : null,
					transform: el.parentElement ? getComputedStyle(el.parentElement).transform : null
				},
				timestamp: Date.now()
			})
		}).catch(function () {});
		/* #endregion */
	}

	function initReactionFX() {
		if (typeof gsap === 'undefined') return;
		var area = document.querySelector('.ac-messages');
		if (!area) return;

		function bootExisting() {
			clearReactionTimers();
			area.querySelectorAll('.ac-reaction').forEach(function (el) {
				if (el.dataset.acFx === '1') {
					scheduleReactionLife(el);
					return;
				}
				el.dataset.acFx = '1';
				animateReactionEnter(el);
			});
		}

		var reactObs = new MutationObserver(function (mutations) {
			mutations.forEach(function (mut) {
				mut.addedNodes.forEach(function (node) {
					if (node.nodeType !== 1) return;
					var list = [];
					if (node.classList && node.classList.contains('ac-reaction')) list.push(node);
					if (node.querySelectorAll) {
						node.querySelectorAll('.ac-reaction').forEach(function (r) { list.push(r); });
					}
					list.forEach(function (el) {
						if (el.dataset.acFx === '1') return;
						el.dataset.acFx = '1';
						animateReactionEnter(el);
					});
				});
			});
		});
		reactObs.observe(area, { childList: true, subtree: true });
		observers.push(reactObs);
		bootExisting();
	}

	/* ── Cleanup function to disconnect all observers ── */
	function cleanupAnimations() {
		observers.forEach(function(obs) {
			if (obs) obs.disconnect();
		});
		observers = [];
		clearReactionTimers();
	}

	/* ── Wait for Apollo CDN to finish loading GSAP ── */
	if (typeof gsap !== 'undefined') {
		initChatAnimations();
		initReactionFX();
	} else {
		window.addEventListener('apollo:ready', function () {
			initChatAnimations();
			initReactionFX();
		}, {
			once: true
		});
	}

	/* ── Text FX: loading text + typing indicator ── */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initLoadingTextFX();
			initTypingIndicatorFX();
			if (typeof ApolloTextFX !== 'undefined') { ApolloTextFX.initTimeAgoRefresh(15000); }
		});
	} else {
		initLoadingTextFX();
		initTypingIndicatorFX();
		if (typeof ApolloTextFX !== 'undefined') { ApolloTextFX.initTimeAgoRefresh(15000); }
	}

	/* ── Cleanup on page unload ── */
	window.addEventListener('beforeunload', cleanupAnimations);

})();
</script>
