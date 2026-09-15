# Apollo Templates — Investigação Completa

> Gerado em: 25/02/2026

---

## 1. Árvore Completa de Arquivos

```
apollo-templates/
├── apollo-templates.php              # Main plugin file (539 linhas)
├── correct.md                        # Notas de correção pré-existentes
├── create-page.php                   # Script one-time para criar /classificados (RISCO DE SEGURANÇA)
├── NAVBAR_INTEGRATION_COMPLETE.md    # Documentação de integração navbar
├── test-navbar.html                  # HTML de teste
├── test-timezone.php                 # Teste de timezone
├── _debug_test.php                   # Debug test
│
├── src/                              # PSR-4: Apollo\Templates\
│   ├── Plugin.php                    # Proxy → includes/class-plugin.php
│   ├── Activation.php                # Ativação (135 linhas)
│   ├── Deactivation.php             # Desativação (81 linhas)
│   ├── FrontendEditor.php           # Motor de edição frontend (952 linhas)
│   ├── FrontendFields.php           # Renderizador de campos (563 linhas)
│   └── FrontendRouter.php           # Router de edição: /editar/{cpt}/{id}/ (313 linhas)
│
├── includes/
│   ├── constants.php                 # Define APOLLO_TEMPLATES_REST_NAMESPACE
│   ├── functions.php                 # Template engine: apollo_locate_template, apollo_get_template, apollo_get_template_html
│   ├── pages.php                     # Rewrite rules + template_include para /home, /sobre, /classificados, /test
│   ├── mural-router.php             # Redireciona front_page/home → page-mural.php quando logado
│   ├── class-plugin.php             # Plugin singleton completo (REST routes, admin menu, shortcodes init)
│   ├── class-shortcodes.php         # [apollo_events], [apollo_event], [apollo_calendar], [apollo_event_form]
│   ├── class-persistent-ui.php      # PersistentUI: navbar, FAB, painéis (chat, notif, acesso, detail)
│   ├── class-navbar-settings.php    # Admin UI para configurar apps do navbar (456 linhas)
│   ├── weather-helpers.php          # OpenMeteo API para clima do Rio (282 linhas)
│   └── README-WEATHER.md
│
├── templates/
│   ├── page-home.php                # Landing guest / Canvas v2 Panel Engine (1281 linhas)
│   ├── page-mural.php              # Dashboard logado (314 linhas)
│   ├── page-sobre.php              # Página institucional /sobre (332 linhas)
│   ├── page-classificados.php      # Marketplace /classificados (523 linhas)
│   ├── page-mapa.php               # Mapa interativo Leaflet (1189 linhas)
│   ├── page-test.php               # Spreadsheet admin /test (2397 linhas)
│   ├── edit-post.php               # Editor frontend genérico (522 linhas)
│   ├── home.html                    # HTML estático de referência
│   │
│   ├── event/
│   │   ├── card-style-01.php        # Card de evento estilo 01
│   │   └── listing.php             # Grid de listagem de eventos
│   │
│   ├── auth/
│   │   ├── login-register.php       # Login/Register page
│   │   └── parts/
│   │       ├── new_header.php
│   │       ├── new_footer.php
│   │       ├── new_login-form.php
│   │       ├── new_register-form.php
│   │       ├── new_aptitude-quiz.php
│   │       └── new_lockout-overlay.php
│   │
│   └── template-parts/
│       ├── navbar.php               # Navbar v1 original
│       ├── navbar.v1.php            # Navbar v1
│       ├── navbar.v2.php            # Navbar v2 (ativa)
│       ├── navbar-old-backup.php    # Backup antigo
│       │
│       ├── home/                    # Partes do page-sobre.php (antigo home)
│       │   ├── hero.php
│       │   ├── marquee.php
│       │   ├── events-listing.php
│       │   ├── classifieds.php
│       │   ├── hub-section.php
│       │   ├── infra.php
│       │   ├── mission.php
│       │   ├── coupon-modal.php
│       │   ├── tools-accordion.php
│       │   └── footer.php
│       │
│       ├── mural/                   # Partes do page-mural.php
│       │   ├── greeting.php
│       │   ├── weather-hero.php
│       │   ├── upcoming.php
│       │   ├── favorites.php
│       │   ├── same-vibe.php
│       │   ├── classifieds.php
│       │   ├── sounds.php
│       │   ├── ticker.php
│       │   ├── news.php
│       │   └── README-GREETING-SYSTEM.md
│       │
│       └── new-home/                # Canvas v2 components (usados por page-home.php, page-mapa.php)
│           ├── navbar.php
│           ├── menu-fab.php
│           ├── hero.php
│           ├── marquee.php
│           ├── events.php
│           ├── classifieds.php
│           ├── crash.php
│           ├── tracks.php
│           ├── map.php
│           ├── radio.php
│           ├── footer.php
│           ├── panel-acesso.php     # DOWN: login/register (guest)
│           ├── panel-forms.php      # DOWN: criar evento, report (logged)
│           ├── panel-chat.php       # LEFT: chat inbox
│           ├── panel-chat-list.php
│           ├── panel-chat-inbox.php
│           ├── panel-notif.php      # UP: notificações
│           ├── panel-mural.php      # UP: mural mini
│           ├── panel-detail.php     # RIGHT: detalhe de CPT
│           ├── panel-explore.php    # CENTER: explore panel
│           ├── panel-event-page.php # RIGHT: evento page
│           └── panel-dynamic.php    # RIGHT: dynamic detail
│
├── assets/
│   ├── css/
│   │   ├── av2-design-system.css    # Design tokens Apollo v2
│   │   ├── navbar.css
│   │   ├── navbar.v1.css
│   │   ├── navbar.v2.css            # Ativa
│   │   ├── new-home.css
│   │   ├── mural.css
│   │   ├── templates.css
│   │   ├── classifieds.css
│   │   ├── event-card.css
│   │   ├── frontend-editor.css
│   │   ├── new_auth-styles.css
│   │   └── admin-navbar-settings.css
│   └── js/
│       ├── navbar.js
│       ├── navbar.v1.js
│       ├── navbar.v2.js             # Ativa
│       ├── new-home.js
│       ├── mural.js
│       ├── templates.js
│       ├── frontend-editor.js
│       ├── new_auth-scripts.js
│       ├── new_auth-scripts.php     # ⚠️ PHP em diretório JS
│       └── admin-navbar-settings.js
│
├── examples/
│   └── user-radar-examples.php      # Exemplo de radar (não carregado)
│
├── docs/                            # Vazio
├── pages/                           # Vazio
├── phpcs-logs/                      # 5 logs JSON PHPCS
```

