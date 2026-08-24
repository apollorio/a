/*
   APOLLO::RIO · MODERA · data.boot.js
   Merges the server truth (window.APOLLO_MODERA_BOOT, printed by
   templates/modera/parts/scripts.php) into window.APOLLO_ADMIN — the
   dataset seeded by data.defaults.js — KEEPING THE KEYS.

   Server wins on: currentUser, roles (already role-gated server-side so a
   MOD can never escalate to admin), counts (per-key override), initial
   deep-link and REST hints. Demo datasets stay as placeholders until each
   section is wired to REST (structure-first build).

   Load order contract: core.js → data.defaults.js → [inline BOOT] →
   data.boot.js → shell.admin.js → roles.js → render.js → views.js → app.admin.js
*/
(function (w) {
  'use strict';
  var D = w.APOLLO_ADMIN || (w.APOLLO_ADMIN = {});
  var B = w.APOLLO_MODERA_BOOT || {};

  if (B.currentUser) { D.currentUser = B.currentUser; }
  if (B.roles)       { D.roles = B.roles; }   /* server-filtered: mod build ships only { mod } */
  if (B.initial)     { D.initial = B.initial; }
  if (B.rest)        { D.rest = B.rest; }

  /* Real counts override the demo-derived ones key-by-key (null = keep demo). */
  if (B.counts) {
    var demoCounts = (typeof D.counts === 'function') ? D.counts : function () { return {}; };
    D.counts = function () {
      var base = demoCounts();
      for (var k in B.counts) {
        if (Object.prototype.hasOwnProperty.call(B.counts, k) && B.counts[k] !== null && B.counts[k] !== undefined) {
          base[k] = B.counts[k];
        }
      }
      return base;
    };
  }
})(window);
