/**
 * safety/render-checks.js — people rows, settle states, verdict paint
 */
(function (w) {
  'use strict';

  var IG = 'https://www.instagram.com/';
  var AP = '/id/';

  function qs(s, r) { return (r || document).querySelector(s); }

  function tone(u) {
    var h = 0, i;
    for (i = 0; i < u.length; i++) { h = (h * 31 + u.charCodeAt(i)) >>> 0; }
    return (h % 5) + 1;
  }

  function initials(full, user) {
    var p = String(full || user || '?').trim().split(/\s+/);
    return (p[0][0] + (p.length > 1 ? p[p.length - 1][0] : '')).slice(0, 2);
  }

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

  function text(name, value) {
    var el = qs('[data-bind="' + name + '"]');
    if (el) { el.textContent = value; }
  }

  function block(key) {
    return qs('.ap-check[data-check="' + key + '"]');
  }

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

  function confirmedMark() {
    var s = document.createElement('span');
    s.className = 'ap-person__ok';
    s.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 15.172L19.192 5.979L20.607 7.393L10 18L3.636 11.636L5.05 10.222L10 15.172Z"/></svg>';
    s.appendChild(document.createTextNode(' Confirmou que conhece'));
    return s;
  }

  w.ApolloSafetyRender = {
    qs: qs,
    block: block,
    text: text,
    settle: settle,
    say: say,
    avatar: avatar,
    confirmedMark: confirmedMark,
    AP: AP,
    IG: IG,

    personRow: function (person, base, withAsk, onAsk) {
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

      if (withAsk && typeof onAsk === 'function') {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-secondary btn-sm ap-ask';
        btn.textContent = 'Pedir confirmação';
        btn.addEventListener('click', function () { onAsk(person, li, btn); });
        li.appendChild(btn);
      }
      return li;
    },

    list: function (el, people, base, withAsk, onAsk, cap) {
      var ul = document.createElement('ul');
      ul.className = 'ap-people';
      people.slice(0, cap || people.length).forEach(function (p) {
        ul.appendChild(w.ApolloSafetyRender.personRow(p, base, withAsk, onAsk));
      });
      el.appendChild(ul);

      if (cap && people.length > cap) {
        var more = document.createElement('button');
        more.type = 'button';
        more.className = 'btn btn-secondary btn-sm ap-more';
        more.textContent = 'Ver os outros ' + (people.length - cap);
        more.addEventListener('click', function () {
          people.slice(cap).forEach(function (p) {
            ul.appendChild(w.ApolloSafetyRender.personRow(p, base, withAsk, onAsk));
          });
          more.remove();
        });
        el.appendChild(more);
      }
    },

    paintVerdict: function (STATE) {
      var foot = document.getElementById('apSafetyFoot');
      var go = document.getElementById('apSafetyProceed');
      var icon = document.getElementById('apVerdictIcon');
      if (!foot) { return; }

      foot.hidden = false;
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

      foot.setAttribute('data-verdict', 'danger');
      if (icon) { icon.hidden = false; }
      if (go) { go.hidden = true; }
      text('verdict-title', STATE.found ? 'Ninguém confirmou ainda' : 'Nenhuma confirmação');
      text('verdict-sub', STATE.found
        ? 'Peça a um amigo em comum para confirmar que conhece essa pessoa. Uma confirmação já libera.'
        : 'Sem um amigo em comum que confirme, e sem verificação da Apollo, não siga com essa negociação.');
    },

    waitingPanel: function (username) {
      var box = document.createElement('div');
      box.className = 'ap-waiting';
      box.id = 'apWaiting';
      box.innerHTML =
        '<p class="ap-waiting__title">Pedido enviado a @' + String(username).replace(/[<>&]/g, '') + '</p>' +
        '<p class="ap-waiting__sub">Assim que a pessoa confirmar, a conversa libera automaticamente. Você pode deixar esta página aberta.</p>';
      return box;
    },

    primaryAsk: function (person, onClick) {
      var wrap = document.createElement('div');
      wrap.className = 'ap-primary-ask';
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-primary ap-primary-ask__btn';
      btn.textContent = 'Pedir confirmação para @' + person.username;
      btn.addEventListener('click', function () { onClick(person, btn); });
      wrap.appendChild(btn);
      return wrap;
    }
  };
})(window);