---

## 2. Sistema de Roteamento de Páginas

### 2.1 Rewrite Rules (includes/pages.php)

```
/home              → apollo_home_page=1    → page-home.php (guest) | page-mural.php (logged)
/sobre             → apollo_sobre_page=1   → page-sobre.php
/about-us          → apollo_about_redirect=1 → 301 redirect → /sobre
/test              → apollo_test_page=1    → page-test.php (admin only)
/classificados     → pagename=classificados → page-classificados.php (via WP page)
/classificados/novo → pagename=classificados&action=new (futuro)
```

Registradas em `init` prioridade 10, com fallback via `parse_request` prioridade 1 para compatibilidade nginx.

### 2.2 Template Include Chain (prioridades)

| Prioridade | Função | Descrição |
|-----------|--------|-----------|
| 96 | `apollo_templates_mapa_router` | /mapa → page-mapa.php |
| 97 | `apollo_templates_front_page_landing` | Front page (guest) → page-home.php |
| 98 | `apollo_templates_resolve_page_template` | Resolve slugs de template do WP admin |
| 99 (pages.php) | `apollo_templates_load_template` | /home, /sobre, /test, /classificados |
| 99 (mural-router.php) | anônimo | Front page (logged) → page-mural.php |

### 2.3 Frontend Editor Router (src/FrontendRouter.php)

