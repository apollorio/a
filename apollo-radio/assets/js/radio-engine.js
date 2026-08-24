/* ══════════════════════════════════════════════════════════════════════════════
   APOLLO::RADIO v4.0 — ULTRA-PRO WEB AUDIO ENGINE
   WordPress Plugin Edition — apollo-radio/assets/js/radio-engine.js
   ══════════════════════════════════════════════════════════════════════════════

   Architecture — Web Audio + Proxy + WAM + Spectral Flux BPM
   ───────────────────────────────────────────────────────────
   Proxy:          Cloudflare Worker (primary) + WP REST (fallback)
   AudioBuffers:   Lazy-loaded, LRU cache (max 3 buffers ≈ 150MB)
   WebAudioMixer:  Auto BPM / beat-sync mixing (no lame fades)
   BPM Detection:  OfflineAudioContext (offline) + Spectral Flux (realtime)
   Schedule:       radio.HH.json blocks, wall-clock sync

   ┌─ AudioContext ──────┐    ┌─ WebAudioMixer ───┐
   │  decodeAudioData    │    │  BPM detection    │
   │  progressive MP3    │───→│  beat-aligned     │
   │  LRU cache (3 buf)  │    │  crossfades       │
   └─────────────────────┘    └───────────────────┘

   NO credentials on client-side. All SC resolution through proxy.
   ══════════════════════════════════════════════════════════════════════════════ */
