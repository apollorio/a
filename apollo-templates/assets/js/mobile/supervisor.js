/**
 * ApolloSupervisor — isolated parallel unit runner (fail-soft).
 *
 * Plug-n-play: register({ id, run, fallback, critical, timeout, retries })
 * then runAll() → { results, statuses, ok, hardStopped }.
 *
 * Rules enforced here:
 *   · independent units — no shared mutable state inside the runner
 *   · one failure never aborts siblings (allSettled + per-unit try/catch)
 *   · timeout, circuit breaker, idempotent retry with backoff + jitter
 *   · on fail: isolate → log → retry → fallback → degrade
 *   · hard-stop only if a CRITICAL unit fails after all fallbacks
 *
 * @global window.ApolloSupervisor
 */
(function (w) {
  'use strict';

  if (w.ApolloSupervisor && w.ApolloSupervisor.__v) return;

  var BREAKER_OPEN_MS = 30000;
  var DEFAULT_TIMEOUT = 4000;
  var DEFAULT_RETRIES = 2;

  var registry = Object.create(null);
  var breakers = Object.create(null);
  var lastRun = null;

  function now() {
    return Date.now();
  }

  function log(level, id, msg, detail) {
    try {
      var fn = level === 'error' ? 'error' : level === 'warn' ? 'warn' : 'debug';
      if (typeof console !== 'undefined' && console[fn]) {
        console[fn].call(console, '[ApolloSupervisor:' + id + '] ' + msg, detail || '');
      }
    } catch (e) { /* never throw from logger */ }
  }

  function jitter(base) {
    return Math.round(base * (0.7 + Math.random() * 0.6));
  }

  function sleep(ms) {
    return new Promise(function (resolve) {
      setTimeout(resolve, ms);
    });
  }

  function withTimeout(promise, ms, id) {
    return new Promise(function (resolve, reject) {
      var settled = false;
      var t = setTimeout(function () {
        if (settled) return;
        settled = true;
        reject(new Error('timeout ' + ms + 'ms (' + id + ')'));
      }, ms);
      Promise.resolve(promise).then(
        function (v) {
          if (settled) return;
          settled = true;
          clearTimeout(t);
          resolve(v);
        },
        function (e) {
          if (settled) return;
          settled = true;
          clearTimeout(t);
          reject(e);
        }
      );
    });
  }

  function breakerOpen(id) {
    var b = breakers[id];
    if (!b) return false;
    if (b.openUntil && b.openUntil > now()) return true;
    if (b.openUntil && b.openUntil <= now()) {
      breakers[id] = { fails: 0, openUntil: 0 };
      return false;
    }
    return false;
  }

  function breakerFail(id) {
    var b = breakers[id] || { fails: 0, openUntil: 0 };
    b.fails += 1;
    if (b.fails >= 3) {
      b.openUntil = now() + BREAKER_OPEN_MS;
      log('warn', id, 'circuit open', { ms: BREAKER_OPEN_MS });
    }
    breakers[id] = b;
  }

  function breakerOk(id) {
    breakers[id] = { fails: 0, openUntil: 0 };
  }

  /**
   * @param {object} unit
   * @param {string} unit.id
   * @param {function(): (any|Promise<any>)} unit.run
   * @param {function(Error): (any|Promise<any>)=} unit.fallback
   * @param {boolean=} unit.critical
   * @param {number=} unit.timeout
   * @param {number=} unit.retries
   * @param {boolean=} unit.enabled
   */
  function register(unit) {
    if (!unit || !unit.id || typeof unit.run !== 'function') {
      log('warn', 'register', 'invalid unit', unit);
      return Api;
    }
    registry[unit.id] = {
      id: unit.id,
      run: unit.run,
      fallback: typeof unit.fallback === 'function' ? unit.fallback : null,
      critical: !!unit.critical,
      timeout: unit.timeout > 0 ? unit.timeout : DEFAULT_TIMEOUT,
      retries: unit.retries >= 0 ? unit.retries : DEFAULT_RETRIES,
      enabled: unit.enabled !== false
    };
    return Api;
  }

  function unregister(id) {
    delete registry[id];
    return Api;
  }

  function list() {
    return Object.keys(registry).map(function (id) {
      var u = registry[id];
      return { id: u.id, critical: u.critical, enabled: u.enabled };
    });
  }

  function runOne(unit) {
    var attempt = 0;
    var lastErr = null;

    function attemptRun() {
      if (breakerOpen(unit.id)) {
        return Promise.reject(new Error('circuit open'));
      }
      return withTimeout(
        Promise.resolve().then(function () {
          return unit.run();
        }),
        unit.timeout,
        unit.id
      );
    }

    function loop() {
      return attemptRun().then(
        function (value) {
          breakerOk(unit.id);
          return {
            id: unit.id,
            status: 'fulfilled',
            value: value,
            attempts: attempt + 1,
            critical: unit.critical,
            usedFallback: false
          };
        },
        function (err) {
          lastErr = err;
          breakerFail(unit.id);
          log('warn', unit.id, 'attempt failed', err && err.message ? err.message : err);
          if (attempt < unit.retries) {
            attempt += 1;
            var backoff = jitter(120 * Math.pow(2, attempt - 1));
            return sleep(backoff).then(loop);
          }
          if (unit.fallback) {
            return Promise.resolve()
              .then(function () {
                return unit.fallback(lastErr);
              })
              .then(
                function (value) {
                  log('warn', unit.id, 'fallback used');
                  return {
                    id: unit.id,
                    status: 'fulfilled',
                    value: value,
                    attempts: attempt + 1,
                    critical: unit.critical,
                    usedFallback: true,
                    error: lastErr && lastErr.message ? lastErr.message : String(lastErr)
                  };
                },
                function (fbErr) {
                  return {
                    id: unit.id,
                    status: 'rejected',
                    reason: fbErr && fbErr.message ? fbErr.message : String(fbErr),
                    attempts: attempt + 1,
                    critical: unit.critical,
                    usedFallback: true,
                    error: lastErr && lastErr.message ? lastErr.message : String(lastErr)
                  };
                }
              );
          }
          return {
            id: unit.id,
            status: 'rejected',
            reason: lastErr && lastErr.message ? lastErr.message : String(lastErr),
            attempts: attempt + 1,
            critical: unit.critical,
            usedFallback: false
          };
        }
      );
    }

    return loop().catch(function (e) {
      /* Absolute isolation — never reject the outer allSettled slot. */
      return {
        id: unit.id,
        status: 'rejected',
        reason: e && e.message ? e.message : String(e),
        attempts: attempt + 1,
        critical: unit.critical,
        usedFallback: false
      };
    });
  }

  /**
   * Run all enabled units in parallel. Never throws.
   * @param {object=} opts
   * @param {string[]=} opts.only
   * @param {string[]=} opts.except
   * @returns {Promise<{results:object, statuses:object, ok:boolean, hardStopped:boolean, partial:boolean}>}
   */
  function runAll(opts) {
    opts = opts || {};
    var ids = Object.keys(registry).filter(function (id) {
      var u = registry[id];
      if (!u.enabled) return false;
      if (opts.only && opts.only.indexOf(id) === -1) return false;
      if (opts.except && opts.except.indexOf(id) !== -1) return false;
      return true;
    });

    var tasks = ids.map(function (id) {
      return runOne(registry[id]);
    });

    return Promise.all(tasks).then(function (settled) {
      /* Note: Promise.all on already-isolated runOne results — never rejects. */
      var results = Object.create(null);
      var statuses = Object.create(null);
      var hardStopped = false;
      var anyFail = false;

      settled.forEach(function (row) {
        results[row.id] = row;
        statuses[row.id] = row.status;
        if (row.status !== 'fulfilled') {
          anyFail = true;
          if (row.critical) hardStopped = true;
          log('error', row.id, 'unit rejected after fallbacks', row.reason);
        }
      });

      lastRun = {
        results: results,
        statuses: statuses,
        ok: !hardStopped,
        hardStopped: hardStopped,
        partial: anyFail && !hardStopped,
        at: now()
      };

      try {
        w.dispatchEvent(
          new CustomEvent('apollo:mobile-supervisor', { detail: lastRun })
        );
      } catch (e) { /* IE / locked DOM */ }

      return lastRun;
    });
  }

  var Api = {
    __v: 1,
    register: register,
    unregister: unregister,
    list: list,
    runAll: runAll,
    last: function () {
      return lastRun;
    },
    resetBreakers: function () {
      breakers = Object.create(null);
      return Api;
    }
  };

  w.ApolloSupervisor = Api;
})(window);