```
/editar/{cpt}/{post_id}/  → edit-post.php (canvas mode)
/editar/{cpt}/            → create draft → redirect → /editar/{cpt}/{new_id}/
```

CPTs registrados via filtro `apollo_editable_post_types`. Requer login + `can_edit()`.

### 2.4 Query Vars Registrados

- `apollo_home_page`
- `apollo_test_page`
- `apollo_sobre_page`
- `apollo_about_redirect`
- `apollo_edit_cpt`
- `apollo_edit_id`

---

## 3. Como Cada Página é Renderizada

### /home (guest)
- **Tipo:** Canvas v2 (sem wp_head/wp_footer)
- **Template:** `templates/page-home.php` (1281 linhas)
- **CDN:** `https://cdn.apollo.rio.br/v1.0.0/core.min.js`
- **Componentes:** Panel Engine com persistent UI (navbar, FAB, panels)

### /home (logged) → Mural
- **Tipo:** Canvas parcial
- **Template:** `templates/page-mural.php` (314 linhas)
- **Dados:** User prefs, sound tags, favorite events, same-vibe events, classifieds
- **Componentes:** Greeting, weather, upcoming, favorites, same-vibe, classifieds, sounds, ticker, news

### /sobre
- **Tipo:** Canvas (sem wp_head/wp_footer)
- **Template:** `templates/page-sobre.php` (332 linhas)
- **CDN:** Hardcoded `https://cdn.apollo.rio.br/v1.0.0/core.min.js`
- **Parts:** template-parts/home/ (hero, marquee, events-listing, classifieds, etc.)

### /classificados
- **Tipo:** Full HTML canvas
- **Template:** `templates/page-classificados.php` (523 linhas)
- **CPT utilizado:** `apollo_classified`
- **Taxonomias:** `classified_intent`, `classified_domain`
- **WP Page:** criada na ativação com slug `classificados`

### /editar/{cpt}/{id}/
- **Tipo:** Canvas (sem wp_head/wp_footer)
- **Template:** `templates/edit-post.php` (522 linhas)
- **Sistema:** FrontendEditor + FrontendFields
- **AJAX:** `apollo_frontend_save`, `apollo_frontend_upload`, `apollo_frontend_delete_image`

### /mapa
- **Tipo:** Canvas v2
- **Template:** `templates/page-mapa.php` (1189 linhas)
- **Dados:** Event CPT → linked loc → lat/lng
- **Tech:** Leaflet.js com CARTO Positron tiles

### /test
- **Tipo:** Canvas (admin only)
- **Template:** `templates/page-test.php` (2397 linhas)
- **Função:** Spreadsheet interativa de rotas/forms/REST endpoints

---

## 4. REST Routes Registradas

| Endpoint | Método | Auth | Descrição |
|-----------|--------|------|-----------|
| `apollo/v1/templates` | GET | Público | Lista templates disponíveis |
| `apollo/v1/templates/calendars` | GET | Público | Tipos de calendário (hardcoded) |
| `apollo/v1/canvas/save` | POST | `edit_posts` | Salva dados de canvas em meta |
| `apollo/v1/canvas/blocks` | GET | Público | Lista blocos disponíveis (hardcoded) |

---

## 5. Template Engine (includes/functions.php)

3 funções core:

1. **`apollo_locate_template($name, $plugin_dir)`** — Busca template com hierarquia tema/plugin
2. **`apollo_get_template($name, $args, $plugin_dir)`** — Carrega template com variáveis via extract()
3. **`apollo_get_template_html($name, $args, $plugin_dir)`** — Retorna HTML renderizado (buffer)

Hierarquia de busca:
```
1. {child-theme}/apollo-templates/{template}
2. {child-theme}/apollo/{template}
3. {parent-theme}/apollo-templates/{template}
4. {parent-theme}/apollo/{template}
5. {calling-plugin}/templates/{template}
6. apollo-templates/templates/{template}
```

---

## 6. CDN Loading Patterns

### Templates Canvas (sem wp_head/wp_footer)
Carregam CDN diretamente no `<head>`:
```html
<script src="https://cdn.apollo.rio.br/v1.0.0/core.min.js?v=1.0.0" fetchpriority="high"></script>
```