;(function () {
'use strict';

/* ═══════════════════════════════════════════════════════════════════════════════
   §0  WAM LIBRARY (Web Audio Mixer)
   ═══════════════════════════════════════════════════════════════════════════════ */

/* ── EventEmitter ─────────────────────────────────────────────────────────── */
class EventEmitter {
  constructor () { this.events = {}; }
  on (event, listener) {
    if (!this.events[event]) this.events[event] = [];
    this.events[event].push(listener);
    return this;
  }
  emit (event, ...args) {
    if (this.events[event]) {
      this.events[event].forEach(fn => fn.apply(this, args));
    }
    return this;
  }
  removeListener (event, listener) {
    if (this.events[event]) {
      this.events[event] = this.events[event].filter(l => l !== listener);
    }
    return this;
  }
}

/* ── WAAClock (sub-ms scheduling via AudioContext) ────────────────────────── */
class WAAClock {
  constructor (audioContext, options) {
    this.audioContext = audioContext;
    this._events     = [];
    this._started    = false;
    this._tid        = null;
    this._options    = Object.assign({ toleranceEarly: 0.1, toleranceLate: 0.1 }, options);
  }
  start () {
    if (this._started) return;
    this._started = true;
    this._tick();
  }
  stop () {
    this._started = false;
    if (this._tid) { cancelAnimationFrame(this._tid); this._tid = null; }
  }
  setTimeout (func, delay) {
    var event = { func: func, time: this.audioContext.currentTime + delay };
    this._events.push(event);
    this._events.sort(function (a, b) { return a.time - b.time; });
    return event;
  }
  clearTimeout (event) {
    var i = this._events.indexOf(event);
    if (i !== -1) this._events.splice(i, 1);
  }
  _tick () {
    if (!this._started) return;
    var now    = this.audioContext.currentTime;
    var toFire = this._events.filter(function (e) { return e.time <= now + 0.1; });
    for (var k = 0; k < toFire.length; k++) {
      var idx = this._events.indexOf(toFire[k]);
      if (idx !== -1) this._events.splice(idx, 1);
      toFire[k].func();
    }
    this._tid = requestAnimationFrame(this._tick.bind(this));
  }
}

/* ══════════════════════════════════════════════════════════════════════════════
   §0-B  BPM DETECTION — OfflineAudioContext + Bandpass + Peak Detection
   ══════════════════════════════════════════════════════════════════════════════ */

var BM_SAMPLE_SIZE = 60;

function getPeaks (context, channelData) {
  var peaks    = [];
  var partSize = context.sampleRate / 2;
  var parts    = channelData[0].length / partSize;
  for (var i = 0; i < parts; i++) {
    var max = null;
    for (var j = i * partSize; j < (i + 1) * partSize; j++) {
      var volume = Math.max(
        Math.abs(channelData[0][j] || 0),
        Math.abs(channelData[1] ? channelData[1][j] || 0 : 0)
      );
      if (!max || volume > max.volume) {
        max = { position: j, volume: volume };
      }
    }
    if (max) peaks.push(max);
  }
  peaks.sort(function (a, b) { return b.volume - a.volume; });
  peaks = peaks.splice(0, Math.min(BM_SAMPLE_SIZE, peaks.length));
  peaks.sort(function (a, b) { return a.position - b.position; });
  return peaks;
}

function getIntervals (context, peaks) {
  var groups = [];
  peaks.forEach(function (peak, index) {
    for (var i = 1; (index + i) < peaks.length && i < 10; i++) {
      var tempo = (60 * context.sampleRate) / (peaks[index + i].position - peak.position);
      while (tempo < 90) tempo *= 2;
      while (tempo > 180) tempo /= 2;
      tempo = Math.round(tempo);
      var existing = groups.find(function (g) { return g.tempo === tempo; });
      if (existing) {
        existing.count++;
      } else {
        groups.push({
          tempo: tempo,
          count: 1,
          interval: peaks[index + i].position - peak.position
        });
      }
    }
  });
  return groups;
}

async function calculateBPM (context, buffer) {
  var OfflineCtx = window.OfflineAudioContext || window.webkitOfflineAudioContext;
  if (!OfflineCtx) {
    return { bpm: 120, firstPeak: 0, mixoutPosition: buffer.duration, interval: 0.5, guesses: [] };
  }
  var offCtx = new OfflineCtx(buffer.numberOfChannels, buffer.length, buffer.sampleRate);
  var source = offCtx.createBufferSource();
  source.buffer = buffer;

  var lowpass = offCtx.createBiquadFilter();
  lowpass.type = 'lowpass'; lowpass.frequency.value = 150; lowpass.Q.value = 1;
  source.connect(lowpass);

  var highpass = offCtx.createBiquadFilter();
  highpass.type = 'highpass'; highpass.frequency.value = 100; highpass.Q.value = 1;
  lowpass.connect(highpass);
  highpass.connect(offCtx.destination);

  source.start(0);
  var rendered = await offCtx.startRendering();
  var ch0 = rendered.getChannelData(0);
  var ch1 = rendered.numberOfChannels > 1 ? rendered.getChannelData(1) : ch0;

  var peaks  = getPeaks(offCtx, [ch0, ch1]);
  var groups = getIntervals(offCtx, peaks);
  for (var g of groups) {
    g.intervalActual = offCtx.sampleRate / (g.tempo / 60);
  }
  var guesses = groups.sort(function (a, b) { return b.count - a.count; }).splice(0, 5);

  if (guesses[0]) {
    var interval   = guesses[0].intervalActual / offCtx.sampleRate;
    var firstPeak  = 0;
    var mixoutPos  = buffer.duration - 5;
    return {
      bpm: guesses[0].tempo,
      firstPeak: firstPeak,
      mixoutPosition: mixoutPos,
      interval: interval,
      intervalActual: guesses[0].intervalActual,
      guesses: guesses
    };
  }
  return { bpm: 120, firstPeak: 0, mixoutPosition: buffer.duration, interval: 0.5, guesses: [] };
}

/* ══════════════════════════════════════════════════════════════════════════════
   §0-C  SPECTRAL FLUX BPM VERIFIER (Real-time)
   ══════════════════════════════════════════════════════════════════════════════
   Adapted from bpm.apollo.rio.br — Spectral Flux detection with
   3-window progressive EMA stabilizer + phase/downbeat tracking.
   Used for LIVE BPM verification during playback.
   ══════════════════════════════════════════════════════════════════════════════ */

var SpectralFluxBPM = {
  analyser: null,
  freqBins: null,
  prevBins: null,
  intervals: null,
  intervIdx: 0,
  lastBeatMs: 0,
  avgFlux: 0,
  mtBpm8: 0,
  mtBpm16: 0,
  mtBpm32: 0,
  phase: 0,
  downbeat: 0,
  confidence: 0,
  finalBpm: 0,
  _active: false,

  init: function (audioContext, sourceNode) {
    this.analyser = audioContext.createAnalyser();
    this.analyser.fftSize = 2048;
    this.analyser.smoothingTimeConstant = 0.3;
    sourceNode.connect(this.analyser);

    var bins = this.analyser.frequencyBinCount;
    this.freqBins  = new Uint8Array(bins);
    this.prevBins  = new Uint8Array(bins);
    this.intervals = new Float64Array(64);
    this.intervIdx = 0;
    this.lastBeatMs = 0;
    this.avgFlux   = 0;
    this.mtBpm8  = 0; this.mtBpm16 = 0; this.mtBpm32 = 0;
    this.phase   = 0; this.downbeat = 0;
    this.confidence = 0; this.finalBpm = 0;
    this._active = true;
  },

  detect: function (nowMs) {
    if (!this._active || !this.analyser) return this.finalBpm;

    this.analyser.getByteFrequencyData(this.freqBins);

    // Spectral flux — sum of positive frequency changes.
    var flux = 0;
    for (var i = 0; i < this.freqBins.length; i++) {
      var diff = this.freqBins[i] - this.prevBins[i];
      if (diff > 0) flux += diff;
    }
    // Swap bins.
    var tmp = this.prevBins;
    this.prevBins = this.freqBins;
    this.freqBins = tmp;

    // Adaptive threshold.
    this.avgFlux += (flux - this.avgFlux) * 0.05;

    if (flux > this.avgFlux * 1.8 && this.lastBeatMs > 0) {
      var dt = nowMs - this.lastBeatMs;
      if (dt > 250 && dt < 2000) {
        this.intervals[this.intervIdx % 64] = dt;
        this.intervIdx++;
        this._updateBpm();
      }
      this.lastBeatMs = nowMs;

      // Phase tracking.
      this.phase = (this.phase % 4) + 1;
      if (this.phase === 1) this.downbeat++;
    } else if (this.lastBeatMs === 0 && flux > this.avgFlux * 1.8) {
      this.lastBeatMs = nowMs;
    }

    return this.finalBpm;
  },

  _updateBpm: function () {
    var count = Math.min(this.intervIdx, 64);
    if (count < 4) return;

    // Collect valid intervals.
    var arr = [];
    for (var i = 0; i < count; i++) arr.push(this.intervals[i]);
    arr.sort(function (a, b) { return a - b; });
    var median = arr[Math.floor(arr.length / 2)];

    // Outlier rejection: median ± 35%.
    var lo = median * 0.65, hi = median * 1.35;
    var clean = arr.filter(function (v) { return v >= lo && v <= hi; });
    if (clean.length < 3) return;

    var avg = clean.reduce(function (s, v) { return s + v; }, 0) / clean.length;
    var raw = 60000 / avg;

    // 3-window EMA stabilizer.
    var a8  = count < 8  ? 0.35 : 0.20;
    var a16 = count < 16 ? 0.20 : 0.10;
    var a32 = count < 32 ? 0.12 : 0.06;

    this.mtBpm8  = this.mtBpm8  === 0 ? raw : this.mtBpm8  + (raw - this.mtBpm8)  * a8;
    this.mtBpm16 = this.mtBpm16 === 0 ? raw : this.mtBpm16 + (raw - this.mtBpm16) * a16;
    this.mtBpm32 = this.mtBpm32 === 0 ? raw : this.mtBpm32 + (raw - this.mtBpm32) * a32;

    // Weighted blend: long-term dominates.
    this.finalBpm = Math.round(this.mtBpm8 * 0.15 + this.mtBpm16 * 0.25 + this.mtBpm32 * 0.60);

    // Confidence.
    this.confidence = Math.min(100, Math.round(clean.length / 12 * 100));
  },

  stop: function () {
    this._active = false;
    try { this.analyser && this.analyser.disconnect(); } catch (_) {}
  }
};

/* ══════════════════════════════════════════════════════════════════════════════
   §0-D  WAM TRACK — Beat-aware scheduling + Tempo sync
   ══════════════════════════════════════════════════════════════════════════════ */

class Track extends EventEmitter {
  constructor (options) {
    super();
    this.audioContext = options.audioContext;
    this.buffer      = options.buffer;
    this.mixOptions  = options.mixOptions || {};

    this.gain = this.audioContext.createGain();
    this.gain.connect(this.audioContext.destination);
    this.source = null;

    this.playing   = false;
    this.connected = false;
    this.duration  = this.buffer.duration;

    this._audioStartTime   = null;
    this._audioPauseTime   = null;
    this._audioCurrentTime = 0;

    this.analysis  = null;
    this.bpm       = null;
    this.schedules = null;

    this._clock = new WAAClock(this.audioContext);
    this._clock.start();
    this.clockSchedules = {};

    this.gain.gain.value = options.volume || 1.0;
  }

  async analyze () {
    try {
      this.analysis = await calculateBPM(this.audioContext, this.buffer);
      this.emit('analyzed', this.analysis);
    } catch (_) {
      this.analysis = { bpm: 120, firstPeak: 0, mixoutPosition: this.duration, interval: 0.5, guesses: [] };
    }
    return this.analysis;
  }

  _scheduleEvents () {
    if (!this.analysis || !this._audioStartTime) return;

    var startTime  = this._audioStartTime;
    var interval   = this.analysis.interval || 0.5;
    var mixoutPos  = this.analysis.mixoutPosition || this.duration;
    var mixLen     = this.mixOptions.mixLength || 20;
    var maxBpmDiff = this.mixOptions.maxBpmDiff || 8;
    var tweenLen   = this.mixOptions.playbackRateTween || 60;
    var now        = this.audioContext.currentTime;

    // MIXOUT.
    var mixoutTime = startTime + mixoutPos - this._audioCurrentTime;
    if (mixoutTime > now) {
      this.gain.gain.linearRampToValueAtTime(0.0001, mixoutTime);
      this.stop(mixoutTime + 0.05);
      var self = this;
      var delay = Math.max(0, mixoutTime - now - 0.1);
      this.clockSchedules.mixout = this._clock.setTimeout(function () {
        self.emit('mixout', mixoutTime);
      }, delay);
    }

    // MIXIN event.
    var mixLenInterval = 0;
    var estIn = mixoutPos - mixLen;
    while ((mixoutPos - mixLenInterval) - estIn > interval && interval > 0.01) {
      mixLenInterval += interval;
    }
    var mixinTime = mixoutTime - mixLenInterval;
    if (mixinTime > now) {
      var self2 = this;
      var d2 = Math.max(0, mixinTime - now - 0.1);
      this.clockSchedules.mixin = this._clock.setTimeout(function () {
        self2.emit('mixin', mixinTime);
      }, d2);
    }

    // TEMPO SYNC.
    if (this.bpm && this.analysis.bpm && this.bpm !== this.analysis.bpm) {
      var diff = Math.abs(this.bpm - this.analysis.bpm);
      if (maxBpmDiff > 0 && diff <= maxBpmDiff && this.source) {
        var rate = this.bpm / this.analysis.bpm;
        if (this.source.playbackRate) {
          this.source.playbackRate.value = rate;
          this.source.playbackRate.setValueAtTime(rate, startTime + mixLen);
          this.source.playbackRate.linearRampToValueAtTime(1.0, startTime + mixLen + tweenLen);
        }
        if (this.source.detune) {
          var detune = 12 * (Math.log(rate) / Math.log(2)) * 100 * (rate < 1 ? -1 : 1);
          this.source.detune.value = detune;
          this.source.detune.setValueAtTime(detune, startTime + mixLen);
          this.source.detune.exponentialRampToValueAtTime(0.0001, startTime + mixLen + tweenLen);
        }
      }
    }

    // LOAD NEXT.
    var remaining   = this.buffer.duration - this._audioCurrentTime;
    var loadTime    = 60 + mixLen;
    var loadNextAt  = remaining > loadTime ? startTime + remaining - loadTime : now + 5;
    var self3 = this;
    this.clockSchedules.loadNext = this._clock.setTimeout(function () {
      self3.emit('loadNext', self3.schedules);
    }, Math.max(0, loadNextAt - now));

    this.schedules = {
      bpm: this.analysis.bpm,
      mixinTime: mixinTime,
      mixoutTime: mixoutTime,
      interval: this.analysis.interval
    };
  }

  play (when, emit) {
    if (emit === undefined) emit = true;
    var now = this.audioContext.currentTime;
    if (when === undefined) when = now;
    if (this.playing) return;
    this.playing = true;

    if (this.source) { try { this.source.disconnect(); } catch (_) {} }
    this.source = this.audioContext.createBufferSource();
    this.source.buffer = this.buffer;
    var self = this;
    this.source.onended = function () { self.ended(); };
    this.source.connect(this.gain);
    this.source.start(when, this._audioCurrentTime);
    this._audioStartTime = when;

    this._scheduleEvents();
    if (emit) this.emit('playing', when);
  }

  pause (when, end, emit) {
    if (!this.playing) return;
    if (emit === undefined) emit = true;
    var now = this.audioContext.currentTime;
    if (when === undefined) when = now;
    this.playing = false;
    if (!end && this.source) this.source.onended = null;
    this.cancelEvents();
    try { if (this.source) this.source.stop(when); } catch (_) {}
    this._audioPauseTime   = when;
    this._audioCurrentTime += (this._audioPauseTime - this._audioStartTime);
    if (!end && emit) this.emit('paused', when);
  }

  stop (when) { this.pause(when, true); }

  cancelEvents () {
    try { this.gain.gain.cancelScheduledValues(this.audioContext.currentTime); } catch (_) {}
    var self = this;
    Object.values(this.clockSchedules).forEach(function (s) {
      if (s) self._clock.clearTimeout(s);
    });
    this.clockSchedules = {};
  }

  connect (destination) {
    this.connected = true;
    try { this.gain.disconnect(); } catch (_) {}
    this.gain.connect(destination);
  }

  disconnect () {
    this.cancelEvents();
    this.playing = false;
    this.connected = false;
    try { if (this.source) this.source.disconnect(); } catch (_) {}
    try { this.gain.disconnect(); } catch (_) {}
  }

  ended () { this.disconnect(); this.emit('ended'); }

  getCurrentTime () {
    if (this.playing) return this.audioContext.currentTime - this._audioStartTime + this._audioCurrentTime;
    return this._audioCurrentTime;
  }

  getDuration () { return this.buffer.duration; }

  setBPM (bpm) { this.bpm = bpm; }
  getVolume ()  { return this.gain.gain.value; }
  setVolume (v) { this.gain.gain.value = v; }
}

/* ══════════════════════════════════════════════════════════════════════════════
   §0-E  WAM AUDIO MIXER — Beat-aligned auto-mix + Tempo match
   ══════════════════════════════════════════════════════════════════════════════ */

class AudioMixer extends EventEmitter {
  constructor (options) {
    super();
    this.audioContext = options.audioContext;
    this._options = Object.assign({
      maxBpmDiff: 8, mixLength: 20, playbackRateTween: 60, volume: 1.0
    }, options);

    this.tracks       = [];
    this.currentTrack = null;
    this.nextTrack    = null;

    this.clock = new WAAClock(this.audioContext);
    this.clock.start();

    this.gain = this.audioContext.createGain();
    this.gain.connect(options.destination || this.audioContext.destination);
  }

  async addBuffer (buffer, options) {
    options = options || {};
    var track = new Track({
      audioContext: this.audioContext,
      buffer: buffer,
      volume: this._options.volume,
      mixOptions: this._options
    });

    track.connect(this.gain);
    await track.analyze();
    this.emit('analyzed', { track: track, analysis: track.analysis });

    if (options.matchBPM && this.currentTrack && this.currentTrack.analysis) {
      track.setBPM(this.currentTrack.analysis.bpm);
    }

    this.tracks.push(track);

    var self = this;
    track.on('mixin',    function (t) { self.emit('mixin', { track: track, time: t }); });
    track.on('mixout',   function (t) { self.emit('mixout', { track: track, time: t }); });
    track.on('loadNext', function (s) { self.emit('loadNext', { track: track, schedules: s }); });
    track.on('ended', function () {
      var idx = self.tracks.indexOf(track);
      if (idx > -1) self.tracks.splice(idx, 1);
      if (self.currentTrack === track) {
        self.currentTrack = self.nextTrack;
        self.nextTrack = null;
      }
      self.emit('trackEnd', { track: track });
    });

    if (!this.currentTrack)       this.currentTrack = track;
    else if (!this.nextTrack)     this.nextTrack = track;

    return track;
  }

  add (buffer, options) {
    options = options || {};
    var track = new Track({
      audioContext: this.audioContext,
      buffer: buffer,
      mixOptions: this._options
    });
    track.connect(this.gain);
    this.tracks.push(track);

    if (!this.currentTrack) {
      this.currentTrack = track;
      this.playTrack(track, options.startTime || 0, options.offset || 0);
    } else if (!this.nextTrack) {
      this.nextTrack = track;
      this.scheduleMix(track, options.startTime || 0);
    }
    return track;
  }

  playTrack (track, when, offset) {
    track._audioCurrentTime = offset || 0;
    track.play(when || this.audioContext.currentTime);
    this.emit('trackStarted', track);
  }

  scheduleMix (nextTrack, startTime) {
    var delay = startTime - this._options.mixLength - this.audioContext.currentTime;
    var self  = this;
    if (delay <= 0) {
      this.performMix(nextTrack);
    } else {
      this.clock.setTimeout(function () { self.performMix(nextTrack); }, delay);
    }
  }

  performMix (nextTrack) {
    if (!this.currentTrack) return;

    var ctx  = this.audioContext;
    var dur  = this._options.mixLength;
    var now  = ctx.currentTime;

    var firstPeak = (nextTrack.analysis && nextTrack.analysis.firstPeak) || 0;
    nextTrack._audioCurrentTime = firstPeak;
    nextTrack.play(now);

    var cg = this.currentTrack.gain.gain;
    cg.setValueAtTime(cg.value, now);
    cg.linearRampToValueAtTime(0, now + dur);

    var ng = nextTrack.gain.gain;
    ng.setValueAtTime(0, now);
    ng.linearRampToValueAtTime(1, now + dur);

    var self = this;
    this.clock.setTimeout(function () {
      try { self.currentTrack.stop(); } catch (_) {}
      self.currentTrack = nextTrack;
      self.nextTrack = null;
      self.emit('mixComplete', nextTrack);
    }, dur);
  }

  play () {
    if (this.currentTrack && !this.currentTrack.playing) this.currentTrack.play();
  }
  pause () {
    this.tracks.forEach(function (t) { if (t.playing) t.pause(); });
  }
  setVolume (v) { this.gain.gain.value = v; }
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §1  CONFIGURATION — from wp_localize_script (no secrets!)
   ═══════════════════════════════════════════════════════════════════════════════ */

var WPC = window.apolloRadioConfig || {};

var CFG = {
  SC_PROXY       : WPC.proxyUrl  || 'https://apradio.pages.dev/sc-proxy',
  REST_URL       : WPC.restUrl   || '/wp-json/apollo/v1/radio/',
  RADIO_JSON     : WPC.cdnUrl    || 'https://assets.apollo.rio.br/radio/json/',
  USE_FALLBACK   : WPC.useFallback || false,
  BLOCK_HOURS    : [0, 3, 6, 9, 12, 15, 18, 21],
  TICK_MS        : 400,
  XFADE_SEC      : 20,
  XFADE_AT       : 24,
  PRELOAD_AT     : 40,
  VIZ_BARS       : 28,
  FETCH_RETRIES  : 1,         // Initial + 1 smart retry, then die
  FETCH_DELAY    : 3000,       // Single retry waits 3s
  FETCH_BACKOFF  : 1,          // No escalation — only 1 retry
  FETCH_MAX_DELAY: 3000,       // Hard cap = delay (single retry)
  FETCH_TIMEOUT  : 10000,
  MAX_BPM_DIFF   : 8,
  PLAYBACK_RATE_TWEEN: 60,
  LRU_MAX_BUFFERS: 3,
};

/* ═══════════════════════════════════════════════════════════════════════════════
   §1.5  STREAM RESOLUTION — Proxy only (NO client-side credentials)
   ═══════════════════════════════════════════════════════════════════════════════ */

/**
 * Resolve SC permalink → direct MP3 stream via Cloudflare Worker proxy.
 * Falls back to WP REST /radio/stream if CF Worker is unreachable.
 */
async function getStreamUrl (permalink) {
  // Strategy 1: Cloudflare Worker proxy (primary — edge cached, fast).
  try {
    var proxyUrl = CFG.SC_PROXY + '?url=' + encodeURIComponent(permalink);
    var ctrl1 = new AbortController();
    var tid1  = setTimeout(function () { ctrl1.abort(); }, CFG.FETCH_TIMEOUT);
    var res = await fetchWithRetry(proxyUrl, { signal: ctrl1.signal });
    clearTimeout(tid1);
    if (res.ok) {
      var data = await res.json();
      if (data.stream_url || data.url) {
        log('Stream via CF proxy');
        return data.stream_url || data.url;
      }
    }
  } catch (e) {
    warn('CF proxy failed:', e.message);
  }

  // Strategy 2: WP REST fallback.
  try {
    var restUrl = CFG.REST_URL + 'stream?url=' + encodeURIComponent(permalink);
    var ctrl2 = new AbortController();
    var tid2  = setTimeout(function () { ctrl2.abort(); }, CFG.FETCH_TIMEOUT);
    var res2 = await fetchWithRetry(restUrl, { signal: ctrl2.signal });
    clearTimeout(tid2);
    if (res2.ok) {
      var data2 = await res2.json();
      if (data2.stream_url) {
        log('Stream via WP REST fallback');
        return data2.stream_url;
      }
    }
  } catch (e2) {
    warn('WP REST fallback failed:', e2.message);
  }

  throw new Error('Stream indisponível: ' + permalink);
}

/** Fetch + decode AudioBuffer from SC permalink. Uses LRU cache. */
async function getBuffer (url) {
  // LRU cache check.
  if (S.buffers.has(url)) return S.buffers.get(url);

  var streamUrl = await getStreamUrl(url);

  // 30s timeout for audio download (large files expected).
  var audioCtrl = new AbortController();
  var audioTid  = setTimeout(function () { audioCtrl.abort(); }, 30000);
  var audioResponse = await fetchWithRetry(streamUrl, { signal: audioCtrl.signal });
  clearTimeout(audioTid);
  if (!audioResponse.ok) throw new Error('Stream fetch failed: ' + audioResponse.status);

  var arrayBuffer = await audioResponse.arrayBuffer();
  if (!arrayBuffer || arrayBuffer.byteLength === 0) throw new Error('Empty audio response');

  if (!S.ctx) initAudioEngine();
  var buffer = await S.ctx.decodeAudioData(arrayBuffer);
  log('Decoded: ' + buffer.duration.toFixed(1) + 's, ' + buffer.sampleRate + 'Hz');

  // LRU eviction.
  if (S.buffers.size >= CFG.LRU_MAX_BUFFERS) {
    var oldest = S.buffers.keys().next().value;
    S.buffers.delete(oldest);
    S.bpms.delete(oldest);
    S.analyses.delete(oldest);
    log('LRU evicted buffer');
  }
  S.buffers.set(url, buffer);

  // BPM analysis.
  try {
    var analysis = await calculateBPM(S.ctx, buffer);
    S.bpms.set(url, analysis.bpm);
    S.analyses.set(url, analysis);
    log('BPM: ' + analysis.bpm);
  } catch (_) {
    S.bpms.set(url, 120);
  }

  return buffer;
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §2  STATE
   ═══════════════════════════════════════════════════════════════════════════════ */

var S = {
  on        : false,
  loading   : false,
  list      : [],
  dur       : 0,
  file      : null,
  idx       : 0,
  tickId    : null,
  tStart    : 0,
  tOffset   : 0,
  ctx       : null,
  mixer     : null,
  gain      : null,
  buffers   : new Map(),
  bpms      : new Map(),
  analyses  : new Map(),
  _mixScheduled: false,
  nextList  : null,
  nextFile  : null,
};

function initAudioEngine () {
  if (S.ctx && S.mixer && S.gain) return;

  var Ctor = window.AudioContext || window.webkitAudioContext;
  if (!Ctor) throw new Error('Web Audio API indisponível');

  S.ctx  = new Ctor();
  S.gain = S.ctx.createGain();
  S.gain.gain.value = 0.891; // -1 dB headroom
  S.gain.connect(S.ctx.destination);

  S.mixer = new AudioMixer({
    audioContext: S.ctx,
    mixLength: CFG.XFADE_SEC,
    maxBpmDiff: CFG.MAX_BPM_DIFF,
    playbackRateTween: CFG.PLAYBACK_RATE_TWEEN,
    destination: S.gain
  });

  S.mixer.on('mixComplete', function () {
    advanceTrack();
    log('Crossfade complete → track ' + S.idx);
  });
  S.mixer.on('trackStarted', function (track) {
    var bpm = track.analysis ? track.analysis.bpm : '?';
    log('WAM track started (' + bpm + ' BPM)');

    // Attach Spectral Flux BPM verifier to live track.
    if (track.source && S.ctx) {
      try { SpectralFluxBPM.init(S.ctx, track.gain); } catch (_) {}
    }
  });
  S.mixer.on('analyzed', function (d) {
    log('WAM analyzed: ' + d.analysis.bpm + ' BPM');
  });
  S.mixer.on('trackEnd', function () { log('WAM track ended'); });
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §3  DOM REFERENCES
   ═══════════════════════════════════════════════════════════════════════════════ */

var D = {};
var IC_PLAY  = '<polygon points="6,3 20,12 6,21"></polygon>';
var IC_PAUSE = '<rect x="5" y="3.5" width="4" height="17" rx="1.2"></rect>'
             + '<rect x="15" y="3.5" width="4" height="17" rx="1.2"></rect>';

function initDOM () {
  ['ar', 'xLive', 'liveText', 'liveDot', 'xWave', 'xInfo', 'xTitle', 'xArtist',
   'xProg', 'xDur', 'xNxt', 'xBtn', 'xIco', 'xElap', 'xBar', 'xSrc', 'xBlk',
   'sysState', 'xErr'].forEach(function (id) {
    D[id] = document.getElementById(id);
  });
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §4  UTILS
   ═══════════════════════════════════════════════════════════════════════════════ */

var _T = '[apollo::radio]';
var log  = function () { var a = [_T]; for (var i = 0; i < arguments.length; i++) a.push(arguments[i]); console.log.apply(console, a); };
var warn = function () { var a = [_T]; for (var i = 0; i < arguments.length; i++) a.push(arguments[i]); console.warn.apply(console, a); };

var fmt = function (s) {
  s = Math.max(0, s | 0);
  return (s / 60 | 0) + ':' + String(s % 60).padStart(2, '0');
};

function sleep (ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

async function fetchWithRetry (url, options, retries) {
  if (retries === undefined) retries = CFG.FETCH_RETRIES;
  var lastErr;
  for (var attempt = 1; attempt <= retries; attempt++) {
    try {
      var res = await fetch(url, options || {});
      if (res.status !== 429) return res;
      var delay = Math.min(CFG.FETCH_DELAY * Math.pow(CFG.FETCH_BACKOFF, attempt - 1), CFG.FETCH_MAX_DELAY);
      warn('HTTP 429, retry in ' + Math.round(delay / 1000) + 's');
      if (attempt < retries) await sleep(delay);
    } catch (e) {
      lastErr = e;
      if (attempt < retries) {
        await sleep(Math.min(CFG.FETCH_DELAY * Math.pow(CFG.FETCH_BACKOFF, attempt - 1), CFG.FETCH_MAX_DELAY));
      }
    }
  }
  if (lastErr) throw lastErr;
  throw new Error('HTTP 429 after ' + retries + ' attempts');
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §5  TIME ENGINE
   ═══════════════════════════════════════════════════════════════════════════════ */

function currentBlockHour () {
  var h = new Date().getHours();
  var bh = 0;
  for (var i = CFG.BLOCK_HOURS.length - 1; i >= 0; i--) {
    if (CFG.BLOCK_HOURS[i] <= h) { bh = CFG.BLOCK_HOURS[i]; break; }
  }
  return bh;
}

function blockFile () {
  return 'radio.' + String(currentBlockHour()).padStart(2, '0') + '.json';
}

function blockLabel () {
  var bh = currentBlockHour();
  return String(bh).padStart(2, '0') + '–' + String((bh + 3) % 24).padStart(2, '0') + ' h';
}

function nextBlockFile () {
  var cur = currentBlockHour();
  var nIdx = (CFG.BLOCK_HOURS.indexOf(cur) + 1) % CFG.BLOCK_HOURS.length;
  return 'radio.' + String(CFG.BLOCK_HOURS[nIdx]).padStart(2, '0') + '.json';
}

function blockElapsed () {
  var now = new Date();
  var bh  = currentBlockHour();
  var bs  = new Date(now.getFullYear(), now.getMonth(), now.getDate(), bh, 0, 0);
  return (now - bs) / 1000;
}

function resolvePosition (pos) {
  var cum = 0;
  for (var i = 0; i < S.list.length; i++) {
    var t = S.list[i];
    if (pos < cum + t.time) {
      var off = pos - cum;
      var ni  = (i + 1) % S.list.length;
      return { i: i, t: t, off: off, rem: t.time - off, nxt: S.list[ni], ni: ni };
    }
    cum += t.time;
  }
  return {
    i: 0, t: S.list[0], off: 0, rem: S.list[0].time,
    nxt: S.list.length > 1 ? S.list[1] : S.list[0],
    ni: S.list.length > 1 ? 1 : 0
  };
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §6  PLAYLIST LOADER — Lazy buffer loading (current + next 1-2 only)
   ═══════════════════════════════════════════════════════════════════════════════ */

async function loadPlaylist () {
  var file = blockFile();
  if (D.xBlk) D.xBlk.textContent = blockLabel();

  var url = CFG.RADIO_JSON + file;
  log('Loading ' + url);

  var data = null;
  var lastErr;

  for (var attempt = 1; attempt <= CFG.FETCH_RETRIES; attempt++) {
    try {
      var controller = new AbortController();
      var tid = setTimeout(function () { controller.abort(); }, CFG.FETCH_TIMEOUT);
      var res = await fetch(url, { signal: controller.signal });
      clearTimeout(tid);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      data = await res.json();
      log('Fetched ' + file + ': ' + data.length + ' tracks');
      break;
    } catch (e) {
      lastErr = e;
      warn('Fetch attempt ' + attempt + ' failed: ' + e.message);
      if (attempt < CFG.FETCH_RETRIES) {
        await sleep(Math.min(CFG.FETCH_DELAY * Math.pow(CFG.FETCH_BACKOFF, attempt - 1), CFG.FETCH_MAX_DELAY));
      }
    }
  }

  // WP REST fallback.
  if ((!data || !Array.isArray(data) || data.length === 0) && CFG.USE_FALLBACK) {
    try {
      var bh = currentBlockHour();
      var restUrl = CFG.REST_URL + 'playlist?block=' + bh;
      log('CDN failed — trying WP REST fallback');
      var rCtrl = new AbortController();
      var rTid   = setTimeout(function () { rCtrl.abort(); }, 10000);
      var rRes   = await fetch(restUrl, { signal: rCtrl.signal });
      clearTimeout(rTid);
      if (rRes.ok) data = await rRes.json();
    } catch (fe) {
      warn('REST fallback failed: ' + fe.message);
    }
  }

  if (!data || !Array.isArray(data) || data.length === 0) {
    throw new Error('Playlist "' + file + '" indisponível');
  }

  S.list = data;
  S.dur  = data.reduce(function (s, t) { return s + t.time; }, 0);
  S.file = file;
  log('Playlist ready: ' + data.length + ' tracks, ' + fmt(S.dur));

  // LAZY LOAD: only current + next track (not entire playlist).
  var elapsed = blockElapsed() % S.dur;
  var info    = resolvePosition(elapsed);

  try {
    await getBuffer(info.t.url);
    log('Loaded current: "' + info.t.title + '"');
  } catch (e) { warn('Failed to load current track: ' + e.message); }

  // Pre-load next track in background.
  if (info.nxt && info.nxt.url !== info.t.url) {
    getBuffer(info.nxt.url).then(function () {
      log('Pre-loaded next: "' + info.nxt.title + '"');
    }).catch(function (e) { warn('Pre-load next failed: ' + e.message); });
  }

  UI.track(info.t, info.nxt);
  UI.progress(info.off, info.t.time);

  // Prefetch next block JSON.
  prefetchNextBlock();
}

async function prefetchNextBlock () {
  var nf = nextBlockFile();
  if (S.nextFile === nf) return;
  try {
    var res = await fetch(CFG.RADIO_JSON + nf, { priority: 'low' });
    if (res.ok) {
      S.nextList = await res.json();
      S.nextFile = nf;
      log('Prefetched next block: ' + nf);
    }
  } catch (_) {}
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §8  TRACK TIMER + MIXBRAIN
   ═══════════════════════════════════════════════════════════════════════════════ */

function trackElapsed () {
  if (!S.on || S.tStart === 0) return 0;
  return S.tOffset + (performance.now() - S.tStart) / 1000;
}

function trackRemaining () {
  if (S.list.length === 0) return Infinity;
  return Math.max(0, S.list[S.idx].time - trackElapsed());
}

function startTimer (offset) { S.tOffset = offset || 0; S.tStart = performance.now(); }

function mixBrain () {
  if (!S.on || !S.mixer || S.list.length < 2) return;

  var rem = trackRemaining();
  var ni  = (S.idx + 1) % S.list.length;
  var nxt = S.list[ni];

  // Lazy-load next buffer when approaching end.
  if (rem <= CFG.PRELOAD_AT && nxt && !S.buffers.has(nxt.url)) {
    getBuffer(nxt.url).catch(function (e) { warn('Preload failed: ' + e.message); });
  }

  // Schedule beat-aligned crossfade.
  if (rem <= CFG.XFADE_AT && nxt && !S.mixer.nextTrack && !S._mixScheduled) {
    var buf = S.buffers.get(nxt.url);
    if (buf) {
      S._mixScheduled = true;
      S.mixer.addBuffer(buf, { matchBPM: true })
        .then(function (wt) {
          S.mixer.scheduleMix(wt, S.ctx.currentTime + rem);
          log('Mix scheduled: ' + (wt.analysis ? wt.analysis.bpm : '?') + ' BPM, in ' + rem.toFixed(1) + 's');
        })
        .catch(function (e) {
          warn('addBuffer failed: ' + e.message);
          S.mixer.add(buf, { startTime: S.ctx.currentTime + rem });
        })
        .finally(function () { S._mixScheduled = false; });
    }
  }
}

function advanceTrack () {
  if (S.list.length === 0) return;
  S.idx = (S.idx + 1) % S.list.length;
  startTimer(0);
  UI.track(S.list[S.idx], S.list[(S.idx + 1) % S.list.length]);

  // Pre-load track after next.
  var nextNext = (S.idx + 1) % S.list.length;
  var nn = S.list[nextNext];
  if (nn && !S.buffers.has(nn.url)) {
    getBuffer(nn.url).catch(function () {});
  }
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §12  UI
   ═══════════════════════════════════════════════════════════════════════════════ */

var UI = {
  _lastTitle: '',

  initViz: function () {
    if (!D.xWave) return;
    D.xWave.innerHTML = '';
    for (var i = 0; i < CFG.VIZ_BARS; i++) {
      var b = document.createElement('div');
      b.className = 'ar-bar';
      b.style.setProperty('--s', (0.35 + Math.random() * 0.9).toFixed(3) + 's');
      b.style.setProperty('--lo', (2 + Math.random() * 4).toFixed(0) + 'px');
      b.style.setProperty('--hi', (10 + Math.random() * 32).toFixed(0) + 'px');
      b.style.setProperty('--d', (Math.random() * -2).toFixed(3) + 's');
      D.xWave.appendChild(b);
    }
  },

  track: function (t, nxt) {
    if (!t) return;
    var title = t.title || '—';

    if (title !== this._lastTitle && S.on) {
      if (D.xInfo) D.xInfo.classList.add('fade');
      setTimeout(function () {
        if (D.xTitle)  D.xTitle.textContent  = title;
        if (D.xArtist) D.xArtist.textContent = t.dj || '';
        if (D.xProg)   D.xProg.textContent   = t.prog || '';
        if (D.xDur)    D.xDur.textContent    = fmt(t.time);
        if (D.xInfo) D.xInfo.classList.remove('fade');
      }, 250);
    } else {
      if (D.xTitle)  D.xTitle.textContent  = title;
      if (D.xArtist) D.xArtist.textContent = t.dj || '';
      if (D.xProg)   D.xProg.textContent   = t.prog || '';
      if (D.xDur)    D.xDur.textContent    = fmt(t.time);
    }
    this._lastTitle = title;

    if (nxt && D.xNxt) D.xNxt.textContent = nxt.title + ' · ' + nxt.dj;

    // data-radio-* cross-page protocol.
    document.querySelectorAll('[data-radio="title"]').forEach(function (el) { el.textContent = title; });
    document.querySelectorAll('[data-radio="artist"]').forEach(function (el) { el.textContent = t.dj || ''; });
    document.querySelectorAll('[data-radio="prog"]').forEach(function (el) { el.textContent = t.prog || ''; });
    document.querySelectorAll('[data-radio="next"]').forEach(function (el) {
      el.textContent = nxt ? nxt.title + ' · ' + (nxt.dj || '') : '';
    });

    // CustomEvent.
    window.dispatchEvent(new CustomEvent('apollo:track', {
      detail: {
        title: title, artist: t.dj || '', prog: t.prog || '',
        url: t.url || '', ref: t.ref || '', time: t.time || 0,
        next: nxt ? { title: nxt.title, artist: nxt.dj, prog: nxt.prog } : null
      }
    }));

    // MediaSession.
    if ('mediaSession' in navigator) {
      navigator.mediaSession.metadata = new MediaMetadata({
        title: title, artist: t.dj || '', album: 'Apollo Radio'
      });
    }

    // BPM badge — show offline analysis + realtime verification.
    if (D.sysState && S.on) {
      var offlineBpm = S.bpms.get(t.url) || 0;
      var liveBpm    = SpectralFluxBPM.finalBpm;
      var conf       = SpectralFluxBPM.confidence;
      if (liveBpm > 0 && conf > 50) {
        D.sysState.textContent = liveBpm + ' BPM';
      } else if (offlineBpm > 0) {
        D.sysState.textContent = offlineBpm + ' BPM';
      } else {
        D.sysState.textContent = 'ao vivo';
      }
      D.sysState.className = 'sys-badge live';
    }
  },

  progress: function (off, dur) {
    if (D.xElap) D.xElap.textContent = fmt(off);
    if (D.xBar) D.xBar.style.width = (dur > 0 ? (off / dur) * 100 : 0).toFixed(2) + '%';
  },

  play: function (on) {
    S.on = on;
    if (D.ar) D.ar.classList.toggle('on', on);
    if (D.xIco) D.xIco.innerHTML = on ? IC_PAUSE : IC_PLAY;
    if (D.xBtn) D.xBtn.setAttribute('aria-label', on ? 'Pausar' : 'Tocar');

    if (D.sysState) {
      if (on) {
        var t   = S.list[S.idx];
        var bpm = t ? (S.bpms.get(t.url) || 0) : 0;
        D.sysState.textContent = bpm ? bpm + ' BPM' : 'ao vivo';
        D.sysState.className   = 'sys-badge live';
      } else {
        D.sysState.textContent = 'pausado';
        D.sysState.className   = 'sys-badge';
      }
    }

    if (D.liveDot) D.liveDot.classList.toggle('on', on);
    if (D.liveText) D.liveText.textContent = on ? 'AO VIVO' : '';
  },

  loading: function (on) {
    S.loading = on;
    if (D.ar) D.ar.classList.toggle('loading', on);
    if (D.sysState) D.sysState.textContent = on ? 'carregando…' : '';
    if (on) {
      if (D.xTitle)  D.xTitle.textContent  = 'Conectando…';
      if (D.xArtist) D.xArtist.textContent = 'Carregando playlist';
      if (D.xProg)   D.xProg.textContent   = '';
    }
  },

  error: function (msg) {
    if (D.xErr) {
      D.xErr.textContent = msg;
      D.xErr.classList.add('on');
      setTimeout(function () { if (D.xErr) D.xErr.classList.remove('on'); }, 6000);
    }
    warn(msg);
  }
};

/* ═══════════════════════════════════════════════════════════════════════════════
   §13  HEARTBEAT TICK
   ═══════════════════════════════════════════════════════════════════════════════ */

var lastTick = 0;
var rafId    = null;

function tick () {
  if (!S.on || S.list.length === 0) return;
  var track = S.list[S.idx];
  var ni    = (S.idx + 1) % S.list.length;
  var el    = trackElapsed();

  UI.track(track, S.list[ni]);
  UI.progress(Math.min(el, track.time), track.time);

  // Spectral Flux real-time BPM.
  SpectralFluxBPM.detect(performance.now());

  mixBrain();

  // Fallback advance if mixer didn't fire.
  if (el >= track.time + 1) {
    log('Fallback advance');
    advanceTrack();
    var next = S.list[S.idx];
    var buf  = next ? S.buffers.get(next.url) : null;
    if (buf && S.mixer) S.mixer.add(buf, { startTime: S.ctx.currentTime });
  }
}

function loop (now) {
  if (!S.on) return;
  if (now - lastTick >= CFG.TICK_MS) { tick(); lastTick = now; }
  rafId = requestAnimationFrame(loop);
}

function startTick () { stopTick(); lastTick = performance.now(); rafId = requestAnimationFrame(loop); }
function stopTick ()  { if (rafId) { cancelAnimationFrame(rafId); rafId = null; } }

/* ═══════════════════════════════════════════════════════════════════════════════
   §14  PLAY / PAUSE
   ═══════════════════════════════════════════════════════════════════════════════ */

async function play () {
  if (S.on || S.loading) return;
  try {
    UI.loading(true);

    initAudioEngine();
    if (S.ctx.state === 'suspended') await S.ctx.resume();

    if (S.list.length === 0) await loadPlaylist();

    S.gain.gain.cancelScheduledValues(S.ctx.currentTime);
    S.gain.gain.setValueAtTime(0, S.ctx.currentTime);

    var elapsed = blockElapsed() % S.dur;
    var info    = resolvePosition(elapsed);
    S.idx = info.i;

    log('▶ "' + info.t.title + '" at ' + fmt(info.off));

    var buffer = S.buffers.get(info.t.url);
    if (!buffer) {
      buffer = await getBuffer(info.t.url);
    }

    try {
      var wamTrack = await S.mixer.addBuffer(buffer);
      S.mixer.playTrack(wamTrack, S.ctx.currentTime, info.off);
      log('BPM: ' + (wamTrack.analysis ? wamTrack.analysis.bpm : '?'));
    } catch (e) {
      warn('addBuffer failed, legacy:', e.message);
      S.mixer.add(buffer, { startTime: info.off });
      S.mixer.play();
    }

    S.on = true;
    startTimer(info.off);
    UI.loading(false);
    UI.play(true);
    UI.track(info.t, info.nxt);

    // Default master gain fade-in (widget mode or direct call).
    // The fullscreen template calls apolloRadio.fadeIn(3000) which overrides this.
    var nowFade = S.ctx.currentTime;
    S.gain.gain.cancelScheduledValues(nowFade);
    S.gain.gain.setValueAtTime(0, nowFade);
    S.gain.gain.linearRampToValueAtTime(0.891, nowFade + 1.5);

    startTick();
    window.dispatchEvent(new CustomEvent('apollo:radio:play'));
  } catch (e) {
    UI.loading(false);
    warn('Play failed:', e);
    UI.error('Falha ao carregar. Verifique sua conexão e tente novamente.');
  }
}

function pause () {
  if (!S.on) return;
  UI.play(false);
  stopTick();
  if (S.mixer) S.mixer.pause();
  SpectralFluxBPM.stop();
  S.on = false;
  S.tStart = 0;
  window.dispatchEvent(new CustomEvent('apollo:radio:pause'));
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §15  INIT — Kill SC Widget + Boot
   ═══════════════════════════════════════════════════════════════════════════════ */

function killSCWidget () {
  var pat = /w\.soundcloud\.com\/player\/api/i;
  Array.from(document.querySelectorAll('script[src]')).forEach(function (s) {
    if (pat.test(s.src) && s.parentNode) s.parentNode.removeChild(s);
  });
  var obs = new MutationObserver(function (muts) {
    muts.forEach(function (m) {
      m.addedNodes.forEach(function (n) {
        if (n.nodeType === 1 && n.tagName === 'SCRIPT' && n.src && pat.test(n.src) && n.parentNode) {
          n.parentNode.removeChild(n);
        }
      });
    });
  });
  obs.observe(document.documentElement, { childList: true, subtree: true });
  if (!window.SC) {
    window.SC = {
      Widget: function () { return { bind:function(){}, play:function(){}, pause:function(){}, toggle:function(){}, seekTo:function(){}, setVolume:function(){}, getPosition:function(c){if(c)c(0);}, getDuration:function(c){if(c)c(0);}, isPaused:function(c){if(c)c(true);}, getCurrentSound:function(c){if(c)c(null);}, getSounds:function(c){if(c)c([]);}, load:function(){} }; },
      initialized: true, _apolloStub: true
    };
  }
}

function init () {
  log('v4.0 initialising…');
  killSCWidget();
  try { initAudioEngine(); } catch (e) { warn('Audio init failed:', e.message); }
  initDOM();
  UI.initViz();

  loadPlaylist().catch(function (e) { warn('Pre-fetch failed:', e.message); });

  if (D.xBtn) {
    D.xBtn.addEventListener('click', function () { S.on ? pause() : play(); });
  }

  document.addEventListener('keydown', function (e) {
    if (e.code === 'Space' && e.target === document.body) {
      e.preventDefault();
      S.on ? pause() : play();
    }
  });

  if ('mediaSession' in navigator) {
    navigator.mediaSession.setActionHandler('play',      play);
    navigator.mediaSession.setActionHandler('pause',     pause);
    navigator.mediaSession.setActionHandler('nexttrack',  advanceTrack);
  }

  // Block boundary check (every 60s).
  setInterval(async function () {
    var nf = blockFile();
    if (nf !== S.file) {
      log('Block boundary → ' + nf);
      try {
        await loadPlaylist();
        if (S.on) { pause(); await sleep(200); play(); }
      } catch (_) {}
    }
  }, 60000);

  log('v4.0 ready');
}

/* ═══════════════════════════════════════════════════════════════════════════════
   §16  PUBLIC API
   ═══════════════════════════════════════════════════════════════════════════════ */

window.apolloRadio = {
  play:   play,
  pause:  pause,
  toggle: function () { S.on ? pause() : play(); },
  state:  S,

  get currentTrack () {
    if (!S.list.length) return null;
    var t = S.list[S.idx];
    return { title: t.title, artist: t.dj, prog: t.prog, url: t.url, ref: t.ref, time: t.time };
  },
  get currentProg () { return S.list.length ? (S.list[S.idx].prog || '') : ''; },
  get isPlaying ()   { return S.on; },
  get liveBpm ()     { return SpectralFluxBPM.finalBpm; },
  get bpmConfidence () { return SpectralFluxBPM.confidence; },

  fadeIn: function (durationMs) {
    if (!S.gain) return;
    durationMs = durationMs || 3000;
    var target = 0.891;
    var now    = S.ctx.currentTime;
    S.gain.gain.cancelScheduledValues(now);
    S.gain.gain.setValueAtTime(S.gain.gain.value || 0, now);
    S.gain.gain.linearRampToValueAtTime(target, now + durationMs / 1000);
  },

  reload: async function () {
    var wasOn = S.on;
    if (wasOn) pause();
    S.list = []; S.file = null;
    await loadPlaylist();
    if (wasOn) play();
  }
};

/* Boot */
init();

})();
