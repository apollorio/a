/**
 * Apollo track player — orchestrates listen fallback chain on Out Now cards.
 *
 * Chain (SoundCloud): Widget API → REST MP3 stream → visible embed.
 * Native audio: shared <audio> with volume fade 0 → 0.2 → 0.
 */
(function () {
  'use strict';

  var CFG = window.APOLLO_TRACK_PLAYER || {};
  var TARGET_VOL = typeof CFG.targetVol === 'number' ? CFG.targetVol : 0.2;
  var FADE_IN_MS = CFG.fadeInMs || 420;
  var FADE_OUT_MS = CFG.fadeOutMs || 280;
  var HOLD_SECONDS = CFG.holdSeconds || 60;
  var REST_STREAM = CFG.restStream || '/wp-json/apollo/v1/radio/stream';

  var audio = null;
  var fadeTimer = null;
  var stopTimer = null;
  var activeCard = null;
  var chainBusy = false;

  function emit(name, detail) {
    document.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
  }

  function parseChain(card) {
    var raw = card.getAttribute('data-listen-chain');
    if (raw) {
      try {
        var parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length) { return parsed; }
      } catch (e) {}
    }
    return synthesizeChain(card);
  }

  function scHref(card) {
    var href = card.getAttribute('data-listen-canonical')
      || card.getAttribute('data-apsc-url')
      || '';
    if (href) { return href; }
    var el = card.querySelector('[data-apsc]');
    if (el) { href = el.getAttribute('data-apsc-url') || ''; }
    if (href) { return href; }
    var a = card.querySelector('a[href*="soundcloud.com"], a[href*="snd.sc"]');
    return a ? (a.getAttribute('href') || '') : '';
  }

  function embedFrom(canonical) {
    return 'https://w.soundcloud.com/player/?url=' + encodeURIComponent(canonical)
      + '&auto_play=false&hide_related=true&show_comments=false&show_user=false'
      + '&show_reposts=false&visual=false';
  }

  function synthesizeChain(card) {
    var href = scHref(card);
    if (!href || !/soundcloud\.com|snd\.sc/i.test(href)) { return []; }
    var embed = card.getAttribute('data-listen-embed-url') || embedFrom(href);
    return [
      { mode: 'widget', provider: 'soundcloud', canonical: href, embed_url: embed, start_pct: 20, hold: HOLD_SECONDS, seconds: HOLD_SECONDS },
      { mode: 'audio', provider: 'soundcloud_stream', canonical: href, start_pct: 20, seconds: HOLD_SECONDS }
    ];
  }

  function ensureTransport(card, embedUrl, canonical) {
    var t = card.querySelector('[data-apsc]');
    if (t) {
      t.setAttribute('data-apsc-never-reveal', '');
      t.setAttribute('data-apsc-hold', String(HOLD_SECONDS));
      t.setAttribute('data-apsc-vol', '20');
      if (canonical) { t.setAttribute('data-apsc-url', canonical); }
      var slot = t.querySelector('[data-apsc-transport]');
      if (slot && embedUrl) { slot.setAttribute('data-apsc-embed', embedUrl); }
      return t;
    }
    t = document.createElement('div');
    t.className = 'nh-track-sc-transport apsc apsc--rail';
    t.setAttribute('data-apsc', '');
    t.setAttribute('data-apsc-manual-fallback', '');
    t.setAttribute('data-apsc-never-reveal', '');
    t.setAttribute('data-apsc-mode', 'preview');
    t.setAttribute('data-apsc-start', '20');
    t.setAttribute('data-apsc-hold', String(HOLD_SECONDS));
    t.setAttribute('data-apsc-vol', '20');
    if (canonical) { t.setAttribute('data-apsc-url', canonical); }
    t.hidden = true;
    var slot = document.createElement('div');
    slot.className = 'apsc-transport';
    slot.setAttribute('data-apsc-transport', '');
    if (embedUrl) { slot.setAttribute('data-apsc-embed', embedUrl); }
    slot.hidden = true;
    t.appendChild(slot);
    card.appendChild(t);
    return t;
  }

  function ensureAudio() {
    if (audio) { return audio; }
    audio = document.createElement('audio');
    audio.preload = 'auto';
    audio.setAttribute('playsinline', '');
    audio.style.display = 'none';
    document.body.appendChild(audio);
    audio.addEventListener('ended', clearPlaying);
    return audio;
  }

  function collapseAllExpanded(except) {
    document.querySelectorAll('.nh-track-card.is-expanded').forEach(function (card) {
      if (except && card === except) { return; }
      card.classList.remove('is-expanded');
      var panel = card.querySelector('.nh-track-expand');
      if (panel) { panel.setAttribute('aria-hidden', 'true'); }
    });
  }

  function setExpanded(card, expanded) {
    if (!card) { return; }
    var panel = card.querySelector('.nh-track-expand');
    if (expanded) {
      collapseAllExpanded(card);
      card.classList.add('is-expanded');
      if (panel) { panel.setAttribute('aria-hidden', 'false'); }
    } else {
      card.classList.remove('is-expanded');
      if (panel) { panel.setAttribute('aria-hidden', 'true'); }
    }
  }

  function setLoading(card, on) {
    if (!card) { return; }
    card.classList.toggle('is-loading', !!on);
  }

  function clearPlaying() {
    chainBusy = false;
    document.querySelectorAll('.nh-track-card.is-loading').forEach(function (c) {
      c.classList.remove('is-loading');
    });
    if (fadeTimer) {
      clearInterval(fadeTimer);
      fadeTimer = null;
    }
    if (stopTimer) {
      clearTimeout(stopTimer);
      stopTimer = null;
    }
    if (activeCard) {
      activeCard.classList.remove('is-playing');
      setExpanded(activeCard, false);
      activeCard = null;
    }
    if (audio) {
      try { audio.pause(); } catch (e) {}
    }
    emit('apollo:track:stop');
  }

  function fadeTo(target, ms, done) {
    var a = ensureAudio();
    if (fadeTimer) { clearInterval(fadeTimer); }
    var startVol = a.volume;
    var t0 = performance.now();
    fadeTimer = setInterval(function () {
      var p = Math.min(1, (performance.now() - t0) / ms);
      a.volume = startVol + (target - startVol) * p;
      if (p >= 1) {
        clearInterval(fadeTimer);
        fadeTimer = null;
        if (typeof done === 'function') { done(); }
      }
    }, 16);
  }

  function stopOthers() {
    if (window.ApolloSC && typeof window.ApolloSC.stopAll === 'function') {
      window.ApolloSC.stopAll();
    }
    if (audio) {
      try { audio.pause(); } catch (e) {}
    }
  }

  function bindScCard(card, transport) {
    transport.__trackCard = card;
    if (transport.__trackCardBound) { return; }
    transport.__trackCardBound = true;
    document.addEventListener('apollo:sc:play', function (e) {
      if (e.detail && e.detail.el === transport) {
        card.classList.add('is-playing');
        setExpanded(card, true);
        activeCard = card;
      }
    });
  }

  function fetchStreamUrl(canonical) {
    var url = REST_STREAM + (REST_STREAM.indexOf('?') >= 0 ? '&' : '?') + 'url=' + encodeURIComponent(canonical);
    return fetch(url, { credentials: 'same-origin' })
      .then(function (res) {
        if (!res.ok) { throw new Error('stream ' + res.status); }
        return res.json();
      })
      .then(function (data) {
        if (!data || !data.stream_url) { throw new Error('no stream_url'); }
        return data.stream_url;
      });
  }

  function playNativeAudio(card, step) {
    return new Promise(function (resolve, reject) {
      var src = step.src || '';
      var startSec = parseInt(step.start, 10) || 0;
      var startPct = parseInt(step.start_pct, 10);
      var endPct = parseInt(step.end_pct, 10);
      var seconds = parseInt(step.seconds, 10) || HOLD_SECONDS;

      if (step.provider === 'soundcloud_stream' && step.canonical) {
        fetchStreamUrl(step.canonical).then(function (streamUrl) {
          playNativeAudio(card, Object.assign({}, step, {
            provider: 'direct',
            src: streamUrl
          })).then(resolve).catch(reject);
        }).catch(reject);
        return;
      }

      if (!src) {
        reject(new Error('no src'));
        return;
      }

      stopOthers();
      emit('apollo:audio:play', { source: 'track' });

      activeCard = card;
      card.classList.add('is-playing');
      setExpanded(card, true);

      var a = ensureAudio();
      a.src = src;
      a.volume = 0;

      function onReady() {
        a.removeEventListener('loadedmetadata', onReady);
        try {
          if (startSec > 0) {
            a.currentTime = startSec;
          } else if (!isNaN(startPct) && isFinite(a.duration) && a.duration > 0) {
            a.currentTime = Math.min(a.duration * (startPct / 100), Math.max(0, a.duration - 1));
          }
        } catch (e) {}

        var previewEndSec = 0;
        if (!isNaN(endPct) && isFinite(a.duration) && a.duration > 0 && endPct > startPct) {
          previewEndSec = a.duration * (endPct / 100);
        }

        var playPromise = a.play();
        var afterPlay = function () {
          setLoading(card, false);
          fadeTo(TARGET_VOL, FADE_IN_MS);
          stopTimer = setTimeout(function () {
            fadeTo(0, FADE_OUT_MS, clearPlaying);
          }, HOLD_SECONDS * 1000);
          resolve(card);
        };

        if (playPromise && typeof playPromise.then === 'function') {
          playPromise.then(afterPlay).catch(function () {
            reject(new Error('audio play blocked'));
          });
        } else {
          afterPlay();
        }
      }

      a.addEventListener('loadedmetadata', onReady);
      a.load();
    });
  }

  function playWidget(card, step) {
    ensureTransport(card, step.embed_url || '', step.canonical || '');
    var transport = card.querySelector('[data-apsc]');
    if (!transport || !window.ApolloSC || typeof window.ApolloSC.playAsync !== 'function') {
      return Promise.reject(new Error('no ApolloSC'));
    }

    stopOthers();
    emit('apollo:audio:play', { source: 'soundcloud' });

    if (step.start_pct != null) {
      transport.setAttribute('data-apsc-start', String(step.start_pct));
    }
    transport.setAttribute('data-apsc-hold', String(step.hold || HOLD_SECONDS));
    transport.setAttribute('data-apsc-vol', '20');
    transport.setAttribute('data-apsc-never-reveal', '');
    if (step.embed_url) {
      var slot = transport.querySelector('[data-apsc-transport]');
      if (slot) { slot.setAttribute('data-apsc-embed', step.embed_url); }
    }

    bindScCard(card, transport);

    return window.ApolloSC.playAsync(transport, 12000).then(function () {
      setLoading(card, false);
      card.classList.add('is-playing');
      setExpanded(card, true);
      activeCard = card;
      return card;
    });
  }

  function playEmbed() {
    return Promise.reject(new Error('embed chrome forbidden'));
  }

  function runStep(card, step) {
    var mode = step.mode || '';
    if (mode === 'widget') {
      return playWidget(card, step);
    }
    if (mode === 'audio') {
      return playNativeAudio(card, step);
    }
    if (mode === 'embed') {
      return playEmbed(card, step);
    }
    return Promise.reject(new Error('unknown mode'));
  }

  function runChain(card) {
    var chain = parseChain(card);
    if (!chain.length) {
      return Promise.reject(new Error('empty chain'));
    }

    var idx = 0;
    function next(err) {
      if (idx >= chain.length) {
        return Promise.reject(err || new Error('chain exhausted'));
      }
      var step = chain[idx++];
      return runStep(card, step).catch(function (e) {
        return next(e);
      });
    }

    return next();
  }

  function playCard(card) {
    if (activeCard === card && ((audio && !audio.paused) || card.classList.contains('is-playing'))) {
      clearPlaying();
      return;
    }

    clearPlaying();
    chainBusy = true;
    activeCard = card;
    setLoading(card, true);
    setExpanded(card, true);
    return runChain(card).then(function () {
      chainBusy = false;
      setLoading(card, false);
    }).catch(function () {
      chainBusy = false;
      setLoading(card, false);
      clearPlaying();
    });
  }

  function handlePreviewClick(e) {
    var card = e.target.closest('#tracks .nh-track-card, .nh-track-card[data-casa-track-rail]');
    if (!card) { return; }
    if (e.target.closest('[data-track-plat]') && card.classList.contains('is-expanded')) { return; }
    var a = e.target.closest('a');
    if (a && card.contains(a)) {
      e.preventDefault();
      a.removeAttribute('target');
    }
    e.preventDefault();
    e.stopPropagation();
    playCard(card);
  }

  document.addEventListener('click', handlePreviewClick, true);

  document.addEventListener('apollo:sc:pause', function (e) {
    if (!e.detail || !e.detail.el || !e.detail.el.__trackCard) { return; }
    var card = e.detail.el.__trackCard;
    if (chainBusy && activeCard === card) { return; }
    card.classList.remove('is-playing');
    setExpanded(card, false);
    if (activeCard === card) { activeCard = null; }
  });
})();
