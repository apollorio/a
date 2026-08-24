/*
   APOLLO::RIO · ADMIN · roles.js
   Runtime role (admin ⇄ mod) + capability gating + two-level plugin/sub filter.
   Exposes window.AdminRoles.
*/
(function (w) {
  'use strict';
  var D = w.APOLLO_ADMIN || {};
  var role = (D.currentUser && D.currentUser.role) || 'admin';
  var listeners = [];

  function caps() { return ((D.roles && D.roles[role]) || {}).caps || []; }
  function plugin(id) { return (D.plugins || []).filter(function (p) { return p.id === id; })[0]; }

  var API = {
    current: function () { return role; },
    label:   function () { return ((D.roles && D.roles[role]) || {}).label || role; },
    isMod:   function () { return role === 'mod'; },
    isAdmin: function () { return role === 'admin'; },
    can: function (c) { return caps().indexOf(c) !== -1; },

    /* father plugins visible to the current role */
    plugins: function () { return (D.plugins || []).filter(function (p) { return p.roles.indexOf(role) !== -1; }); },
    pluginAllowed: function (id) { var p = plugin(id); return !!p && p.roles.indexOf(role) !== -1; },

    /* sub-tabs of a plugin visible to the current role */
    subs: function (pid) { var p = plugin(pid); return p ? p.subs.filter(function (s) { return s.roles.indexOf(role) !== -1; }) : []; },
    subAllowed: function (pid, sid) { return API.subs(pid).some(function (s) { return s.id === sid; }); },
    firstSub: function (pid) { var s = API.subs(pid)[0]; return s ? s.id : null; },
    plugin: plugin,

    /* membership tiers this role may grant (mods → admin-allowed only) */
    grantableTiers: function () {
      var all = D.memberships || [];
      return role === 'admin' ? all : all.filter(function (m) { return m.grantableByMod; });
    },

    set: function (next) { if (next !== role && D.roles[next]) { role = next; listeners.forEach(function (cb) { try { cb(role); } catch (e) {} }); } },
    onChange: function (cb) { if (typeof cb === 'function') listeners.push(cb); }
  };
  w.AdminRoles = API;
})(window);
