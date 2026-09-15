/**
 * safety/signals-api.js — REST wrappers for /apollo/v1/safety/
 */
(function (w) {
  'use strict';

  var cfg = w.ApolloSafety || {};

  function api(path, options) {
    return fetch(cfg.rest + path, Object.assign(
      {
        credentials: 'same-origin',
        headers: {
          'X-WP-Nonce': cfg.nonce,
          'Content-Type': 'application/json'
        }
      },
      options || {}
    )).then(function (r) {
      return r.json().then(function (b) {
        return { ok: r.ok, status: r.status, body: b };
      });
    });
  }

  w.ApolloSafetyAPI = {
    signals: function (advert) {
      return api('signals?advert=' + encodeURIComponent(advert));
    },
    vouch: function (advert, username) {
      return api('vouch', {
        method: 'POST',
        body: JSON.stringify({ advert: advert, username: username })
      });
    },
    confirm: function (advert, buyer) {
      return api('vouch/confirm', {
        method: 'POST',
        body: JSON.stringify({ advert: advert, buyer: buyer })
      });
    }
  };
})(window);
