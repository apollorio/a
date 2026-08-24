/**
 * APOLLO::RIO — HOME / radio.js
 * SoundCloud synchronized playback engine v4.0
 * Loads playlist JSON, syncs to wall clock, per-second drift correction.
 */
;(function () {
    'use strict';

    var radioWidget = document.getElementById('nhRadio');
    var radioBtn    = document.getElementById('nhRadioBtn');
    var radioIcon   = document.getElementById('nhRadioIcon');

    if (!radioBtn) return;

    var SVG_PAUSE = '<rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect>';
    var SVG_PLAY  = '<polygon points="5 3 19 12 5 21 5 3"></polygon>';
    var RADIO_BASE = '/wp-content/plugins/';

    var isPlaying = false;
    var currentPlaylist = [];
    var totalDuration = 0;
    var syncInterval = null;
    var scWidget = null;
    var scApiLoaded = false;
    var currentTrackUrl = null;

    function loadSCApi() {
        if (scApiLoaded) return Promise.resolve();
        if (window.SC && window.SC.Widget) { scApiLoaded = true; return Promise.resolve(); }
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://w.soundcloud.com/player/api.js';
            s.onload = function () { scApiLoaded = true; resolve(); };
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function getCurrentLivePosition() {
        if (totalDuration === 0) return 0;
        var now = new Date();
        var midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var seconds = (now - midnight) / 1000;
        return seconds % totalDuration;
    }

    function findTrackAndOffset(position) {
        var elapsed = 0;
        for (var i = 0; i < currentPlaylist.length; i++) {
            var track = currentPlaylist[i];
            if (position < elapsed + track.time) {
                return { index: i, offset: position - elapsed, track: track };
            }
            elapsed += track.time;
        }
        return { index: 0, offset: 0, track: currentPlaylist[0] };
    }

    function fmtTime(s) {
        return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
    }

    function updateUI(title, dj) {
        var t = document.querySelector('.nh-radio-track');
        var a = document.querySelector('.nh-radio-artist');
        if (t) t.textContent = title || '\u2014';
        if (a) a.textContent = dj || '';
    }

    function loadAndPlayTrack(track, offset) {
        loadSCApi().then(function () {
            var iframe = document.getElementById('sc-widget');
            if (!iframe) return;
            if (!scWidget) scWidget = SC.Widget(iframe);

            updateUI(track.title, track.dj);

            if (currentTrackUrl !== track.url) {
                currentTrackUrl = track.url;
                scWidget.load(track.url, {
                    auto_play: true,
                    hide_related: true,
                    show_comments: false,
                    show_user: false,
                    show_reposts: false,
                    visual: false,
                    callback: function () {
                        scWidget.seekTo(offset * 1000);
                        scWidget.play();
                    }
                });
            } else {
                scWidget.seekTo(offset * 1000);
                scWidget.play();
            }
        });
    }

    function startSyncCheck() {
        if (syncInterval) clearInterval(syncInterval);
        syncInterval = setInterval(function () {
            if (!isPlaying || !scWidget) return;
            var livePos = getCurrentLivePosition();
            var info = findTrackAndOffset(livePos);
            var timeEl = document.getElementById('nhRadioTime');
            if (timeEl) timeEl.textContent = fmtTime(info.offset);

            scWidget.getPosition(function (posMs) {
                var pos = posMs / 1000;
                if (currentTrackUrl !== info.track.url || Math.abs(pos - info.offset) > 3) {
                    loadAndPlayTrack(info.track, info.offset);
                }
            });
        }, 1000);
    }

    function initRadio() {
        var now = new Date();
        var hour = Math.floor(now.getHours() / 3) * 3;
        var pad = String(hour).padStart(2, '0');

        fetch(RADIO_BASE + 'radio.' + pad + '.json')
            .then(function (r) { return r.ok ? r.json() : Promise.reject('fetch failed'); })
            .then(function (data) {
                currentPlaylist = data;
                totalDuration = data.reduce(function (sum, t) { return sum + t.time; }, 0);
                var livePos = getCurrentLivePosition();
                var info = findTrackAndOffset(livePos);
                if (info.track) updateUI(info.track.title, info.track.dj);
            })
            .catch(function () {
                updateUI('Offline', '\u2014');
            });

        radioBtn.addEventListener('click', function () {
            isPlaying = !isPlaying;
            if (radioWidget) radioWidget.classList.toggle('is-paused', !isPlaying);
            if (radioIcon) radioIcon.innerHTML = isPlaying ? SVG_PAUSE : SVG_PLAY;
            radioBtn.setAttribute('aria-label', isPlaying ? 'Pausar r\u00e1dio' : 'Tocar r\u00e1dio');

            if (isPlaying) {
                var pos = getCurrentLivePosition();
                var info = findTrackAndOffset(pos);
                loadAndPlayTrack(info.track, info.offset);
                startSyncCheck();
            } else {
                if (scWidget) scWidget.pause();
                if (syncInterval) clearInterval(syncInterval);
            }
        });
    }

    initRadio();
})();
