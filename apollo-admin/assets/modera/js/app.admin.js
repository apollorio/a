/*
   APOLLO::RIO · ADMIN · app.admin.js
   Multi-level sliding burger drawer:
     L1 = PLUGINS APOLLO (fathers) → tap slides to L2 = that plugin's sub-links.
   A horizontal subtab strip mirrors L2 in the content header for quick switching.
   Role switch (admin ⇄ mod) re-gates both levels. Handles all data actions.
*/
(function (w, d) {
  'use strict';
  var D = w.APOLLO_ADMIN, R = w.AdminRoles, X = w.AdminRender, V = w.AdminViews, S = w.AdminShell;
  var $ = function (s, c) { return (c || d).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };

  var l1Host = $('#adm-nav-l1'), l2Host = $('#adm-nav-l2'), subtabHost = $('#adm-subtabs'), viewHost = $('#adm-views');
  var l2Title = $('#nav-l2-title'), l2Name = $('#nav-l2-name'), crumbP = $('#crumb-plugin'), crumbS = $('#crumb-sub');

  /* deep-link (/modera/{plugin}/{sub}) injected by data.boot.js — falls back to first allowed */
  var initial = D.initial || {};
  var activeP = initial.plugin || 'membership', activeS = initial.sub || 'grant';

  function renderPanels() {
    var nf = $('#notif-feed'); if (nf) nf.innerHTML = D.notifs.map(X.notif).join('');
    var mf = $('#msgs-feed');  if (mf) mf.innerHTML = D.msgs.map(X.msg).join('');
  }

  /* ── L1 : father plugins grouped ── */
  function buildL1() {
    var counts = D.counts(), groups = [], bucket = {};
    R.plugins().forEach(function (p) { if (!bucket[p.group]) { bucket[p.group] = []; groups.push(p.group); } bucket[p.group].push(p); });
    l1Host.innerHTML = groups.map(function (g) {
      return '<div class="sh"><span class="slbl">' + X.esc(g) + '</span></div>'
        + bucket[g].map(function (p) { return X.navItem(p, p.id === activeP, counts[p.id] || null); }).join('');
    }).join('');
  }

  /* ── L2 : sub-links of active plugin (drawer) + subtab strip (content) ── */
  function buildL2() {
    var p = R.plugin(activeP), subs = R.subs(activeP);
    if (l2Name)  l2Name.textContent = p ? p.name : '';
    if (l2Title) l2Title.textContent = p ? p.label : 'Seções';
    var items = subs.map(function (s) { return X.subItem(s, s.id === activeS); }).join('');
    l2Host.innerHTML = items;
    subtabHost.innerHTML = items;
  }

  function markActive() {
    $$('.ni', l1Host).forEach(function (a) { a.classList.toggle('on', a.getAttribute('data-plugin') === activeP); });
    $$('.adm-sub').forEach(function (b) { b.classList.toggle('on', b.getAttribute('data-sub') === activeS); });
  }

  function renderView() {
    var p = R.plugin(activeP);
    var s = R.subs(activeP).filter(function (x) { return x.id === activeS; })[0] || R.subs(activeP)[0];
    if (!s) { viewHost.innerHTML = '<div class="adm-empty"><i class="ri-lock-2-line"></i>Sem acesso</div>'; return; }
    activeS = s.id;
    var fn = (V[activeP] || {})[activeS];
    viewHost.innerHTML = '<section class="admin-view is-active">' + (fn ? fn(p, s) : '<div class="adm-empty"><i class="ri-inbox-line"></i>Em breve</div>') + '</section>';
    if (crumbP) crumbP.textContent = (p.label || '').toLowerCase();
    if (crumbS) crumbS.textContent = (s.label || '').toLowerCase();
    markActive();
  }

  /* open a plugin → slide drawer to L2, render its first sub */
  function openPlugin(id, keepSub) {
    if (!R.pluginAllowed(id)) { S.toast('Sem acesso a este módulo', 'ri-lock-2-line'); return; }
    activeP = id;
    if (!keepSub || !R.subAllowed(id, activeS)) activeS = R.firstSub(id);
    buildL1(); buildL2(); renderView();
    S.drawerToL2();
  }
  function gotoSub(id) {
    if (!R.subAllowed(activeP, id)) return;
    activeS = id; buildL2(); renderView();
    if (w.innerWidth < 1000) { S.closeAside(); }
    S.closePanels();
  }

  function reflectRole() {
    var isMod = R.isMod();
    $$('#role-switch button').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-role') === R.current()); });
    var pfRole = $('#pf-role'), pfBadge = $('#pf-badge'), aRole = $('#aside-role');
    if (pfRole) pfRole.textContent = 'Núcleo Apollo · ' + R.label();
    if (pfBadge) { pfBadge.textContent = isMod ? 'Mod' : 'Admin'; pfBadge.className = 'tag ' + (isMod ? 'tag' : 'tag-accent'); }
    if (aRole) aRole.textContent = R.label();
    d.body.classList.toggle('is-mod', isMod);
  }

  /* ── role switch ── */
  $$('#role-switch button').forEach(function (b) { b.addEventListener('click', function () { R.set(b.getAttribute('data-role')); }); });
  R.onChange(function (role) {
    reflectRole();
    if (!R.pluginAllowed(activeP)) activeP = (R.plugins()[0] || {}).id;
    if (!R.subAllowed(activeP, activeS)) activeS = R.firstSub(activeP);
    buildL1(); buildL2(); renderView(); S.drawerToL1();
    S.toast('Perfil: ' + R.label(), role === 'mod' ? 'ri-shield-user-line' : 'ri-shield-star-line');
  });

  /* ── nav delegation ── */
  l1Host.addEventListener('click', function (e) { var a = e.target.closest('[data-plugin]'); if (!a) return; e.preventDefault(); openPlugin(a.getAttribute('data-plugin')); });
  l2Host.addEventListener('click', function (e) { var b = e.target.closest('[data-sub]'); if (!b) return; gotoSub(b.getAttribute('data-sub')); });
  subtabHost.addEventListener('click', function (e) { var b = e.target.closest('[data-sub]'); if (!b) return; gotoSub(b.getAttribute('data-sub')); });
  $$('[data-goto]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); openPlugin(a.getAttribute('data-goto')); if (w.innerWidth < 1000) S.closeAside(); S.closePanels(); }); });

  /* ── content actions ── */
  function refreshAfterData() { buildL1(); buildL2(); renderView(); }
  viewHost.addEventListener('click', function (e) {
    var el = e.target.closest('[data-act]');
    if (el) {
      var act = el.getAttribute('data-act');
      if (act === 'grant') { openPlugin('membership'); activeS = 'grant'; buildL2(); renderView(); var sel = $('#grant-member'); if (sel) sel.value = el.getAttribute('data-member'); return; }
      if (act === 'pick-tier') { var ts = $('#grant-tier'); if (ts) ts.value = el.getAttribute('data-tier'); S.toast('Plano selecionado — escolha o membro', 'ri-vip-crown-2-line'); return; }
      if (act === 'resolve' || act === 'dismiss') { var rid = el.getAttribute('data-report'); D.queue = D.queue.filter(function (q) { return q.id !== rid; }); D.audit.unshift({ at:'agora', actor:D.currentUser.handle, action: act==='resolve'?'Resolveu denúncia':'Descartou denúncia', target:rid, kind:'moderation' }); refreshAfterData(); S.toast(act==='resolve'?'Denúncia resolvida':'Descartada', 'ri-check-line'); return; }
      if (act === 'approve-event') { var ev = D.events.filter(function (x) { return x.id === el.getAttribute('data-event'); })[0]; if (ev) { ev.status='approved'; ev.flag=''; } refreshAfterData(); S.toast('Evento aprovado', 'ri-calendar-check-line'); return; }
      if (act === 'advert-approve' || act === 'advert-reject') { var ad = D.adverts.filter(function (x) { return x.id === el.getAttribute('data-advert'); })[0]; if (ad) ad.status = act==='advert-approve'?'published':'flagged'; refreshAfterData(); S.toast(act==='advert-approve'?'Anúncio publicado':'Anúncio recusado', 'ri-megaphone-line'); return; }
      if (act === 'group-approve' || act === 'group-reject') { var g = D.groups.filter(function (x) { return x.id === el.getAttribute('data-group'); })[0]; if (g) { if (act==='group-approve') { g.status='active'; g.members=1; } else { D.groups = D.groups.filter(function (x) { return x.id !== g.id; }); } } refreshAfterData(); S.toast(act==='group-approve'?'Comuna aprovada':'Comuna recusada', 'ri-community-line'); return; }
      if (act === 'req-accept' || act === 'req-deny') { var q = el.getAttribute('data-req'); D.coauthorRequests = D.coauthorRequests.filter(function (r) { return r.id !== q; }); refreshAfterData(); S.toast(act==='req-accept'?'Coautor aceito':'Solicitação negada', 'ri-team-line'); return; }
      if (act === 'coauthor-invite') { S.toast('Convite de coautoria enviado', 'ri-mail-send-line'); return; }
    }
    if (e.target.closest('#grant-do')) {
      var mSel = $('#grant-member'), tSel = $('#grant-tier');
      if (!mSel || !tSel || !tSel.value) return;
      var member = D.members.filter(function (m) { return m.id === mSel.value; })[0];
      var t = D.memberships.filter(function (x) { return x.id === tSel.value; })[0];
      if (!member || !t) return;
      if (R.isMod() && !t.grantableByMod) { S.toast('Plano não liberado para moderação', 'ri-lock-2-line'); return; }
      member.membership = t.id;
      D.audit.unshift({ at:'agora', actor:D.currentUser.handle, action:'Concedeu filiação ' + t.name, target:member.handle, kind:'membership' });
      S.toast(t.name + ' concedido a ' + member.name, 'ri-vip-crown-2-line'); renderView();
    }
  });

  /* admin permission toggles */
  viewHost.addEventListener('change', function (e) {
    var perm = e.target.closest('[data-perm-tier]'); if (!perm) return;
    var t = D.memberships.filter(function (m) { return m.id === perm.getAttribute('data-perm-tier'); })[0];
    if (t) t.grantableByMod = perm.checked;
    S.toast((perm.checked ? 'Liberado' : 'Bloqueado') + ' p/ moderação: ' + (t ? t.name : ''), 'ri-shield-user-line');
  });

  /* boot — sanitize deep-link against the server-gated role before first paint */
  if (!R.pluginAllowed(activeP)) { activeP = (R.plugins()[0] || {}).id; activeS = R.firstSub(activeP); }
  else if (!R.subAllowed(activeP, activeS)) { activeS = R.firstSub(activeP); }
  reflectRole(); renderPanels(); buildL1(); buildL2(); renderView();
})(window, document);
