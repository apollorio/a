/*!
 * Apollo SoundCloud — THE player runtime.
 * ---------------------------------------------------------------------------
 * One runtime for every SoundCloud player in the ecosystem. Replaces seven
 * hand-rolled implementations that each bound their own SC.Widget, each loaded
 * the SDK separately, and none of which could stop one another.
 *
 * WHAT IT GIVES YOU
 *   · Apollo's own UI. The iframe is a hidden transport, never seen.
 *   · preview mode — plays 25%..65% of the track (configurable per player)
 *   · full mode    — 0%..100%
 *   · exactly ONE player audible at a time, ecosystem-wide
 *   · lazy everything: the SDK loads on first play, the iframe is created on
 *     first play, metadata is fetched after paint
 *
 * WHY THE IFRAME AND NOT AN <audio>
 * SoundCloud closed open API registration — credentials are application-only
 * and selective. Without them there is no direct stream URL, so the Widget API
 * is the only way to control playback. It is also the only way to implement a
 * percentage-based preview at all: that needs getDuration(), which a plain
 * embed cannot report.
 *
 * PUBLIC API
 *   ApolloSC.play(el) · pause(el) · toggle(el) · stopAll() · mount(root)
 *   document events: apollo:sc:play · apollo:sc:pause · apollo:sc:ready
 */
