# Apollo Statistics v2

**Ultra Modular Pro Analytics Engine** para o ecossistema Apollo — 15 classes MetricGroup base, 68 instâncias plug-and-play cobrindo todos os 27 plugins, tracker.js PostHog-inspired, amCharts 5, integração com gamificação.

---

## Arquitetura

### 15 Classes MetricGroup → 68 Instâncias

| Classe | Chart | Instâncias |
|--------|-------|------------|
| `ViewCounter` | LINE | 6 instâncias (event, hub, classified, journal, dashboard, pageviews) |
| `Ranking` | TABLE+BAR | 5 instâncias |
| `Distribution` | DONUT | 16 instâncias |
| `Funnel` | FUNNEL | 5 instâncias |
| `TimeSeries` | LINE | 14 instâncias |
| `Session` | LINE+NUMBER | 2 instâncias |
| `ClickTrack` | TABLE+BAR | 2 instâncias |
| `EngagementScore` | NUMBER+BAR | 3 instâncias |
| `Growth` | LINE | 2 instâncias |
| `Lifecycle` | FUNNEL+LINE | 3 instâncias |
| `Comparison` | BAR | 4 instâncias |
| `Leaderboard` | TABLE | 4 instâncias |
| `Profile` | DASHBOARD | 1 instância |
| `Radio` | LINE+BAR | 4 instâncias |
| `Skeleton` | PLACEHOLDER | 3 instâncias |

### 7 Tabelas DB

| Tabela | Propósito |
|--------|-----------|
| `apollo_stats_events` | Eventos de conteúdo (legacy) |
| `apollo_stats_users` | Ações de usuário (legacy) |
| `apollo_stats_content` | Métricas por CPT (legacy) |
| `apollo_stats_sessions` | Sessões de usuário (v2) |
| `apollo_stats_pageviews` | Pageviews com scroll depth (v2) |
| `apollo_stats_clicks` | Clicks e navegação (v2) |
| `apollo_stats_radio` | Sessões de escuta do Radio Apollo (v2) |

---

## Estrutura de Arquivos

```
apollo-statistics/
├── apollo-statistics.php          # Constants, PSR-4 autoloader, 7 tabelas
├── src/
│   ├── Plugin.php                 # Singleton bootstrap
│   ├── Core/
│   │   ├── MetricGroup.php        # Abstract base class
│   │   ├── MetricRegistry.php     # Singleton registry (68 métricas)
│   │   ├── MetricBootstrap.php    # Registra todas as 68 instâncias
│   │   ├── MetricConnector.php    # Configuração admin por CPT
│   │   └── DataAggregator.php     # SQL compartilhado (sum/count/top/funnel)
│   ├── Collectors/
│   │   ├── HookCollector.php      # 40+ hooks → stats tables
│   │   ├── SessionCollector.php   # Processa heartbeats do tracker.js
│   │   └── CronCollector.php      # Agregação diária + rotação de dados
│   ├── Metrics/                   # 15 classes base
│   ├── Processors/
│   │   ├── ScoreProcessor.php     # Engagement scores por conteúdo
│   │   └── GamificationBridge.php # Tempo online → pontos de gamificação
│   ├── API/
│   │   ├── StatsController.php    # GET /stats/* (admin, requer auth)
│   │   ├── TrackController.php    # POST /track/* (público, rate-limited)
│   │   └── ProfileController.php  # GET /profile-stats/{id}
│   ├── Admin/
│   │   ├── SettingsSchema.php     # Tab "Statistics" no apollo-admin
│   │   └── DashboardWidgets.php   # Widget WP admin dashboard
│   └── Frontend/
│       ├── ProfileStats.php       # Rota /id/{username}/stats
│       ├── ChartAdapter.php       # MetricGroup data → amCharts JSON
│       └── WidgetRenderer.php     # Renderiza cards HTML+JS
├── assets/
│   ├── js/
│   │   ├── tracker.js             # PostHog-inspired (~536 linhas)
│   │   ├── admin-charts.js        # amCharts 5 (~814 linhas)
│   │   └── profile-stats.js       # Visit graph + period selector
│   └── css/
│       ├── admin-stats.css        # Admin (Apollo design tokens)
│       └── profile-stats.css      # Profile stats (mobile-first)
└── uninstall.php                  # Remove 7 tabelas + options + usermeta
```

---

## REST API

**Namespace:** `apollo/v1`

### Tracker (público)
| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/track/pageview` | Registra pageview |
| POST | `/track/click` | Registra click |
| POST | `/track/session` | Heartbeat / start / end de sessão |
| POST | `/track/event` | Evento customizado (wow, fav, share) |
| POST | `/track/radio` | Sessão de escuta do radio |
| POST | `/track/batch` | Batch de eventos do queue do tracker.js |

### Stats (admin only)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/stats/metrics` | Lista os 68 MetricGroups com schemas |
| GET | `/stats/metric/{slug}` | Computa uma métrica |
| PUT | `/stats/metric/{slug}/toggle` | Habilita/desabilita uma métrica |
| GET | `/stats/dashboard` | Todas as métricas habilitadas para o dashboard |
| GET | `/stats/profile/{user_id}` | Stats de um usuário (respeita visibilidade) |

### Profile (público com controle de visibilidade)
| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/profile-stats/{user_id}` | Stats da página pública |
| PUT | `/profile-stats/visibility` | Atualiza `public|followers|private` |

---

## tracker.js Pipeline

```
[Page Load] → detectPageType() → start/resume session
     ├── IntersectionObserver → scroll milestones 25/50/75/100%
     ├── Visibility API → time on page (só aba ativa)
     ├── click delegate → a[href] → queue click event
     ├── setInterval(30s) → heartbeat → /track/session
     ├── apollo:radio:play → start radio session
     ├── apollo:radio:pause → end radio session
     └── pagehide → sendBeacon → final session data

[Batch Queue] → flush every 5s OR 10 events → POST /track/batch

[Session Rules] → 30min idle = new session
               → 24h max = force new session
               → localStorage persistence
```

**Global:** `window.ApolloTrack`

---

## Gamificação

- `GamificationBridge` escuta `apollo/membership/points_awarded`
- `ScoreProcessor` roda via cron diário → `_apollo_content_score` usermeta
- Fórmula engagement: `views×1 + wows×2 + favs×3 + shares×5`

---

## Retenção de Dados

| Dados | Período |
|-------|---------|
| Sessions | 365 dias |
| Pageviews | 90 dias |
| Clicks | 30 dias |
| Dados agregados | Indefinido |

Configurável via painel `apollo-admin → Statistics → Data Retention`.

---

## Instalação

Ativação automática via `register_activation_hook` cria as 7 tabelas. Remove tudo ao deletar via `uninstall.php`.

**Requer:**
- PHP 8.1+
- WordPress 6.4+
- apollo-core (L0)
