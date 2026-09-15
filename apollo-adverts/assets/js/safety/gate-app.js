/**
 * safety/gate-app.js — boot, polling, proceed, witness confirm
 *
 * Unlock: one mutual confirmed OR Apollo verified. Trust votes never unlock.
 * After vouch 202, poll signals every 3s (max 2 min) until cleared.
 */
(function () {
  'use strict';

  var stage = document.getElementById('apSafetyStage');
  if (!stage) { return; }

  var API = window.ApolloSafetyAPI;
  var R = window.ApolloSafetyRender;
  if (!API || !R) { return; }

  /* ── Witness mode ─────────────────────────────────────────────────────── */
  if (stage.getAttribute('data-mode') === 'witness') {
    var panel = document.getElementById('apWitness');
    var confirmBtn = document.getElementById('apWitnessConfirm');
    if (!panel || !confirmBtn) { return; }

    var advertW = parseInt(panel.getAttribute('data-advert'), 10) || 0;
    var buyerW = parseInt(panel.getAttribute('data-buyer'), 10) || 0;

    confirmBtn.addEventListener('click', function () {
      confirmBtn.disabled = true;
      confirmBtn.textContent = 'Confirmando…';
      API.confirm(advertW, buyerW).then(function (res) {
        if (res.ok && res.body && res.body.confirmed) {
          var done = document.getElementById('apWitnessDone');
          if (done) { done.hidden = false; }
          confirmBtn.hidden = true;
          return;
        }
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Tentar de novo';
      }).catch(function () {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Tentar de novo';
      });
    });
    return;
  }

  /* ── Buyer mode ───────────────────────────────────────────────────────── */
  var host = document.querySelector('.ap-checks');
  if (!host) { return; }

  var ADVERT = parseInt(host.getAttribute('data-advert'), 10) || 0;
  var STATE = { confirmed: [], verified: false, found: 0 };
  var IG_DATA = { mutuals: [], provider: false };
  var pollTimer = null;
  var pollUntil = 0;

  function qs(s, r) { return R.qs(s, r); }

  function askVouch(person, li, btn) {
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Aguardando…';
    }
    if (li) { li.classList.add('is-loading'); }

    API.vouch(ADVERT, person.username).then(function (res) {
      if (li) { li.classList.remove('is-loading'); }

      if (res.ok && res.body && res.body.confirmed) {
        if (li) {
          li.classList.add('is-active');
          if (btn) { btn.replaceWith(R.confirmedMark()); }
        }
        STATE.confirmed.push(person);
        paintInstagram();
        R.paintVerdict(STATE);
        return;
      }

      /* 202 = asked — start polling */
      showWaiting(person.username);
      startPoll();

      if (btn) {
        btn.disabled = false;
        btn.textContent = (res.body && res.body.asked)
          ? 'Pedido enviado — aguardando'
          : 'Tentar de novo';
      }
    }).catch(function () {
      if (li) { li.classList.remove('is-loading'); }
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Tentar de novo';
      }
    });
  }

  function showWaiting(username) {
    var existing = document.getElementById('apWaiting');
    if (existing) { existing.remove(); }
    var note = qs('.ap-note');
    var panel = R.waitingPanel(username);
    if (note && note.parentNode) {
      note.parentNode.insertBefore(panel, note);
    } else {
      var inner = document.getElementById('apSafetyInner');
      if (inner) { inner.appendChild(panel); }
    }
  }

  function startPoll() {
    stopPoll();
    pollUntil = Date.now() + 120000;
    pollTimer = setInterval(function () {
      if (Date.now() > pollUntil) {
        stopPoll();
        return;
      }
      API.signals(ADVERT).then(function (res) {
        var d = (res.ok && res.body) ? res.body : null;
        if (!d) { return; }
        applySignals(d, true);
      });
    }, 3000);
  }

  function stopPoll() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  function paintInstagram() {
    var el = R.block('instagram');
    if (!el) { return; }
    var body = qs('.ap-check__body', el);
    body.textContent = '';

    if (STATE.confirmed.length) {
      R.settle('instagram', 'on', STATE.confirmed.length === 1
        ? '@' + STATE.confirmed[0].username + ' confirmou'
        : STATE.confirmed.length + ' amigos confirmaram');
    } else if (!IG_DATA.provider) {
      R.settle('instagram', 'off', 'Não foi possível verificar agora');
      R.say(body, 'Sem esse sinal, trate o perfil como um desconhecido completo.');
      return;
    } else {
      R.settle('instagram', 'off', STATE.found
        ? STATE.found + ' em comum · ninguém confirmou ainda'
        : 'Nenhum amigo em comum');
    }

    if (!STATE.found) {
      R.say(body, 'Ninguém em comum entre vocês na Apollo. Para você, essa pessoa é um estranho completo.');
      return;
    }

    R.say(body, 'Achamos ' + STATE.found + ' em comum — e isso, sozinho, não prova nada. ' +
      'Peça a um deles para confirmar que conhece essa pessoa de verdade.');

    var done = STATE.confirmed.map(function (p) { return p.username; });
    var pending = IG_DATA.mutuals.filter(function (p) { return done.indexOf(p.username) === -1; });

    if (STATE.confirmed.length) {
      R.list(body, STATE.confirmed, R.AP, false, null);
    }

    if (pending.length && !STATE.confirmed.length) {
      body.appendChild(R.primaryAsk(pending[0], function (person, btn) {
        askVouch(person, null, btn);
      }));
    }

    R.list(body, pending, R.AP, true, askVouch, 6);
  }

  function paintTrust(data) {
    var el = R.block('trust');
    if (!el) { return; }
    var body = qs('.ap-check__body', el);
    body.textContent = '';
    var n = (data && data.count) || 0;

    R.settle('trust', n ? 'info' : 'off',
      n ? n + (1 === n ? ' pessoa respondeu por ele' : ' pessoas responderam por ele')
        : 'Ninguém respondeu por ele');

    if (!n) {
      R.say(body, 'Nenhum usuário da Apollo respondeu por esse perfil até agora.');
      return;
    }
    R.say(body, 'Vale ler, mas não libera a conversa sozinho: esses votos podem ser plantados pelo próprio vendedor.');
    R.list(body, data.voters, R.AP, false, null, 6);
  }

  function paintVerified(isVerified) {
    var el = R.block('verified');
    if (!el) { return; }
    var body = qs('.ap-check__body', el);
    body.textContent = '';

    R.settle('verified', isVerified ? 'on' : 'off',
      isVerified ? 'Perfil verificado' : 'Ainda não verificado');

    R.say(body, isVerified
      ? 'Documento e identidade conferidos pela equipe. É o sinal mais forte que existe hoje na plataforma.'
      : 'Esse perfil ainda não passou pela verificação da equipe. Isso não prova fraude — é ausência de sinal.');
  }

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
    stage.setAttribute('data-boot', 'ready');
  }

  function applySignals(d, fromPoll) {
    var ig = d.instagram || {};
    IG_DATA.mutuals = ig.mutuals || [];
    IG_DATA.provider = !!ig.provider;
    STATE.found = ig.found || 0;
    STATE.confirmed = ig.confirmed || [];
    STATE.verified = !!d.verified;

    if (!fromPoll) {
      paintInstagram();
      if (IG_DATA.provider) { resolve('instagram'); }

      if (d.trust && typeof d.trust.count !== 'undefined') {
        paintTrust(d.trust);
        resolve('trust');
      }

      if (typeof d.verified !== 'undefined') {
        paintVerified(STATE.verified);
        resolve('verified');
      }
    } else {
      paintInstagram();
    }

    R.paintVerdict(STATE);

    if (d.cleared || STATE.confirmed.length || STATE.verified) {
      stopPoll();
      var wait = document.getElementById('apWaiting');
      if (wait) { wait.remove(); }
    }

    /* Returning buyer already unlocked */
    if (!fromPoll && (STATE.confirmed.length || STATE.verified || d.cleared)) {
      R.paintVerdict(STATE);
    }
  }

  var proceed = document.getElementById('apSafetyProceed');
  if (proceed) {
    proceed.addEventListener('click', function () {
      proceed.disabled = true;
      proceed.textContent = 'Abrindo conversa…';
      window.location.href = proceed.getAttribute('data-redirect') || '/';
    });
  }

  API.signals(ADVERT).then(function (res) {
    var d = (res.ok && res.body) ? res.body : null;
    if (!d) { return; }
    applySignals(d, false);
  }).catch(function () { /* stay loading */ });
})();
