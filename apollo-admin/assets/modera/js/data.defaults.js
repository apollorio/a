/*
   ═══════════════════════════════════════════════════════════════════════════
   APOLLO::RIO · ADMIN + MODERATION · simulated.data.admin.js
   ───────────────────────────────────────────────────────────────────────────
   ⚠ SIMULATION ONLY. No money exists on the platform — every value is in UTILS
   (utilitário / crédito interno), never currency. In production swap this file
   for wp_localize_script('apollo-admin','APOLLO_ADMIN',[…]) keeping the keys.

   Load order: core.js → simulated.data.admin.js → shell.admin.js → roles.js
             → render.js → views.js → app.admin.js
   ═══════════════════════════════════════════════════════════════════════════
*/
(function (w) {
  'use strict';

  var CURRENT_USER = { id: 'u-001', name: 'Rafael Valle', initials: 'RV', handle: '@valle', role: 'admin', title: 'Núcleo Apollo' };

  var ROLES = {
    admin: { label: 'Administrador', caps: ['manage_users','manage_roles','manage_plans','grant_any','moderate','view_audit','system','configure','grant_membership'] },
    mod:   { label: 'Moderação',     caps: ['moderate','grant_membership'] }
  };

  /* ═══ FATHER TABS = APOLLO PLUGINS. Each plugin owns SUB-TABS (right rail). ═══
     roles[] gates both plugins and their subs. group orders the aside. */
  var PLUGINS = [
    { id: 'membership', name: 'apollo-membership', label: 'Filiações', icon: 'ri-vip-crown-2-line', roles: ['admin','mod'], group: 'Plugins Apollo', subs: [
        { id: 'grant',      label: 'Conceder filiação',  icon: 'ri-user-add-line',    roles: ['admin','mod'] },
        { id: 'plans',      label: 'Planos de filiação', icon: 'ri-price-tag-3-line', roles: ['admin'] },
        { id: 'members',    label: 'Filiados',           icon: 'ri-group-line',       roles: ['admin','mod'] },
        { id: 'delegation', label: 'Delegação (mod)',    icon: 'ri-shield-user-line', roles: ['admin'] }
    ]},
    { id: 'groups', name: 'apollo-groups', label: 'Grupos', icon: 'ri-community-line', roles: ['admin','mod'], group: 'Plugins Apollo', subs: [
        { id: 'nucleos',   label: 'Núcleos',    icon: 'ri-shield-star-line',     roles: ['admin'] },          /* só admin cria + define admin do grupo */
        { id: 'comunas',   label: 'Comunas',    icon: 'ri-group-2-line',         roles: ['admin','mod'] },     /* público — usuário cria, mod aprova */
        { id: 'approvals', label: 'Aprovações', icon: 'ri-checkbox-circle-line', roles: ['admin','mod'] },
        { id: 'members',   label: 'Membros',    icon: 'ri-group-line',           roles: ['admin'] }
    ]},
    { id: 'events', name: 'apollo-events', label: 'Eventos', icon: 'ri-calendar-event-line', roles: ['admin','mod'], group: 'Plugins Apollo', subs: [
        { id: 'all',        label: 'Todos os eventos',      icon: 'ri-list-check-2',        roles: ['admin','mod'] },
        { id: 'queue',      label: 'Fila de aprovação',     icon: 'ri-inbox-unarchive-line', roles: ['admin','mod'] },
        { id: 'taxonomies', label: 'Categorias & tipos',    icon: 'ri-price-tag-3-line',    roles: ['admin'] },
        { id: 'venues',     label: 'Locais',                icon: 'ri-map-pin-line',        roles: ['admin'] },
        { id: 'lineup',     label: 'Line-up / DJs',         icon: 'ri-disc-line',           roles: ['admin','mod'] },
        { id: 'fields',     label: 'Campos personalizados', icon: 'ri-input-cursor-move',   roles: ['admin'] },
        { id: 'settings',   label: 'Configurações',         icon: 'ri-settings-4-line',     roles: ['admin'] }
    ]},
    { id: 'coauthor', name: 'apollo-coauthor', label: 'Coautoria', icon: 'ri-team-line', roles: ['admin','mod'], group: 'Plugins Apollo', subs: [
        { id: 'byevent',     label: 'Coautores por evento', icon: 'ri-user-shared-line', roles: ['admin','mod'] },
        { id: 'invites',     label: 'Convites',             icon: 'ri-mail-send-line',   roles: ['admin','mod'] },
        { id: 'requests',    label: 'Solicitações',         icon: 'ri-user-add-line',    roles: ['admin','mod'] },
        { id: 'permissions', label: 'Permissões',           icon: 'ri-key-2-line',       roles: ['admin'] }
    ]},
    { id: 'adverts', name: 'apollo-adverts', label: 'Anúncios', icon: 'ri-megaphone-line', roles: ['admin','mod'], group: 'Plugins Apollo', subs: [
        { id: 'all',        label: 'Todos os anúncios',     icon: 'ri-list-check-2',      roles: ['admin','mod'] },
        { id: 'moderation', label: 'Moderação',             icon: 'ri-flag-2-line',       roles: ['admin','mod'] },
        { id: 'categories', label: 'Categorias',            icon: 'ri-price-tag-3-line',  roles: ['admin'] },
        { id: 'fields',     label: 'Campos personalizados', icon: 'ri-input-cursor-move', roles: ['admin'] },
        { id: 'plans',      label: 'Planos de publicação',  icon: 'ri-coupon-3-line',     roles: ['admin'] },
        { id: 'settings',   label: 'Configurações',         icon: 'ri-settings-4-line',   roles: ['admin'] }
    ]},
    { id: 'hub', name: 'apollo-hub', label: 'Hub', icon: 'ri-links-line', roles: ['admin','mod'], group: 'Plugins Apollo', subs: [
        { id: 'links',      label: 'Meus links',  icon: 'ri-links-line',       roles: ['admin','mod'] },   /* linktree by apollo */
        { id: 'appearance', label: 'Aparência',   icon: 'ri-palette-line',     roles: ['admin'] },
        { id: 'clicks',     label: 'Cliques',     icon: 'ri-cursor-line',      roles: ['admin','mod'] },
        { id: 'domain',     label: 'Domínio',     icon: 'ri-global-line',      roles: ['admin'] }
    ]},
    { id: 'moderation', name: 'apollo-moderation', label: 'Moderação', icon: 'ri-shield-check-line', roles: ['admin','mod'], group: 'Plugins Apollo', subs: [
        { id: 'queue',   label: 'Fila',              icon: 'ri-inbox-line',   roles: ['admin','mod'] },
        { id: 'reports', label: 'Denúncias',         icon: 'ri-flag-2-line',  roles: ['admin','mod'] },
        { id: 'rules',   label: 'Regras & automação', icon: 'ri-robot-2-line', roles: ['admin'] },
        { id: 'log',     label: 'Registro',          icon: 'ri-history-line', roles: ['admin'] }
    ]},
    /* ── SOFTWARE (not a plugin, but lives here) ── */
    { id: 'apollodj', name: 'ApolloDJ.exe', label: 'ApolloDJ.exe', icon: 'ri-disc-line', roles: ['admin','mod'], group: 'Software', software: true, subs: [
        { id: 'central',      label: 'Central de filiação', icon: 'ri-vip-crown-2-line', roles: ['admin','mod'] },
        { id: 'include',      label: 'Incluir membros',     icon: 'ri-user-add-line',    roles: ['admin','mod'] },
        { id: 'delegation',   label: 'Delegação',           icon: 'ri-share-forward-line', roles: ['admin'] },
        { id: 'capabilities', label: 'Capacidades',         icon: 'ri-key-2-line',       roles: ['admin'] }
    ]},
    /* ── SYSTEM (admin-exclusive) ── */
    { id: 'system', name: 'apollo-core', label: 'Sistema', icon: 'ri-settings-4-line', roles: ['admin'], group: 'Sistema', subs: [
        { id: 'roles',      label: 'Funções & permissões', icon: 'ri-shield-user-line', roles: ['admin'] },
        { id: 'audit',      label: 'Auditoria',            icon: 'ri-history-line',     roles: ['admin'] },
        { id: 'appearance', label: 'Aparência',            icon: 'ri-palette-line',     roles: ['admin'] },
        { id: 'general',    label: 'Geral',                icon: 'ri-tools-line',       roles: ['admin'] }
    ]}
  ];

  /* ═══ MEMBERSHIP TIERS — price is UTILS or invite/free, never money ═══ */
  var MEMBERSHIPS = [
    { id: 'free',   name: 'Free',   price: 'Grátis',        util: 0,   color: '#9aa0a6', grantableByMod: false, perks: ['Acesso à comunidade','Feed social'] },
    { id: 'plus',   name: 'Apollo+', price: '120 utils/mês', util: 120, color: '#EDAC41', grantableByMod: true,  perks: ['Ingressos antecipados','Sem anúncios','Selo +'] },
    { id: 'vip',    name: 'VIP',    price: '340 utils/mês', util: 340, color: '#d1860a', grantableByMod: true,  perks: ['Pista premium','Meet & greet','Cortesias'] },
    { id: 'nucleo', name: 'Núcleo', price: 'Convite',       util: 0,   color: '#0f0f11', grantableByMod: false, perks: ['Coautoria de eventos','Painel de gestor'] },
    { id: 'staff',  name: 'Staff',  price: 'Operacional',   util: 0,   color: '#0066CC', grantableByMod: false, perks: ['Acesso operacional','Credencial'] }
  ];

  /* ═══ KPIs — no revenue; "Utils em circulação" instead ═══ */
  var KPIS = [
    { icon: 'ri-group-line',          value: '24.5k', label: 'Membros totais',     trend: 12,   roles: ['admin','mod'] },
    { icon: 'ri-flag-2-line',         value: '18',    label: 'Denúncias abertas',  trend: -6,   roles: ['admin','mod'], mod: true },
    { icon: 'ri-vip-crown-2-line',    value: '1.284', label: 'Filiados ativos',    trend: 9,    roles: ['admin','mod'] },
    { icon: 'ri-calendar-check-line', value: '482',   label: 'Eventos publicados', trend: 8,    roles: ['admin','mod'] },
    { icon: 'ri-copper-coin-line',    value: '1,9M',  label: 'Utils em circulação', trend: 5,   roles: ['admin'] },
    { icon: 'ri-shield-check-line',   value: '99,3%', label: 'SLA de moderação',   trend: 3,    roles: ['admin','mod'], mod: true }
  ];

  var MEMBERS = [
    { id:'m01', name:'Emily Hoffman',  handle:'@emhoff',  role:'Administrador', membership:'nucleo', status:'active',    joined:'Out 2023', last:'Agora',    avatar:'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100' },
    { id:'m02', name:'Michel Lewin',   handle:'@mlewin',  role:'Editor',        membership:'plus',   status:'active',    joined:'Out 2023', last:'há 2 h',   avatar:'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' },
    { id:'m03', name:'Tessa Dietrich', handle:'@tessa',   role:'Assinante',     membership:'vip',    status:'active',    joined:'Set 2023', last:'Ontem',    avatar:'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100' },
    { id:'m04', name:'Lucas Art',      handle:'@lucas',   role:'Assinante',     membership:'free',   status:'pending',   joined:'Jan 2026', last:'há 5 min', avatar:'https://images.unsplash.com/photo-1599566150163-29194dcaad36?w=100' },
    { id:'m05', name:'Ana Costa',      handle:'@ana',     role:'Assinante',     membership:'plus',   status:'active',    joined:'Dez 2025', last:'há 1 h',   avatar:'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100' },
    { id:'m06', name:'Pedro Beats',    handle:'@pedro',   role:'Assinante',     membership:'free',   status:'suspended', joined:'Nov 2025', last:'há 3 d',   avatar:'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=100' },
    { id:'m07', name:'Julia V.',       handle:'@julia_v', role:'Moderação',     membership:'staff',  status:'active',    joined:'Ago 2023', last:'Agora',    avatar:'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100' },
    { id:'m08', name:'Marcos Silva',   handle:'@marcos',  role:'Assinante',     membership:'vip',    status:'active',    joined:'Fev 2025', last:'há 20 min', avatar:'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' },
    { id:'m09', name:'Bianca Rocha',   handle:'@bia',     role:'Assinante',     membership:'free',   status:'active',    joined:'Jan 2026', last:'há 4 h',   avatar:'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=100' }
  ];

  var QUEUE = [
    { id:'r01', type:'Comentário', severity:'high',   target:'chat do Warm-Up',            reporter:'@ana',    reason:'Discurso de ódio',        at:'há 4 min',  status:'open' },
    { id:'r02', type:'Evento',     severity:'medium', target:'After Hours · Galpão X',     reporter:'@julia_v', reason:'Local não verificado',    at:'há 22 min', status:'open' },
    { id:'r03', type:'Perfil',     severity:'low',    target:'@pedro',                      reporter:'sistema',  reason:'Spam de mensagens',       at:'há 1 h',    status:'open' },
    { id:'r04', type:'Anúncio',    severity:'high',   target:'Repasse · Sunset (900 utils)', reporter:'@marcos', reason:'Repasse acima do teto de utils', at:'há 2 h', status:'open' },
    { id:'r05', type:'Imagem',     severity:'medium', target:'Galeria · Rara Festival',    reporter:'@bia',     reason:'Conteúdo sensível',       at:'Ontem',     status:'reviewing' }
  ];

  /* ═══ APOLLO-EVENTS (wp-events-manager style, admin-rich) ═══ */
  var EVENTS = [
    { id:'e01', day:'28', month:'Jan', title:'Noite Techno Bunker', venue:'Club Aurora', author:'@julia_v', cat:'Underground', type:'Techno', status:'flagged',  flag:'Capacidade acima do alvará', cap:600 },
    { id:'e02', day:'02', month:'Fev', title:'Startup Summit 2026',  venue:'Grand Hall',  author:'@emhoff',  cat:'Conferência', type:'Talks', status:'pending',  flag:'', cap:1200 },
    { id:'e03', day:'14', month:'Fev', title:'Workshop de Produto',   venue:'On-line',     author:'@mlewin',  cat:'Workshop',    type:'Online', status:'approved', flag:'', cap:80 },
    { id:'e04', day:'24', month:'Fev', title:'Sunset Theory Vol.04',  venue:'Fabrika',     author:'@valle',   cat:'Underground', type:'Techno', status:'pending',  flag:'', cap:900 }
  ];
  var EVENT_CATEGORIES = [
    { id:'underground', name:'Underground', count:128, color:'#0f0f11' },
    { id:'conference',  name:'Conferência', count:34,  color:'#0066CC' },
    { id:'workshop',    name:'Workshop',    count:52,  color:'#EDAC41' },
    { id:'festival',    name:'Festival',    count:19,  color:'#d1860a' }
  ];
  var EVENT_TYPES = ['Techno','House','Talks','Online','Live','Híbrido'];
  var VENUES = [
    { id:'v1', name:'Fabrika',     city:'Rio de Janeiro', cap:900,  verified:true },
    { id:'v2', name:'Club Aurora', city:'Rio de Janeiro', cap:600,  verified:false },
    { id:'v3', name:'Grand Hall',  city:'São Paulo',      cap:1200, verified:true }
  ];
  var EVENT_FIELDS = [
    { id:'f1', label:'Line-up (DJs)',      key:'_event_dj_ids',      type:'relação',  required:true,  show:true },
    { id:'f2', label:'Vídeo (YouTube / .mp4 .webm .mov)', key:'_event_video_url', type:'url', required:false, show:true },
    { id:'f3', label:'Galeria',            key:'_event_gallery',     type:'mídia',    required:false, show:true },
    { id:'f4', label:'Cupom (utils)',      key:'_event_coupon',      type:'texto',    required:false, show:true },
    { id:'f5', label:'Lista Amiga (URL)',  key:'_event_list_url',    type:'url',      required:false, show:false },
    { id:'f6', label:'Privacidade',        key:'_event_privacy',     type:'seleção',  required:true,  show:true }
  ];
  var EVENT_SETTINGS = [
    { id:'es1', label:'Fila de revisão para novos eventos', on:true },
    { id:'es2', label:'Exigir local verificado',            on:false },
    { id:'es3', label:'Permitir coautoria (apollo-coauthor)', on:true },
    { id:'es4', label:'Auto-encerrar após a data',          on:true }
  ];

  /* ═══ APOLLO-ADVERTS (wp-adverts complete, admin-controlled) ═══ */
  var ADVERTS = [
    { id:'a01', title:'Repasse · Sunset Theory', cat:'Repasses',   author:'@marcos', price:'149 utils', status:'published', at:'há 20 min' },
    { id:'a02', title:'Quarto · Santa Teresa',   cat:'Acomodação', author:'@ana',    price:'280 utils', status:'pending',   at:'há 1 h' },
    { id:'a03', title:'Carona · Zona Sul → Fabrika', cat:'Caronas', author:'@bia',   price:'0 utils',   status:'published', at:'há 3 h' },
    { id:'a04', title:'Repasse · Industrial C.',  cat:'Repasses',   author:'@pedro',  price:'900 utils', status:'flagged',   at:'Ontem' }
  ];
  var ADVERT_CATEGORIES = [
    { id:'resell', name:'Repasses',   count:212, fields:['Evento','Setor','Utils'] },
    { id:'accom',  name:'Acomodação', count:88,  fields:['Bairro','Diárias','Utils'] },
    { id:'rides',  name:'Caronas',    count:44,  fields:['Origem','Destino','Vagas'] },
    { id:'gear',   name:'Equipamento', count:31, fields:['Tipo','Estado','Utils'] }
  ];
  var ADVERT_FIELDS = [
    { id:'af1', label:'Preço (utils)',   key:'_ap_util',      type:'número',  required:true,  filter:true },
    { id:'af2', label:'Categoria',       key:'_ap_cat',       type:'seleção', required:true,  filter:true },
    { id:'af3', label:'Localização',     key:'_ap_region',    type:'seleção', required:false, filter:true },
    { id:'af4', label:'Galeria',         key:'_ap_gallery',   type:'mídia',   required:false, filter:false },
    { id:'af5', label:'Negociável',      key:'_ap_negotiable', type:'boolean', required:false, filter:true }
  ];
  var ADVERT_PLANS = [
    { id:'p1', name:'Básico',   util:0,   days:7,  featured:false },
    { id:'p2', name:'Destaque', util:60,  days:15, featured:true },
    { id:'p3', name:'Topo',     util:150, days:30, featured:true }
  ];
  var ADVERT_SETTINGS = [
    { id:'as1', label:'Moderar anúncios antes de publicar', on:true },
    { id:'as2', label:'Teto de repasse (utils)',            on:true, note:'máx 500 utils acima do valor original' },
    { id:'as3', label:'Permitir anúncios gratuitos',        on:true },
    { id:'as4', label:'Exigir foto',                        on:false }
  ];

  /* ═══ APOLLODJ.EXE (from section of apolloDJ.html) ═══ */
  var DJ_MEMBERSHIPS = [
    { id:'trusted', name:'Trusted Contributor', meta:'Moderação da comunidade + delegação limitada', active:true },
    { id:'fmod',    name:'Forum Moderator',     meta:'Gerencia denúncias e filas de moderação' },
    { id:'editor',  name:'Studio Editor',       meta:'Edita recursos de DJ e módulos de exportação' },
    { id:'gadmin',  name:'Global Admin',        meta:'Acesso total ao ApolloDJ.exe' }
  ];
  var DJ_MEMBERS = [
    { id:'d1', name:'@user_beta',  role:'Trusted Contributor' },
    { id:'d2', name:'@audio_eng',  role:'Trusted Contributor' }
  ];
  var DJ_DELEGATION = [
    { id:'dl1', action:'Adicionar filiação a usuários',   on:true },
    { id:'dl2', action:'Remover filiação de usuários',    on:false },
    { id:'dl3', action:'Editar permissões de capacidade', on:false }
  ];
  var DJ_CAPS = [
    { id:'c1', name:'Analisador',  view:true,  use:true,  edit:false, admin:false },
    { id:'c2', name:'collab.share', view:true, use:true,  edit:true,  admin:false },
    { id:'c3', name:'ExportGlobal', view:true, use:false, edit:false, admin:false },
    { id:'c4', name:'autoHotCUE',   view:true, use:true,  edit:false, admin:false },
    { id:'c5', name:'stemSplit',    view:true, use:false, edit:false, admin:false }
  ];

  /* ═══ APOLLO-GROUPS — Núcleo (admin-only, has group admin) vs Comuna (public) ═══ */
  var GROUPS = [
    { id:'g1', name:'Núcleo Produção',     type:'nucleo', admin:'@julia_v', members:8,  status:'active',  by:'@valle', desc:'Preparação de line-up e operação' },
    { id:'g2', name:'Núcleo Comunicação',  type:'nucleo', admin:'@emhoff',  members:5,  status:'active',  by:'@valle', desc:'Marketing, imprensa e social' },
    { id:'g3', name:'Comuna Techno RJ',    type:'comuna', admin:'@marcos',  members:214, status:'active',  by:'@marcos', desc:'Comunidade pública de techno' },
    { id:'g4', name:'Comuna Vinyl Lovers', type:'comuna', admin:'@bia',     members:0,   status:'pending', by:'@bia',    desc:'Aguardando aprovação da moderação' },
    { id:'g5', name:'Comuna Afterhours',   type:'comuna', admin:'@ana',     members:0,   status:'pending', by:'@ana',    desc:'Aguardando aprovação da moderação' }
  ];

  /* ═══ APOLLO-COAUTHOR — coautoria por evento + convites/solicitações ═══ */
  var EVENT_COAUTHORS = {
    e01: ['@julia_v'], e02: ['@mlewin','@ana'], e03: [], e04: ['@julia_v','@marcos']
  };
  var COAUTHOR_INVITES = [
    { id:'ci1', event:'Sunset Theory Vol.04', invitee:'@marcos', by:'@valle',   status:'accepted' },
    { id:'ci2', event:'Startup Summit 2026',  invitee:'@ana',    by:'@emhoff',  status:'pending' },
    { id:'ci3', event:'Noite Techno Bunker',  invitee:'@pedro',  by:'@julia_v', status:'pending' }
  ];
  var COAUTHOR_REQUESTS = [
    { id:'cr1', user:'@bia',   event:'Rara Festival', role:'Coautor', status:'pending' },
    { id:'cr2', user:'@lucas', event:'O/NDA',         role:'Editor',  status:'pending' }
  ];

  /* ═══ APOLLO-HUB — linktree by apollo ═══ */
  var HUB_LINKS = [
    { id:'h1', label:'Ingressos oficiais', url:'apollo.rio/ingressos', clicks:'4.2k', on:true },
    { id:'h2', label:'Warm-Up (chat)',     url:'apollo.rio/warmup',    clicks:'1.1k', on:true },
    { id:'h3', label:'Instagram',          url:'instagram.com/apollo', clicks:'8.7k', on:true },
    { id:'h4', label:'SoundCloud',         url:'soundcloud.com/apollo', clicks:'930',  on:false }
  ];

  var AUDIT = [
    { at:'10:42', actor:'@valle',   action:'Concedeu filiação VIP',        target:'@marcos',        kind:'membership' },
    { at:'10:31', actor:'@julia_v', action:'Removeu comentário',           target:'r01',            kind:'moderation' },
    { at:'09:58', actor:'@valle',   action:'Liberou plano p/ moderação',   target:'Apollo+',        kind:'roles' },
    { at:'09:12', actor:'sistema',  action:'Suspensão automática',         target:'@pedro',         kind:'moderation' },
    { at:'Ontem', actor:'@emhoff',  action:'Publicou evento',              target:'e02',            kind:'events' }
  ];

  var NOTIFS = [
    { icon:'ri-flag-2-line',      text:'<strong>4 novas denúncias</strong> aguardam revisão', time:'há 2 min',  unread:true },
    { icon:'ri-vip-crown-2-line', text:'<strong>@marcos</strong> recebeu filiação VIP',        time:'há 20 min', unread:true },
    { icon:'ri-megaphone-line',   text:'<strong>2 anúncios</strong> pendentes de moderação',   time:'há 1 h',    unread:false },
    { icon:'ri-shield-check-line', text:'Revisão de segurança concluída',                       time:'Ontem',     unread:false }
  ];
  var MSGS = [
    { initials:'JV', text:'<strong>Julia V.</strong> · Aprovei o Startup Summit', time:'Agora',  unread:true },
    { initials:'EM', text:'<strong>Emily Hoffman</strong> · Rever o plano VIP em utils', time:'há 2 h', unread:false }
  ];

  w.APOLLO_ADMIN = {
    currentUser: CURRENT_USER, roles: ROLES, plugins: PLUGINS,
    memberships: MEMBERSHIPS, kpis: KPIS, members: MEMBERS, queue: QUEUE,
    events: EVENTS, eventCategories: EVENT_CATEGORIES, eventTypes: EVENT_TYPES,
    venues: VENUES, eventFields: EVENT_FIELDS, eventSettings: EVENT_SETTINGS,
    adverts: ADVERTS, advertCategories: ADVERT_CATEGORIES, advertFields: ADVERT_FIELDS,
    advertPlans: ADVERT_PLANS, advertSettings: ADVERT_SETTINGS,
    dj: { memberships: DJ_MEMBERSHIPS, members: DJ_MEMBERS, delegation: DJ_DELEGATION, caps: DJ_CAPS },
    groups: GROUPS, eventCoauthors: EVENT_COAUTHORS, coauthorInvites: COAUTHOR_INVITES, coauthorRequests: COAUTHOR_REQUESTS,
    hubLinks: HUB_LINKS,
    audit: AUDIT, notifs: NOTIFS, msgs: MSGS,
    counts: function () {
      return {
        membership: MEMBERS.filter(function (m) { return m.status === 'pending'; }).length,
        groups: GROUPS.filter(function (g) { return g.status === 'pending'; }).length,
        events: EVENTS.filter(function (e) { return e.status !== 'approved'; }).length,
        coauthor: COAUTHOR_REQUESTS.filter(function (r) { return r.status === 'pending'; }).length,
        adverts: ADVERTS.filter(function (a) { return a.status !== 'published'; }).length,
        moderation: QUEUE.filter(function (r) { return r.status !== 'resolved'; }).length
      };
    }
  };
})(window);
