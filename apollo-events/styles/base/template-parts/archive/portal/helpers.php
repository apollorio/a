<?php

/**
 * Portal de Eventos — portal-data.js (calendar helpers)
 *
 * Ported from the mockup's simulated.data-portal.js. Pure derivation over
 * window.APOLLO_EVENTS -- month/season labels, day-window filters, rails,
 * taxonomy chips. Holds NO data of its own.
 *
 * PHASE 002: split out of the single 1258-line portal-scripts.php so each
 * concern is independently readable, replaceable and themeable. Behaviour is
 * byte-identical to what was there before — this is a split, not a rewrite.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>

/* APOLLO::RIO · portal-data.js (ported from simulated.data-portal.js)
   Portal Netflix calendar helpers — derives ONLY from window.APOLLO_EVENTS.
   Load order: (inline APOLLO_EVENTS) → portal-data.js → app-portal-eventos.js
*/
(function (w) {
  'use strict';

  var MES_FULL = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
  var SEASON_LABEL = {
    summer: 'Verão',
    winter: 'Inverno',
    rockinrio: 'Rock in Rio',
    nye: 'Réveillon',
    carnival: 'Carnaval',
    /* delta #1 — see file header: real events derive a calendar season with
       no equivalent in the mockup's hand-authored fixture set. */
    outono: 'Outono',
    primavera: 'Primavera'
  };

  var GENRE_LABEL = {
    house: 'House',
    disco: 'Disco',
    techno: 'Techno',
    trance: 'Trance',
    tech_house: 'Tech House',
    melodic: 'Melodic'
  };

  function yyShort(y) { return "' " + String(y).slice(-2); }

  /** Banner under month — Festa Julina window 01-06 → 01-08 of year */
  function seasonBanner(ym) {
    var p = parseYm(ym);
    var start = new Date(p.y, p.m, 1);
    var julinaStart = new Date(p.y, 5, 1);  /* Jun 1 */
    var julinaEnd = new Date(p.y, 7, 1);    /* Aug 1 exclusive */
    if (start >= julinaStart && start < julinaEnd) {
      return "Season de Festa Julina " + yyShort(p.y);
    }
    /* Dominant season among events in month, else calendar heuristic */
    var pool = byMonth(ym);
    var counts = {};
    pool.forEach(function (e) {
      var s = e.season || 'winter';
      counts[s] = (counts[s] || 0) + 1;
    });
    var best = null, n = 0;
    Object.keys(counts).forEach(function (k) {
      if (counts[k] > n) { n = counts[k]; best = k; }
    });
    if (best) return (SEASON_LABEL[best] || best) + ' ' + yyShort(p.y);
    /* BR seasons rough */
    var m = p.m;
    if (m >= 11 || m <= 1) return 'Verão ' + yyShort(p.y);
    if (m >= 2 && m <= 4) return 'Outono ' + yyShort(p.y);
    if (m >= 5 && m <= 7) return 'Inverno ' + yyShort(p.y);
    return 'Primavera ' + yyShort(p.y);
  }

  function monthNameOnly(ym) {
    var p = parseYm(ym);
    return MES_FULL[p.m] || '—';
  }

  /** Square filter chips: seasons + genres present in data */
  function taxChips() {
    var chips = [];
    var seasons = {};
    var genres = {};
    events().forEach(function (e) {
      var s = e.season || 'winter';
      seasons[s] = true;
      (e.genres || []).forEach(function (g) { genres[g] = true; });
    });
    /* Special festa julina chip always available for the window.
       CSP AUDIT (2026-08-01): icon WAS 'ri-fireworks-line', which is not a
       real Remixicon glyph (the set core.js loads has no "fireworks" icon).
       core.js's icon runtime falls back to fetching a custom apolloIcons SVG
       at assets.apollo.rio.br/i/fireworks-v.svg for any unrecognized class,
       that file 404s, and the CDN's 404 handler has no CORS headers — the
       exact 'blocked by CORS policy' console error reported on /eventos.
       'ri-fire-line' is a real Remixicon icon and reads well for São João/
       Festa Junina bonfires anyway. */
    chips.push({
      key: 'festajulina',
      kind: 'season',
      label: 'Festa Julina',
      icon: 'ri-fire-line',
      filter: function (e) {
        var d = parseISO(e.startDate);
        var y = d.getFullYear();
        return d >= new Date(y, 5, 1) && d < new Date(y, 7, 1);
      }
    });
    Object.keys(seasons).forEach(function (s) {
      chips.push({
        key: 'season-' + s,
        kind: 'taxonomy',
        label: SEASON_LABEL[s] || s,
        icon: 'ri-calendar-event-line',
        filter: function (e) { return (e.season || '') === s; }
      });
    });
    Object.keys(genres).forEach(function (g) {
      chips.push({
        key: 'genre-' + g,
        kind: 'genre',
        label: GENRE_LABEL[g] || g.replace(/_/g, ' '),
        icon: 'ri-disc-line',
        filter: function (e) { return (e.genres || []).indexOf(g) !== -1; }
      });
    });
    return chips;
  }

  function eventsForTax(chipKey, ym) {
    var chips = taxChips();
    var chip = chips.find(function (c) { return c.key === chipKey; });
    var base = byMonth(ym);
    if (!chip) return base;
    var hit = events().filter(chip.filter).sort(sortByStart);
    /* Prefer month overlap; if empty show all matching tax */
    var inMonth = hit.filter(function (e) {
      return String(e.startDate || '').slice(0, 7) === ym;
    });
    return inMonth.length ? inMonth : hit;
  }

  function events() { return w.APOLLO_EVENTS || []; }

  function pad2(n) { return n < 10 ? '0' + n : '' + n; }

  function parseISO(iso) {
    var p = String(iso || '').split('-');
    return new Date(+p[0], (+p[1] || 1) - 1, +p[2] || 1);
  }

  function todayStart() {
    var t = new Date();
    return new Date(t.getFullYear(), t.getMonth(), t.getDate());
  }

  function ymKey(d) {
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1);
  }

  function ymFromParts(y, m0) {
    return y + '-' + pad2(m0 + 1);
  }

  function parseYm(ym) {
    var p = String(ym || '').split('-');
    return { y: +p[0] || todayStart().getFullYear(), m: (+p[1] || 1) - 1 };
  }

  function sortByStart(a, b) {
    return String(a.startDate || '').localeCompare(String(b.startDate || ''));
  }

  function addDays(fromDate, n) {
    var d = new Date(fromDate.getFullYear(), fromDate.getMonth(), fromDate.getDate());
    d.setDate(d.getDate() + (n || 0));
    return d;
  }

  /** Inclusive local-midnight window: startOffset..endOffset days from `from`. */
  function dayWindow(fromDate, startOffset, endOffset) {
    var base = fromDate instanceof Date ? fromDate : todayStart();
    return {
      start: addDays(base, startOffset || 0),
      end: addDays(base, endOffset == null ? 0 : endOffset)
    };
  }

  function inRange(ev, start, end) {
    var s = parseISO(ev.startDate);
    return s >= start && s <= end;
  }

  /** Neste FDS: today → today+4 inclusive. Prox: after today+4 → today+40. */
  function inRangeAfter(ev, afterExclusive, endInclusive) {
    var s = parseISO(ev.startDate);
    return s > afterExclusive && s <= endInclusive;
  }

  function byMonth(ym) {
    var key = ym || ymKey(todayStart());
    return events()
      .filter(function (e) { return String(e.startDate || '').slice(0, 7) === key; })
      .slice()
      .sort(sortByStart);
  }

  function filterByMode(mode, cursorDate, list) {
    var src = (list || events()).slice();
    var today = todayStart();
    var cursor = cursorDate instanceof Date ? cursorDate : todayStart();
    var modeKey = mode || 'mes';

    if (modeKey === 'passados') {
      return src.filter(function (e) { return parseISO(e.startDate) < today; }).sort(sortByStart).reverse();
    }
    if (modeKey === 'neste-fds') {
      var w0 = dayWindow(today, 0, 4);
      return src.filter(function (e) { return inRange(e, w0.start, w0.end); }).sort(sortByStart);
    }
    if (modeKey === 'prox-fds') {
      var cut = addDays(today, 4);
      var end40 = addDays(today, 40);
      return src.filter(function (e) { return inRangeAfter(e, cut, end40); }).sort(sortByStart);
    }
    if (modeKey === 'proximos') {
      return src.filter(function (e) { return parseISO(e.startDate) >= today; }).sort(sortByStart);
    }
    /* mes — calendar month of cursor */
    var key = ymKey(cursor);
    return src.filter(function (e) { return String(e.startDate || '').slice(0, 7) === key; }).sort(sortByStart);
  }

  function featured(ym) {
    var pool = byMonth(ym);
    if (!pool.length) {
      pool = events().filter(function (e) { return parseISO(e.startDate) >= todayStart(); }).sort(sortByStart);
    }
    if (!pool.length) pool = events().slice().sort(sortByStart);
    /* Prefer active + available/soldout_soon */
    var ranked = pool.slice().sort(function (a, b) {
      var score = function (e) {
        var s = 0;
        if (e.status === 'active') s += 10;
        if (e.tickets === 'soldout_soon') s += 5;
        if (e.tickets === 'available') s += 3;
        if (e.tickets === 'free') s += 2;
        return s;
      };
      return score(b) - score(a) || sortByStart(a, b);
    });
    return ranked[0] || null;
  }

  /* Destaques do hero: eventos FUTUROS marcados com highlight:true no CPT.
     Sem nenhum marcado, cai no melhor ranqueado do mês (featured) pra que o
     topo da tela nunca fique vazio. */
  function highlights(limit) {
    var list = events().filter(function (e) {
      return e.highlight === true &&
        e.status !== 'canceled' && e.status !== 'cancelled' &&
        parseISO(e.startDate) >= todayStart();
    }).sort(sortByStart);
    /* BUGFIX (phase 002 review): this used to fall back to featured() -- the
       best-ranked event of the month -- whenever nothing carried
       highlight:true, and the hero then labelled that event "Destaque". On a
       demo that keeps the screen full; on a real site it asserts an editorial
       decision nobody made. (Reported as: "Dismantle 2 is highlighted true? I
       dont remember tagging as meta cpt event of highlight TRUE".)
       No _event_highlighted, no highlight. The hero renders its own honest
       empty state instead. */
    return limit ? list.slice(0, limit) : list;
  }

  function recent(limit) {
    var n = limit || 6;
    return events().slice().sort(sortByStart).reverse().slice(0, n);
  }

  function monthLabel(ym) {
    var p = parseYm(ym);
    return MES_FULL[p.m] + ' ' + p.y;
  }

  function shiftMonth(ym, delta) {
    var p = parseYm(ym);
    var d = new Date(p.y, p.m + (delta || 0), 1);
    return ymFromParts(d.getFullYear(), d.getMonth());
  }

  function idsOf(list) {
    return (list || []).map(function (e) { return e.id; });
  }

  function resolveIds(ids) {
    var map = {};
    events().forEach(function (e) { map[e.id] = e; });
    return (ids || []).map(function (id) { return map[id]; }).filter(Boolean);
  }

  function buildRails(ym, mode, cursorDate) {
    var filtered = filterByMode(mode === 'mes' ? 'mes' : mode, cursorDate || parseISO(ym + '-01'), events());
    /* When mode is mes, filterByMode uses cursor month; align with ym */
    if (!mode || mode === 'mes') filtered = byMonth(ym);

    var upcoming = events().filter(function (e) {
      return parseISO(e.startDate) >= todayStart() && e.status !== 'canceled' && e.status !== 'cancelled';
    }).sort(sortByStart);

    var rails = [];
    var today = todayStart();

    /* ── REGIME DO PAR PADRÃO: mês corrente vs. mês navegado ───────────────────
       Até aqui `ym` chegava e não era usado por estes dois trilhos: as duas
       janelas eram relativas a HOJE, então agosto, setembro e dezembro
       devolviam exatamente a mesma lista sob os mesmos rótulos. "Esse FDS" em
       dezembro é uma afirmação falsa sobre a tela, não um rótulo genérico.

       Agora há DOIS regimes e `ym` decide qual vale:

         corrente  → recortes relativos a hoje (hoje→+4, hoje+4→+40). É a tela
                     de quem chega: o que rola já, e o que vem logo depois.
         navegado  → o mês inteiro, dia 01 → fim do mês, via byMonth(ym) (que
                     já ordena ascendente — esse é o contrato de ordem). Não
                     existe "próximo" dentro de um mês que o usuário escolheu
                     olhar, então o segundo trilho não fica vazio: ele sai.

       As CHAVES não se mexem em nenhum dos dois regimes — 'neste-fds' e
       'prox-fds' são contrato de dados (data-pev-mode, o grupo de período do
       cabeçalho, filterByMode(), state.sectionViews e a lightbox casam por
       elas). Só título, piscina e visibilidade são cientes do mês. ── */
    var currentYm = ymKey(today);
    var isCurrent = !ym || ym === currentYm;

    rails.push({
      key: 'neste-fds',
      /* Rótulo é "Esse FDS"; a CHAVE continua 'neste-fds' porque é contrato de
         dados — data-pev-mode, o grupo de período do cabeçalho e filterByMode()
         casam por ela. Renomear a chave junto com o texto quebraria os três. */
      title: isCurrent ? 'Esse FDS' : 'Neste mês',
      subtitle: isCurrent ? 'hoje → +4 dias' : '01 → fim do mês',
      eventIds: idsOf(
        isCurrent
          ? filterByMode('neste-fds', today, events())
          : byMonth(ym)
      ),
      visible: true
    });

    rails.push({
      key: 'prox-fds',
      title: 'Próximos eventos',
      subtitle: 'hoje+4 → +40 dias',
      /* Fora do mês corrente a piscina é vazia E a seção é invisível — as duas
         coisas, não uma. Só esvaziar deixaria a moldura, o título e o estado
         vazio ocupando uma banda inteira de rolagem por nada. */
      eventIds: isCurrent ? idsOf(filterByMode('prox-fds', today, events())) : [],
      visible: isCurrent
    });

    rails.push({
      key: 'revelacoes',
      title: 'Revelações',
      subtitle: 'os novos eventos do circuito',
      eventIds: idsOf(upcoming.slice().reverse()).slice(0, 8)
    });

    var seasons = {};
    events().forEach(function (e) {
      var s = e.season || 'winter';
      if (!seasons[s]) seasons[s] = [];
      seasons[s].push(e);
    });
    Object.keys(seasons).forEach(function (s) {
      var list = seasons[s].slice().sort(sortByStart);
      if (!list.length) return;
      rails.push({
        key: 'season-' + s,
        title: 'Taxonomy ' + (SEASON_LABEL[s] || s).toUpperCase(),
        subtitle: SEASON_LABEL[s] || s,
        eventIds: idsOf(list)
      });
    });

    /* Genre density rails */
    var genres = {};
    events().forEach(function (e) {
      (e.genres || []).forEach(function (g) {
        if (!genres[g]) genres[g] = [];
        genres[g].push(e);
      });
    });
    Object.keys(genres).forEach(function (g) {
      if (genres[g].length < 2) return;
      rails.push({
        key: 'genre-' + g,
        title: g.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); }),
        subtitle: 'por gênero',
        eventIds: idsOf(genres[g].slice().sort(sortByStart))
      });
    });

    return rails;
  }

  function ticketLabel(tickets) {
    return ({
      free: 'Gratuito',
      available: 'Disponível',
      soldout_soon: 'Sold-out soon',
      sold_out: 'Sold-out'
    })[tickets] || tickets || '';
  }

  w.APOLLO_PORTAL = {
    MES_FULL: MES_FULL,
    SEASON_LABEL: SEASON_LABEL,
    GENRE_LABEL: GENRE_LABEL,
    ymKey: ymKey,
    parseYm: parseYm,
    monthLabel: monthLabel,
    monthNameOnly: monthNameOnly,
    seasonBanner: seasonBanner,
    shiftMonth: shiftMonth,
    byMonth: byMonth,
    featured: featured,
    highlights: highlights,
    recent: recent,
    filterByMode: filterByMode,
    buildRails: buildRails,
    taxChips: taxChips,
    eventsForTax: eventsForTax,
    resolveIds: resolveIds,
    ticketLabel: ticketLabel,
    todayStart: todayStart,
    parseISO: parseISO
  };
})(window);
</script>