Templates que usam: page-home.php, page-sobre.php, page-mapa.php, edit-post.php.

### PersistentUI::head()
Classe helper que gera o bloco `<head>` padronizado com CDN, usa constante `APOLLO_CDN_CORE_JS` com fallback.

### Templates WP (com wp_head/wp_footer)
- Navbar v2 CSS/JS adicionado via `wp_enqueue_scripts` prioridade 20
- Design system CSS (`av2-design-system.css`) como dependência

---

## 7. PROBLEMAS ENCONTRADOS

### 7.1 🔴 CRÍTICO — Arquivo `create-page.php` acessível publicamente

```php
// create-page.php linha 7
require_once '../../../wp-load.php';
```

Este arquivo pode ser acessado diretamente via browser (`/wp-content/plugins/apollo-templates/create-page.php`), carrega wp-load.php e executa `wp_insert_post()` + `flush_rewrite_rules()` **sem nenhuma verificação de autenticação**. Qualquer pessoa pode criar páginas. **DEVE SER REMOVIDO.**

### 7.2 🔴 CRÍTICO — `$_SERVER['REQUEST_URI']` sem sanitização

Em `includes/pages.php` linha ~90:
```php
$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
```

`$_SERVER['REQUEST_URI']` não é sanitizado com `sanitize_text_field()` ou `wp_unslash()`.

### 7.3 🟡 MODERADO — Termos proibidos "Locais" nas labels de navbar

Encontrados em:
- `includes/class-navbar-settings.php:211` — `'label' => 'Locais'`, `'url' => '/locais'`
- `templates/template-parts/navbar.php:67-68` — `'label' => 'Locais'`, `'url' => home_url('/locais')`
- `templates/template-parts/navbar.v1.php:67` — `'label' => 'Locais'`
- `templates/template-parts/new-home/navbar.php:76` — `'label' => 'Locais'`
- `templates/template-parts/navbar-old-backup.php:130-135` — `Locais`, `/locais/`

Segundo as naming rules do registry, "location/local/venue" devem usar `loc`. O label pode ser UI-facing (aceitável), mas a URL `/locais` não é a slug canônica.

### 7.4 🟡 MODERADO — `user_location` meta key pode violar naming rules

Em `templates/template-parts/navbar.php:342` e `navbar.v1.php:342`:
```php
$user_location = get_user_meta($user_id, 'user_location', true);
```

A meta key `user_location` contém o termo proibido "location". Verificar no registro se esta é a meta key correta.

### 7.5 🟡 MODERADO — Registros duplicados entre `mural-router.php` e `pages.php`

Ambos registram lógica para redirecionar `/home` logado → `page-mural.php`:
- `pages.php` L109-117: verifica `apollo_home_page` + `is_user_logged_in()` → retorna `page-mural.php`
- `mural-router.php` L30: filtra `is_front_page()/is_home()/is_page('home')` + logged → retorna `page-mural.php`
- `apollo-templates.php` L290-302: `apollo_templates_front_page_landing` na prioridade 97 faz o mesmo para guest

Três layers de redirecionamento sobrepostos. Funciona, mas é confuso e frágil.

### 7.6 🟡 MODERADO — Prioridades conflitantes no `template_include`

- Prioridade 96: `apollo_templates_mapa_router`
- Prioridade 97: `apollo_templates_front_page_landing` (namespace)
- Prioridade 98: `apollo_templates_resolve_page_template` (namespace)
- Prioridade 99: `apollo_templates_load_template` (pages.php, global)
- Prioridade 99: anônimo em `mural-router.php` (global)

Duas funções na prioridade 99 — a ordem de execução depende da ordem de `require_once` no main file.

### 7.7 🟡 MODERADO — Registry lista apenas 2 pages, código registra 6+

O `apollo-registry.json` define apenas:
```json
"pages": [
    { "slug": "sobre" },
    { "slug": "about-us" }
]
```

