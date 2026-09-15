/*
 * Apollo UI — card → full-viewport lightbox runtime.
 *
 * DELEGATION FIRST. apollo-events already ships a complete, live lightbox
 * (`[data-ev-lightbox]` shell, `[data-ev-open]` triggers, REST fragment at
 * /wp-json/apollo/v1/eventos/{id}/fragmento). For any surface declaring a
 * `delegate`, this file hands the click over instead of opening a second
 * overlay. Two overlays for one card is the duplication apollo-ui exists to end.
 *
 * Everything here is progressive enhancement. Cards render as real <a href>
 * links; if this script never loads, or a fetch fails, the click falls through
 * to a normal navigation. That ordering is deliberate — a lightbox that
 * swallows the click and then fails leaves the user with nothing.
 */
(function () {
	'use strict';

	var CFG = window.APOLLO_UI || {};
	var SURFACES = CFG.surfaces || {};
	var STR = CFG.strings || {};

	if (!Object.keys(SURFACES).length) { return; }

	var lb = null;
	var lastFocus = null;

	function build() {
		if (lb) { return lb; }
		lb = document.createElement('div');
		lb.className = 'aui-lb';
		lb.setAttribute('role', 'dialog');
		lb.setAttribute('aria-modal', 'true');
		lb.hidden = true;
		lb.innerHTML =
			'<div class="aui-lb-panel">' +
				'<button type="button" class="aui-lb-x" data-aui-close aria-label="' +
					(STR.close || 'Close') + '">&times;</button>' +
				'<div class="aui-lb-body" data-aui-body></div>' +
			'</div>';
		document.body.appendChild(lb);

		lb.addEventListener('click', function (e) {
			if (e.target.closest('[data-aui-close]')) { close(); return; }
			/* backdrop click: only when the panel itself was not the target */
			if (e.target === lb) { close(); }
		});
		return lb;
	}

	function open() {
		build();
		lb.hidden = false;
		lb.setAttribute('data-open', '');
		document.documentElement.classList.add('aui-lb-open');
		document.body.classList.add('aui-lb-open');
		var x = lb.querySelector('[data-aui-close]');
		if (x) { x.focus(); }
	}

	function close() {
		if (!lb) { return; }
		lb.removeAttribute('data-open');
		lb.hidden = true;
		document.documentElement.classList.remove('aui-lb-open');
		document.body.classList.remove('aui-lb-open');
		var body = lb.querySelector('[data-aui-body]');
		if (body) { body.innerHTML = ''; }
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
		lastFocus = null;
		if (location.hash.indexOf('#aui-') === 0) {
			history.replaceState(null, '', location.pathname + location.search);
		}
	}

	function state(msg) {
		var body = lb && lb.querySelector('[data-aui-body]');
		if (body) { body.innerHTML = '<div class="aui-lb-state"></div>'; body.firstChild.textContent = msg; }
	}

	/* Run <script> tags that arrived with injected HTML. innerHTML never
	 * executes them, and Apollo fragments carry their own config blocks. */
	function runScripts(root) {
		var list = root.querySelectorAll('script');
		for (var i = 0; i < list.length; i++) {
			var old = list[i];
			var s = document.createElement('script');
			if (old.type) { s.type = old.type; }
			if (old.src) { s.src = old.src; } else { s.textContent = old.textContent; }
			old.parentNode.replaceChild(s, old);
		}
	}

	function loadFragment(surface, id) {
		var url = surface.rest + encodeURIComponent(id) +
			(surface.fragment ? '/' + surface.fragment : '');

		return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
			.then(function (r) {
				if (!r.ok) { throw new Error('HTTP ' + r.status); }
				return r.json();
			})
			.then(function (j) {
				/* Apollo fragment shape: {success, data:{html,…}}.
				 * Fall back through the shapes other controllers use rather
				 * than assuming one. */
				var html = (j && j.data && j.data.html) || (j && j.html) || '';
				if (!html) { throw new Error('empty fragment'); }
				var body = lb.querySelector('[data-aui-body]');
				body.innerHTML = html;
				runScripts(body);
				body.scrollTop = 0;
			});
	}

	/* Hand off to apollo-events' runtime when it owns the surface. */
	function delegate(cpt, id, trigger) {
		var shell = document.querySelector('[data-ev-lightbox]');
		if (!shell) { return false; }
		/* The runtime binds on [data-ev-open]; synthesising a click on a real
		 * trigger is more robust than reaching into its internals. */
		var proxy = document.createElement('button');
		proxy.type = 'button';
		proxy.setAttribute('data-ev-open', String(id));
		proxy.style.display = 'none';
		(trigger.parentNode || document.body).appendChild(proxy);
		proxy.click();
		setTimeout(function () {
			if (proxy.parentNode) { proxy.parentNode.removeChild(proxy); }
		}, 0);
		/* Did it actually take? If the shell never opened, tell the caller so
		 * the click can fall through to navigation instead of dying silently. */
		return shell.getAttribute('aria-hidden') === 'false' || !shell.hasAttribute('hidden');
	}

	document.addEventListener('click', function (e) {
		var t = e.target.closest('[data-apollo-open]');
		if (!t) { return; }
		if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) { return; }

		var id = t.getAttribute('data-apollo-open');
		var cpt = t.getAttribute('data-apollo-cpt') || '';
		var surface = SURFACES[cpt];
		if (!id || !surface) { return; }   // no surface -> normal navigation

		if (surface.delegate) {
			if (delegate(cpt, id, t)) { e.preventDefault(); }
			return;                        // delegation failed -> let the link work
		}

		e.preventDefault();
		lastFocus = t;
		open();
		state(STR.loading || 'Loading…');
		loadFragment(surface, id)['catch'](function () {
			/* A failed fetch must not strand the user in an empty overlay. */
			close();
			window.location.href = t.href || (surface.rest + id);
		});
	}, false);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && lb && lb.hasAttribute('data-open')) { close(); }
	});

	window.addEventListener('popstate', function () {
		if (lb && lb.hasAttribute('data-open')) { close(); }
	});
}());
