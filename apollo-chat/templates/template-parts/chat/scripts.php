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
			'Conectando ao Chat::Rio...',
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
			.fromTo('.ac-starfield', {
				opacity: 0,
				scaleY: 0.5
			}, {
				opacity: 1,
				scaleY: 1,
				duration: 0.6,
				ease: 'power2.out',
				transformOrigin: 'top center'
			}, '-=0.35')
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

		/* ═══════════════════════════════════════════════════════════════
			2. MESSAGE BUBBLES — Observe and animate entrance
			═══════════════════════════════════════════════════════════════ */
		var messagesArea = document.querySelector('.ac-messages');
		if (messagesArea) {
			var msgObs = new MutationObserver(function(mutations) {
				mutations.forEach(function(mut) {
					mut.addedNodes.forEach(function(node) {
						if (node.nodeType === 1 && node.classList && node.classList
							.contains('ac-msg-row')) {
							var isSent = node.classList.contains('sent');
							gsap.fromTo(node, {
								opacity: 0,
								y: 8,
								x: isSent ? 16 : -16,
								scale: 0.96
							}, {
								opacity: 1,
								y: 0,
								x: 0,
								scale: 1,
								duration: 0.35,
								ease: 'power3.out',
								clearProps: 'transform'
							});
						}
					});
				});
			});
			msgObs.observe(messagesArea, {
				childList: true
			});
			observers.push(msgObs);
		}

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
		if (sendBtn) {
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

		/* ═══════════════════════════════════════════════════════════════
			7. STARFIELD — Premium depth parallax + shooting stars
			═══════════════════════════════════════════════════════════════ */
		var starfield = document.querySelector('.ac-starfield');
		if (starfield) {
			// Parallax depth on thread scroll
			var threadListEl = document.querySelector('.ac-thread-list');
			if (threadListEl) {
				var layer1 = starfield.querySelector('.ac-stars-layer-1');
				var layer2 = starfield.querySelector('.ac-stars-layer-2');
				var layer3 = starfield.querySelector('.ac-stars-layer-3');
				var staticStars = starfield.querySelector('.ac-stars-static');
				threadListEl.addEventListener('scroll', function() {
					var s = threadListEl.scrollTop;
					if (layer1) gsap.to(layer1, {
						y: s * 0.08,
						duration: 0.3,
						ease: 'none',
						overwrite: 'auto'
					});
					if (layer2) gsap.to(layer2, {
						y: s * 0.05,
						duration: 0.3,
						ease: 'none',
						overwrite: 'auto'
					});
					if (layer3) gsap.to(layer3, {
						y: s * 0.03,
						duration: 0.3,
						ease: 'none',
						overwrite: 'auto'
					});
					if (staticStars) gsap.to(staticStars, {
						y: s * 0.01,
						duration: 0.3,
						ease: 'none',
						overwrite: 'auto'
					});
				}, {
					passive: true
				});
			}

			// Flare — random reposition each cycle
			var flare = starfield.querySelector('.ac-stars-flare');
			if (flare) {
				function repositionFlare() {
					gsap.set(flare, {
						left: (20 + Math.random() * 60) + '%',
						top: (15 + Math.random() * 60) + '%'
					});
				}
				repositionFlare();
				setInterval(repositionFlare, 4000);
			}

			// Shooting star — random streaks of light
			function spawnShootingStar() {
				var star = document.createElement('div');
				star.style.cssText =
					'position:absolute;width:1px;height:1px;background:#fff;border-radius:50%;pointer-events:none;z-index:3;box-shadow:0 0 6px 2px rgba(255,200,140,0.7),0 0 14px 4px rgba(244,95,0,0.3);';
				var startX = Math.random() * 100;
				var startY = Math.random() * 40;
				star.style.left = startX + '%';
				star.style.top = startY + '%';
				starfield.appendChild(star);

				var angle = 25 + Math.random() * 20; // degrees
				var dist = 60 + Math.random() * 80;
				var rad = angle * Math.PI / 180;
				var dx = Math.cos(rad) * dist;
				var dy = Math.sin(rad) * dist;

				gsap.fromTo(star, {
					opacity: 0,
					scale: 0.3,
					width: '1px',
					height: '1px'
				}, {
					opacity: 1,
					scale: 1,
					x: dx,
					y: dy,
					width: '40px',
					height: '1px',
					duration: 0.6 + Math.random() * 0.4,
					ease: 'power2.in',
					onComplete: function() {
						gsap.to(star, {
							opacity: 0,
							duration: 0.2,
							onComplete: function() {
								star.remove();
							}
						});
					}
				});
			}

			// Spawn shooting stars at random intervals
			function scheduleShootingStar() {
				var delay = 2000 + Math.random() * 5000;
				setTimeout(function() {
					spawnShootingStar();
					scheduleShootingStar();
				}, delay);
			}
			scheduleShootingStar();

			// Entrance animation for the starfield
			gsap.fromTo(starfield, {
				opacity: 0
			}, {
				opacity: 1,
				duration: 1.2,
				ease: 'power2.out',
				delay: 0.3
			});
		}

		/* ═══════════════════════════════════════════════════════════════
			8. HEADER + LAYOUT STARS — sparkle micro-pulses
			═══════════════════════════════════════════════════════════════ */
		// Spawn tiny sparkle particles in the header
		var sidebarHeader = document.querySelector('.ac-sidebar-header');
		if (sidebarHeader) {
			function spawnHeaderSparkle() {
				var sp = document.createElement('div');
				var size = 1 + Math.random() * 2;
				sp.style.cssText = 'position:absolute;width:' + size + 'px;height:' + size +
					'px;background:#fff;border-radius:50%;pointer-events:none;z-index:1;box-shadow:0 0 ' + (size *
						3) + 'px ' + size + 'px rgba(255,220,180,0.6);';
				sp.style.left = Math.random() * 100 + '%';
				sp.style.top = Math.random() * 100 + '%';
				sidebarHeader.appendChild(sp);

				gsap.fromTo(sp, {
					opacity: 0,
					scale: 0
				}, {
					opacity: 0.8,
					scale: 1.5,
					duration: 0.4 + Math.random() * 0.3,
					ease: 'power2.out',
					onComplete: function() {
						gsap.to(sp, {
							opacity: 0,
							scale: 0,
							duration: 0.6 + Math.random() * 0.4,
							ease: 'power1.in',
							onComplete: function() {
								sp.remove();
							}
						});
					}
				});
			}

			function scheduleHeaderSparkle() {
				setTimeout(function() {
					spawnHeaderSparkle();
					scheduleHeaderSparkle();
				}, 800 + Math.random() * 2000);
			}
			scheduleHeaderSparkle();
		}

		// Shooting stars across the layout (visible above sidebar)
		var acLayout = document.querySelector('.ac-layout');
		if (acLayout) {
			function spawnLayoutShootingStar() {
				var s = document.createElement('div');
				s.style.cssText =
					'position:absolute;width:1px;height:1px;background:#fff;border-radius:50%;pointer-events:none;z-index:1;opacity:0;box-shadow:0 0 4px 1px rgba(255,200,140,0.5);';
				var startX = Math.random() * 80;
				s.style.left = startX + '%';
				s.style.top = (Math.random() * 30) + '%';
				acLayout.appendChild(s);

				var angle = 20 + Math.random() * 30;
				var dist = 50 + Math.random() * 100;
				var rad = angle * Math.PI / 180;

				gsap.to(s, {
					opacity: 0.7,
					x: Math.cos(rad) * dist,
					y: Math.sin(rad) * dist,
					width: '30px',
					height: '1px',
					duration: 0.5 + Math.random() * 0.3,
					ease: 'power2.in',
					onComplete: function() {
						gsap.to(s, {
							opacity: 0,
							duration: 0.15,
							onComplete: function() {
								s.remove();
							}
						});
					}
				});
			}

			function scheduleLayoutShootingStar() {
				setTimeout(function() {
					spawnLayoutShootingStar();
					scheduleLayoutShootingStar();
				}, 3000 + Math.random() * 6000);
			}
			scheduleLayoutShootingStar();
		}

	} /* end initChatAnimations */

	/* ── Cleanup function to disconnect all observers ── */
	function cleanupAnimations() {
		observers.forEach(function(obs) {
			if (obs) obs.disconnect();
		});
		observers = [];
	}

	/* ── Wait for Apollo CDN to finish loading GSAP ── */
	if (typeof gsap !== 'undefined') {
		initChatAnimations();
	} else {
		window.addEventListener('apollo:ready', initChatAnimations, {
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