(function (w, d) {
	'use strict';

	if (w.ApolloSC) { return; }

	var CFG = w.APOLLO_SC || {};
	var SDK = CFG.sdk || 'https://w.soundcloud.com/player/api.js';
	var MIN_SECONDS = parseInt(CFG.minSeconds, 10) || 20;

	/* The one currently-audible player. A social feed with two things playing
	   at once is a bug every time, and seven independent widgets could not
	   enforce this — none of them knew the others existed. */
	var current = null;

	/* ── SDK, loaded once, on demand ─────────────────────────────────────────
	   Three plugins used to load api.js on every page that MIGHT hold a player,
	   and a page carrying two of them loaded it twice. This resolves once and
	   every later caller gets the same promise. */
	var sdkPromise = null;
	function sdk() {
		if (sdkPromise) { return sdkPromise; }
		if (w.SC && w.SC.Widget) { sdkPromise = Promise.resolve(); return sdkPromise; }

		sdkPromise = new Promise(function (resolve, reject) {
			var existing = d.querySelector('script[src*="w.soundcloud.com/player/api.js"]');
			if (existing) {
				existing.addEventListener('load', function () { resolve(); });
				existing.addEventListener('error', reject);
				/* Already loaded before we attached — check directly. */
				if (w.SC && w.SC.Widget) { resolve(); }
				return;
			}
			var s = d.createElement('script');
			s.src = SDK;
			s.async = true;
			s.onload = function () { resolve(); };
			s.onerror = function () { reject(new Error('SoundCloud SDK blocked')); };
			d.head.appendChild(s);
		});
		return sdkPromise;
	}

	function emit(name, el, detail) {
		d.dispatchEvent(new CustomEvent(name, {
			detail: Object.assign({ el: el }, detail || {})
		}));
	}

	function setUi(el, playing) {
		el.classList.toggle('is-playing', !!playing);
		var btn = el.querySelector('[data-apsc-toggle]');
		if (!btn) { return; }
		var i = btn.querySelector('i');
		if (i) { i.className = playing ? 'ri-pause-fill' : 'ri-play-fill'; }
		btn.setAttribute('aria-label', playing
			? ((CFG.i18n && CFG.i18n.pause) || 'Pause')
			: ((CFG.i18n && CFG.i18n.play) || 'Play'));
	}

	/**
	 * Build the transport iframe on first play.
	 *
	 * Deliberately NOT rendered by PHP: fifteen players on /casa would mean
	 * fifteen SoundCloud connections before anyone clicked anything.
	 */
	function transport(el) {
		var slot = el.querySelector('[data-apsc-transport]');
		if (!slot) { return null; }

		var existing = slot.querySelector('iframe');
		if (existing) { return existing; }

		var src = slot.getAttribute('data-apsc-embed');
		if (!src) { return null; }

		var f = d.createElement('iframe');
		f.setAttribute('allow', 'autoplay');
		f.setAttribute('scrolling', 'no');
		f.setAttribute('frameborder', 'no');
		f.setAttribute('tabindex', '-1');
		f.setAttribute('aria-hidden', 'true');
		f.setAttribute('title', 'SoundCloud transport');
		f.width = '100%';
		f.height = '166';
		f.src = src;
		slot.appendChild(f);
		return f;
	}

	/**
	 * Attach a widget to a player element, once.
	 *
	 * Resolves with a state object holding the widget and the computed preview
	 * window. Duration is only knowable after READY, which is why the window is
	 * computed here and not in PHP.
	 */
	function bind(el) {
		if (el.__apsc) { return el.__apsc.ready; }

		var state = {
			widget: null,
			start: 0,      // ms
			end: 0,        // ms — 0 means "play to the end"
			duration: 0,
			mode: el.getAttribute('data-apsc-mode') === 'full' ? 'full' : 'preview',
			ready: null
		};
		el.__apsc = state;

		state.ready = sdk().then(function () {
			var frame = transport(el);
			if (!frame || !w.SC || !w.SC.Widget) {
				throw new Error('no transport');
			}

			var widget = w.SC.Widget(frame);
			state.widget = widget;

			return new Promise(function (resolve) {
				widget.bind(w.SC.Widget.Events.READY, function () {
					widget.getDuration(function (ms) {
						state.duration = ms || 0;

			var hold = parseInt(el.getAttribute('data-apsc-hold'), 10);
						var sPct = parseInt(el.getAttribute('data-apsc-start'), 10);
						var ePct = parseInt(el.getAttribute('data-apsc-end'), 10);
						if (isNaN(sPct)) { sPct = parseInt(CFG.startPct, 10) || 20; }
						if (isNaN(ePct)) { ePct = parseInt(CFG.endPct, 10) || 65; }

						if (!isNaN(hold) && hold > 0 && state.duration > MIN_SECONDS * 1000) {
							state.start = Math.floor(state.duration * (sPct / 100));
							state.end = Math.min(state.duration, state.start + hold * 1000);
						} else if (state.mode === 'preview' && state.duration > MIN_SECONDS * 1000) {
							state.start = Math.floor(state.duration * (sPct / 100));
							state.end = Math.floor(state.duration * (ePct / 100));
						} else {
							state.start = 0;
							state.end = 0;
						}

						var volAttr = parseInt(el.getAttribute('data-apsc-vol'), 10);
						state.volTarget = !isNaN(volAttr) ? volAttr : (parseInt(CFG.vol, 10) || 20);
						state.vol = 0;

						el.classList.add('is-ready');
						emit('apollo:sc:ready', el, { duration: state.duration });
						resolve(state);
					});
				});

				widget.bind(w.SC.Widget.Events.PLAY, function () {
					setUi(el, true);
					fadeVol(state, state.volTarget || 20, parseInt(CFG.fadeInMs, 10) || 420);
					emit('apollo:sc:play', el, { url: el.getAttribute('data-apsc-url') });
				});

				widget.bind(w.SC.Widget.Events.PAUSE, function () {
					setUi(el, false);
					emit('apollo:sc:pause', el);
				});

				widget.bind(w.SC.Widget.Events.FINISH, function () {
					setUi(el, false);
					if (current === el) { current = null; }
					emit('apollo:sc:pause', el, { finished: true });
				});

				/* The preview window is enforced HERE. There is no SoundCloud
				   API for "play from A to B" — the only way is to watch position
				   and stop. Also drives the progress bar, which shows the whole
				   track with the window marked rather than a 0-100% bar of the
				   excerpt: a bar that fills completely on a 40% slice is a lie. */
				widget.bind(w.SC.Widget.Events.PLAY_PROGRESS, function (e) {
					var pos = (e && e.currentPosition) || 0;

					if (state.end && pos >= state.end && !state.stopping) {
						state.stopping = true;
						fadeVol(state, 0, parseInt(CFG.fadeOutMs, 10) || 280, function () {
							try { widget.pause(); } catch (err) {}
							try { widget.seekTo(state.start); } catch (err2) {}
							setUi(el, false);
							if (current === el) { current = null; }
							emit('apollo:sc:pause', el, { finished: true });
						});
						return;
					}

					if (state.duration > 0) {
						var fill = el.querySelector('[data-apsc-fill]');
						if (fill) { fill.style.width = ((pos / state.duration) * 100) + '%'; }
					}
				});

				widget.bind(w.SC.Widget.Events.ERROR, function () {
					if (el.hasAttribute('data-apsc-manual-fallback')) {
						setUi(el, false);
						if (current === el) { current = null; }
						emit('apollo:sc:error', el);
					} else {
						fallback(el);
					}
				});
			});
		}).catch(function () {
			if (!el.hasAttribute('data-apsc-manual-fallback')) {
				fallback(el);
			} else {
				emit('apollo:sc:error', el);
			}
			throw new Error('apsc: transport unavailable');
		});

		return state.ready;
	}

	function fadeVol(state, to, ms, done) {
		if (!state || !state.widget) {
			if (typeof done === 'function') { done(); }
			return;
		}
		if (state.fadeTimer) {
			clearInterval(state.fadeTimer);
			state.fadeTimer = null;
		}
		var from = typeof state.vol === 'number' ? state.vol : 0;
		var t0 = (w.performance && performance.now) ? performance.now() : Date.now();
		ms = Math.max(80, ms || 300);
		state.fadeTimer = setInterval(function () {
			var now = (w.performance && performance.now) ? performance.now() : Date.now();
			var p = Math.min(1, (now - t0) / ms);
			var v = Math.round(from + (to - from) * p);
			state.vol = v;
			try { state.widget.setVolume(v); } catch (err) {}
			if (p >= 1) {
				clearInterval(state.fadeTimer);
				state.fadeTimer = null;
				if (typeof done === 'function') { done(); }
			}
		}, 40);
	}

	/**
	 * SDK blocked, or the widget errored — reveal the real embed.
	 *
	 * Out Now cards set data-apsc-never-reveal: the iframe stays a hidden
	 * transport. SoundCloud chrome on the rail is a UX defect, not a fallback.
	 */
	function fallback(el) {
		if (!el || el.hasAttribute('data-apsc-never-reveal')) {
			emit('apollo:sc:error', el);
			return;
		}
		if (el.classList.contains('is-fallback')) { return; }
		el.classList.add('is-fallback');

		var slot = el.querySelector('[data-apsc-transport]');
		if (!slot) { return; }

		transport(el);
		slot.hidden = false;
		slot.removeAttribute('aria-hidden');

		var frame = slot.querySelector('iframe');
		if (frame) {
			frame.removeAttribute('aria-hidden');
			frame.removeAttribute('tabindex');
			frame.setAttribute('title', 'SoundCloud');
		}

		var btn = el.querySelector('[data-apsc-toggle]');
		if (btn) { btn.hidden = true; }
	}

	function play(el) {
		if (!el) { return Promise.reject(new Error('no element')); }
		stopAll(el);
		return bind(el).then(function (state) {
			if (!state || !state.widget) {
				return Promise.reject(new Error('no widget'));
			}
			current = el;
			state.stopping = false;
			try { state.widget.setVolume(0); } catch (err) {}
			state.vol = 0;
			state.widget.seekTo(state.start || 0);
			state.widget.play();
			return state;
		}).catch(function (err) {
			if (!el.hasAttribute('data-apsc-manual-fallback')) {
				/* fallback() already ran */
			}
			return Promise.reject(err);
		});
	}

	/**
	 * Play and resolve when SC fires PLAY, or reject on error / timeout.
	 * Used by track-card orchestrator for chained fallbacks.
	 */
	function playAsync(el, timeoutMs) {
		timeoutMs = timeoutMs || 8000;
		return new Promise(function (resolve, reject) {
			if (!el) { reject(new Error('no element')); return; }

			var done = false;
			function finish(ok, payload) {
				if (done) { return; }
				done = true;
				d.removeEventListener('apollo:sc:play', onPlay);
				d.removeEventListener('apollo:sc:error', onErr);
				clearTimeout(timer);
				if (ok) { resolve(payload); } else { reject(payload); }
			}

			function onPlay(e) {
				if (e.detail && e.detail.el === el) {
					finish(true, el);
				}
			}

			function onErr(e) {
				if (e.detail && e.detail.el === el) {
					finish(false, new Error('sc widget error'));
				}
			}

			d.addEventListener('apollo:sc:play', onPlay);
			d.addEventListener('apollo:sc:error', onErr);

			var timer = setTimeout(function () {
				if (!el.classList.contains('is-playing')) {
					finish(false, new Error('sc timeout'));
				}
			}, timeoutMs);

			play(el).catch(function (err) {
				finish(false, err);
			});
		});
	}

	function pause(el) {
		if (!el || !el.__apsc || !el.__apsc.widget) { return; }
		var state = el.__apsc;
		if (state.stopping) {
			try { state.widget.pause(); } catch (err) {}
			if (current === el) { current = null; }
			return;
		}
		state.stopping = true;
		fadeVol(state, 0, parseInt(CFG.fadeOutMs, 10) || 280, function () {
			try { state.widget.pause(); } catch (err2) {}
			setUi(el, false);
			if (current === el) { current = null; }
		});
	}

	function stopAll(except) {
		if (current && current !== except) { pause(current); }
	}

	function toggle(el) {
		if (el.classList.contains('is-playing')) { pause(el); } else { play(el); }
	}

	/* ── delegated control ───────────────────────────────────────────────────
	   One listener for every player on the page, including any injected later
	   by a lightbox fragment — which is why this is delegated rather than bound
	   per element at load. */
	d.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-apsc-toggle]');
		if (!btn) { return; }
		var el = btn.closest('[data-apsc]');
		if (!el) { return; }
		e.preventDefault();
		toggle(el);
	});

	/* Autoplay opt-in, honoured only where the browser allows it. */
	function mount(root) {
		(root || d).querySelectorAll('[data-apsc][data-apsc-autoplay]').forEach(function (el) {
			if (!el.__apscAuto) { el.__apscAuto = true; play(el); }
		});
	}
	if ('loading' === d.readyState) {
		d.addEventListener('DOMContentLoaded', function () { mount(d); }, { once: true });
	} else {
		mount(d);
	}

	/* Something else started playing — a track preview, a radio stream. Stop. */
	d.addEventListener('apollo:audio:play', function (e) {
		if (!e.detail || e.detail.source !== 'soundcloud') { stopAll(null); }
	});

	/* Track orchestrator stopped — pause SC widget. */
	d.addEventListener('apollo:track:stop', function () {
		stopAll(null);
	});

	w.ApolloSC = {
		play: play,
		playAsync: playAsync,
		pause: pause,
		toggle: toggle,
		stopAll: function () { stopAll(null); },
		fallback: fallback,
		mount: mount,
		current: function () { return current; }
	};
})(window, document);
