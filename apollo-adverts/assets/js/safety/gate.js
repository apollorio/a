/* ══════════════════════════════════════════════════════════════════════════
   safety/gate.js — /seguranca/
   apollo::rio · apollo-adverts

   Fills the three check blocks the PHP shipped in their loading state, and
   owns exactly one decision:

       one mutual confirmed  OR  Apollo verified   →  the conversation opens

   Nothing else opens it. Trust votes are rendered and never counted — a
   seller can farm those the same way he farms followers.

   The button this reveals is a convenience. The rule is enforced server-side
   in includes/safety-gate.php, which refuses the thread endpoint for a pair
   that has not cleared, so nothing here is load-bearing for security.
   ══════════════════════════════════════════════════════════════════════════ */
(function () {
	'use strict';

	var cfg = window.ApolloSafety || {};
	var host = document.querySelector('.ap-checks');
	if (!host || !cfg.rest) { return; }

	var ADVERT = parseInt(host.getAttribute('data-advert'), 10) || 0;
	var IG = 'https://www.instagram.com/';
	var AP = '/id/'; /* vocabulary law: profiles live at /id/{username} */

	function qs(s, r) { return (r || document).querySelector(s); }
	function block(key) { return qs('.ap-check[data-check="' + key + '"]'); }
	function bind(name) { return qs('[data-bind="' + name + '"]'); }
	function text(name, value) {
		var el = bind(name);
		if (el) { el.textContent = value; }
	}

	function api(path, options) {
		return fetch(cfg.rest + path, Object.assign(
			{ credentials: 'same-origin', headers: { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' } },
			options || {}
		)).then(function (r) { return r.json().then(function (b) { return { ok: r.ok, body: b }; }); });
	}

	/* ── people ─────────────────────────────────────────────────────────── */

	function tone(u) {
		var h = 0, i;
		for (i = 0; i < u.length; i++) { h = (h * 31 + u.charCodeAt(i)) >>> 0; }
		return (h % 5) + 1;
	}

	function initials(full, user) {
		var p = String(full || user || '?').trim().split(/\s+/);
		return (p[0][0] + (p.length > 1 ? p[p.length - 1][0] : '')).slice(0, 2);
	}

	/* monogram is the honest default; <img> wins when a profile_pic_url
	   actually resolves. scontent URLs are hotlink-guarded and expire, so the
	   fallback runs in production too, not only when a provider is missing. */
	function avatar(person, cls) {
		var el = document.createElement('span');
		el.className = 'ap-av' + (cls ? ' ' + cls : '');
		el.setAttribute('data-tone', String(tone(person.username)));
		el.textContent = initials(person.full_name, person.username);

		if (person.profile_pic_url) {
			var img = new Image();
			img.alt = '';
			img.loading = 'lazy';
			img.referrerPolicy = 'no-referrer';
			img.addEventListener('error', function () { img.remove(); }, { once: true });
			img.src = person.profile_pic_url;
			el.appendChild(img);
		}
		return el;
	}

	function personRow(person, base, withAsk) {
		var li = document.createElement('li');
		li.className = 'ap-person__row';

		var a = document.createElement('a');
		a.className = 'ap-person';
		a.href = base + person.username + (base === IG ? '/' : '');
		if (base === IG) { a.target = '_blank'; a.rel = 'noopener noreferrer'; }
		a.appendChild(avatar(person, 'ap-person__av'));

		var id = document.createElement('span');
		id.className = 'ap-person__id';
		id.innerHTML = '<span class="ap-person__name"></span><span class="ap-person__handle"></span>';
		id.firstChild.textContent = person.full_name || person.username;
		id.lastChild.textContent = '@' + person.username;
		a.appendChild(id);

		li.appendChild(a);
		if (withAsk) { li.appendChild(askButton(person, li)); }
		return li;
	}

	/* ── the ask — the control this whole page exists for ────────────────── */

	var STATE = { confirmed: [], verified: false, found: 0 };

	function confirmedMark() {
		var s = document.createElement('span');
		s.className = 'ap-person__ok';
		s.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 15.172L19.192 5.979L20.607 7.393L10 18L3.636 11.636L5.05 10.222L10 15.172Z"/></svg>';
		s.appendChild(document.createTextNode(' Confirmou que conhece'));
		return s;
	}

	function askButton(person, li) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'btn btn-secondary btn-sm ap-ask';
		btn.textContent = 'Pedir confirmação';

		btn.addEventListener('click', function () {
			btn.disabled = true;
			btn.textContent = 'Aguardando…';
			li.classList.add('is-loading');

			api('vouch', {
				method: 'POST',
				body: JSON.stringify({ advert: ADVERT, username: person.username })
			}).then(function (res) {
				li.classList.remove('is-loading');

				if (res.ok && res.body && res.body.confirmed) {
					li.classList.add('is-active');
					btn.replaceWith(confirmedMark());
					STATE.confirmed.push(person);
					paintInstagram();
					verdict();
					return;
				}

				/* 202 = asked, not yet answered. Silence is not consent, so the
				   gate stays shut and says so instead of pretending. */
				btn.disabled = false;
				btn.textContent = (res.body && res.body.confirmed === false)
					? 'Sem resposta — tentar de novo'
					: 'Tentar de novo';
			}).catch(function () {
				li.classList.remove('is-loading');
				btn.disabled = false;
				btn.textContent = 'Tentar de novo';
			});
		});

		return btn;
	}

	/* ── rendering the blocks ────────────────────────────────────────────── */

	function settle(key, state, status) {
		var el = block(key);
		if (!el) { return; }
		el.classList.remove('is-loading');
		el.setAttribute('data-state', state);
		text('status-' + key, status);
	}

	function say(el, message) {
		var p = document.createElement('p');
		p.className = 'ap-check__say';
		p.textContent = message;
		el.appendChild(p);
	}

	function list(el, people, base, withAsk, cap) {
		var ul = document.createElement('ul');
		ul.className = 'ap-people';
		people.slice(0, cap || people.length).forEach(function (p) {
			ul.appendChild(personRow(p, base, withAsk));
		});
		el.appendChild(ul);

		if (cap && people.length > cap) {
			var more = document.createElement('button');
			more.type = 'button';
			more.className = 'btn btn-secondary btn-sm ap-more';
			more.textContent = 'Ver os outros ' + (people.length - cap);
			more.addEventListener('click', function () {
				people.slice(cap).forEach(function (p) { ul.appendChild(personRow(p, base, withAsk)); });
				more.remove();
			});
			el.appendChild(more);
		}
	}

	var IG_DATA = { mutuals: [], provider: false };

	function paintInstagram() {
		var el = block('instagram');
		if (!el) { return; }
		var body = qs('.ap-check__body', el);
		body.textContent = '';

		if (STATE.confirmed.length) {
			settle('instagram', 'on', STATE.confirmed.length === 1
				? '@' + STATE.confirmed[0].username + ' confirmou'
				: STATE.confirmed.length + ' amigos confirmaram');
		} else if (!IG_DATA.provider) {
			/* No provider wired. "Could not check" — never "fine". */
			settle('instagram', 'off', 'Não foi possível verificar agora');
			say(body, 'Sem esse sinal, trate o perfil como um desconhecido completo.');
			return;
		} else {
			settle('instagram', 'off', STATE.found
				? STATE.found + ' em comum · ninguém confirmou ainda'
				: 'Nenhum amigo em comum');
		}

		if (!STATE.found) {
			say(body, 'Ninguém em comum entre vocês na Apollo. Para você, essa pessoa é um estranho completo.');
			return;
		}

		say(body, 'Achamos ' + STATE.found + ' em comum — e isso, sozinho, não prova nada. ' +
			'Peça a um deles para confirmar que conhece essa pessoa de verdade.');

		var done = STATE.confirmed.map(function (p) { return p.username; });
		var pending = IG_DATA.mutuals.filter(function (p) { return done.indexOf(p.username) === -1; });

		/* AP, not IG: these are Apollo members, and their profile is /id/{user}.
		   A future Instagram sidecar adds people with the same four keys, so
		   this stays one list and one renderer. */
		if (STATE.confirmed.length) { list(body, STATE.confirmed, AP, false); }
		list(body, pending, AP, true, 6);
	}

	/* ── the verdict ─────────────────────────────────────────────────────── */

	function verdict() {
		var foot = document.getElementById('apSafetyFoot');
		var go = document.getElementById('apSafetyProceed');
		var icon = document.getElementById('apVerdictIcon');
		if (!foot) { return; }

		foot.hidden = false;

		/* THE RULE. Trust votes are deliberately absent from this expression. */
		var unlocked = STATE.confirmed.length > 0 || STATE.verified;

		if (unlocked) {
			foot.setAttribute('data-verdict', 'safe');
			if (icon) { icon.hidden = true; }
			if (go) { go.hidden = false; }

			if (STATE.confirmed.length) {
				text('verdict-title', STATE.confirmed.length === 1
					? '@' + STATE.confirmed[0].username + ' confirmou que conhece'
					: STATE.confirmed.length + ' amigos em comum confirmaram');
			} else {
				text('verdict-title', 'Perfil verificado pela Apollo');
			}
			text('verdict-sub', 'Você pode seguir — o combinado continua sendo por sua conta.');
			return;
		}

		/* Hard stop. The button is absent, not disabled: a greyed-out button
		   you are told not to press still reads as a button. */
		foot.setAttribute('data-verdict', 'danger');
		if (icon) { icon.hidden = false; }
		if (go) { go.hidden = true; }
		text('verdict-title', STATE.found ? 'Ninguém confirmou ainda' : 'Nenhuma confirmação');
		text('verdict-sub', STATE.found
			? 'Peça a um amigo em comum para confirmar que conhece essa pessoa. Uma confirmação já libera.'
			: 'Sem um amigo em comum que confirme, e sem verificação da Apollo, não siga com essa negociação.');
	}

	/* ── boot ────────────────────────────────────────────────────────────── */

	function paintTrust(data) {
		var el = block('trust');
		if (!el) { return; }
		var body = qs('.ap-check__body', el);
		var n = (data && data.count) || 0;

		/* "info", never "on": found, but it opens nothing. */
		settle('trust', n ? 'info' : 'off',
			n ? n + (1 === n ? ' pessoa respondeu por ele' : ' pessoas responderam por ele')
			  : 'Ninguém respondeu por ele');

		if (!n) {
			say(body, 'Nenhum usuário da Apollo respondeu por esse perfil até agora.');
			return;
		}
		say(body, 'Vale ler, mas não libera a conversa sozinho: esses votos podem ser plantados pelo próprio vendedor.');
		list(body, data.voters, AP, false, 6);
	}

	function paintVerified(isVerified) {
		var el = block('verified');
		if (!el) { return; }
		var body = qs('.ap-check__body', el);

		settle('verified', isVerified ? 'on' : 'off',
			isVerified ? 'Perfil verificado' : 'Ainda não verificado');

		say(body, isVerified
			? 'Documento e identidade conferidos pela equipe. É o sinal mais forte que existe hoje na plataforma.'
			: 'Esse perfil ainda não passou pela verificação da equipe. Isso não prova fraude — é ausência de sinal.');
	}

	/* The server has already recorded clearance by the time this is visible —
	   the vouch endpoint does it. This is just the door. */
	var proceed = document.getElementById('apSafetyProceed');
	if (proceed) {
		proceed.addEventListener('click', function () {
			proceed.disabled = true;
			proceed.textContent = 'Abrindo conversa…';
			window.location.href = proceed.getAttribute('data-redirect') || '/';
		});
	}

	/* ── boot gate ───────────────────────────────────────────────────────────
	   The page reveals itself only when all three signals have ANSWERED and
	   every avatar has decoded. A signal that never answers never resolves,
	   the preloader never fades, and no verdict is ever shown — because
	   "could not check" must not be allowed to look like "checked, and fine".

	   Answering "zero" IS an answer. That resolves, and the page shows the
	   hard stop. Only silence spins forever. ── */

	var RESOLVED = { instagram: false, trust: false, verified: false };

	function resolve(key) {
		if (RESOLVED[key]) { return; }
		RESOLVED[key] = true;

		var step = qs('.ap-preloader__steps i[data-step="' + key + '"]');
		if (step) { step.classList.add('is-active'); }

		if (RESOLVED.instagram && RESOLVED.trust && RESOLVED.verified) {
			whenAvatarsSettled(reveal);
		}
	}

	/* Images, not just data: a card that pops its avatar in after the fade is
	   the same half-drawn page the preloader exists to prevent. decode() where
	   it exists, load/error otherwise, and never block on a slow CDN forever —
	   a stalled avatar is cosmetic, unlike a stalled signal. */
	function whenAvatarsSettled(done) {
		var imgs = Array.prototype.slice.call(document.querySelectorAll('.ap-av img'));
		if (!imgs.length) { done(); return; }

		var settled = imgs.map(function (img) {
			if (img.complete) { return Promise.resolve(); }
			if (img.decode) { return img.decode().catch(function () {}); }
			return new Promise(function (r) {
				img.addEventListener('load', r, { once: true });
				img.addEventListener('error', r, { once: true });
			});
		});

		Promise.race([
			Promise.all(settled),
			new Promise(function (r) { setTimeout(r, 2500); })
		]).then(done);
	}

	function reveal() {
		var stage = document.getElementById('apSafetyStage');
		if (stage) { stage.setAttribute('data-boot', 'ready'); }
	}

	api('signals?advert=' + ADVERT).then(function (res) {
		var d = (res.ok && res.body) ? res.body : null;
		if (!d) { return; } /* no answer — stay loading, on purpose */

		var ig = d.instagram;
		IG_DATA.mutuals = (ig && ig.mutuals) || [];
		IG_DATA.provider = !!(ig && ig.provider);
		STATE.found = (ig && ig.found) || 0;
		STATE.confirmed = (ig && ig.confirmed) || [];
		STATE.verified = !!d.verified;

		paintInstagram();
		/* No provider wired means the Instagram question was never actually
		   asked. That is silence, not a zero, so it never resolves. */
		if (IG_DATA.provider) { resolve('instagram'); }

		if (d.trust && typeof d.trust.count !== 'undefined') {
			paintTrust(d.trust);
			resolve('trust');
		}

		if (typeof d.verified !== 'undefined') {
			paintVerified(STATE.verified);
			resolve('verified');
		}

		verdict();
	}).catch(function () {
		/* Network failure resolves nothing. The member waits rather than being
		   handed a conclusion Apollo never reached. */
	});
})();
