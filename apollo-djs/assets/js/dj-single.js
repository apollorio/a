/**
 * DJ Single Page JavaScript
 * Handles SoundCloud widget, vinyl player, bio modal
 *
 * @package Apollo\DJs
 * @version 2.0.0
 */
(function() {
  'use strict';

  let scWidget = null;
  let widgetReady = false;

  function setVinylState(isPlaying) {
    const vinylPlayer = document.getElementById('vinylPlayer');
    const icon = document.getElementById('vinylIcon');
    if (!vinylPlayer || !icon) return;

    if (isPlaying) {
      vinylPlayer.classList.add('is-playing');
      vinylPlayer.classList.remove('is-paused');
      icon.classList.remove('ri-play-fill');
      icon.classList.add('ri-pause-fill');
    } else {
      vinylPlayer.classList.remove('is-playing');
      vinylPlayer.classList.add('is-paused');
      icon.classList.remove('ri-pause-fill');
      icon.classList.add('ri-play-fill');
    }
  }

  function toggleVinylPlayback() {
    if (!widgetReady || !scWidget) return;
    scWidget.isPaused(function(paused) {
      if (paused) { scWidget.play(); } else { scWidget.pause(); }
    });
  }

  function initSoundCloud() {
    const iframe = document.getElementById('scPlayer');
    if (!iframe || !window.SC || typeof SC.Widget !== 'function') return;

    try {
      scWidget = SC.Widget(iframe);
      scWidget.bind(SC.Widget.Events.READY, function() { widgetReady = true; });
      scWidget.bind(SC.Widget.Events.PLAY, function() { setVinylState(true); });
      scWidget.bind(SC.Widget.Events.PAUSE, function() { setVinylState(false); });
      scWidget.bind(SC.Widget.Events.FINISH, function() { setVinylState(false); });
      scWidget.bind(SC.Widget.Events.ERROR, function() { setVinylState(false); });
    } catch (e) { console.error('[Apollo DJ] SC init error:', e); }
  }

  function initVinylPlayer() {
    ['vinylPlayer', 'vinylToggle'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          toggleVinylPlayback();
        });
      }
    });
  }

  function initBioModal() {
    var bioToggleBtn = document.getElementById('bioToggle');
    var bioBackdrop = document.getElementById('bioBackdrop');
    var bioClose = document.getElementById('bioClose');

    function openBio() {
      if (!bioBackdrop) return;
      bioBackdrop.setAttribute('data-open', 'true');
      document.body.style.overflow = 'hidden';
    }

    function closeBio() {
      if (!bioBackdrop) return;
      bioBackdrop.setAttribute('data-open', 'false');
      document.body.style.overflow = '';
      if (bioToggleBtn) bioToggleBtn.focus();
    }

    if (bioToggleBtn) bioToggleBtn.addEventListener('click', openBio);
    if (bioClose) bioClose.addEventListener('click', closeBio);
    if (bioBackdrop) {
      bioBackdrop.addEventListener('click', function(e) {
        if (e.target === bioBackdrop) closeBio();
      });
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && bioBackdrop.getAttribute('data-open') === 'true') closeBio();
      });
    }
  }

  function init() {
    initSoundCloud();
    initVinylPlayer();
    initBioModal();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else { init(); }

  window.ApolloDJPlayer = {
    togglePlayback: toggleVinylPlayback,
    setPlaying: function() { setVinylState(true); },
    setPaused: function() { setVinylState(false); }
  };
})();
