<?php
/**
 * DJ Single — cell: scripts
 *
 * OWNS: the runtime — behaviour only. Holds no data and emits no styling
 *
 * GENERATED — do not hand-edit. Sliced from the approved mockup
 * (_sandbox/dj-single-page.mockup.html) by _sandbox/build-dj-cells.py, which
 * also namespaces every class to `dj-`. Edit the mockup, re-run the generator,
 * review the diff. Hand-editing here is how the page and the design drift.
 *
 * Dynamic values arrive as $dj_* in scope from apollo_dj_single_context().
 * Anything the runtime fills client-side is left as the empty container it
 * expects to find.
 *
 * @package Apollo\DJs
 * @since   1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script id="apollo-dj-runtime">
/* ════════════════════════════════════════════════════════════════════════════
   RUNTIME — progressive enhancement in two layers:
   BASE  (always): render Apollo data, shares, SoundCloud shelf, IO reveals.
   LUXE  (core.js): Lenis smooth scroll + GSAP ScrollTrigger — line-split hero,
   word-fill statement, PINNED horizontal scrub carousel with per-card parallax
   (containerAnimation), count-ups, drift marquee/footer, progress hairline,
   hero + footer full-bleed image parallax.
   Troque APOLLO_DJ pelo payload REST da Apollo — markup intocado.
   ════════════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';
  var q = function (s, c) { return (c || document).querySelector(s); };
  var qq = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c]; }); };
  var reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ═══ Dados Apollo — CPT dj (→ wp_localize_script / apollo_get_dj_context) ═══
     Cada campo é um INPUT INDEPENDENTE (custom field próprio).
       name          → title / meta._dj_name
       soundcloud    → meta._dj_soundcloud
       bandcamp      → meta._dj_bandcamp
       spotify       → meta._dj_spotify
       bookingEmail  → meta._dj_booking
       mediaKitUrl   → meta._dj_media_kit_url (pasta Drive; CTA "Acessar Kit Promo")
       videoUrl      → meta._dj_about_video
       aboutPhoto    → meta._dj_about_photo (≠ hero)
       genres[]      → taxonomy sound
       playedOn[]    → WP_Query event WHERE _event_dj_ids CONTAINS dj_id (past)
       playedWith[]  → co-DJs derivados dos mesmos eventos
       tracks[]      → meta._dj_tracks (title, url, year, duration)
                       display meta = "{year} · RIO DE JANEIRO · {duration}"
     ═══ */
  /* PAYLOAD COMES FROM PHP (data.php). The mockup hard-codes this object so
     it can run as a standalone file; here it is published as window.APOLLO_DJ
     from apollo_dj_single_context(). Same shape, same keys — the fallback
     keeps the runtime from throwing if the data cell is ever omitted from a
     partial render. */
  var APOLLO_DJ = window.APOLLO_DJ || { genres: [], playedOn: [], playedWith: [], tracks: [] };

  /* ═══ BASE LAYER ═══ */

  /* marquee (dobrado para o loop) */
  q('#mqInner').innerHTML = APOLLO_DJ.genres.concat(APOLLO_DJ.genres).map(function (g, i) {
    return '<span class="' + (i % 3 === 0 ? 'dj-on' : '') + '">' + esc(g) + '</span><span>·</span>';
  }).join('');

  /* tocou em */
  q('#poTrack').innerHTML = APOLLO_DJ.playedOn.map(function (e) {
    return '<article class="dj-po-card"><div class="dj-po-cover"><img src="' + e.cover + '" alt="" loading="lazy"><span class="dj-po-date dj-pill">' + esc(e.date) + '</span></div>'
      + '<div class="dj-po-body"><h3 class="dj-po-title">' + esc(e.title) + '</h3><div class="dj-po-meta"><span><i class="ri-map-pin-2-line"></i>' + esc(e.venue) + '</span><span><i class="ri-team-line"></i>+' + e.withCount + ' artistas</span></div></div></article>';
  }).join('');

  /* roster */
  q('#roster').innerHTML = APOLLO_DJ.playedWith.map(function (d) {
    return '<a class="dj-ro dj-rv" href="#"><span class="dj-ro-av dj-pill"><img src="' + d.avatar + '" alt="" loading="lazy"></span>'
      + '<span class="dj-ro-name">' + esc(d.name) + '</span>'
      + '<span class="dj-ro-x"><span class="dj-ro-role">' + esc(d.role) + '</span><i class="ri-arrow-right-up-line dj-ro-arrow"></i></span></a>';
  }).join('');

  /* plataformas — fonte única global (dock + Sons + footer), ver comentário nos dados */
  q('#scFollow').href = APOLLO_DJ.soundcloud;
  q('#bcFollow').href = APOLLO_DJ.bandcamp;
  q('#spFollow').href = APOLLO_DJ.spotify;
  q('#footSc').href = APOLLO_DJ.soundcloud;
  q('#footBc').href = APOLLO_DJ.bandcamp;
  q('#footSp').href = APOLLO_DJ.spotify;
  q('#dockSoundcloud').href = APOLLO_DJ.soundcloud;
  q('#dockBandcamp').href = APOLLO_DJ.bandcamp;
  q('#dockSpotify').href = APOLLO_DJ.spotify;
  q('#dockKit').href = APOLLO_DJ.mediaKitUrl;
  var bookingHref = 'mailto:' + APOLLO_DJ.bookingEmail + '?subject=' + encodeURIComponent('Booking — ' + APOLLO_DJ.name);
  q('#heroBooking').href = bookingHref;
  q('#dockBooking').href = bookingHref;

  /* nome do rodapé — sempre o nome do DJ, maiúsculo, nunca a marca da plataforma */
  q('#footName').textContent = APOLLO_DJ.name.toUpperCase();
  /* identidade da ev-top — revelada quando o nome gigante do hero sai de cena */
  q('#evTopName').textContent = APOLLO_DJ.name;

  /* Sobre — vídeo cobre a mesma caixa 4/5 em loop mudo; sem vídeo, cai pra uma
     foto DIFERENTE da imagem principal do hero (nunca repete o 1º img). */
  (function () {
    var fig = q('#aboutFig');
    if (APOLLO_DJ.videoUrl) {
      fig.innerHTML = '<video id="aboutVideo" src="' + esc(APOLLO_DJ.videoUrl) + '" autoplay muted loop playsinline></video>';
    } else {
      fig.innerHTML = '<img id="aboutImg" src="' + esc(APOLLO_DJ.aboutPhoto) + '" alt="' + esc(APOLLO_DJ.name) + ' nos bastidores">';
    }
  })();

  /* Out now! — max 5 + row 06 Ver todos → lightbox; meta = year · RIO DE JANEIRO · duration */
  function trackMeta(t) {
    if (t.meta) return t.meta;
    var bits = [];
    if (t.year) bits.push(t.year);
    bits.push('RIO DE JANEIRO');
    if (t.duration) bits.push(t.duration);
    return bits.join(' · ');
  }
  function trackRowHtml(t, i) {
    return '<div class="dj-trk dj-rv" data-url="' + esc(t.url || '') + '"><span class="dj-trk-i">0' + (i + 1) + '</span>'
      + '<div><div class="dj-trk-t">' + esc(t.title) + '</div><div class="dj-trk-m">' + esc(trackMeta(t)) + '</div></div>'
      + '<button class="dj-trk-share dj-pill" data-share-track="' + esc(t.url || '') + '" aria-label="Compartilhar faixa"><i class="ri-share-forward-box-line"></i></button>'
      + '<span class="dj-trk-play dj-pill"><i class="ri-play-fill"></i></span></div>';
  }
  var allTracks = Array.isArray(APOLLO_DJ.tracks) ? APOLLO_DJ.tracks : [];
  var trkList = q('#trkList');
  if (trkList) {
    var visible = allTracks.slice(0, 5);
    var htmlTrk = visible.map(function (t, i) { return trackRowHtml(t, i); }).join('');
    if (allTracks.length > 5) {
      htmlTrk += '<button type="button" class="dj-trk dj-trk-more dj-rv" id="trkMore" aria-haspopup="dialog">'
        + '<span class="dj-trk-i">06</span><div><div class="dj-trk-t">Ver todos</div>'
        + '<div class="dj-trk-m">' + esc(String(allTracks.length)) + ' lançamentos</div></div>'
        + '<span class="dj-trk-play dj-pill"><i class="ri-arrow-right-up-line"></i></span></button>';
    }
    trkList.innerHTML = htmlTrk;
  }
  var lbFull = q('#outNowLbList');
  if (lbFull) {
    lbFull.innerHTML = allTracks.map(function (t, i) { return trackRowHtml(t, i); }).join('');
  }
  function playTrack(row) {
    var url = row.getAttribute('data-url');
    if (!url) return;
    qq('.dj-trk').forEach(function (r) { r.classList.toggle('dj-is-on', r === row); });
    var shelf = q('#scShelf');
    var player = q('#scPlayer');
    if (player) {
      player.src = 'https://w.soundcloud.com/player/?url=' + encodeURIComponent(url) + '&color=%23d1860a&auto_play=true&hide_related=true&show_comments=false&show_user=true';
    }
    if (shelf) shelf.classList.add('dj-open');
  }
  var outLb = q('#outNowLb');
  var outLbLastFocus = null;
  function openOutLb(el) {
    if (!outLb) return;
    outLbLastFocus = el || document.activeElement;
    outLb.classList.add('dj-open');
    outLb.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    var closer = q('#outNowLbClose');
    if (closer) closer.focus();
  }
  function closeOutLb() {
    if (!outLb) return;
    outLb.classList.remove('dj-open');
    outLb.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (outLbLastFocus && outLbLastFocus.focus) outLbLastFocus.focus();
  }
  var moreBtn = q('#trkMore');
  if (moreBtn) moreBtn.addEventListener('click', function (e) { e.preventDefault(); openOutLb(moreBtn); });
  var lbClose = q('#outNowLbClose');
  if (lbClose) lbClose.addEventListener('click', closeOutLb);
  if (outLb) outLb.addEventListener('click', function (e) { if (e.target === outLb) closeOutLb(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && outLb && outLb.classList.contains('dj-open')) closeOutLb();
  });
  function onTrkClick(ev) {
    var sh = ev.target.closest('[data-share-track]');
    if (sh) { ev.stopPropagation(); share('Faixa', sh.getAttribute('data-share-track')); return; }
    if (ev.target.closest('.dj-trk-more')) return;
    var row = ev.target.closest('.dj-trk'); if (!row || row.classList.contains('dj-trk-more')) return;
    playTrack(row);
  }
  if (trkList) trkList.addEventListener('click', onTrkClick);
  if (lbFull) lbFull.addEventListener('click', onTrkClick);

  /* Kit Promo — #kitDl is an <a> to mediaKitUrl */
  if (q('#kitDl') && APOLLO_DJ.mediaKitUrl) q('#kitDl').href = APOLLO_DJ.mediaKitUrl;

  /* share + toast */
  var toastT;
  function toast(msg, icon) {
    var t = q('#toast');
    t.innerHTML = '<i class="' + (icon || 'ri-check-line') + '"></i>' + esc(msg);
    t.classList.add('dj-show');
    clearTimeout(toastT); toastT = setTimeout(function () { t.classList.remove('dj-show'); }, 2200);
  }
  function share(title, url) {
    url = url || location.href;
    if (navigator.share) navigator.share({ title: APOLLO_DJ.name + ' · ' + title, url: url }).catch(function () {});
    else if (navigator.clipboard) { navigator.clipboard.writeText(url); toast('Link copiado'); }
    if (navigator.vibrate) navigator.vibrate(8);
  }
  qq('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      share(b.getAttribute('data-share') === 'kit' ? 'Kit de Imprensa' : 'Cartão de Artista',
            b.getAttribute('data-share') === 'kit' ? location.href + '#kit' : location.href);
    });
  });

  /* favoritar (dock 100% ícone) — troca o glifo/estado, sem rótulo de texto */
  var followed = false;
  q('#dockFollow').addEventListener('click', function () {
    followed = !followed;
    this.classList.toggle('dj-is-active', followed);
    this.querySelector('i').className = followed ? 'ri-heart-3-fill' : 'ri-heart-3-line';
    this.setAttribute('aria-label', followed ? 'Seguindo' : 'Seguir');
    this.setAttribute('title', followed ? 'Seguindo' : 'Seguir');
    toast(followed ? 'Você segue ' + APOLLO_DJ.name + ' ✓' : 'Deixou de seguir', 'ri-heart-3-line');
    if (navigator.vibrate) navigator.vibrate(8);
  });

  /* IO reveals (com ou sem GSAP) */
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('dj-in'); io.unobserve(e.target); } });
  }, { threshold: .1, rootMargin: '0px 0px -6% 0px' });
  qq('.dj-rv').forEach(function (el) { io.observe(el); });

  /* dock depois do hero */
  new IntersectionObserver(function (es) { q('#dock').classList.toggle('dj-show', !es[0].isIntersecting); }, { threshold: 0 })
    .observe(q('#hero'));

  /* progresso do carrossel mobile (rail nativo) */
  var poTrack = q('#poTrack'), poBar = q('#poBar');
  poTrack.addEventListener('scroll', function () {
    var max = poTrack.scrollWidth - poTrack.clientWidth;
    if (max > 0) poBar.style.transform = 'scaleX(' + (poTrack.scrollLeft / max) + ')';
  }, { passive: true });

  /* ═══ LUXE LAYER — core.js GSAP + ScrollTrigger + Lenis ═══ */
  function splitLines(containerSel) {
    var lines = qq(containerSel + ' .hn-line');
    var allChars = [];
    lines.forEach(function (lineEl) {
      var t = lineEl.textContent;
      lineEl.textContent = '';
      t.split('').forEach(function (ch) {
        var w = document.createElement('span'); w.className = 'dj-ch-w';
        var i = document.createElement('span'); i.className = 'dj-ch'; i.textContent = ch === ' ' ? ' ' : ch;
        w.appendChild(i); lineEl.appendChild(w); allChars.push(i);
      });
    });
    return allChars;
  }
  function splitWords(el) {
    var out = [];
    Array.prototype.slice.call(el.childNodes).forEach(function (n) {
      if (n.nodeType === 3) {
        n.textContent.split(/\s+/).filter(Boolean).forEach(function (wd) {
          var s = document.createElement('span'); s.className = 'dj-wd'; s.textContent = wd;
          el.insertBefore(s, n); out.push(s);
        });
        el.removeChild(n);
      } else if (n.nodeType === 1) { n.classList.add('dj-wd'); out.push(n); }
    });
    return out;
  }

  /* corta um nó de texto em caracteres mascarados (.ch-w > .ch) — mesma
     mecânica do hero, reutilizada por títulos de seção e nome do rodapé */
  function splitChars(el) {
    var t = el.textContent, out = [];
    el.textContent = '';
    t.split('').forEach(function (c) {
      var w = document.createElement('span'); w.className = 'dj-ch-w';
      var i = document.createElement('span'); i.className = 'dj-ch';
      i.textContent = c === ' ' ? ' ' : c;
      w.appendChild(i); el.appendChild(w); out.push(i);
    });
    return out;
  }

  function luxe() {
    if (reduced || !window.gsap || !window.ScrollTrigger) return;
    var g = window.gsap, ST = window.ScrollTrigger;
    g.registerPlugin(ST);
    if (typeof window.initLenisSync === 'function') window.initLenisSync();
    var mm = g.matchMedia();

    /* ═══ 00 · o GSAP assume TODOS os reveals — desarma a camada CSS/IO (.rv)
       pra que nenhum `from()` leia opacity:0 como valor final ═══ */
    qq('.dj-rv').forEach(function (el) { el.classList.add('dj-in'); el.style.transition = 'none'; });

    /* ═══ 01 · fio de progresso ═══ */
    g.to('#pg', { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: .3 } });

    /* ═══ 02 · EV-TOP — glass + nome do artista assim que o nome gigante sai ═══ */
    var evTop = q('#evTop');
    ST.create({
      trigger: '#hero', start: 'top+=150 top', end: 'max',
      onToggle: function (s) { evTop.classList.toggle('dj-is-solid', s.isActive); }
    });

    /* ═══ 03 · HERO — abertura em cascata ═══ */
    var chars = splitLines('#heroName');
    g.timeline({ defaults: { ease: 'expo.out' } })
      .from('.hero-eyebrow', { y: 16, opacity: 0, duration: .9 }, 0)
      .from(chars, { yPercent: 112, duration: 1.25, stagger: .045 }, .1)
      .from('#heroImg', { scale: 1.18, duration: 2, ease: 'power3.out' }, .3)
      .from('.hero-bio', { y: 26, opacity: 0, duration: 1 }, .62)
      .from('.hero-ctas', { y: 26, opacity: 0, duration: 1 }, .72)
      .from('.gpill', { y: 26, opacity: 0, duration: .85, stagger: .08 }, .95)
      .from('.hero-cue', { opacity: 0, duration: .8 }, 1.2);

    /* ═══ 04 · HERO FIGURE — card recuado ABRE em full-bleed (assinatura Apple) ═══ */
    g.fromTo('#heroFig',
      { clipPath: 'inset(0% 7% 0% 7% round 28px)' },
      { clipPath: 'inset(0% 0% 0% 0% round 0px)', ease: 'none',
        scrollTrigger: { trigger: '#heroFig', start: 'top 94%', end: 'top 24%', scrub: .6 } });

    /* ═══ 05 · parallax da foto + saída do bloco de texto ═══ */
    g.to('#heroImg', { yPercent: 7, ease: 'none', scrollTrigger: { trigger: '#heroFig', start: 'top bottom', end: 'bottom top', scrub: true } });
    g.to('#heroName', { yPercent: -9, opacity: .16, ease: 'none', scrollTrigger: { trigger: '#hero', start: 'bottom 82%', end: 'bottom 16%', scrub: true } });
    g.to('.hero-under', { y: -34, opacity: .2, ease: 'none', scrollTrigger: { trigger: '#hero', start: 'bottom 88%', end: 'bottom 28%', scrub: true } });
    g.to('.hero-cue', { opacity: 0, ease: 'none', scrollTrigger: { trigger: '#heroFig', start: 'top 60%', end: 'top 20%', scrub: true } });

    /* ═══ 06 · reveals globais — o GSAP assume o lugar do IntersectionObserver ═══ */
    var rvEls = qq('.dj-rv').filter(function (el) {
      return !el.closest('#hero') && !el.matches('.dj-ro, .dj-trk') && el.id !== 'kit' && el.id !== 'aboutFig';
    });
    ST.batch(rvEls, {
      start: 'top 90%',
      onEnter: function (els) { g.from(els, { y: 38, opacity: 0, duration: 1.1, ease: 'expo.out', stagger: .09, overwrite: true }); }
    });

    /* ═══ 07 · STATEMENT — word-fill pinado ═══ */
    var words = splitWords(q('#stmtTxt'));
    g.set(words, { opacity: .1 });
    g.to(words, { opacity: 1, stagger: .05, ease: 'none',
      scrollTrigger: { trigger: '#stmt', start: 'top 24%', end: '+=120%', scrub: true, pin: true, pinSpacing: true } });

    /* ═══ 08 · MARQUEE — velocidade reativa ao scroll (Lenis alimenta a leitura) ═══ */
    var mqInner = q('#mqInner');
    mqInner.style.animation = 'none';
    var mqLoop = g.to(mqInner, { xPercent: -50, duration: 26, ease: 'none', repeat: -1 });
    var mqDecay;
    ST.create({ onUpdate: function (self) {
      mqLoop.timeScale(1 + Math.min(Math.abs(self.getVelocity()) / 800, 4.5));
      clearTimeout(mqDecay);
      mqDecay = setTimeout(function () { g.to(mqLoop, { timeScale: 1, duration: 1.2, ease: 'power2.out' }); }, 110);
    }});
    g.fromTo('#mq', { xPercent: 2 }, { xPercent: -4, ease: 'none', scrollTrigger: { trigger: '#mq', start: 'top bottom', end: 'bottom top', scrub: true } });

    /* ═══ 09 · TÍTULOS DE SEÇÃO — sobem caractere a caractere ═══ */
    qq('.dj-sh-t').forEach(function (h) {
      g.from(splitChars(h), { yPercent: 118, duration: 1.15, ease: 'expo.out', stagger: .028,
        scrollTrigger: { trigger: h, start: 'top 88%', once: true } });
    });
    qq('.dj-sh .dj-lbl').forEach(function (l) {
      g.from(l, { y: 14, opacity: 0, duration: .9, ease: 'expo.out',
        scrollTrigger: { trigger: l, start: 'top 92%', once: true } });
    });

    /* ═══ 10 · EM NÚMEROS — hairline desenha + contador sobe ═══ */
    qq('.dj-num').forEach(function (n) {
      g.fromTo(n, { '--rl': 0 }, { '--rl': 1, duration: 1.15, ease: 'power2.out',
        scrollTrigger: { trigger: n, start: 'top 90%', once: true } });
    });
    qq('[data-count]').forEach(function (el) {
      var to = parseFloat(el.getAttribute('data-count')), dec = +(el.getAttribute('data-dec') || 0);
      var o = { v: 0 };
      g.to(o, { v: to, duration: 1.9, ease: 'power2.out',
        onUpdate: function () { el.textContent = (dec ? o.v.toFixed(dec) : Math.round(o.v)).toString().replace('.', ','); },
        scrollTrigger: { trigger: el, start: 'top 88%', once: true } });
    });

    /* ═══ 11 · LISTAS (roster + faixas) — cascata + hairline desenhada por linha ═══ */
    ['#roster', '#trkList'].forEach(function (sel) {
      var host = q(sel); if (!host) return;
      g.fromTo(host, { '--rl': 0 }, { '--rl': 1, duration: 1.2, ease: 'power2.out',
        scrollTrigger: { trigger: host, start: 'top 90%', once: true } });
      var rows = qq('.dj-ro, .dj-trk', host);
      g.from(rows, { y: 36, opacity: 0, duration: .95, ease: 'expo.out', stagger: .07,
        scrollTrigger: { trigger: host, start: 'top 84%', once: true } });
      rows.forEach(function (r) {
        g.fromTo(r, { '--rl': 0 }, { '--rl': 1, duration: .85, ease: 'power2.out',
          scrollTrigger: { trigger: r, start: 'top 94%', once: true } });
      });
    });

    /* ═══ 12 · TOCOU EM — desktop: scrub horizontal pinado + parallax por card ═══ */
    mm.add('(min-width: 900px)', function () {
      var stage = q('#poStage'), track = q('#poTrack');
      var dist = function () { return Math.max(0, track.scrollWidth - stage.clientWidth); };
      var tween = g.to(track, {
        x: function () { return -dist(); },
        ease: 'none',
        scrollTrigger: {
          trigger: '#poStage', start: 'top top',
          end: function () { return '+=' + (dist() + innerHeight * .25); },
          pin: true, scrub: 1, invalidateOnRefresh: true,
          onUpdate: function (self) { poBar.style.transform = 'scaleX(' + self.progress + ')'; }
        }
      });
      qq('.dj-po-cover img', stage).forEach(function (im) {
        g.fromTo(im, { xPercent: -7 }, { xPercent: 0, ease: 'none',
          scrollTrigger: { trigger: im.closest('.dj-po-card'), containerAnimation: tween, start: 'left right', end: 'right left', scrub: true } });
      });
      return function () { tween.scrollTrigger && tween.scrollTrigger.kill(); tween.kill(); };
    });
    /* mobile: cards entram em cascata no rail nativo */
    mm.add('(max-width: 899px)', function () {
      var t = g.from(qq('.dj-po-card'), { y: 44, opacity: 0, duration: 1, ease: 'expo.out', stagger: .08,
        scrollTrigger: { trigger: '#poStage', start: 'top 86%', once: true } });
      return function () { t.scrollTrigger && t.scrollTrigger.kill(); t.kill(); };
    });

    /* ═══ 13 · elasticidade de scroll — as listas inclinam de leve com a velocidade ═══ */
    mm.add('(min-width: 900px)', function () {
      var setters = ['#roster', '#trkList'].filter(function (s) { return q(s); }).map(function (s) {
        return g.quickTo(s, 'skewY', { duration: .55, ease: 'power3.out' });
      });
      var st = ST.create({ onUpdate: function (self) {
        var s = g.utils.clamp(-2.6, 2.6, self.getVelocity() / -520);
        setters.forEach(function (fn) { fn(s); });
      }});
      return function () { st.kill(); setters.forEach(function (fn) { fn(0); }); };
    });

    /* ═══ 14 · KIT DE IMPRENSA — o card ink cresce e a luz varre no scrub ═══ */
    g.fromTo('#kit', { yPercent: 5, scale: .94, opacity: .5 },
      { yPercent: 0, scale: 1, opacity: 1, ease: 'none',
        scrollTrigger: { trigger: '#kitSection', start: 'top 90%', end: 'top 32%', scrub: .5 } });
    g.fromTo('#kit', { '--sx': '2%' }, { '--sx': '98%', ease: 'none',
      scrollTrigger: { trigger: '#kit', start: 'top bottom', end: 'bottom top', scrub: true } });
    g.fromTo('.kit-wm', { xPercent: 12 }, { xPercent: -12, ease: 'none',
      scrollTrigger: { trigger: '#kit', start: 'top bottom', end: 'bottom top', scrub: true } });
    g.from(qq('.dj-kit-meta div'), { y: 20, opacity: 0, duration: .9, ease: 'expo.out', stagger: .07,
      scrollTrigger: { trigger: '.kit-meta', start: 'top 92%', once: true } });

    /* ═══ 15 · SOBRE — clip abre no scrub, mídia deriva, tags entram ═══ */
    g.fromTo('#aboutFig', { clipPath: 'inset(14% 0% 14% 0% round 28px)' },
      { clipPath: 'inset(0% 0% 0% 0% round 28px)', ease: 'none',
        scrollTrigger: { trigger: '#aboutFig', start: 'top 92%', end: 'top 32%', scrub: .6 } });
    g.to('#aboutImg', { yPercent: 6, ease: 'none', scrollTrigger: { trigger: '#aboutFig', start: 'top bottom', end: 'bottom top', scrub: true } });
    g.from(qq('.dj-tag'), { y: 16, opacity: 0, duration: .7, ease: 'expo.out', stagger: .05,
      scrollTrigger: { trigger: '.tags', start: 'top 92%', once: true } });

    /* ═══ 16 · RODAPÉ — nome sobe por caractere, deriva no scroll, foto em parallax ═══ */
    g.from(splitChars(q('#footName')), { yPercent: 118, duration: 1.3, ease: 'expo.out', stagger: .03,
      scrollTrigger: { trigger: '.foot', start: 'top 84%', once: true } });
    g.fromTo('#footName', { xPercent: 4 }, { xPercent: -8, ease: 'none',
      scrollTrigger: { trigger: '.foot', start: 'top bottom', end: 'bottom top', scrub: true } });
    g.fromTo('#footImg', { yPercent: -12 }, { yPercent: 4, ease: 'none',
      scrollTrigger: { trigger: '#footImgWrap', start: 'top bottom', end: 'bottom bottom', scrub: true } });
    g.from(qq('.dj-soc'), { y: 18, opacity: 0, duration: .7, ease: 'expo.out', stagger: .06,
      scrollTrigger: { trigger: '.socials', start: 'top 94%', once: true } });

    /* ═══ 17 · recalibra depois que as imagens pesam ═══ */
    window.addEventListener('load', function () { ST.refresh(); });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { ST.refresh(); });
  }

  /* boot LUXE após o core.js entregar o gsap (nunca em DOMContentLoaded — lei do DS) */
  if (window.gsap && window.ScrollTrigger) luxe();
  else {
    var booted = false;
    var go = function () { if (!booted) { booted = true; luxe(); } };
    window.addEventListener('apollo:gsap-ready', go, { once: true });
    document.addEventListener('apollo:gsap-ready', go, { once: true });
    window.addEventListener('apollo:ready', function () { if (window.gsap) go(); }, { once: true });
    setTimeout(function () { if (window.gsap && window.ScrollTrigger) go(); }, 4200);
  }
})();
</script>
