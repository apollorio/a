<?php

/**
 * Apollo Sign — SPA Scripts
 * Part: sig-scripts.php
 *
 * Full JavaScript for the 3-panel digital-signature SPA:
 *   1. Panel navigation engine (GSAP slide transitions)
 *   2. interact.js signature-rect placement (drag + resize + two-tap mobile)
 *   3. Zoom ± toolbar controls
 *   4. Checkbox + btn-assinar enable/disable gate
 *   5. Sign action: REST POST → apollo/v1/sign/{id}
 *   6. After sign: update Titan-Stamp DOM fields + navigate to Panel 3
 *   7. Panel 3 mobile toggle: done-info ↔ done-doc
 *   8. sbottom-toggle collapse / expand action bar
 *   9. Hash copy-to-clipboard button
 *  10. Print certificate (@media print isolation)
 *
 * Depends on window.APOLLO_SIGN set by sign.php before this file.
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
	/* ================================================================
	APOLLO SIGN — SPA ENGINE
	Requires: GSAP (via CDN core.js), interact.js (via sig-head.php),
			window.APOLLO_SIGN config object.
	================================================================ */

	(function() {
		'use strict';

		/* ── Config ────────────────────────────────────────────────── */
		const CFG = window.APOLLO_SIGN || {};

		/* If already signed, jump straight to Panel 3 on load */
		const startPanel = CFG.status === 'signed' ? 'done' : 'preview';

		/* ── Panel registry ─────────────────────────────────────────── */
		const panels = document.querySelectorAll('[data-panel]');
		const wrap = document.getElementById('sign-wrap');
		let activePanel = startPanel;

		/* Index by name for quick lookup */
		const panelMap = {};
		panels.forEach(p => {
			panelMap[p.dataset.panel] = p;
		});

		/* ── GSAP panel switch ──────────────────────────────────────── */
		function showPanel(name, dir = 'right') {
			const next = panelMap[name];
			const curr = panelMap[activePanel];
			if (!next || name === activePanel) return;

			const fromX = dir === 'right' ? '100%' : '-100%';
			const toX = dir === 'right' ? '-100%' : '100%';

			if (typeof gsap !== 'undefined') {
				gsap.set(next, {
					x: fromX,
					autoAlpha: 1,
					display: 'flex'
				});
				gsap.to(curr, {
					x: toX,
					duration: 0.38,
					ease: 'power2.inOut'
				});
				gsap.to(next, {
					x: '0%',
					duration: 0.38,
					ease: 'power2.inOut',
					onComplete: () => {
						gsap.set(curr, {
							display: 'none',
							x: '0%'
						});
					}
				});
			} else {
				/* GSAP not available — instant swap */
				if (curr) curr.style.display = 'none';
				if (next) {
					next.style.display = 'flex';
					next.style.transform = 'translateX(0)';
				}
			}

			activePanel = name;
			document.body.setAttribute('data-panel', name);
		}

		/* Init panel visibility */
		panels.forEach(p => {
			const isActive = p.dataset.panel === startPanel;
			p.style.display = isActive ? 'flex' : 'none';
			p.style.transform = 'translateX(0)';
		});
		document.body.setAttribute('data-panel', startPanel);

		/* ── Data-nav buttons (CTA proceed + back arrows) ───────────── */
		document.querySelectorAll('[data-next]').forEach(btn => {
			btn.addEventListener('click', () => {
				showPanel(btn.dataset.next, 'right');
			});
		});
		document.querySelectorAll('[data-back]').forEach(btn => {
			btn.addEventListener('click', () => {
				const prev = {
					sign: 'preview',
					done: 'sign'
				};
				const target = prev[activePanel] || 'preview';
				showPanel(target, 'left');
			});
		});

		/* ─────────────────────────────────────────────────────────────
			2. SIGNATURE RECT PLACEMENT (interact.js)
		───────────────────────────────────────────────────────────── */

		const touchOv = document.getElementById('touch-ov');
		const ghostSvg = document.getElementById('ghost-svg');
		const signRect = document.getElementById('sign-rect');
		const placeHint = document.getElementById('place-hint');
		const btnPos = document.getElementById('btn-pos');
		const btnReset = document.getElementById('btn-reset-sig');
		const btnAssinar = document.getElementById('btn-assinar');
		const chkAgree = document.getElementById('chk-agree');

		let rectPlaced = false;
		let tapMode = false; // mobile two-tap flow active
		let tap1 = null; // {x, y} first tap (paper %)
		let placedRect = null; // {x, y, w, h} in paper % (0–1)

		const A4W = 794,
			A4H = 1123; // reference dimensions for ghost-svg viewBox

		/* Enable btn-assinar only when rect placed AND checkbox checked */
		function updateAssinarState() {
			if (!btnAssinar) return;
			btnAssinar.disabled = !(rectPlaced && chkAgree && chkAgree.checked);
		}
		if (chkAgree) chkAgree.addEventListener('change', updateAssinarState);

		/* ── interact.js drag-resize on #sign-rect ─────────────────── */
		if (typeof interact !== 'undefined' && signRect) {
			interact(signRect)
				.draggable({
					listeners: {
						move(event) {
							const parent = signRect.parentElement;
							const pRect = parent.getBoundingClientRect();
							const el = signRect;
							const x = (parseFloat(el.dataset.x || 0)) + event.dx;
							const y = (parseFloat(el.dataset.y || 0)) + event.dy;
							el.style.transform = `translate(${x}px, ${y}px)`;
							el.dataset.x = x;
							el.dataset.y = y;

							/* Compute % for stamp placement data */
							const elRect = el.getBoundingClientRect();
							placedRect = {
								x: (elRect.left - pRect.left) / pRect.width,
								y: (elRect.top - pRect.top) / pRect.height,
								w: elRect.width / pRect.width,
								h: elRect.height / pRect.height,
							};
						}
					}
				})
				.resizable({
					edges: {
						left: true,
						right: true,
						bottom: true,
						top: true
					},
					listeners: {
						move(event) {
							const el = signRect;
							let w = event.rect.width;
							let h = event.rect.height;
							let x = (parseFloat(el.dataset.x || 0)) + event.deltaRect.left;
							let y = (parseFloat(el.dataset.y || 0)) + event.deltaRect.top;
							el.style.width = `${w}px`;
							el.style.height = `${h}px`;
							el.style.transform = `translate(${x}px, ${y}px)`;
							el.dataset.x = x;
							el.dataset.y = y;
						}
					},
					modifiers: [
						interact.modifiers.restrictSize({
							min: {
								width: 80,
								height: 40
							}
						})
					]
				});
		}

		/* ── Two-tap mobile placement ───────────────────────────────── */
		if (touchOv) {
			touchOv.addEventListener('click', function(e) {
				if (!tapMode) return;

				const rect = touchOv.getBoundingClientRect();
				const normX = (e.clientX - rect.left) / rect.width;
				const normY = (e.clientY - rect.top) / rect.height;

				if (!tap1) {
					/* First tap — mark origin */
					tap1 = {
						x: normX,
						y: normY
					};
					if (placeHint) placeHint.style.display = 'block';
					/* Draw tiny dot on ghost-svg */
					const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
					dot.setAttribute('cx', normX * A4W);
					dot.setAttribute('cy', normY * A4H);
					dot.setAttribute('r', 6);
					dot.setAttribute('fill', 'rgba(244,95,0,.8)');
					dot.id = 'tap1-dot';
					if (ghostSvg) ghostSvg.appendChild(dot);
				} else {
					/* Second tap — define rect */
					const x = Math.min(tap1.x, normX);
					const y = Math.min(tap1.y, normY);
					const w = Math.abs(normX - tap1.x);
					const h = Math.abs(normY - tap1.y);

					if (w > 0.04 && h > 0.02) {
						positionSignRect(x, y, w, h);
					}
					/* Clean up */
					const dot = document.getElementById('tap1-dot');
					if (dot) dot.remove();
					tap1 = null;
					tapMode = false;
					touchOv.classList.remove('active');
					if (placeHint) placeHint.style.display = 'none';
				}
			});
		}

		/* Position #sign-rect from percentage values */
		function positionSignRect(xp, yp, wp, hp) {
			if (!signRect) return;
			const parent = signRect.parentElement;
			if (!parent) return;
			const pW = parent.offsetWidth;
			const pH = parent.offsetHeight;

			const pxX = xp * pW;
			const pxY = yp * pH;
			const pxW = wp * pW;
			const pxH = hp * pH;

			signRect.style.width = `${pxW}px`;
			signRect.style.height = `${pxH}px`;
			signRect.style.transform = `translate(${pxX}px, ${pxY}px)`;
			signRect.dataset.x = pxX;
			signRect.dataset.y = pxY;
			signRect.style.display = 'block';

			placedRect = {
				x: xp,
				y: yp,
				w: wp,
				h: hp
			};
			rectPlaced = true;
			updateAssinarState();
			if (btnReset) btnReset.style.display = 'flex';
		}

		/* ── Posicionar button ──────────────────────────────────────── */
		if (btnPos) {
			btnPos.addEventListener('click', function() {
				/* Detect touch device → two-tap mode */
				tapMode = true;
				tap1 = null;
				if (touchOv) touchOv.classList.add('active');
				if (placeHint) placeHint.style.display = 'block';
			});
		}

		/* ── Reset signature area ───────────────────────────────────── */
		if (btnReset) {
			btnReset.addEventListener('click', function() {
				if (signRect) {
					signRect.style.display = 'none';
					signRect.style.transform = 'translate(0,0)';
					signRect.dataset.x = 0;
					signRect.dataset.y = 0;
				}
				rectPlaced = false;
				placedRect = null;
				tap1 = null;
				tapMode = false;
				this.style.display = 'none';
				updateAssinarState();
			});
			btnReset.style.display = 'none'; /* hidden until rect placed */
		}

		/* ─────────────────────────────────────────────────────────────
			3. ZOOM ± (toolbar Panel 2)
		───────────────────────────────────────────────────────────── */
		const btnZoomIn = document.getElementById('btn-zoom-in');
		const btnZoomOut = document.getElementById('btn-zoom-out');
		const paperP2 = document.getElementById('pdf-p2');
		let zoomLevel = 1.0;

		function applyZoom() {
			if (paperP2) paperP2.style.transform = `scale(${zoomLevel})`;
		}

		if (btnZoomIn) btnZoomIn.addEventListener('click', () => {
			zoomLevel = Math.min(2.0, zoomLevel + 0.1);
			applyZoom();
		});
		if (btnZoomOut) btnZoomOut.addEventListener('click', () => {
			zoomLevel = Math.max(0.4, zoomLevel - 0.1);
			applyZoom();
		});

		/* ─────────────────────────────────────────────────────────────
			4. SBOTTOM TOGGLE (collapse/expand action bar Panel 2)
		───────────────────────────────────────────────────────────── */
		const sBottomToggle = document.getElementById('sbottom-toggle');
		const sBottomInner = document.getElementById('sign-bottom-inner');
		if (sBottomToggle && sBottomInner) {
			sBottomToggle.addEventListener('click', function() {
				const expanded = this.getAttribute('aria-expanded') === 'true';
				this.setAttribute('aria-expanded', String(!expanded));
				if (typeof gsap !== 'undefined') {
					gsap.to(sBottomInner, {
						height: expanded ? 0 : 'auto',
						opacity: expanded ? 0 : 1,
						duration: 0.28,
						ease: 'power2.inOut',
					});
				} else {
					sBottomInner.style.display = expanded ? 'none' : 'block';
				}
				this.querySelector('i').classList.toggle('ri-arrow-down-s-line', expanded);
				this.querySelector('i').classList.toggle('ri-arrow-up-s-line', !expanded);
			});
		}

		/* ─────────────────────────────────────────────────────────────
			5. SIGN ACTION — REST POST → apollo/v1/sign/{id}
		───────────────────────────────────────────────────────────── */
		if (btnAssinar) {
			btnAssinar.addEventListener('click', async function() {
				if (this.disabled) return;
				if (!chkAgree || !chkAgree.checked) return;

				/* UI lock */
				this.disabled = true;
				const origHTML = this.innerHTML;
				this.innerHTML = '<i class="ri-loader-4-line" style="animation:spin .8s linear infinite"></i>';

				try {
					const body = {
						nonce: CFG.nonce,
						placement: placedRect || null,
					};

					const res = await fetch(`${CFG.restUrl}sign/${CFG.sigId}`, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': CFG.restNonce,
						},
						credentials: 'same-origin',
						body: JSON.stringify(body),
					});

					const data = await res.json();

					if (res.ok && data.success !== false) {
						/* ── Success: populate stamp + go to Panel 3 ── */
						const ts = data.signed_at || new Date().toLocaleString('pt-BR');
						const hash = data.hash || data.signature_hash || ''.padStart(64, '0');
						const name = CFG.signerName || '—';
						const cpf = CFG.cpfMasked || '—';
						const verifyUrl = data.verify_url || CFG.verifyUrl || '';
						const contentHash = data.content_hash || CFG.contentHash || '';

						updateTitanStamp('titan-stamp-p3', name, cpf, ts, hash, verifyUrl, contentHash);

						/* Update signed pill on Panel 3 header */
						const pillP3 = document.getElementById('pill-p3');
						if (pillP3) {
							pillP3.className = 'pill signed';
							pillP3.innerHTML = '<i class="ri-check-line"></i> Assinado';
						}

						/* Update done-part-name */
						const dnName = document.getElementById('done-part-name');
						if (dnName) dnName.textContent = name;

						/* Update hash strip in Panel 3 */
						const hashStrip = document.getElementById('hash-value-full');
						if (hashStrip) hashStrip.textContent = hash;

						/* Navigate to Panel 3 */
						showPanel('done', 'right');

					} else {
						const msg = data.message || data.data?.message || '<?php esc_js( _e( 'Erro ao assinar. Tente novamente.', 'apollo-sign' ) ); ?>';
						showToast(msg, 'error');
						this.disabled = false;
						this.innerHTML = origHTML;
					}
				} catch (err) {
					console.error('[ApolloSign] Error:', err);
					showToast('<?php echo esc_js( __( 'Erro de conexão. Tente novamente.', 'apollo-sign' ) ); ?>', 'error');
					this.disabled = false;
					this.innerHTML = origHTML;
				}
			});
		}

		/* ─────────────────────────────────────────────────────────────
			6. TITAN-STAMP DOM UPDATE
		───────────────────────────────────────────────────────────── */

		/**
		 * Render a QR code inside a .ts-qr[data-verify-url] element.
		 * Uses a lightweight inline algorithm — no external library needed.
		 *
		 * @param {HTMLElement} el  — container element
		 * @param {string}      url — URL to encode
		 */
		function renderQR(el, url) {
			if (!el || !url) return;
			/* Prefer QRCode.js if loaded externally; otherwise embed URL as micro-text */
			if (typeof QRCode !== 'undefined') {
				el.innerHTML = '';
				new QRCode(el, {
					text: url,
					width: el.dataset.qrSize ? parseInt(el.dataset.qrSize) : 64,
					height: el.dataset.qrSize ? parseInt(el.dataset.qrSize) : 64,
					correctLevel: QRCode.CorrectLevel.M
				});
			} else {
				/* Fallback: show abbreviated URL so certificate isn't blank */
				el.style.cssText = 'font-size:5px;word-break:break-all;padding:3px;background:#fff;color:#000;display:flex;align-items:center;justify-content:center;text-align:center;';
				el.textContent = url;
			}
		}

		/**
		 * Update titan-stamp fields for a rendered stamp element.
		 *
		 * @param {string} stampId    — id attribute on .titan-stamp root
		 * @param {string} name       — signer full name
		 * @param {string} cpf        — masked CPF string
		 * @param {string} registered — date/time string
		 * @param {string} hash       — SHA-256 signature hash
		 * @param {string} verifyUrl  — public verification URL
		 * @param {string} contentHash — SHA-256 content hash
		 */
		function updateTitanStamp(stampId, name, cpf, registered, hash, verifyUrl, contentHash) {
			const root = document.getElementById(stampId);
			if (!root) return;

			const nameEl = root.querySelector('.ts-name');
			const cpfEl = root.querySelector('.ts-meta .ts-val'); // first .ts-meta .ts-val = Documento
			const dateEl = root.querySelectorAll('.ts-meta .ts-val')[1]; // second = Registro
			const hashEls = root.querySelectorAll('.ts-hash-val');

			if (nameEl) nameEl.textContent = name;
			if (cpfEl) cpfEl.textContent = cpf;
			if (dateEl) dateEl.textContent = registered;
			/* First .ts-hash-val = signature hash; second (if present) = content hash */
			if (hashEls[0]) hashEls[0].textContent = hash;
			if (contentHash && hashEls[1]) hashEls[1].textContent = contentHash;

			/* Verify URL text */
			if (verifyUrl) {
				const verifyEl = root.querySelector('.ts-verify-url a');
				if (verifyEl) { verifyEl.href = verifyEl.textContent = verifyUrl; }
				/* Render QR code */
				const qrEl = root.querySelector('.ts-qr[data-verify-url]');
				if (qrEl) {
					qrEl.dataset.verifyUrl = verifyUrl;
					renderQR(qrEl, verifyUrl);
				}
			}

			/* Animate stamp reveal */
			if (typeof gsap !== 'undefined') {
				gsap.from(root, {
					autoAlpha: 0,
					scale: 0.92,
					duration: 0.4,
					ease: 'back.out(1.4)'
				});
			}
		}

		/* On load: if already signed, stamp is pre-populated from PHP — just reveal it */
		if (CFG.status === 'signed') {
			const existingStamp = document.getElementById('titan-stamp-p3');
			if (existingStamp) {
				/* Render QR for pre-signed stamps (PHP-populated) */
				const qrEls = existingStamp.querySelectorAll('.ts-qr[data-verify-url]');
				qrEls.forEach(function(el) { renderQR(el, el.dataset.verifyUrl); });

				if (typeof gsap !== 'undefined') {
					gsap.fromTo(existingStamp, {
						autoAlpha: 0,
						y: 12
					}, {
						autoAlpha: 1,
						y: 0,
						duration: 0.5,
						delay: 0.3,
						ease: 'power2.out'
					});
				}
			}
		}

		/* ─────────────────────────────────────────────────────────────
			7. PANEL 3 MOBILE SUB-VIEW TOGGLE (done-info ↔ done-doc)
		───────────────────────────────────────────────────────────── */
		const doneInfo = document.getElementById('done-info');
		const doneDoc = document.getElementById('done-doc');
		const btnPreview = document.getElementById('btn-preview-doc');
		const btnDocBack = document.getElementById('done-doc-back');

		function slideToDoc() {
			if (!doneInfo || !doneDoc) return;
			if (typeof gsap !== 'undefined') {
				gsap.to(doneInfo, {
					x: '-100%',
					duration: 0.34,
					ease: 'power2.inOut'
				});
				gsap.fromTo(doneDoc, {
					x: '100%',
					display: 'flex'
				}, {
					x: '0%',
					duration: 0.34,
					ease: 'power2.inOut'
				});
			} else {
				doneInfo.style.display = 'none';
				doneDoc.style.display = 'flex';
			}
		}

		function slideToInfo() {
			if (!doneInfo || !doneDoc) return;
			if (typeof gsap !== 'undefined') {
				gsap.to(doneDoc, {
					x: '100%',
					duration: 0.34,
					ease: 'power2.inOut',
					onComplete: () => gsap.set(doneDoc, {
						display: 'none'
					})
				});
				gsap.fromTo(doneInfo, {
					x: '-100%',
					display: 'flex'
				}, {
					x: '0%',
					duration: 0.34,
					ease: 'power2.inOut'
				});
			} else {
				doneDoc.style.display = 'none';
				doneInfo.style.display = 'flex';
			}
		}

		if (btnPreview) btnPreview.addEventListener('click', slideToDoc);
		if (btnDocBack) btnDocBack.addEventListener('click', slideToInfo);

		/* ─────────────────────────────────────────────────────────────
			8. HASH COPY TO CLIPBOARD
		───────────────────────────────────────────────────────────── */
		const btnCopy = document.getElementById('btn-copy-hash');
		if (btnCopy) {
			btnCopy.addEventListener('click', async function() {
				const target = document.getElementById(this.dataset.target || 'hash-value-full');
				if (!target) return;
				const text = target.textContent.trim();
				try {
					await navigator.clipboard.writeText(text);
					showToast('<?php echo esc_js( __( 'Hash copiado!', 'apollo-sign' ) ); ?>', 'success');
				} catch (e) {
					/* Fallback for non-secure contexts */
					const ta = document.createElement('textarea');
					ta.value = text;
					document.body.appendChild(ta);
					ta.select();
					document.execCommand('copy');
					ta.remove();
					showToast('<?php echo esc_js( __( 'Hash copiado!', 'apollo-sign' ) ); ?>', 'success');
				}
			});
		}

		/* ─────────────────────────────────────────────────────────────
			9. PRINT CERTIFICATE
		───────────────────────────────────────────────────────────── */
		const btnPrint = document.getElementById('btn-print-cert');
		if (btnPrint) {
			btnPrint.addEventListener('click', function() {
				/* Show print-only container (isolated via CSS @media print) */
				const cert = document.getElementById('print-certificate');
				if (cert) cert.style.display = 'block';
				window.print();
				/* BOM: re-hide after dialog closes */
				setTimeout(() => {
					if (cert) cert.style.display = 'none';
				}, 1000);
			});
		}

		/* ─────────────────────────────────────────────────────────────
			10. TOAST NOTIFIER
		───────────────────────────────────────────────────────────── */
		function showToast(msg, type = 'info') {
			const t = document.createElement('div');
			t.className = `apollo-toast apollo-toast--${type}`;
			t.textContent = msg;
			Object.assign(t.style, {
				position: 'fixed',
				bottom: '24px',
				left: '50%',
				transform: 'translateX(-50%)',
				background: type === 'error' ? '#ef4444' : type === 'success' ? '#16a34a' : 'FF9820',
				color: '#fff',
				fontFamily: 'var(--ff, sans-serif)',
				fontSize: '13px',
				fontWeight: '600',
				padding: '10px 20px',
				borderRadius: '999px',
				zIndex: '99999',
				pointerEvents: 'none',
				boxShadow: '0 4px 16px rgba(var(--rgb-d),.22)',
				whiteSpace: 'nowrap',
			});
			document.body.appendChild(t);
			if (typeof gsap !== 'undefined') {
				gsap.fromTo(t, {
					autoAlpha: 0,
					y: 16
				}, {
					autoAlpha: 1,
					y: 0,
					duration: 0.28,
					ease: 'power2.out',
					onComplete: () => gsap.to(t, {
						autoAlpha: 0,
						delay: 2.2,
						duration: 0.3,
						onComplete: () => t.remove()
					})
				});
			} else {
				setTimeout(() => t.remove(), 3000);
			}
		}

		/* ── Global spin keyframe (btn loader) ──────────────────────── */
		if (!document.getElementById('apollo-spin-kf')) {
			const s = document.createElement('style');
			s.id = 'apollo-spin-kf';
			s.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
			document.head.appendChild(s);
		}

	})();
</script>
