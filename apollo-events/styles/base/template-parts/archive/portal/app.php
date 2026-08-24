<?php

/**
 * Portal de Eventos — app-portal-eventos.js (screen runtime)
 *
 * The screen's behaviour: sticky month/filter chrome, highlighted-events hero
 * slider, capped 'ultimos eventos' rail + lightbox, day-window rails with
 * infinite load, and the event quick-view modal.
 *
 * Card pipeline (shared data, CSS decides size):
 *   #pevRails  → .pev-rail sections → full .eve-grid.grid-layout + eveCardHTML()
 *   .pev-browse → #pevDynamic tax   → .pev-mini-grid + eveCardMiniHTML()
 *
 * PHASE 002: split out of the single 1258-line portal-scripts.php so each
 * concern is independently readable, replaceable and themeable.
 * Synced from screen/_official_layout/js/app-portal-eventos.js (chrome-lux2).
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
/* APOLLO::RIO · app-portal-eventos.js (ported from
   screen/_official_layout/js/app-portal-eventos.js — Netflix-style portal calendar)
   Depends on: APOLLO_EVENTS, APOLLO_PORTAL, Remix icons (core.js), GSAP not
   required here (this file doesn't use it — core.js still loads it globally
   for the rest of the page).
*/
(function (w, d) {
  'use strict';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c];
    });
  }

  function P() { return w.APOLLO_PORTAL || {}; }

  function reducedMotion() {
    return !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function radar() { return w.APOLLO_RADAR = w.APOLLO_RADAR || []; }
  function radarOf(id) {
    var list = radar();
    for (var i = 0; i < list.length; i++) if (list[i].eventId === id) return list[i];
    return null;
  }
  function setRsvp(id, state) {
    var r = radarOf(id);
    var list = radar();
    if (r && r.rsvp === state) list.splice(list.indexOf(r), 1);
    else if (r) r.rsvp = state;
    else list.push({ eventId: id, rsvp: state, since: new Date().toISOString().slice(0, 10) });
    d.dispatchEvent(new CustomEvent('apollo:radar-change'));
  }

  function ticketChip(tickets) {
    var label = (P().ticketLabel && P().ticketLabel(tickets)) || tickets || '';
    var hot = tickets === 'soldout_soon';
    var gone = tickets === 'sold_out';
    var cls = 'pev-chip' + (hot ? ' is-hot' : '') + (gone ? ' is-gone' : '');
    return label ? '<span class="' + cls + '">' + esc(label) + '</span>' : '';
  }

  var MES_SHORT = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

  function dateShort(iso) {
    var p = String(iso || '').split('-');
    return (p[2] || '--') + ' ' + (MES_SHORT[(+p[1] || 1) - 1] || '');
  }

  function dateBits(iso) {
    var p = String(iso || '').split('-');
    return { d: p[2] || '--', m: MES_SHORT[(+p[1] || 1) - 1] || '---' };
  }

  function ticketStatus(tickets) {
    if (tickets === 'sold_out') return { cls: 'soldout', html: '<span class="status">Sold-out</span>' };
    if (tickets === 'soldout_soon') return { cls: 'featured', html: '<span class="status active">Sold-out soon</span>' };
    if (tickets === 'free') return { cls: '', html: '<span class="status active">Gratuito</span>' };
    return { cls: '', html: '<span class="status">' + esc((P().ticketLabel && P().ticketLabel(tickets)) || 'Ingressos') + '</span>' };
  }

  function prettyGenre(g) {
    return String(g || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
  }

  /* ── CARTÃO DE EVENTO — CONTRATO DE MARKUP DA DESIGN SYSTEM ──────────────
     Stub externo SEMPRE <article class="app-market eve" data-listing="ID">;
     o miolo é EXATAMENTE o que eveInnerHTML() de app-market.js imprime
     (<a class="a-eve-card"> > .a-eve-date + .a-eve-media + .a-eve-content com
     as TRÊS linhas .a-eve-meta: line-up · local · gêneros). As classes são o
     contrato — este SSR local existe só porque o portal lê APOLLO_EVENTS
     (CPT event) em vez de APOLLO_EVENT_LISTINGS, mas o HTML é idêntico byte a
     byte, então CSS e comportamento nunca divergem do showcase.
     Os <i class="ri-*"> ficam crus de propósito: o runtime de ícones do
     core.js os hidrata em <svg> sozinho — nunca escrever SVG na mão aqui.
     ──────────────────────────────────────────────────────────────────────── */
  function eveCardHTML(e) {
    if (!e) return '';
    var db = dateBits(e.startDate);
    var venue = (e.venue && e.venue.name) || 'Local a anunciar';
    var lineup = (e.lineup || []).map(function (l) { return l.name; }).join(', ');
    var sounds = (e.genres || []).map(prettyGenre).join(', ');
    var tags = (e.genres || []).slice(0, 2).map(function (g) {
      return '<span class="a-eve-tag">' + esc(prettyGenre(g)) + '</span>';
    }).join('');
    /* Um beacon de depuração vivia aqui: POST para http://127.0.0.1:7514 a cada
       cartão sem capa. Removido pelo mesmo motivo já documentado em
       bootstrap.php — subrecurso http:// numa página https:// é bloqueado,
       o .catch() escondia a rejeição, e o ruído caía exatamente onde um erro
       real de render apareceria. Telemetria de dev tem que ser opt-in e
       same-origin, decidida no PHP, nunca emitida-e-engolida no cliente. */
    return '<article class="app-market eve" data-listing="' + esc(e.id) + '" data-ev-open="' + esc(e.id) + '">' +
      '<a href="' + esc(e.url || '#') + '" class="a-eve-card" aria-label="' + esc(e.title) + '">' +
        '<div class="a-eve-date">' +
          '<span class="a-eve-date-day">' + esc(db.d) + '</span>' +
          '<span class="a-eve-date-month">' + esc(db.m) + '</span>' +
        '</div>' +
        '<div class="a-eve-media">' +
          '<img src="' + esc(e.cover || '') + '" alt="' + esc(e.title) + '" loading="lazy" decoding="async">' +
          (tags ? '<div class="a-eve-tags">' + tags + '</div>' : '') +
        '</div>' +
        '<div class="a-eve-content">' +
          '<h2 class="a-eve-title">' + esc(e.title) + '</h2>' +
          '<p class="a-eve-meta"><i class="ri-sound-module-fill"></i><span>' + esc(lineup || 'Line-up a confirmar') + '</span></p>' +
          '<p class="a-eve-meta"><i class="ri-map-pin-2-line"></i><span>' + esc(venue) + '</span></p>' +
          '<p class="a-eve-meta"><i class="ri-music-2-line"></i><span>' + esc(sounds || '—') + '</span></p>' +
        '</div>' +
      '</a>' +
    '</article>';
  }

  /* ── MINI CARD — .pev-browse tax feed only ────────────────────────────────
     Own class tree (.pev-mini-*), never .a-eve-* / .app-market.eve. Rails keep
     eveCardHTML() full-size. Clicks run off [data-ev-open] — the documented
     lightbox contract — like every other event surface. ── */
  function eveCardMiniHTML(e) {
    if (!e) return '';
    var db = dateBits(e.startDate);
    var venue = (e.venue && e.venue.name) || 'Local a anunciar';
    var lineup = (e.lineup || []).map(function (l) { return l.name; }).join(', ');
    var sounds = (e.genres || []).map(prettyGenre).join(', ');
    var tags = (e.genres || []).slice(0, 2).map(function (g) {
      return '<span class="pev-mini-tag">' + esc(prettyGenre(g)) + '</span>';
    }).join('');
    return '<article class="pev-mini" data-listing="' + esc(e.id) + '" data-ev-open="' + esc(e.id) + '">' +
      '<a href="' + esc(e.url || '#') + '" class="pev-mini-card" aria-label="' + esc(e.title) + '">' +
        '<div class="pev-mini-date">' +
          '<span class="pev-mini-day">' + esc(db.d) + '</span>' +
          '<span class="pev-mini-month">' + esc(db.m) + '</span>' +
        '</div>' +
        '<div class="pev-mini-media">' +
          '<img src="' + esc(e.cover || '') + '" alt="' + esc(e.title) + '" loading="lazy" decoding="async">' +
          (tags ? '<div class="pev-mini-tags">' + tags + '</div>' : '') +
        '</div>' +
        '<div class="pev-mini-body">' +
          '<h3 class="pev-mini-title">' + esc(e.title) + '</h3>' +
          '<p class="pev-mini-meta pev-mini-meta--wide"><i class="ri-sound-module-fill"></i><span>' + esc(lineup || 'Line-up a confirmar') + '</span></p>' +
          '<p class="pev-mini-meta"><i class="ri-map-pin-2-line"></i><span>' + esc(venue) + '</span></p>' +
          '<p class="pev-mini-meta pev-mini-meta--wide"><i class="ri-music-2-line"></i><span>' + esc(sounds || '—') + '</span></p>' +
        '</div>' +
      '</a>' +
    '</article>';
  }

  /** Cabeçalho de seção: título + badge numérico + toggle grade/lista (inline). */
  function sectionHeadHTML(title, icon, countNum, feedKey, viewMode) {
    var view = viewMode === 'list' ? 'list' : 'grid';
    var toggleIcon = view === 'list' ? 'ri-gallery-view' : 'ri-list-check-2';
    var toggleLabel = view === 'list' ? 'Ver em grade' : 'Ver em lista';
    var toggle = feedKey
      ? '<span class="pev-view-toggle" data-target="' + esc(feedKey) + '">' +
          '<i class="' + toggleIcon + '" data-view="' + view + '" role="button" tabindex="0" title="' + toggleLabel + '" aria-label="' + toggleLabel + '"></i>' +
        '</span>'
      : '';
    /* O texto do título é um <span> próprio, não um nó de texto solto: é o alvo
       da máscara clip-path quando o mês muda (morphRailTitle). Um nó de texto
       não pode ser animado nem substituído sem reescrever o <h2> inteiro — e
       reescrever o <h2> levaria junto o badge e o toggle, que não mudaram. */
    return '<h2 class="section-title">' +
      '<i class="' + esc(icon || 'ri-calendar-event-fill') + '" aria-hidden="true"></i> ' +
      '<span data-rail-title>' + esc(title) + '</span>' +
      ' <small class="pev-section-count">' + esc(String(countNum == null ? 0 : countNum)) + '</small>' +
      toggle +
    '</h2>';
  }

  function feedContainerHTML(feedKey, hasItems, viewMode) {
    if (!hasItems) return emptyRail();
    var view = viewMode === 'list' ? 'list' : 'grid';
    if (view === 'list') {
      return '<div class="pev-list-feed event-list-container pev-rail-rows" data-feed="' + esc(feedKey) + '"></div>';
    }
    /* Browse tax feed uses the standalone mini grid — never .grid-layout.eve-grid */
    if (feedKey === 'tax') {
      return '<div class="pev-mini-grid" data-feed="' + esc(feedKey) + '"></div>';
    }
    return '<div class="grid-layout eve-grid" data-feed="' + esc(feedKey) + '"></div>';
  }

  /* ── LINHA DE LISTAGEM (.event-row) — estrutura canônica da Design System:
     .event-row > .date-box(.date-day + .date-month) + .event-details(h4 +
     .event-meta > span > i + texto) + .event-action > .status[.active].
     Evento on-line troca o pino de mapa por ri-wifi-line (mesma regra do
     showcase). Ícones ficam crus — o core.js hidrata em <svg> sozinho. ── */
  function eventRowHTML(e) {
    if (!e) return '';
    var db = dateBits(e.startDate);
    var st = ticketStatus(e.tickets);
    var rowCls = 'event-row' + (st.cls ? ' ' + st.cls : '');
    var place = (e.venue && e.venue.name) || 'Local a anunciar';
    var online = /on-?line|remoto|streaming/i.test(place);
    return '<div class="' + rowCls + '" tabindex="0" role="button" data-ev-open="' + esc(e.id) + '">' +
      '<div class="date-box"><span class="date-day">' + esc(db.d) + '</span><span class="date-month">' + esc(db.m) + '</span></div>' +
      '<div class="event-details"><h4>' + esc(e.title) + '</h4>' +
      '<div class="event-meta">' +
      '<span><i class="ri-time-line"></i>' + esc(e.startTime || '—') + '</span>' +
      '<span><i class="' + (online ? 'ri-wifi-line' : 'ri-map-pin-2-line') + '"></i>' + esc(place) + '</span>' +
      '</div></div>' +
      '<div class="event-action">' + st.html + '</div>' +
      '</div>';
  }

  function emptyRail() {
    return '<div class="pev-empty">Nenhum evento neste recorte</div>';
  }

  function plural(n, one, many) {
    return n + ' ' + (n === 1 ? one : (many || one + 's'));
  }

  /* ── MOTOR DE CARGA INFINITA + REVEAL ───────────────────────────────────────
     Injeta a lista em páginas: um sentinela no fim do container dispara a
     página seguinte quando entra na viewport. Cada item novo nasce com
     .pev-reveal e sobe com atraso escalonado (delay por índice dentro da
     página, nunca acumulando pela lista inteira — senão o último cartão de uma
     lista longa levaria minutos pra aparecer).
     "Morre" de verdade quando acaba: o observer é desconectado, o sentinela é
     removido do DOM e as classes de reveal saem do elemento no transitionend,
     pra não sobrar estilo inline nem listener pendurado.
     Devolve um handle com .destroy() — chamado a cada re-render pra nunca
     empilhar observers órfãos. ── */
  function mountInfinite(container, list, renderItem, opts) {
    if (!container) return { destroy: function () {} };
    var o = opts || {};
    var pageSize = o.pageSize || 8;
    var step = o.stagger || 55;
    var i = 0;
    var dead = false;
    var io = null;

    var sentinel = d.createElement('div');
    sentinel.className = 'pev-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');

    function settle(el) {
      var done = function () {
        el.classList.remove('pev-reveal', 'is-in');
        el.style.transitionDelay = '';
        el.removeEventListener('transitionend', done);
      };
      el.addEventListener('transitionend', done);
      /* rede de segurança: se a transição não disparar (aba oculta, motion
         reduzido), limpa mesmo assim */
      setTimeout(done, 1200);
    }

    function page() {
      if (dead) return;
      var slice = list.slice(i, i + pageSize);
      if (!slice.length) return finish();
      var frag = d.createDocumentFragment();
      slice.forEach(function (item, n) {
        var holder = d.createElement('div');
        holder.innerHTML = renderItem(item);
        var el = holder.firstElementChild;
        if (!el) return;
        el.classList.add('pev-reveal');
        el.style.transitionDelay = (n * step) + 'ms';
        frag.appendChild(el);
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            el.classList.add('is-in');
            settle(el);
          });
        });
      });
      container.insertBefore(frag, sentinel);
      i += pageSize;
      if (i >= list.length) finish();
    }

    function finish() {
      dead = true;
      if (io) { io.disconnect(); io = null; }
      if (sentinel.parentNode) sentinel.parentNode.removeChild(sentinel);
    }

    container.appendChild(sentinel);

    if ('IntersectionObserver' in w) {
      io = new IntersectionObserver(function (entries) {
        if (entries.some(function (e) { return e.isIntersecting; })) page();
      }, { root: o.scrollRoot || null, rootMargin: '240px 0px' });
      io.observe(sentinel);
      page(); /* primeira dobra imediata — nunca esperar o scroll pra ter conteúdo */
    } else {
      while (!dead) page(); /* sem IO: entrega tudo de uma vez */
    }

    return { destroy: finish };
  }

  function findChip(key) {
    var chips = (P().taxChips && P().taxChips()) || [];
    for (var i = 0; i < chips.length; i++) if (chips[i].key === key) return chips[i];
    return null;
  }

  /* BUGFIX (2026-08-01) — "no events for August" in the browse grid.
     taxChips() always pushes the 'festajulina' chip FIRST (chips[0]), because
     that window (Jun 1–Aug 1) needs to be selectable year-round even in months
     with none of its own events. This function used to fall through to
     `chips[0].key` for every month that ISN'T inside the julina window — i.e.
     eleven months of the year — which pinned #pevDynamic to the Festa Julina
     taxonomy by default even in, say, August. eventsForTax('festajulina', ym)
     then correctly returns nothing for an August cursor (its own filter is
     Jun–Aug exclusive), so the grid looked empty even with published August
     events in window.APOLLO_EVENTS — they just weren't in the section HTML
     was displaying by default.

     Fix: outside the julina window, default to whichever season chip actually
     has the most events in the cursor month — the browse grid should open on
     "this month's events", not on an unrelated fixed taxonomy. */
  function defaultTaxKey(ym) {
    var banner = P().seasonBanner ? P().seasonBanner(ym) : '';
    if (/Festa Julina/i.test(banner)) return 'festajulina';

    var monthPool = (P().byMonth && P().byMonth(ym)) || [];
    var counts = {};
    monthPool.forEach(function (e) {
      var s = e.season || '';
      if (s) counts[s] = (counts[s] || 0) + 1;
    });
    var bestSeason = null, n = 0;
    Object.keys(counts).forEach(function (k) {
      if (counts[k] > n) { n = counts[k]; bestSeason = k; }
    });
    if (bestSeason) {
      var seasonChip = findChip('season-' + bestSeason);
      if (seasonChip) return seasonChip.key;
    }

    var chips = (P().taxChips && P().taxChips()) || [];
    return chips.length ? chips[0].key : 'festajulina';
  }

  function AppPortalEventos() {
    var root = null;
    /* feeds de carga infinita vivos — destruídos a cada re-render pra nunca
       acumular IntersectionObserver órfão apontando pra DOM já descartado */
    var feedsByKey = {};
    var lightboxFeed = null;
    var heroTimer = null;
    /* Último resultado de buildRails() pro par padrão, indexado por chave.
       A lightbox de "ver todos" consome isto — antes ela recalculava o título e
       a janela de dias sozinha, o que a tornava um SEGUNDO dono da mesma regra:
       o dia em que buildRails passou a conhecer o mês, a lightbox continuaria
       anunciando "Esse FDS · hoje → +4 dias" por cima da lista de dezembro. */
    var lastDefaultRails = {};
    function killFeed(key) {
      if (feedsByKey[key] && feedsByKey[key].destroy) feedsByKey[key].destroy();
      delete feedsByKey[key];
    }
    function killFeeds() {
      Object.keys(feedsByKey).forEach(killFeed);
      feedsByKey = {};
    }
    function setFeed(key, handle) {
      killFeed(key);
      feedsByKey[key] = handle;
    }
    var state = {
      mode: 'mes',
      ym: null,
      tax: null,
      /* Escritos só pelo cabeçalho, via setFilters/setSearch. `search` fica
         sempre em minúsculas porque é comparado contra um haystack minúsculo. */
      tags: [],
      sounds: [],
      search: '',
      heroIndex: 0,
      sectionViews: { 'neste-fds': 'grid', 'prox-fds': 'grid', tax: 'grid' }
    };

    function cursorDate() {
      var p = P().parseYm(state.ym);
      return new Date(p.y, p.m, 1);
    }

    /* ── SEM MASTHEAD ─────────────────────────────────────────────────────────
       Até 1.7.0 o skeleton abria com `<header class="pev-masthead">`: mês,
       setas de navegação e um dropdown de período, tudo montado aqui dentro
       como string de JS. Isso saiu inteiro.

       O cabeçalho de /eventos agora é o bloco apollo_listing_header() —
       "Header 02 · Kinetic Mask · Apple", renderizado em PHP por
       template-parts/archive/portal/header.php ANTES de #apollo-portal-root, e
       ligado a este runtime por header-bridge.php via CustomEvent.

       Três consequências que valem o registro, porque são o motivo da troca:
       o cabeçalho passa a existir no primeiro paint (não depende deste
       runtime); vira reutilizável por qualquer listagem do ecossistema; e o
       filtro deixa de ser só "período" — o painel full-viewport carrega
       período + categorias + tipos + tags + sons, todos de termos reais.

       Este arquivo, portanto, não desenha mais cabeçalho nenhum. Ele expõe
       setMonth/setFilters/setSearch e obedece. ─────────────────────────────── */
    function skeleton() {
      return '' +
        '<div class="pev" data-apollo-portal="1">' +
          '<div class="pev-layout">' +
            '<section class="pev-hero" id="pevHero" data-ev-open="" tabindex="0" role="button" aria-label="Evento em destaque"></section>' +
            '<aside class="pev-recent">' +
              '<h2 class="pev-recent-title">Últimos eventos registrados</h2>' +
              /* O rodapé "Ver todos" vive DENTRO da lista, não embaixo dela: a
                 .event-list-container é uma coluna flex, então o botão entra
                 como o último item dessa mesma coluna e o olho lê uma lista só
                 terminando numa ação — e não um bloco solto de largura cheia.
                 renderRecent() reescreve o innerHTML da lista inteira, rodapé
                 incluído, para o id nunca ficar órfão. */
              '<div class="event-list-container" id="pevRecent">' +
                '<div class="pev-recent-footer" id="pevRecentFooter"></div>' +
              '</div>' +
            '</aside>' +
          '</div>' +
          '<div class="pev-rails" id="pevRails"></div>' +
          '<div class="pev-browse">' +
            '<div class="pev-tax" id="pevTax" role="tablist" aria-label="Temporada e gênero"></div>' +
            '<div class="pev-dynamic eve-section" id="pevDynamic"></div>' +
          '</div>' +
        '</div>' +
        '<div class="pev-modal" id="pevModal" aria-hidden="true">' +
          '<div class="pev-modal-card" role="dialog" aria-modal="true" aria-labelledby="pevModalTitle">' +
            '<button type="button" class="pev-modal-close" data-pev-close aria-label="Fechar"><i class="ri-close-line"></i></button>' +
            '<div id="pevModalInner"></div>' +
          '</div>' +
        '</div>' +
        '<div class="pev-modal pev-rail-modal" id="pevRailModal" aria-hidden="true">' +
          '<div class="pev-modal-card pev-rail-modal-card" role="dialog" aria-modal="true" aria-labelledby="pevRailModalTitle">' +
            '<button type="button" class="pev-modal-close" data-pev-rail-close aria-label="Fechar"><i class="ri-close-line"></i></button>' +
            '<div class="pev-rail-modal-body">' +
              '<h2 id="pevRailModalTitle">Próximos eventos</h2>' +
              '<p class="pev-rail-modal-sub" id="pevRailModalSub"></p>' +
              '<div class="event-list-container" id="pevRailModalList"></div>' +
            '</div>' +
          '</div>' +
        '</div>';
    }

    /* ── REFINO: filtros de taxonomia + busca ─────────────────────────────────
       O cabeçalho (apollo_listing_header) manda intenção; o refino acontece
       aqui, sobre as MESMAS linhas que o portal já tem em memória — nenhuma
       ida ao servidor, nenhuma segunda fonte de verdade.

       Casamento de campos, definido por header.php e transportado por
       header-bridge.php:
         state.tags   ↔ e.tags    (NOMES de termo: categoria + tipo + tag,
                                   fundidos numa lista só por archive-event.php)
         state.sounds ↔ e.genres  (SLUGS da taxonomia sound)
       Grupo vazio não filtra nada — OR dentro do grupo, AND entre grupos, que
       é o que um painel de chips comunica visualmente. */
    function hitsAny(haystack, needles) {
      if (!needles || !needles.length) return true;
      var pool = haystack || [];
      for (var i = 0; i < needles.length; i++) {
        if (pool.indexOf(needles[i]) !== -1) return true;
      }
      return false;
    }

    function matchesSearch(e) {
      if (!state.search) return true;
      var hay = [
        e.title || '',
        (e.venue && e.venue.name) || '',
        (e.lineup || []).map(function (l) { return l.name || ''; }).join(' '),
        (e.genres || []).join(' '),
        (e.tags || []).join(' ')
      ].join(' ').toLowerCase();
      return hay.indexOf(state.search) !== -1;
    }

    /** Aplica busca + chips a qualquer lista de eventos do portal. */
    function refine(list) {
      if (!state.search && !state.tags.length && !state.sounds.length) return list || [];
      return (list || []).filter(function (e) {
        return hitsAny(e.tags, state.tags) &&
          hitsAny(e.genres, state.sounds) &&
          matchesSearch(e);
      });
    }

    function renderTax() {
      var host = root.querySelector('#pevTax');
      if (!host) return;
      var chips = (P().taxChips && P().taxChips()) || [];
      if (!state.tax || !findChip(state.tax)) state.tax = defaultTaxKey(state.ym);
      /* Busca ativa vira o primeiro chip da fileira, com um X. O termo tem que
         estar visível na tela que ele está filtrando — senão o usuário vê uma
         lista curta e não sabe por quê. */
      /* `btn btn-secondary` é o botão universal do core.js. Os chips de
         taxonomia SÃO botões secundários — antes eram uma cápsula própria com
         borda em gradiente e rótulo mono maiúsculo, que reimplementava (e
         contradizia) o mesmo componente. `pev-tax-btn` sobrou só como gancho
         para os deltas da fileira (não encolher, rótulo com reticências) e
         `is-on` para o estado selecionado, que o core não tem. */
      var taxBtnClass = 'btn btn-secondary pev-tax-btn';
      var searchChip = state.search
        ? '<button type="button" class="' + taxBtnClass + ' is-on" data-pev-search-clear title="Limpar busca">' +
            '<span class="pev-tax-label">Busca · ' + esc(state.search) + '</span>' +
            '<i class="ri-close-line" aria-hidden="true"></i>' +
          '</button>'
        : '';
      host.innerHTML = searchChip + chips.map(function (c) {
        return '<button type="button" class="' + taxBtnClass + (c.key === state.tax ? ' is-on' : '') + '" data-pev-tax="' + esc(c.key) + '" title="' + esc(c.label) + '">' +
          '<span class="pev-tax-label">' + esc(c.label) + '</span>' +
          '</button>';
      }).join('');
    }

    function poolForTax() {
      var list = (P().eventsForTax && P().eventsForTax(state.tax, state.ym)) || [];
      if (state.mode && state.mode !== 'mes') {
        list = (P().filterByMode && P().filterByMode(state.mode, cursorDate(), list)) || list;
      }
      return refine(list);
    }

    function listForFeed(feedKey) {
      if (feedKey === 'tax') return poolForTax();
      var rails = (P().buildRails && P().buildRails(state.ym, state.mode, cursorDate())) || [];
      for (var i = 0; i < rails.length; i++) {
        if (rails[i].key === feedKey) {
          /* refine() aqui também: trocar grade↔lista não pode ressuscitar
             eventos que os chips do cabeçalho tinham acabado de excluir. */
          return refine((P().resolveIds && P().resolveIds(rails[i].eventIds)) || []);
        }
      }
      return [];
    }

    function setPevViewToggleState(feedKey) {
      if (!root) return;
      var icon = root.querySelector('.pev-view-toggle[data-target="' + feedKey + '"] [data-view]');
      if (!icon) return;
      var view = state.sectionViews[feedKey] || 'grid';
      icon.classList.remove('ri-list-check-2', 'ri-gallery-view');
      icon.classList.add(view === 'list' ? 'ri-gallery-view' : 'ri-list-check-2');
      icon.setAttribute('data-view', view);
      var label = view === 'list' ? 'Ver em grade' : 'Ver em lista';
      icon.setAttribute('title', label);
      icon.setAttribute('aria-label', label);
    }

    function mountSectionFeed(feedKey, list) {
      var el = root.querySelector('[data-feed="' + feedKey + '"]');
      if (!el || !list.length) return;
      var view = state.sectionViews[feedKey] || 'grid';
      var renderer = view === 'list'
        ? eventRowHTML
        : (feedKey === 'tax' ? eveCardMiniHTML : eveCardHTML);
      var pageSize = feedKey === 'tax' ? 12 : 8;
      setFeed(feedKey, mountInfinite(el, list, renderer, { pageSize: pageSize, stagger: 60 }));
    }

    function toggleSectionView(feedKey) {
      if (!feedKey) return;
      var cur = state.sectionViews[feedKey] || 'grid';
      state.sectionViews[feedKey] = cur === 'list' ? 'grid' : 'list';
      var view = state.sectionViews[feedKey];
      var list = listForFeed(feedKey);
      var body = null;
      if (feedKey === 'tax') {
        var dyn = root.querySelector('#pevDynamic');
        body = dyn && dyn.querySelector('.eve-body');
      } else {
        var sec = root.querySelector('[data-rail="' + feedKey + '"]');
        body = sec && sec.querySelector('.eve-body');
      }
      if (!body) return;
      killFeed(feedKey);
      body.innerHTML = feedContainerHTML(feedKey, !!list.length, view);
      body.classList.toggle('is-list-view', view === 'list');
      mountSectionFeed(feedKey, list);
      setPevViewToggleState(feedKey);
    }

    /* v3 · MOBILE APP FIRST — todas as listagens de evento nascem em GRADE
       (.grid-layout = 2 por linha no mobile, 4 no ≥1000px), exatamente como a
       lei do showcase: "ONLOAD: as seções nascem em GRADE". Nada de trilho
       horizontal por padrão — isso quebrava a leitura no celular. */
    function renderDynamic() {
      var host = root.querySelector('#pevDynamic');
      if (!host) return;
      var chip = findChip(state.tax);
      var kind = chip ? (chip.kind === 'genre' ? 'Gênero' : 'Temporada') : '';
      var label = chip ? (chip.label + (kind ? ' · ' + kind : '')) : 'Eventos';
      var list = poolForTax();
      var view = state.sectionViews.tax || 'grid';
      host.innerHTML =
        sectionHeadHTML(label, chip && chip.icon, list.length, 'tax', view) +
        '<div class="eve-body' + (view === 'list' ? ' is-list-view' : '') + '">' +
          feedContainerHTML('tax', !!list.length, view) +
        '</div>';
      mountSectionFeed('tax', list);
    }

    /* ── HERO · carrossel dos destaques ───────────────────────────────────────
       Fonte: APOLLO_PORTAL.highlights() — eventos futuros com highlight:true.
       Cada slide fica HERO_DWELL no ar; a bolinha correspondente é uma barra
       de progresso que enche durante esse tempo (é o mesmo elemento: a "bola"
       é o trilho, o preenchimento é o ::after animado). Pausa no hover/foco e
       quando a aba sai de vista — nada de contador correndo escondido. ── */
    var HERO_DWELL = 6200;

    function stopHero() {
      if (heroTimer) { clearTimeout(heroTimer); heroTimer = null; }
    }

    function heroPool() {
      return (P().highlights && P().highlights()) || [];
    }

    function heroSlideHTML(ev, i, active) {
      return '<article class="pev-hero-slide' + (active ? ' is-on' : '') + '" data-hero-slide="' + i + '"' +
        (active ? '' : ' aria-hidden="true"') + '>' +
        '<img class="pev-hero-cover" src="' + esc(ev.cover || '') + '" alt="" ' + (i === 0 ? '' : 'loading="lazy" ') + 'decoding="async">' +
        '<div class="pev-hero-fade"></div>' +
        '<div class="pev-hero-body">' +
          '<span class="pev-hero-kicker"><i class="ri-sparkling-line"></i> Destaque</span>' +
          '<h1>' + esc(ev.title) + '</h1>' +
          '<div class="pev-hero-meta">' +
            ticketChip(ev.tickets) +
            '<span class="pev-chip"><i class="ri-calendar-line"></i> ' + esc(dateShort(ev.startDate)) + (ev.startTime ? ' · ' + esc(ev.startTime) : '') + '</span>' +
            '<span class="pev-chip"><i class="ri-map-pin-2-line"></i> ' + esc((ev.venue && ev.venue.name) || 'Local') + '</span>' +
          '</div>' +
        '</div>' +
      '</article>';
    }

    function paintHero(list) {
      var el = root.querySelector('#pevHero');
      if (!el) return;
      var i = state.heroIndex;
      var cur = list[i];
      el.setAttribute('data-ev-open', cur ? cur.id : '');
      el.querySelectorAll('[data-hero-slide]').forEach(function (s) {
        var on = +s.getAttribute('data-hero-slide') === i;
        s.classList.toggle('is-on', on);
        if (on) s.removeAttribute('aria-hidden');
        else s.setAttribute('aria-hidden', 'true');
      });
      el.querySelectorAll('[data-hero-dot]').forEach(function (dot) {
        var n = +dot.getAttribute('data-hero-dot');
        dot.classList.toggle('is-done', n < i);
        dot.classList.toggle('is-on', n === i);
        dot.setAttribute('aria-selected', n === i ? 'true' : 'false');
        if (n === i) {
          /* reinicia a animação de preenchimento desta bolinha */
          var fill = dot.querySelector('.pev-hero-dot-fill');
          if (fill) { fill.style.animation = 'none'; void fill.offsetWidth; fill.style.animation = ''; }
        }
      });
    }

    function scheduleHero(list) {
      stopHero();
      if (list.length < 2) return;
      if (reducedMotion()) return; /* sem auto-play se o usuário pediu menos movimento */
      heroTimer = setTimeout(function () {
        state.heroIndex = (state.heroIndex + 1) % list.length;
        paintHero(list);
        scheduleHero(list);
      }, HERO_DWELL);
    }

    /* ── CAMADA FINAL DE FALLBACK ───────────────────────────────────────────
       Contrato: quando NENHUM evento carrega highlight:true (P().highlights()
       devolve lista vazia), o hero não inventa um destaque — ele cai nesta
       camada. Ver helpers.php: o fallback pra featured() foi removido de
       propósito, porque rotular o melhor evento do mês como "Destaque" afirma
       uma decisão editorial que ninguém tomou.

       São QUATRO camadas empilhadas, da mais resiliente pra mais frágil, e o
       cartão continua legível mesmo que as de cima falhem:
         0 · degradê CSS  (.pev-hero.is-fallback) — sobrevive a tudo
         1 · <iframe> do CDN                      — o visual rico
         2 · véu                                  — garante contraste
         3 · mensagem                             — sempre por cima

       X-FRAME-OPTIONS, E COMO ELE FOI RESOLVIDO (2026-08-01)
       O embed DIRETO em assets.apollo.rio.br era recusado pelo navegador:
         "Refused to display 'https://assets.apollo.rio.br/' in a frame
          because it set 'X-Frame-Options' to 'sameorigin'."
       Esse cabeçalho é do HOST DO ASSET e vale contra a origem do documento
       EMBUTIDO. apollo.rio.br embutindo assets.apollo.rio.br é cross-origin,
       então "sameorigin" proíbe — e nenhum CSP, sandbox ou atributo daqui
       derruba um cabeçalho mandado por outro servidor. Também não dá pra
       DETECTAR o bloqueio no cliente: a origem é opaca (contentDocument é
       inacessível) e o Chrome ainda dispara 'load' na navegação bloqueada.

       Solução: parar de fazer a requisição ser cross-origin. O documento é
       buscado NO SERVIDOR e re-servido por este domínio —
         /wp-json/apollo/v1/iframe/highlighted-fallback
       (includes/iframe-proxy.php: allowlist fixa de slugs, nunca uma URL do
       chamador, com cache em transient). O <iframe> passa a ser same-origin,
       o SAMEORIGIN é satisfeito por construção, e o proxy reemite o mesmo
       X-Frame-Options em nome próprio pra não virar embed livre pra terceiros.

       O src é lido de #apollo-portal-root[data-iframe-fallback], impresso por
       archive-event.php via apollo_event_iframe_proxy_url() — a URL do REST
       vem do WordPress, nunca de string montada no cliente. Se o atributo não
       existir (tema antigo), a camada some e o degradê CSS assume. ── */

    function heroFallbackSrc() {
      return (root && root.getAttribute('data-iframe-fallback')) || '';
    }

    function heroFallbackHTML() {
      var src = heroFallbackSrc();
      return '' +
        (src
          ? '<iframe class="pev-hero-fallback" src="' + esc(src) + '"' +
              ' title="Destaques do Portal de Eventos" loading="eager" scrolling="no"' +
              ' referrerpolicy="no-referrer" sandbox="allow-scripts"' +
              ' tabindex="-1" aria-hidden="true"' +
              ' onload="this.classList.add(\'is-ready\')"' +
              ' onerror="this.remove()"></iframe>'
          : '') +
        '<div class="pev-hero-fallback-shade" aria-hidden="true"></div>' +
        '<div class="pev-hero-body is-empty">' +
          '<span class="pev-hero-kicker"><i class="ri-sparkling-line" aria-hidden="true"></i> Sem destaque</span>' +
          '<h1>Nenhum evento em destaque</h1>' +
          '<div class="pev-hero-meta">' +
            '<span class="pev-chip">Troque o mês ou o filtro</span>' +
          '</div>' +
        '</div>';
    }

    function renderHero() {
      var el = root.querySelector('#pevHero');
      if (!el) return;
      stopHero();
      var list = heroPool();
      if (!list.length) {
        el.classList.remove('is-slider', 'is-paused');
        el.classList.add('is-fallback');
        el.removeAttribute('data-ev-open');
        /* Sem destaque não há evento pra abrir: o cartão deixa de ser um botão
           em vez de virar um botão que não faz nada. */
        el.removeAttribute('role');
        el.removeAttribute('tabindex');
        el.setAttribute('aria-label', 'Nenhum evento em destaque');
        el.onmouseenter = el.onmouseleave = el.onfocusin = el.onfocusout = null;
        el.innerHTML = heroFallbackHTML();
        return;
      }
      /* Voltando a ter destaque, o cartão reassume o papel de botão. */
      el.setAttribute('role', 'button');
      el.setAttribute('tabindex', '0');
      el.setAttribute('aria-label', 'Evento em destaque');
      if (state.heroIndex >= list.length) state.heroIndex = 0;
      el.classList.remove('is-fallback');
      el.classList.add('is-slider');
      el.style.setProperty('--pev-hero-dwell', HERO_DWELL + 'ms');
      el.innerHTML =
        list.map(function (ev, i) { return heroSlideHTML(ev, i, i === state.heroIndex); }).join('') +
        (list.length > 1
          ? '<div class="pev-hero-dots" role="tablist" aria-label="Destaques">' +
              list.map(function (ev, i) {
                return '<button type="button" class="pev-hero-dot" data-hero-dot="' + i + '" role="tab"' +
                  ' aria-selected="' + (i === state.heroIndex ? 'true' : 'false') + '"' +
                  ' aria-label="' + esc(ev.title) + '"><span class="pev-hero-dot-fill"></span></button>';
              }).join('') +
            '</div>'
          : '');
      paintHero(list);
      scheduleHero(list);

      /* pausa cortês: hover, foco por teclado ou aba escondida */
      el.onmouseenter = function () { el.classList.add('is-paused'); stopHero(); };
      el.onmouseleave = function () { el.classList.remove('is-paused'); scheduleHero(list); };
      el.onfocusin = function () { el.classList.add('is-paused'); stopHero(); };
      el.onfocusout = function () { el.classList.remove('is-paused'); scheduleHero(list); };
    }

    function goHero(i) {
      var list = heroPool();
      if (!list.length) return;
      state.heroIndex = ((i % list.length) + list.length) % list.length;
      paintHero(list);
      scheduleHero(list);
    }

    /* Últimos registrados: no máximo 3 linhas na coluna — o resto vive no
       lightbox, que carrega em rolagem infinita. Uma barra lateral não é lugar
       pra lista longa; 3 é o que cabe ao lado do hero sem empurrar layout. */
    var RECENT_PREVIEW = 3;
    function renderRecent() {
      var track = root.querySelector('#pevRecent');
      if (!track) return;
      var all = (P().recent && P().recent(999)) || [];
      var rows = all.length
        ? all.slice(0, RECENT_PREVIEW).map(eventRowHTML).join('')
        : '<div class="pev-empty">Nenhum evento recente</div>';
      var more = all.length > RECENT_PREVIEW
        ? '<button type="button" class="pev-rail-more" data-pev-rail-all="recent">Ver todos · ' + plural(all.length, 'evento') + '</button>'
        : '';
      /* Uma escrita só: o rodapé é filho da lista (ver skeleton()), então
         reescrever a lista sem ele deixaria o #pevRecentFooter fora do DOM. */
      track.innerHTML = rows +
        '<div class="pev-recent-footer" id="pevRecentFooter">' + more + '</div>';
    }

    /* ── O PAR DE TRILHOS PADRÃO ───────────────────────────────────────────────
       Estas duas seções são a única parte da tela que muda de REGIME quando o
       mês muda (ver buildRails em helpers.php): no mês corrente são recortes
       relativos a hoje, em qualquer outro mês a primeira vira a lista do mês e
       a segunda não existe.

       Por isso aqui não há mais `host.innerHTML = …` a cada render. Um wipe
       total é instantâneo e destrói o que se quer animar: não dá pra morfar
       "Esse FDS" → "Neste mês" num <h2> que acabou de ser recriado, nem colapsar
       uma seção que já sumiu do DOM. O host é montado UMA vez; depois disso
       cada render faz cirurgia por `data-rail`: título morfa, corpo remonta,
       seção colapsa ou volta. ── */

    var railIcon = function (key) {
      return key === 'neste-fds' ? 'ri-calendar-check-line' : 'ri-calendar-schedule-line';
    };

    /** Piscina já resolvida e refinada pelos chips do cabeçalho. */
    function railEvents(rail) {
      return refine((P().resolveIds && P().resolveIds(rail.eventIds)) || []);
    }

    function railTitleEl(sec) {
      return sec ? sec.querySelector('[data-rail-title]') : null;
    }

    /** Máscara de troca de texto — MESMA receita do mês no cabeçalho
        (listing-header/kernel-scripts.php::paintMonth): limpa rápido em
        power1.in (0.39) e revela devagar em power2.out (0.78), pro rótulo novo
        parecer que CHEGA em vez de piscar. Sem GSAP ou com movimento reduzido,
        troca seca — a informação nunca depende da animação. */
    function morphRailTitle(sec, next) {
      var el = railTitleEl(sec);
      if (!el || el.textContent === next) return;
      if (!w.gsap || reducedMotion()) { el.textContent = next; return; }
      w.gsap.killTweensOf(el);
      w.gsap.timeline()
        .to(el, { clipPath: 'inset(0 100% 0 0)', duration: 0.39, ease: 'power1.in' })
        .call(function () { el.textContent = next; })
        .set(el, { clipPath: 'inset(0 100% 0 0)' })
        .to(el, { clipPath: 'inset(0 0% 0 0)', duration: 0.78, ease: 'power2.out' });
    }

    /** Sai do fluxo de verdade: `hidden` no fim é o que zera também o `gap` de
        22px do .pev-rails — só animar a altura pra 0 deixaria o buraco. */
    function collapseRail(sec, animate) {
      if (!sec || sec.hasAttribute('hidden')) return;
      var settle = function () {
        sec.setAttribute('hidden', '');
        sec.setAttribute('aria-hidden', 'true');
        sec.classList.remove('is-exiting');
        sec.style.height = '';
        sec.style.opacity = '';
      };
      if (!animate || !w.gsap || reducedMotion()) { settle(); return; }
      var h = sec.offsetHeight;
      sec.classList.add('is-exiting');
      w.gsap.killTweensOf(sec);
      w.gsap.timeline({ onComplete: settle })
        .to(sec, { opacity: 0, duration: 0.2, ease: 'power1.in' })
        .fromTo(sec, { height: h }, { height: 0, duration: 0.36, ease: 'power2.inOut' }, '-=0.06');
    }

    function expandRail(sec, animate) {
      if (!sec) return;
      var wasHidden = sec.hasAttribute('hidden');
      sec.removeAttribute('hidden');
      sec.removeAttribute('aria-hidden');
      if (!wasHidden) return;
      if (!animate || !w.gsap || reducedMotion()) return;
      sec.classList.add('is-exiting'); /* mesma clipagem, sentido inverso */
      var h = sec.offsetHeight;
      w.gsap.killTweensOf(sec);
      w.gsap.timeline({
        onComplete: function () {
          sec.classList.remove('is-exiting');
          sec.style.height = '';
          sec.style.opacity = '';
        }
      })
        .fromTo(sec, { height: 0, opacity: 0 }, { height: h, duration: 0.36, ease: 'power2.out' })
        .to(sec, { opacity: 1, duration: 0.22, ease: 'power1.out' }, '-=0.16');
    }

    /** Molde da seção — usado só na PRIMEIRA montagem; depois é tudo cirurgia. */
    function railSectionHTML(rail, evs, view) {
      return '<section class="pev-rail eve-section" data-rail="' + esc(rail.key) + '"' +
          ' data-window="' + esc(rail.subtitle || '') + '">' +
          sectionHeadHTML(rail.title, railIcon(rail.key), evs.length, rail.key, view) +
          '<div class="eve-body' + (view === 'list' ? ' is-list-view' : '') + '">' +
            feedContainerHTML(rail.key, !!evs.length, view) +
          '</div>' +
        '</section>';
    }

    function renderRails() {
      var host = root.querySelector('#pevRails');
      if (!host) return;
      var rails = (P().buildRails && P().buildRails(state.ym, state.mode, cursorDate())) || [];
      rails = rails.filter(function (r) {
        return r.key === 'neste-fds' || r.key === 'prox-fds';
      });

      /* Primeira pintura: não há o que morfar nem o que colapsar, então monta
         seco. Toda troca de mês a partir daqui cai no ramo cirúrgico. */
      var mounted = !!host.querySelector('[data-rail]');
      if (!mounted) {
        host.innerHTML = rails.map(function (rail) {
          return railSectionHTML(rail, railEvents(rail), state.sectionViews[rail.key] || 'grid');
        }).join('');
      }

      rails.forEach(function (rail) {
        var sec = host.querySelector('[data-rail="' + esc(rail.key) + '"]');
        if (!sec) return;
        var evs = railEvents(rail);
        var visible = rail.visible !== false;
        var view = state.sectionViews[rail.key] || 'grid';

        /* A janela de dias ("hoje → +4 dias", "01 → fim do mês") é REGRA DE
           NEGÓCIO, não rótulo de interface: vive em data-window, legível por
           quem inspeciona e invisível pra quem lê. O badge conta os eventos. */
        sec.setAttribute('data-window', rail.subtitle || '');
        if (mounted) morphRailTitle(sec, rail.title);

        var count = sec.querySelector('.pev-section-count');
        if (count) count.textContent = String(evs.length);

        /* O corpo é sempre remontado: a piscina mudou de regime, não de ordem.
           Um feed infinito herdado apontaria pro recorte antigo. */
        var body = sec.querySelector('.eve-body');
        if (body) {
          killFeed(rail.key);
          body.innerHTML = feedContainerHTML(rail.key, !!evs.length, view);
          body.classList.toggle('is-list-view', view === 'list');
          /* Só monta o que vai ser visto — um IntersectionObserver dentro de
             uma seção display:none nunca dispara e o feed nasceria travado. */
          if (visible && evs.length) mountSectionFeed(rail.key, evs);
        }
        setPevViewToggleState(rail.key);

        if (visible) expandRail(sec, mounted);
        else collapseRail(sec, mounted);
      });

      /* A lightbox lê daqui em vez de recalcular a janela por conta própria —
         era o segundo dono de "Esse FDS" e das regras de dia. */
      lastDefaultRails = {};
      rails.forEach(function (rail) { lastDefaultRails[rail.key] = rail; });
    }

    /* Lightbox de lista completa — rolagem infinita dentro do próprio card do
       modal (scrollRoot = a lista), então o sentinela dispara conforme o
       usuário desce, não conforme a página de fundo rola. */
    function openRailLightbox(railKey) {
      var modal = root.querySelector('#pevRailModal');
      var listEl = root.querySelector('#pevRailModalList');
      var titleEl = root.querySelector('#pevRailModalTitle');
      var subEl = root.querySelector('#pevRailModalSub');
      if (!modal || !listEl) return;
      var key = railKey || 'prox-fds';
      var today = P().todayStart ? P().todayStart() : new Date();
      var evs, title, sub;
      if (key === 'recent') {
        evs = (P().recent && P().recent(999)) || [];
        title = 'Últimos eventos registrados';
        sub = 'do mais recente ao mais antigo';
      } else if (lastDefaultRails[key]) {
        /* Espelha o trilho que está NA TELA — mesmo título, mesma janela, mesma
           piscina. Fora do mês corrente isso é "Neste mês · 01 → fim do mês", e
           'prox-fds' abre vazio de propósito: a seção nem está montada. */
        var meta = lastDefaultRails[key];
        evs = (P().resolveIds && P().resolveIds(meta.eventIds)) || [];
        title = meta.title;
        sub = meta.subtitle;
      } else {
        /* Queda pra quando a lightbox é chamada antes do primeiro render. */
        evs = (P().filterByMode && P().filterByMode(key, today, w.APOLLO_EVENTS || [])) || [];
        title = key === 'neste-fds' ? 'Esse FDS' : 'Próximos eventos';
        sub = key === 'neste-fds' ? 'hoje → +4 dias' : 'hoje+4 → +40 dias';
      }
      if (titleEl) titleEl.textContent = title;
      if (subEl) subEl.textContent = sub + ' · ' + plural(evs.length, 'evento');

      if (lightboxFeed) { lightboxFeed.destroy(); lightboxFeed = null; }
      listEl.innerHTML = '';
      if (!evs.length) {
        listEl.innerHTML = '<div class="pev-empty">Nenhum evento neste recorte</div>';
      } else {
        lightboxFeed = mountInfinite(listEl, evs, eventRowHTML, { pageSize: 8, stagger: 45, scrollRoot: listEl });
      }
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      modal.dataset.railKey = key;
    }

    function closeRailLightbox() {
      var modal = root.querySelector('#pevRailModal');
      if (!modal) return;
      if (lightboxFeed) { lightboxFeed.destroy(); lightboxFeed = null; }
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      delete modal.dataset.railKey;
    }

    /* `dir` só existia para animar o rótulo do mês, que agora vive no
       cabeçalho e anima sozinho (máscara GSAP no kernel do bloco). Mantido na
       assinatura porque renderAll(delta) ainda é chamado assim de fora. */
    function renderAll(dir) { // eslint-disable-line no-unused-vars
      killFeeds();
      renderTax();
      renderDynamic();
      renderHero();
      renderRecent();
      renderRails();
    }

    /* ── ABERTURA DO EVENTO ─────────────────────────────────────────────────
       O cartão abre a PÁGINA COMPLETA do evento numa lightbox de viewport
       inteiro, não um resumo. É o mesmo PHP que monta /evento/{slug} —
       apollo_event_render_single() servido por
       GET /apollo/v1/eventos/{id}/fragmento — então a lightbox e a página não
       podem divergir: uma renderiza, a outra embute, mesmo markup.

       O runtime disso é apollo-event-lightbox.js (ApolloEventLightbox), já
       impresso nesta página por apollo_event_lightbox_boot() no
       archive-event.php. Ele se liga sozinho a [data-ev-open] — o mesmo
       atributo que os cartões agora carregam — então o clique de mouse nem
       passa por aqui. Esta função existe pro TECLADO (Enter/Espaço, que a
       lightbox não escuta) e pra qualquer chamada programática.

       O .pev-modal antigo (resumo com capa + lineup + RSVP) fica como QUEDA:
       se o bundle da lightbox não tiver carregado, o clique ainda abre algo
       útil em vez de não fazer nada. */
    function openModal(id) {
      if (w.ApolloEventLightbox && typeof w.ApolloEventLightbox.open === 'function') {
        w.ApolloEventLightbox.open(id);
        return;
      }
      openQuickView(id);
    }

    function openQuickView(id) {
      var ev = (w.getApolloEvent && w.getApolloEvent(id)) ||
        (function () {
          var list = w.APOLLO_EVENTS || [];
          for (var i = 0; i < list.length; i++) if (list[i].id === id) return list[i];
          return null;
        })();
      if (!ev) return;
      var modal = root.querySelector('#pevModal');
      var inner = root.querySelector('#pevModalInner');
      if (!modal || !inner) return;
      var r = radarOf(ev.id);
      var vou = r && r.rsvp === 'vou';
      var quero = r && r.rsvp === 'quero-ir';
      var lineup = (ev.lineup || []).map(function (l) {
        return '<li><span>' + esc(l.name) + '</span><small>' + esc(l.start || '') + (l.end ? '–' + esc(l.end) : '') + '</small></li>';
      }).join('');
      inner.innerHTML =
        '<img class="pev-modal-cover" src="' + esc(ev.cover || '') + '" alt="">' +
        '<div class="pev-modal-body">' +
          '<h2 id="pevModalTitle">' + esc(ev.title) + '</h2>' +
          '<p>' + esc(ev.about || '') + '</p>' +
          '<div class="pev-modal-meta">' +
            '<span><i class="ri-calendar-line"></i>' + esc(dateShort(ev.startDate)) + (ev.startTime ? ' · ' + esc(ev.startTime) : '') + '</span>' +
            '<span><i class="ri-map-pin-2-line"></i>' + esc((ev.venue && ev.venue.name) || 'Local a anunciar') + '</span>' +
            (ev.venue && ev.venue.address ? '<span><i class="ri-road-map-line"></i>' + esc(ev.venue.address) + '</span>' : '') +
            '<span><i class="ri-ticket-2-line"></i>' + esc((P().ticketLabel && P().ticketLabel(ev.tickets)) || ev.tickets || '—') + '</span>' +
          '</div>' +
          (lineup ? '<ul class="pev-lineup">' + lineup + '</ul>' : '') +
        '</div>' +
        '<div class="pev-modal-actions">' +
          '<button type="button" class="btn btn-sm ' + (vou ? 'btn-primary' : 'btn-secondary') + '" data-rsvp="vou" data-ev="' + esc(ev.id) + '">' + (vou ? 'Vou ✓' : 'Vou') + '</button>' +
          '<button type="button" class="btn btn-sm ' + (quero ? 'btn-primary' : 'btn-secondary') + '" data-rsvp="quero-ir" data-ev="' + esc(ev.id) + '">' + (quero ? 'Quero ir ✓' : 'Quero ir') + '</button>' +
          (ev.ticketsUrl ? '<a class="btn btn-sm btn-secondary" href="' + esc(ev.ticketsUrl) + '" target="_blank" rel="noopener">Ingressos</a>' : '') +
        '</div>';
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      modal.dataset.openId = ev.id;
    }

    function closeModal() {
      var modal = root.querySelector('#pevModal');
      if (!modal) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      delete modal.dataset.openId;
    }

    function onClick(e) {
      /* Chip "Busca · x": limpa a busca aqui e avisa o cabeçalho para esvaziar
         o campo dele (header-bridge.php escuta apollo:pev:search-clear). */
      if (e.target.closest('[data-pev-search-clear]')) {
        setSearch('');
        d.dispatchEvent(new CustomEvent('apollo:pev:search-clear'));
        return;
      }
      var vtHost = e.target.closest('.pev-view-toggle');
      if (vtHost) {
        e.preventDefault();
        e.stopPropagation();
        toggleSectionView(vtHost.getAttribute('data-target'));
        return;
      }
      var railAll = e.target.closest('[data-pev-rail-all]');
      if (railAll) {
        openRailLightbox(railAll.getAttribute('data-pev-rail-all') || 'prox-fds');
        return;
      }
      if (e.target.closest('[data-pev-rail-close]')) { closeRailLightbox(); return; }
      if (e.target.id === 'pevRailModal' || (e.target.classList && e.target.classList.contains('pev-rail-modal'))) {
        closeRailLightbox();
        return;
      }
      var taxBtn = e.target.closest('[data-pev-tax]');
      if (taxBtn) {
        state.tax = taxBtn.getAttribute('data-pev-tax');
        killFeeds();
        renderTax();
        renderDynamic();
        renderRails();
        return;
      }
      if (e.target.closest('[data-pev-close]')) { closeModal(); return; }
      if (e.target.classList && e.target.classList.contains('pev-modal') && !e.target.classList.contains('pev-rail-modal')) {
        closeModal();
        return;
      }
      var rsvp = e.target.closest('[data-rsvp]');
      if (rsvp) {
        setRsvp(rsvp.getAttribute('data-ev'), rsvp.getAttribute('data-rsvp'));
        if (typeof w.toast === 'function') w.toast('Radar atualizado ✓');
        var cnt = d.querySelector('[data-cnt="radar"]');
        if (cnt) cnt.textContent = '(' + radar().length + ')';
        /* openQuickView, não openModal: isto REPINTA o resumo que já está
           aberto. openModal agora delega pra lightbox de página inteira, o que
           aqui trocaria a tela do usuário por outra a cada clique de RSVP. */
        var openId = root.querySelector('#pevModal') && root.querySelector('#pevModal').dataset.openId;
        if (openId) openQuickView(openId);
        else renderAll();
        return;
      }
      /* bolinha do hero: navega o slide, NUNCA abre o evento —
         por isso vem antes do handler genérico de abertura */
      var dot = e.target.closest('[data-hero-dot]');
      if (dot) {
        e.preventDefault();
        e.stopPropagation();
        goHero(+dot.getAttribute('data-hero-dot') || 0);
        return;
      }

      /* ── ABERTURA DE EVENTO ────────────────────────────────────────────────
         Os cartões agora carregam [data-ev-open] + href real do permalink, que
         é o CONTRATO DOCUMENTADO da lightbox. apollo-event-lightbox.js tem o
         próprio listener delegado no document pra esse atributo, então o
         caminho normal é: não fazer nada aqui e deixar ele agir.

         Antes o portal inventava um atributo paralelo ([data-pev-open], href
         "#") e abria por conta própria — era por isso que a tela do evento
         nunca aparecia mesmo com a lightbox na página.

         O bloco abaixo só existe pra QUANDO o runtime não carregou: aí sim
         interceptamos e abrimos o resumo, senão o clique não faria nada. Com a
         lightbox presente, saímos sem tocar no evento — sem preventDefault,
         sem stopPropagation — pra que o listener dela receba o clique intacto
         (e ctrl/middle-click continuem abrindo o permalink numa aba nova). */
      var open = e.target.closest('[data-ev-open]');
      if (open && open.getAttribute('data-ev-open')) {
        if (w.ApolloEventLightbox && typeof w.ApolloEventLightbox.open === 'function') {
          return; /* a lightbox cuida — inclusive do preventDefault */
        }
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button) return;
        e.preventDefault();
        openQuickView(open.getAttribute('data-ev-open'));
      }
    }

    /* v3: as setas laterais existiam só pra rolar o trilho horizontal.
       Com tudo em grade, a navegação por teclado volta a ser a nativa
       (Tab entre cartões) — só o Escape do modal continua sendo nosso. */
    function onKey(e) {
      if (e.key === 'Escape') {
        var railModal = root && root.querySelector('#pevRailModal');
        if (railModal && railModal.classList.contains('is-open')) {
          closeRailLightbox();
          return;
        }
        closeModal();
      }
    }

    /* ── API PÚBLICA — o cabeçalho manda, o portal obedece ────────────────────
       Estes três são o contrato inteiro entre apollo_listing_header() e esta
       tela (ver header-bridge.php). Cada um é idempotente e cada um termina em
       um render — nenhum deles assume que o outro já rodou. ─────────────────── */

    /** @param {string} ym 'YYYY-MM'  @param {number} dir +1/-1/0 */
    function setMonth(ym, dir) {
      if (!ym || ym === state.ym) return;
      state.ym = ym;
      /* Um modo que não seja "mes" é um recorte relativo a hoje (Neste FDS,
         Próximos…), então navegar para outro mês com ele ligado devolveria
         sempre a mesma lista. Trocar de mês É escolher o modo "mes". */
      if (state.mode !== 'mes') state.mode = 'mes';
      state.tax = defaultTaxKey(state.ym);
      renderAll(dir || 0);
    }

    /** @param {{mode?:string, tags?:string[], sounds?:string[]}} next */
    function setFilters(next) {
      next = next || {};
      state.mode = next.mode || 'mes';
      state.tags = (next.tags || []).slice();
      state.sounds = (next.sounds || []).slice();
      renderAll();
    }

    /** @param {string} q Termo cru; normalizado aqui, uma vez só. */
    function setSearch(q) {
      var next = String(q || '').trim().toLowerCase();
      if (next === state.search) return;
      state.search = next;
      renderAll();
    }

    function init(host) {
      if (!host) return;
      root = host;
      host.classList.add('pev-host');
      var today = P().todayStart ? P().todayStart() : new Date();
      state.ym = P().ymKey ? P().ymKey(today) : (today.getFullYear() + '-' + ((today.getMonth() + 1) < 10 ? '0' : '') + (today.getMonth() + 1));
      state.mode = 'mes';
      state.tax = defaultTaxKey(state.ym);
      host.innerHTML = skeleton();
      host.addEventListener('click', onClick);
      host.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        if (e.target.closest && e.target.closest('[data-hero-dot]')) return; /* o click handler já cuida */
        var vtHostKey = e.target.closest && e.target.closest('.pev-view-toggle');
        if (vtHostKey) {
          e.preventDefault();
          toggleSectionView(vtHostKey.getAttribute('data-target'));
          return;
        }
        /* Teclado: a lightbox só escuta 'click', então Enter/Espaço num cartão
           precisam de openModal() explícito (que delega pra ela). */
        var open = e.target.closest && e.target.closest('[data-ev-open]');
        if (open && open.getAttribute('data-ev-open') && !e.target.closest('[data-rsvp]')) {
          e.preventDefault();
          openModal(open.getAttribute('data-ev-open'));
        }
      });
      d.addEventListener('keydown', onKey);
      d.addEventListener('apollo:radar-change', function () {
        /* openQuickView: repinta o resumo aberto, não abre a lightbox. */
        var modal = root.querySelector('#pevModal');
        if (modal && modal.classList.contains('is-open') && modal.dataset.openId) openQuickView(modal.dataset.openId);
      });
      renderAll();
    }

    return {
      init: init,
      render: renderAll,
      openModal: openModal,
      closeModal: closeModal,
      setMonth: setMonth,
      setFilters: setFilters,
      setSearch: setSearch,
      getState: function () { return state; }
    };
  }

  var api = AppPortalEventos();
  w.AppPortalEventos = api;
})(window, document);

</script>