Mas o código registra: `/home`, `/sobre`, `/about-us`, `/test`, `/classificados`, `/classificados/novo`, `/mapa`, `/editar/*`. O registry está desatualizado.

### 7.8 🟡 MODERADO — `$_POST['pass']` sem wp_unslash

Em `apollo-templates.php` L355:
```php
$password = $_POST['pass'] ?? '';
```

Senha não é processada com `wp_unslash()`. Embora senhas não devam ser sanitizadas com `sanitize_text_field()`, `wp_unslash()` é necessário para compatibilidade com magic quotes.

### 7.9 🟢 MENOR — `extract()` usado em vários locais

- `includes/functions.php:100` — `extract($args, EXTR_SKIP)` — marcado com phpcs:ignore, aceitável
- `src/FrontendRouter.php:305` — `extract($template_vars, EXTR_SKIP)` — aceitável

### 7.10 🟢 MENOR — Templates auth/ parecem pertencer ao apollo-login

Os templates em `templates/auth/` (login-register.php, parts/new_*) parecem duplicar funcionalidade do `apollo-login`. Podem causar conflito se ambos os plugins tentarem servir a mesma rota.

### 7.11 🟢 MENOR — Referência ao `event/calendar.php` e `event/form.php` inexistentes

O shortcode `[apollo_calendar]` tenta carregar `event/calendar.php` — este arquivo **não existe** no diretório templates/event/.
O shortcode `[apollo_event_form]` tenta carregar `event/form.php` — este arquivo **também não existe**.

Templates existentes em `event/`: apenas `card-style-01.php` e `listing.php`.

### 7.12 🟢 MENOR — Diretórios `docs/` e `pages/` vazios

Podem ser removidos ou preenchidos.

### 7.13 🟢 MENOR — Arquivo `assets/js/new_auth-scripts.php` em diretório JS

Arquivo PHP dentro da pasta de JavaScript. Pode ser um template que gera JS dinâmico, mas a localização é atípica.

### 7.14 🟢 MENOR — `@package Apollo_Core` errado em page-sobre.php

Em `templates/page-sobre.php:12`:
```php
* @package Apollo_Core
```
Deveria ser `@package Apollo\Templates`.

### 7.15 🟢 MENOR — Múltiplas versões de navbar

4 versões coexistindo:
- `navbar.php` (v1 original)
- `navbar.v1.php`
- `navbar.v2.php` (ativa)
- `navbar-old-backup.php`
- `new-home/navbar.php` (canvas v2)

Apenas `navbar.v2.php` e `new-home/navbar.php` são usadas ativamente. Os outros são dead code.

### 7.16 🟢 OBSERVAÇÃO — `apollo_suggest_event` handler no arquivo principal

O handler AJAX `apollo_suggest_event` em `apollo-templates.php` L400-450 cria posts do tipo `event` — funcionalidade que deveria residir no `apollo-events` plugin. Isso é acoplamento cruzado.

---

## 8. Resumo de Conformidade com o Registry

| Item | Status | Detalhes |
|------|--------|----------|
| Namespace `Apollo\Templates` | ✅ OK | PSR-4 correto |
| Dependências `apollo-core`, `apollo-shortcodes` | ✅ OK | Verificadas em `plugins_loaded` |
| REST endpoints | ✅ OK | 4 rotas conforme registry |
| CPTs/Taxonomias | ✅ OK | Nenhum próprio (correto) |
| Meta keys `_apollo_template`, `_apollo_canvas_data` | ✅ OK | Usados em REST save canvas |
| Pages no registry | ⚠️ PARCIAL | Registry lista 2, código tem 6+ |
| Shortcodes no registry | ⚠️ VAZIO | Registry `"shortcodes": []` mas código registra 4 |
| Naming rules | ⚠️ VIOLAÇÃO | "Locais"/"/locais" usado em navbar defaults |
| Security | ⚠️ RISCO | `create-page.php` acessível, `$_SERVER` não sanitizado |
| CDN | ✅ OK | Carregado corretamente em canvas templates |
| ABSPATH guard | ✅ OK | Todos os templates principais têm guard |
