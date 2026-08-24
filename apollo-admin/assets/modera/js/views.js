/*
   APOLLO::RIO · ADMIN · views.js
   VIEWS[pluginId][subId]() → inner HTML for the content pane. Admin-only subs
   are gated by roles.js so they're never requested for mods. All values are in
   UTILS (no money). Exposes window.AdminViews.
*/
(function (w) {
  'use strict';
  var D = w.APOLLO_ADMIN, R = w.AdminRoles, X = w.AdminRender;

  function head(plugin, sub, actions) {
    var label = typeof sub === 'string' ? sub : sub.label;
    var roles = typeof sub === 'string' ? ['admin','mod'] : sub.roles;
    return '<div class="adm-head"><div><span class="adm-eyebrow"><i class="'+X.esc(plugin.icon)+'"></i> '+X.esc(plugin.name)+'</span>'
      +'<h1 class="display-text" style="font-size:var(--fs-h2)">'+X.esc(label)+'</h1>'
      +'<div class="adm-scope">'+X.scopeChip(roles)+'</div></div>'
      +'<div class="adm-head-x"><span class="tag '+(R.isMod()?'tag':'tag-accent')+'"><i class="'+(R.isMod()?'ri-shield-user-line':'ri-shield-star-line')+'"></i> '+X.esc(R.label())+'</span>'+(actions||'')+'</div></div>';
  }
  function grid(inner){ return '<div class="grid-2">'+inner+'</div>'; }

  var V = {

    /* ══════════ apollo-membership ══════════ */
    membership: {
      grant: function (p, s) {
        var grantable = R.grantableTiers(), gids = grantable.map(function(t){return t.id;});
        var opts = grantable.map(function(t){return '<option value="'+t.id+'">'+X.esc(t.name)+' · '+X.esc(t.price)+'</option>';}).join('');
        var mem = D.members.map(function(m){return '<option value="'+m.id+'">'+X.esc(m.name)+' ('+X.esc(m.handle)+')</option>';}).join('');
        var note = R.isMod()
          ? '<div class="alert alert-accent" style="margin-bottom:16px"><i class="ri-information-line"></i><div><div class="alert-title">Filiações liberadas pelo administrador</div>Você concede apenas os planos abaixo; os demais ficam bloqueados até liberação.</div></div>'
          : '<div class="alert alert-success" style="margin-bottom:16px"><i class="ri-shield-star-line"></i><div><div class="alert-title">Acesso total</div>Você concede qualquer filiação e define o que a moderação pode liberar (aba Delegação).</div></div>';
        var html = head(p, s) + note;
        html += X.card(X.hd('<i class="ri-user-add-line"></i> Conceder a um filiado')
          +'<div class="mg-picker"><select class="apollo-select" id="grant-member">'+mem+'</select>'
          +'<select class="apollo-select" id="grant-tier">'+(opts||'<option>—</option>')+'</select>'
          +'<button class="btn btn-primary" id="grant-do"'+(grantable.length?'':' disabled')+'><i class="ri-check-line"></i>Conceder</button></div>');
        html += '<div class="adm-card-hd" style="margin:26px 0 12px"><h4>Planos disponíveis</h4><span class="cnt-pill">'+grantable.length+' de '+D.memberships.length+'</span></div>';
        html += '<div class="mg-grid">'+D.memberships.map(function(t){
          var ok=gids.indexOf(t.id)!==-1; if (R.isMod()&&!ok) return ''; return X.tierCard(t, R.isAdmin()?true:ok);
        }).join('')+'</div>';
        return html;
      },
      plans: function (p, s) {
        return head(p, s, '<button class="btn btn-accent btn-sm"><i class="ri-add-line"></i>Novo plano</button>')
          + '<p class="tref-sec-desc">Valores em <b>utils</b> — a plataforma não usa dinheiro.</p>'
          + '<div class="mg-grid">'+D.memberships.map(function(m){return X.tierCard(m, true);}).join('')+'</div>';
      },
      members: function (p, s) {
        var canManage = R.can('grant_membership');
        return head(p, s, '<button class="btn btn-secondary btn-sm"><i class="ri-download-line"></i>Exportar</button>')
          + X.card(X.hd('Filiados', D.members.length) + D.members.map(function(m){return X.memberRow(m, canManage);}).join(''));
      },
      delegation: function (p, s) {
        return head(p, s)
          + X.card(X.hd('<i class="ri-shield-user-line"></i> A moderação pode conceder')
            + '<p class="txt-secondary text-sm" style="margin-bottom:10px">Defina quais filiações a moderação tem permissão de conceder. Reflete na hora na aba <b>Conceder filiação</b> do perfil Mod.</p>'
            + D.memberships.map(X.permRow).join(''));
      }
    },

    /* ══════════ apollo-events (wp-events-manager style) ══════════ */
    events: {
      all: function (p, s) {
        var canAct = R.can('moderate');
        return head(p, s, '<button class="btn btn-primary btn-sm"><i class="ri-add-line"></i>Novo evento</button>')
          + X.card(X.hd('Eventos', D.events.length) + '<div class="event-list-container">'+D.events.map(function(e){return X.eventRow(e, canAct);}).join('')+'</div>');
      },
      queue: function (p, s) {
        var canAct = R.can('moderate');
        var pend = D.events.filter(function(e){return e.status!=='approved';});
        return head(p, s)
          + X.card(X.hd('Aguardando aprovação', pend.length) + (pend.length?'<div class="event-list-container">'+pend.map(function(e){return X.eventRow(e, canAct);}).join('')+'</div>':'<div class="adm-empty"><i class="ri-check-double-line"></i>Nada pendente</div>'));
      },
      taxonomies: function (p, s) {
        var types = D.eventTypes.map(function(t){return '<span class="tag tag-primary">'+X.esc(t)+'</span>';}).join(' ');
        return head(p, s, '<button class="btn btn-secondary btn-sm"><i class="ri-add-line"></i>Nova categoria</button>')
          + grid(
            X.card(X.hd('Categorias', D.eventCategories.length) + D.eventCategories.map(X.taxRow).join(''))
          + X.card(X.hd('Tipos de evento') + '<div class="flex-row" style="gap:6px">'+types+'</div>'
              + '<div class="input-group" style="margin-top:16px"><input type="text" class="apollo-input" placeholder=" "><label class="apollo-label">Adicionar tipo</label></div>'));
      },
      venues: function (p, s) {
        return head(p, s, '<button class="btn btn-secondary btn-sm"><i class="ri-add-line"></i>Novo local</button>')
          + X.card(X.hd('Locais', D.venues.length) + D.venues.map(function(v){
              return '<div class="adm-row"><span class="adm-ico"><i class="ri-map-pin-line"></i></span><div class="adm-row-main"><div class="adm-row-name">'+X.esc(v.name)+' '+(v.verified?'<i class="ri-verified-badge-fill" style="color:var(--accent)"></i>':'')+'</div><div class="adm-row-sub">'+X.esc(v.city)+' · cap. '+v.cap+'</div></div>'
              +'<span class="chip '+(v.verified?'ok':'warn')+'">'+(v.verified?'verificado':'a verificar')+'</span></div>';
            }).join(''));
      },
      lineup: function (p, s) {
        return head(p, s)
          + X.card(X.hd('<i class="ri-disc-line"></i> DJs & line-up')
            + '<p class="txt-secondary text-sm" style="margin-bottom:12px">Integração apollo-events ↔ CPT <span class="txt-mono">dj</span>. Ordene, atribua horários e vincule ao evento.</p>'
            + ['Marta Supernova · Opening','Leo Janeiro · Main','DJ Hell · Headliner'].map(function(n,i){
                return '<div class="adm-row"><span class="adm-ord">'+(i+1)+'º</span><span class="adm-ico"><i class="ri-user-star-line"></i></span><div class="adm-row-main"><div class="adm-row-name">'+X.esc(n)+'</div></div><div class="adm-row-actions"><button class="btn btn-ghost btn-sm"><i class="ri-time-line"></i></button></div></div>';
              }).join(''));
      },
      fields: function (p, s) {
        return head(p, s, '<button class="btn btn-secondary btn-sm"><i class="ri-add-line"></i>Novo campo</button>')
          + X.card(X.hd('Campos personalizados', D.eventFields.length)
            + '<p class="txt-secondary text-sm" style="margin-bottom:12px">Meta-campos registrados no CPT <span class="txt-mono">event</span> (estilo wp-events-manager).</p>'
            + D.eventFields.map(X.fieldRow).join(''));
      },
      settings: function (p, s) {
        return head(p, s)
          + grid(
              X.card(X.hd('Fluxo & moderação') + D.eventSettings.map(X.settingToggle).join(''))
            + X.card(X.hd('Padrões')
                + '<div class="input-group"><input type="text" class="apollo-input" placeholder=" " value="America/Sao_Paulo"><label class="apollo-label">Fuso horário</label></div>'
                + '<div class="input-group"><input type="text" class="apollo-input" placeholder=" " value="23:00 → 07:00"><label class="apollo-label">Horário padrão (Rio)</label></div>'
                + '<div class="input-group"><input type="number" class="apollo-input" placeholder=" " value="900"><label class="apollo-label">Capacidade padrão</label></div>'));
      }
    },

    /* ══════════ apollo-adverts (wp-adverts complete) ══════════ */
    adverts: {
      all: function (p, s) {
        var canAct = R.can('moderate');
        return head(p, s, '<button class="btn btn-primary btn-sm"><i class="ri-add-line"></i>Novo anúncio</button>')
          + X.card(X.hd('Anúncios', D.adverts.length) + D.adverts.map(function(a){return X.advertRow(a, canAct);}).join(''));
      },
      moderation: function (p, s) {
        var canAct = R.can('moderate');
        var pend = D.adverts.filter(function(a){return a.status!=='published';});
        return head(p, s)
          + X.card(X.hd('Aguardando moderação', pend.length) + (pend.length?pend.map(function(a){return X.advertRow(a, canAct);}).join(''):'<div class="adm-empty"><i class="ri-check-double-line"></i>Nada pendente</div>'));
      },
      categories: function (p, s) {
        return head(p, s, '<button class="btn btn-secondary btn-sm"><i class="ri-add-line"></i>Nova categoria</button>')
          + X.card(X.hd('Categorias', D.advertCategories.length) + D.advertCategories.map(X.taxRow).join(''));
      },
      fields: function (p, s) {
        return head(p, s, '<button class="btn btn-secondary btn-sm"><i class="ri-add-line"></i>Novo campo</button>')
          + X.card(X.hd('Campos personalizados & filtros', D.advertFields.length)
            + '<p class="txt-secondary text-sm" style="margin-bottom:12px">Cada campo pode virar filtro da busca (estilo wp-adverts).</p>'
            + D.advertFields.map(X.fieldRow).join(''));
      },
      plans: function (p, s) {
        return head(p, s, '<button class="btn btn-accent btn-sm"><i class="ri-add-line"></i>Novo plano</button>')
          + '<p class="tref-sec-desc">Publicação paga em <b>utils</b> — nunca em dinheiro.</p>'
          + '<div class="mg-grid">'+D.advertPlans.map(X.planCard).join('')+'</div>';
      },
      settings: function (p, s) {
        return head(p, s)
          + grid(
              X.card(X.hd('Regras') + D.advertSettings.map(X.settingToggle).join(''))
            + X.card(X.hd('Limites')
                + '<div class="input-group"><input type="number" class="apollo-input" placeholder=" " value="8"><label class="apollo-label">Máx. de imagens</label></div>'
                + '<div class="input-group"><input type="number" class="apollo-input" placeholder=" " value="30"><label class="apollo-label">Dias no ar (padrão)</label></div>'
                + '<div class="input-group"><input type="number" class="apollo-input" placeholder=" " value="500"><label class="apollo-label">Teto de repasse (utils acima do valor)</label></div>'));
      }
    },

    /* ══════════ apollo-moderation ══════════ */
    moderation: {
      queue: function (p, s) {
        var canAct = R.can('moderate');
        return head(p, s)
          + X.card(X.hd('Itens na fila', D.queue.length) + (D.queue.length?D.queue.map(function(q){return X.queueRow(q, canAct);}).join(''):'<div class="adm-empty"><i class="ri-check-double-line"></i>Fila vazia</div>'));
      },
      reports: function (p, s) {
        var canAct = R.can('moderate');
        return head(p, s) + X.card(X.hd('Denúncias por gravidade')
          + D.queue.slice().sort(function(a,b){var o={high:0,medium:1,low:2};return o[a.severity]-o[b.severity];}).map(function(q){return X.queueRow(q, canAct);}).join(''));
      },
      rules: function (p, s) {
        return head(p, s) + X.card(X.hd('<i class="ri-robot-2-line"></i> Automação')
          + [{label:'Auto-suspender após 3 denúncias',on:true},{label:'Ocultar automaticamente conteúdo sinalizado 5×',on:true},{label:'Fila de revisão para novos usuários',on:false},{label:'Notificar admin em denúncias graves',on:true}].map(X.settingToggle).join(''));
      },
      log: function (p, s) {
        return head(p, s) + X.card(X.hd('Ações de moderação', D.audit.filter(function(a){return a.kind==='moderation';}).length)
          + D.audit.filter(function(a){return a.kind==='moderation';}).map(X.auditRow).join(''));
      }
    },

    /* ══════════ ApolloDJ.exe (software) ══════════ */
    apollodj: {
      central: function (p, s) {
        return head(p, s)
          + '<div class="alert alert-accent" style="margin-bottom:16px"><i class="ri-disc-line"></i><div><div class="alert-title">APOLLODJ.EXE · Controle de acesso</div>Selecione uma filiação para gerir usuários, permissões e delegação.</div></div>'
          + X.card(X.hd('Central de filiação', D.dj.memberships.length) + '<div class="dj-mem-list">'+D.dj.memberships.map(X.djMembership).join('')+'</div>');
      },
      include: function (p, s) {
        var users = D.dj.members.map(function(m){return '<option>'+X.esc(m.name)+'</option>';}).join('');
        return head(p, s)
          + X.card(X.hd('Incluir membros')
            + '<div class="mg-picker"><select class="apollo-select"><option selected disabled>Escolher usuário</option>'+users+'<option>@dj_apollo</option><option>@guest_001</option></select>'
            + '<select class="apollo-select"><option>Trusted Contributor</option></select>'
            + '<button class="btn btn-primary"><i class="ri-user-add-line"></i>Adicionar</button></div>')
          + X.card(X.hd('Membros na filiação', D.dj.members.length) + D.dj.members.map(function(m){
              return '<div class="adm-row"><span class="adm-ico"><i class="ri-user-line"></i></span><div class="adm-row-main"><div class="adm-row-name">'+X.esc(m.name)+'</div><div class="adm-row-sub">'+X.esc(m.role)+'</div></div><button class="btn btn-ghost btn-sm"><i class="ri-user-unfollow-line"></i></button></div>';
            }).join(''));
      },
      delegation: function (p, s) {
        return head(p, s)
          + X.card(X.hd('<i class="ri-share-forward-line"></i> Poder de delegação')
            + '<p class="txt-secondary text-sm" style="margin-bottom:12px">Permita que usuários concedam/revoguem esta filiação.</p>'
            + D.dj.delegation.map(function(d){return X.settingToggle({label:d.action, on:d.on});}).join(''));
      },
      capabilities: function (p, s) {
        return head(p, s)
          + X.card(X.hd('Capacidades do ApolloDJ.exe')
            + '<div class="table-wrap"><table class="adm-matrix"><thead><tr><th>Capacidade</th><th>Ver</th><th>Usar</th><th>Editar</th><th>Admin</th></tr></thead>'
            + '<tbody>'+D.dj.caps.map(X.djCapRow).join('')+'</tbody></table></div>');
      }
    },

    /* ══════════ apollo-groups (Núcleo vs Comuna) ══════════ */
    groups: {
      nucleos: function (p, s) {
        var nuc = D.groups.filter(function (g) { return g.type === 'nucleo'; });
        return head(p, s, '<button class="btn btn-primary btn-sm"><i class="ri-add-line"></i>Novo núcleo</button>')
          + '<div class="alert alert-accent" style="margin-bottom:16px"><i class="ri-shield-star-line"></i><div><div class="alert-title">Grupos de trabalho — só o administrador cria</div>Cada núcleo recebe um <b>admin de grupo</b> definido por você; ele gere os membros internamente.</div></div>'
          + X.card(X.hd('Núcleos', nuc.length) + nuc.map(function (g) { return X.groupRow(g, false); }).join(''));
      },
      comunas: function (p, s) {
        var com = D.groups.filter(function (g) { return g.type === 'comuna'; });
        var canAct = R.can('moderate');
        return head(p, s)
          + '<div class="alert alert-success" style="margin-bottom:16px"><i class="ri-group-2-line"></i><div><div class="alert-title">Grupos públicos da comunidade</div>Qualquer usuário cria uma comuna; ela entra no ar após <b>aprovação da moderação</b>.</div></div>'
          + X.card(X.hd('Comunas', com.length) + com.map(function (g) { return X.groupRow(g, canAct); }).join(''));
      },
      approvals: function (p, s) {
        var canAct = R.can('moderate');
        var pend = D.groups.filter(function (g) { return g.status === 'pending'; });
        return head(p, s)
          + X.card(X.hd('Comunas aguardando aprovação', pend.length) + (pend.length ? pend.map(function (g) { return X.groupRow(g, canAct); }).join('') : '<div class="adm-empty"><i class="ri-check-double-line"></i>Nada pendente</div>'));
      },
      members: function (p, s) {
        return head(p, s) + X.card(X.hd('Membros por grupo')
          + D.groups.map(function (g) { return '<div class="adm-row"><span class="adm-ico"><i class="'+(g.type==='nucleo'?'ri-shield-star-line':'ri-group-2-line')+'"></i></span><div class="adm-row-main"><div class="adm-row-name">'+X.esc(g.name)+'</div><div class="adm-row-sub">admin: '+X.esc(g.admin)+' · '+g.members+' membros</div></div><button class="btn btn-ghost btn-sm"><i class="ri-user-add-line"></i></button></div>'; }).join(''));
      }
    },

    /* ══════════ apollo-coauthor ══════════ */
    coauthor: {
      byevent: function (p, s) {
        return head(p, s)
          + '<div class="alert alert-accent" style="margin-bottom:16px"><i class="ri-team-line"></i><div><div class="alert-title">Coautoria em todas as telas</div>Vincule coautores a eventos — eles editam o evento e aparecem no line-up e nas listagens.</div></div>'
          + X.card(X.hd('Eventos & coautores', D.events.length) + D.events.map(function (e) { return X.coauthorRow(e.title, (D.eventCoauthors||{})[e.id]); }).join(''));
      },
      invites: function (p, s) {
        return head(p, s, '<button class="btn btn-secondary btn-sm"><i class="ri-mail-send-line"></i>Convidar</button>')
          + X.card(X.hd('Convites enviados', D.coauthorInvites.length) + D.coauthorInvites.map(X.inviteRow).join(''));
      },
      requests: function (p, s) {
        var canAct = R.can('moderate');
        return head(p, s)
          + X.card(X.hd('Solicitações de coautoria', D.coauthorRequests.length) + (D.coauthorRequests.length ? D.coauthorRequests.map(function (r) { return X.requestRow(r, canAct); }).join('') : '<div class="adm-empty"><i class="ri-check-double-line"></i>Nada pendente</div>'));
      },
      permissions: function (p, s) {
        return head(p, s) + X.card(X.hd('<i class="ri-key-2-line"></i> O que um coautor pode fazer')
          + [{label:'Editar dados do evento',on:true},{label:'Gerir line-up / DJs',on:true},{label:'Publicar / despublicar',on:false},{label:'Gerir anúncios do evento',on:false},{label:'Convidar outros coautores',on:false}].map(X.settingToggle).join(''));
      }
    },

    /* ══════════ apollo-hub (linktree by apollo) ══════════ */
    hub: {
      links: function (p, s) {
        return head(p, s, '<button class="btn btn-primary btn-sm"><i class="ri-add-line"></i>Novo link</button>')
          + '<div class="alert alert-accent" style="margin-bottom:16px"><i class="ri-links-line"></i><div><div class="alert-title">Hub · linktree by apollo</div>Uma página de links única para o evento/artista. Arraste para ordenar, ligue/desligue por link.</div></div>'
          + X.card(X.hd('Meus links', D.hubLinks.length) + D.hubLinks.map(X.hubLinkRow).join(''));
      },
      appearance: function (p, s) {
        return head(p, s) + grid(
          X.card(X.hd('Tema do Hub')
            + '<div class="input-group"><input type="text" class="apollo-input" placeholder=" " value="apollo.rio/valle"><label class="apollo-label">Slug público</label></div>'
            + '<div class="flex-row" style="gap:6px;margin-top:8px"><span class="tag tag-primary">Claro</span><span class="tag">Escuro</span><span class="tag">Vidro</span></div>')
          + X.card(X.hd('Prévia') + '<div class="adm-empty"><i class="ri-smartphone-line"></i>Prévia do linktree</div>'));
      },
      clicks: function (p, s) {
        var kpis = [{icon:'ri-cursor-line',value:'14.9k',label:'Cliques (30d)',trend:11,roles:['admin','mod']},{icon:'ri-eye-line',value:'42.1k',label:'Visitas',trend:7,roles:['admin','mod']},{icon:'ri-links-line',value:'4',label:'Links ativos',trend:0,roles:['admin','mod']}];
        return head(p, s) + '<div class="adm-stat-grid section">'+kpis.map(X.statCard).join('')+'</div>'
          + X.card(X.hd('Links por desempenho') + D.hubLinks.map(X.hubLinkRow).join(''));
      },
      domain: function (p, s) {
        return head(p, s) + X.card(X.hd('Domínio personalizado')
          + '<div class="input-group"><input type="text" class="apollo-input" placeholder=" " value="links.apollo.rio"><label class="apollo-label">Domínio</label></div>'
          + '<div class="alert alert-success" style="margin-top:12px"><i class="ri-checkbox-circle-line"></i><div><div class="alert-title">DNS verificado</div>Certificado ativo.</div></div>');
      }
    },

    /* ══════════ apollo-core (system, admin-only) ══════════ */
    system: {
      roles: function (p, s) {
        return head(p, s)
          + grid(
              X.card(X.hd('<i class="ri-shield-user-line"></i> A moderação pode conceder') + '<p class="txt-secondary text-sm" style="margin-bottom:10px">Espelha a aba Delegação de apollo-membership.</p>' + D.memberships.map(X.permRow).join(''))
            + X.card(X.hd('<i class="ri-key-2-line"></i> Capacidades por função')
                + '<div class="perm-row"><div class="perm-label"><i class="ri-shield-star-line"></i> Administrador</div><span class="chip ok">acesso total</span></div>'
                + '<div class="perm-row"><div class="perm-label"><i class="ri-shield-user-line"></i> Moderação</div><span class="chip">moderar · conceder liberados</span></div>'));
      },
      audit: function (p, s) {
        return head(p, s) + X.card(X.hd('Ações recentes', D.audit.length) + D.audit.map(X.auditRow).join(''));
      },
      appearance: function (p, s) {
        return head(p, s) + X.card(X.hd('Tema')
          + '<div class="user-panel-theme btn-row" role="group" style="display:flex;gap:8px"><button type="button" class="btn btn-secondary btn-sm" onclick="document.documentElement.classList.remove(\'dark-mode\')"><i class="ri-sun-line"></i> Claro</button><button type="button" class="btn btn-secondary btn-sm" onclick="document.documentElement.classList.add(\'dark-mode\')"><i class="ri-moon-line"></i> Escuro</button></div>'
          + '<p class="txt-secondary text-sm" style="margin-top:14px">Tokens vêm do core.js — este painel só alterna <span class="txt-mono">html.dark-mode</span>.</p>');
      },
      general: function (p, s) {
        return head(p, s) + grid(
          X.card(X.hd('Marca')
            + '<div class="input-group"><input type="text" class="apollo-input" placeholder=" " value="apollo::rio"><label class="apollo-label">Nome da comunidade</label></div>'
            + '<div class="input-group"><input type="text" class="apollo-input" placeholder=" " value="utils"><label class="apollo-label">Unidade de crédito</label></div>')
          + X.card(X.hd('Sistema') + [{label:'Modo manutenção',on:false},{label:'Registro de auditoria',on:true},{label:'API REST pública',on:true}].map(X.settingToggle).join('')));
      }
    }
  };

  w.AdminViews = V;
})(window);
