/*
   APOLLO::RIO · ADMIN · render.js — pure HTML builders (design-system classes).
   mod-card surface tint applied via mc() only when acting as a moderator.
   Exposes window.AdminRender.
*/
(function (w) {
  'use strict';
  var R = w.AdminRoles, D = w.APOLLO_ADMIN;
  function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];}); }
  function mc(){ return (R && R.isMod()) ? ' mod-card' : ''; }
  function tier(id){ return (D.memberships||[]).filter(function(m){return m.id===id;})[0]||{name:id,color:'#9aa0a6'}; }

  var API = {
    esc: esc, mc: mc,
    card: function (inner, extra){ return '<div class="card sh01'+mc()+(extra?' '+extra:'')+'">'+inner+'</div>'; },
    hd:   function (title, right){ return '<div class="adm-card-hd"><h4>'+title+'</h4>'+(right!=null?'<span class="cnt-pill">'+esc(right)+'</span>':'')+'</div>'; },

    statCard: function (k){
      var up=k.trend>=0;
      return '<div class="stat-card'+mc()+'"><div class="stat-header"><div class="stat-icon"><i class="'+k.icon+'"></i></div><button class="btn-more"><i class="ri-more-line"></i></button></div>'
        +'<div><div class="stat-value">'+esc(k.value)+'</div><div class="stat-label">'+esc(k.label)+'</div>'
        +'<div class="stat-trend trend-'+(up?'up':'down')+'"><i class="ri-arrow-'+(up?'up':'down')+'-line"></i>'+(up?'+':'')+k.trend+'%</div></div></div>';
    },

    memberRow: function (m, canManage){
      var t=tier(m.membership);
      var chip=m.status==='active'?'<span class="chip ok">ativo</span>':m.status==='pending'?'<span class="chip warn">pendente</span>':'<span class="chip bad">suspenso</span>';
      return '<div class="adm-row" data-member="'+esc(m.id)+'"><img class="av" src="'+esc(m.avatar)+'" alt="" loading="lazy">'
        +'<div class="adm-row-main"><div class="adm-row-name">'+esc(m.name)+' <span class="mini-swatch" style="background:'+t.color+'" title="'+esc(t.name)+'"></span></div>'
        +'<div class="adm-row-sub">'+esc(m.handle)+' · '+esc(m.role)+' · '+esc(t.name)+'</div></div>'+chip
        +(canManage?'<div class="adm-row-actions"><button class="btn btn-ghost btn-sm" data-act="grant" data-member="'+esc(m.id)+'"><i class="ri-vip-crown-2-line"></i></button></div>':'')+'</div>';
    },

    queueRow: function (q, canAct){
      return '<div class="adm-row" data-report="'+esc(q.id)+'"><span class="dot '+q.severity+'"></span>'
        +'<div class="adm-row-main"><div class="adm-row-name">'+esc(q.type)+' · '+esc(q.target)+'</div>'
        +'<div class="adm-row-sub">'+esc(q.reason)+' · '+esc(q.reporter)+' · '+esc(q.at)+'</div></div>'
        +(q.status==='reviewing'?'<span class="chip warn">em análise</span>':'')
        +(canAct?'<div class="adm-row-actions"><button class="btn btn-ghost btn-sm" data-act="dismiss" data-report="'+esc(q.id)+'"><i class="ri-close-line"></i></button>'
          +'<button class="btn btn-primary btn-sm" data-act="resolve" data-report="'+esc(q.id)+'"><i class="ri-check-line"></i></button></div>':'')+'</div>';
    },

    eventRow: function (e, canAct){
      var chip=e.status==='approved'?'<span class="chip ok">aprovado</span>':e.status==='flagged'?'<span class="chip bad">sinalizado</span>':'<span class="chip warn">pendente</span>';
      var co=((D&&D.eventCoauthors)||{})[e.id]||[];
      var coMeta=co.length?'<span><i class="ri-team-line"></i>+'+co.length+' coautor'+(co.length>1?'es':'')+'</span>':'';
      return '<div class="event-row'+(e.status==='flagged'?' featured':'')+'"><div class="date-box"><span class="date-day">'+esc(e.day)+'</span><span class="date-month">'+esc(e.month)+'</span></div>'
        +'<div class="event-details"><h4>'+esc(e.title)+'</h4><div class="event-meta"><span><i class="ri-map-pin-line"></i>'+esc(e.venue)+'</span><span><i class="ri-price-tag-3-line"></i>'+esc(e.cat)+'</span><span><i class="ri-user-line"></i>'+esc(e.author)+'</span>'+coMeta+(e.flag?'<span><i class="ri-error-warning-line"></i>'+esc(e.flag)+'</span>':'')+'</div></div>'
        +'<div class="event-action">'+chip+(canAct&&e.status!=='approved'?' <button class="btn btn-primary btn-sm" data-act="approve-event" data-event="'+esc(e.id)+'">Aprovar</button>':'')+'</div></div>';
    },

    advertRow: function (a, canAct){
      var chip=a.status==='published'?'<span class="chip ok">publicado</span>':a.status==='flagged'?'<span class="chip bad">sinalizado</span>':'<span class="chip warn">pendente</span>';
      return '<div class="adm-row" data-advert="'+esc(a.id)+'"><span class="adm-ico"><i class="ri-megaphone-line"></i></span>'
        +'<div class="adm-row-main"><div class="adm-row-name">'+esc(a.title)+'</div><div class="adm-row-sub">'+esc(a.cat)+' · '+esc(a.author)+' · <b>'+esc(a.price)+'</b> · '+esc(a.at)+'</div></div>'+chip
        +(canAct&&a.status!=='published'?'<div class="adm-row-actions"><button class="btn btn-ghost btn-sm" data-act="advert-reject" data-advert="'+esc(a.id)+'"><i class="ri-close-line"></i></button><button class="btn btn-primary btn-sm" data-act="advert-approve" data-advert="'+esc(a.id)+'"><i class="ri-check-line"></i></button></div>':'')+'</div>';
    },

    taxRow: function (c){
      return '<div class="adm-row"><span class="mini-swatch lg" style="background:'+(c.color||'#9aa0a6')+'"></span><div class="adm-row-main"><div class="adm-row-name">'+esc(c.name)+'</div>'
        +'<div class="adm-row-sub">'+esc(c.count)+' itens'+(c.fields?' · campos: '+esc(c.fields.join(', ')):'')+'</div></div>'
        +'<div class="adm-row-actions"><button class="btn btn-ghost btn-sm"><i class="ri-edit-line"></i></button></div></div>';
    },

    fieldRow: function (f){
      return '<div class="adm-row"><span class="adm-ico"><i class="ri-input-cursor-move"></i></span><div class="adm-row-main"><div class="adm-row-name">'+esc(f.label)+' <span class="chip">'+esc(f.type)+'</span></div>'
        +'<div class="adm-row-sub txt-mono">'+esc(f.key)+(f.required?' · obrigatório':'')+(f.filter?' · filtrável':'')+'</div></div>'
        +'<label class="toggle-wrap"><input type="checkbox" class="toggle-input" role="switch"'+((f.show||f.filter)?' checked':'')+'><span class="toggle-track"></span></label></div>';
    },

    planCard: function (p){
      return '<div class="mg-tier'+mc()+(p.featured?' is-feat':'')+'"><div class="mg-tier-top"><span class="mg-swatch" style="background:'+(p.featured?'var(--accent)':'#9aa0a6')+'"><i class="ri-coupon-3-line"></i></span>'
        +'<span class="mg-tier-price">'+(p.util?esc(p.util)+' utils':'Grátis')+'</span></div><div class="mg-tier-name">'+esc(p.name)+'</div>'
        +'<ul class="mg-perks"><li><i class="ri-time-line"></i>'+esc(p.days)+' dias no ar</li><li><i class="'+(p.featured?'ri-star-fill':'ri-star-line')+'"></i>'+(p.featured?'Destaque na busca':'Sem destaque')+'</li></ul>'
        +'<button class="btn btn-secondary btn-sm" style="width:100%"><i class="ri-edit-line"></i>Editar plano</button></div>';
    },

    tierCard: function (m, grantable){
      return '<div class="mg-tier'+mc()+(grantable?'':' is-locked')+'"><div class="mg-tier-top"><span class="mg-swatch" style="background:'+m.color+'"><i class="ri-vip-crown-2-line"></i></span>'
        +'<span class="mg-tier-price">'+esc(m.price)+'</span></div><div class="mg-tier-name">'+esc(m.name)+'</div>'
        +'<ul class="mg-perks">'+(m.perks||[]).map(function(p){return '<li><i class="ri-check-line"></i>'+esc(p)+'</li>';}).join('')+'</ul>'
        +(grantable?'<button class="btn btn-secondary btn-sm" data-act="pick-tier" data-tier="'+esc(m.id)+'" style="width:100%"><i class="ri-user-add-line"></i>Conceder</button>':'<div class="mg-lock-note"><i class="ri-lock-2-line"></i>Somente administradores</div>')+'</div>';
    },

    permRow: function (m){
      return '<div class="perm-row"><div><div class="perm-label"><span class="mg-swatch sm" style="background:'+m.color+'"><i class="ri-vip-crown-2-line"></i></span>'+esc(m.name)+'</div>'
        +'<div class="perm-hint">Permitir que a moderação conceda esta filiação</div></div>'
        +'<label class="toggle-wrap"><input type="checkbox" class="toggle-input" role="switch" data-perm-tier="'+esc(m.id)+'"'+(m.grantableByMod?' checked':'')+'><span class="toggle-track"></span></label></div>';
    },

    settingToggle: function (s){
      return '<label class="perm-row" style="cursor:pointer"><div><div class="perm-label">'+esc(s.label)+'</div>'+(s.note?'<div class="perm-hint">'+esc(s.note)+'</div>':'')+'</div>'
        +'<span class="toggle-wrap"><input type="checkbox" class="toggle-input" role="switch"'+(s.on?' checked':'')+'><span class="toggle-track"></span></span></label>';
    },

    auditRow: function (a){
      return '<div class="audit-row"><span class="audit-t">'+esc(a.at)+'</span><span class="audit-act"><b>'+esc(a.actor)+'</b> · '+esc(a.action)+'</span><span class="audit-tgt">'+esc(a.target)+'</span></div>';
    },

    /* scope chip — makes "who sees this" explicit (Admin vs Mod) */
    scopeChip: function (roles){
      var adminOnly = roles && roles.length === 1 && roles[0] === 'admin';
      return adminOnly
        ? '<span class="scope-chip scope-admin"><i class="ri-shield-star-line"></i>Exclusivo do Administrador</span>'
        : '<span class="scope-chip scope-both"><i class="ri-shield-user-line"></i>Admin + Moderação</span>';
    },

    /* apollo-groups */
    groupRow: function (g, canManage){
      var isNucleo = g.type === 'nucleo';
      var badge = isNucleo ? '<span class="chip nucleo"><i class="ri-shield-star-line"></i>Núcleo</span>' : '<span class="chip comuna"><i class="ri-group-2-line"></i>Comuna</span>';
      var status = g.status === 'pending' ? '<span class="chip warn">aprovação pendente</span>' : '<span class="chip ok">ativo</span>';
      return '<div class="adm-row" data-group="'+esc(g.id)+'"><span class="adm-ico"><i class="'+(isNucleo?'ri-shield-star-line':'ri-group-2-line')+'"></i></span>'
        +'<div class="adm-row-main"><div class="adm-row-name">'+esc(g.name)+' '+badge+'</div>'
        +'<div class="adm-row-sub">admin do grupo: <b>'+esc(g.admin)+'</b> · '+g.members+' membros · por '+esc(g.by)+'</div></div>'+status
        +(canManage && g.status==='pending' ? '<div class="adm-row-actions"><button class="btn btn-ghost btn-sm" data-act="group-reject" data-group="'+esc(g.id)+'"><i class="ri-close-line"></i></button><button class="btn btn-primary btn-sm" data-act="group-approve" data-group="'+esc(g.id)+'"><i class="ri-check-line"></i></button></div>':'')
        +'</div>';
    },

    /* apollo-coauthor */
    coauthorRow: function (title, coauthors){
      var chips = (coauthors&&coauthors.length) ? coauthors.map(function(c){return '<span class="tag">'+esc(c)+'</span>';}).join(' ') : '<span class="muted text-xs">sem coautores</span>';
      return '<div class="adm-row"><span class="adm-ico"><i class="ri-calendar-event-line"></i></span><div class="adm-row-main"><div class="adm-row-name">'+esc(title)+'</div><div class="adm-row-sub"><div class="flex-row" style="gap:5px;margin-top:4px">'+chips+'</div></div></div><button class="btn btn-ghost btn-sm" data-act="coauthor-invite"><i class="ri-user-add-line"></i></button></div>';
    },
    inviteRow: function (i){
      var chip = i.status==='accepted'?'<span class="chip ok">aceito</span>':'<span class="chip warn">pendente</span>';
      return '<div class="adm-row"><span class="adm-ico"><i class="ri-mail-send-line"></i></span><div class="adm-row-main"><div class="adm-row-name">'+esc(i.invitee)+' → '+esc(i.event)+'</div><div class="adm-row-sub">convidado por '+esc(i.by)+'</div></div>'+chip+'</div>';
    },
    requestRow: function (r, canAct){
      return '<div class="adm-row" data-req="'+esc(r.id)+'"><span class="adm-ico"><i class="ri-user-add-line"></i></span><div class="adm-row-main"><div class="adm-row-name">'+esc(r.user)+' quer ser <b>'+esc(r.role)+'</b></div><div class="adm-row-sub">'+esc(r.event)+'</div></div>'
        +(canAct?'<div class="adm-row-actions"><button class="btn btn-ghost btn-sm" data-act="req-deny" data-req="'+esc(r.id)+'"><i class="ri-close-line"></i></button><button class="btn btn-primary btn-sm" data-act="req-accept" data-req="'+esc(r.id)+'"><i class="ri-check-line"></i></button></div>':'')+'</div>';
    },

    /* apollo-hub (linktree) */
    hubLinkRow: function (l){
      return '<div class="adm-row"><span class="adm-ico"><i class="ri-links-line"></i></span><div class="adm-row-main"><div class="adm-row-name">'+esc(l.label)+'</div><div class="adm-row-sub txt-mono">'+esc(l.url)+' · '+esc(l.clicks)+' cliques</div></div>'
        +'<label class="toggle-wrap"><input type="checkbox" class="toggle-input" role="switch"'+(l.on?' checked':'')+'><span class="toggle-track"></span></label></div>';
    },

    /* apolloDJ */
    djMembership: function (m){
      return '<div class="dj-mem'+(m.active?' active':'')+'" data-djmem="'+esc(m.id)+'"><div class="dj-mem-name">'+esc(m.name)+'</div><div class="dj-mem-meta">'+esc(m.meta)+'</div></div>';
    },
    djCapRow: function (c){
      function chk(on){ return '<input type="checkbox" class="adm-check"'+(on?' checked':'')+'>'; }
      return '<tr><td class="txt-mono">'+esc(c.name)+'</td><td>'+chk(c.view)+'</td><td>'+chk(c.use)+'</td><td>'+chk(c.edit)+'</td><td>'+chk(c.admin)+'</td></tr>';
    },

    notif: function (n){ return '<div class="notif-item'+(n.unread?' unread':'')+'"><div class="notif-av"><i class="'+n.icon+'"></i></div><div class="notif-body"><p class="notif-text">'+n.text+'</p><p class="notif-time">'+esc(n.time)+'</p></div>'+(n.unread?'<span class="notif-dot"></span>':'')+'</div>'; },
    msg:   function (m){ return '<div class="notif-item'+(m.unread?' unread':'')+'"><div class="notif-av notif-av--initials">'+esc(m.initials)+'</div><div class="notif-body"><p class="notif-text">'+m.text+'</p><p class="notif-time">'+esc(m.time)+'</p></div>'+(m.unread?'<span class="notif-dot"></span>':'')+'</div>'; },

    /* nav — father plugin (drawer L1). Chevron = has sub-levels (slides to L2). */
    navItem: function (p, active, badge){
      var adminOnly = p.roles && p.roles.length === 1 && p.roles[0] === 'admin';
      return '<a class="ni'+(active?' on':'')+'" href="#" data-plugin="'+esc(p.id)+'"><i class="'+p.icon+'"></i><span class="sn">'+esc(p.label)+'</span>'
        +(adminOnly?'<span class="ni-lock" title="Exclusivo do Administrador"><i class="ri-shield-star-line"></i></span>':'')
        +(badge?'<span class="cnt">'+esc(badge)+'</span>':'')
        +'<i class="ri-arrow-right-s-line ni-chev"></i></a>';
    },
    /* drawer L2 sub-link + horizontal subtab pill share this builder */
    subItem: function (s, active){
      var adminOnly = s.roles && s.roles.length === 1 && s.roles[0] === 'admin';
      return '<button class="adm-sub'+(active?' on':'')+'" data-sub="'+esc(s.id)+'"><i class="'+s.icon+'"></i><span>'+esc(s.label)+'</span>'
        +(adminOnly?'<i class="ri-shield-star-line adm-sub-lock" title="Exclusivo do Administrador"></i>':'')+'</button>';
    }
  };
  w.AdminRender = API;
})(window);
