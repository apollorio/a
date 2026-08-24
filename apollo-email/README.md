# Apollo Email

**Motor de email transacional e marketing integrado ao ecossistema Apollo** — templates, fila de envio, logging, SMTP/SES/SendGrid, tracking, merge tags, automação de gatilhos e administração completa via wp-admin.

| Item | Valor |
|------|-------|
| **Versão** | `1.0.0` |
| **Namespace** | `Apollo\Email` |
| **Camada** | L3 — Social + Communication |
| **Requer PHP** | `>= 8.1` |
| **Requer WP** | `>= 6.4` |
| **Depende de** | `apollo-core` (guard via `APOLLO_CORE_BOOTSTRAPPED`) |
| **Text Domain** | `apollo-email` |
| **Licença** | GPL-2.0-or-later |

---

## Estrutura de Arquivos

```
apollo-email/
├── apollo-email.php                  # Arquivo principal: constantes, autoloader, bootstrap
├── README.md
├── assets/
│   ├── css/
│   │   └── admin-email.css           # Estilos admin
│   └── js/
│       └── admin-email.js            # Scripts admin
├── includes/
│   ├── index.php                     # Segurança
│   └── functions.php                 # Funções globais helper
├── src/
│   ├── index.php                     # Segurança
│   ├── Plugin.php                    # Singleton principal — orquestra todos os subsistemas
│   ├── Activation.php                # Ativação: cria tabelas, seed defaults, agenda cron
│   ├── Deactivation.php              # Desativação: limpa cron e transients
│   ├── Admin/
│   │   └── AdminPage.php             # Controlador de páginas wp-admin
│   ├── API/
│   │   └── EmailController.php       # REST API controller (apollo/v1/email/*)
│   ├── CLI/
│   │   └── EmailCommand.php          # Comandos WP-CLI
│   ├── Core/
│   │   ├── CPT.php                   # Registro do CPT email_aprio
│   │   ├── Cron.php                  # Agendamento de cron para fila
│   │   └── Schema.php                # Schema do banco (se existir)
│   ├── Log/
│   │   └── Logger.php                # Logger de emails enviados/falhos/abertos/clicados
│   ├── Mailer/
│   │   ├── Message.php               # Builder fluente para construção de emails
│   │   ├── Queue.php                 # Gerenciador de fila com retry, prioridade, scheduling
│   │   └── Sender.php                # Renderiza, envia e loga emails
│   └── Template/
│       ├── StyleInliner.php          # Inline CSS para compatibilidade com clientes de email
│       └── TemplateEngine.php        # Engine de templates com merge tags e branding Apollo
└── templates/
    └── emails/
        ├── base.php                  # Template-base wrapper (header + footer)
        ├── welcome.php               # Boas-vindas ao usuário
        ├── verification.php          # Verificação de email
        ├── password-reset.php        # Recuperação de senha
        ├── notification.php          # Notificação genérica (com action button)
        ├── event-reminder.php        # Lembrete de evento
        ├── digest.php                # Resumo semanal / digest segmentado
        └── report.php                # Relatório / denúncia
```

---

## Constantes

| Constante | Valor | Descrição |
|-----------|-------|-----------|
| `APOLLO_EMAIL_VERSION` | `'1.0.0'` | Versão do plugin |
| `APOLLO_EMAIL_FILE` | `__FILE__` | Caminho absoluto do arquivo principal |
| `APOLLO_EMAIL_PATH` | `plugin_dir_path(__FILE__)` | Diretório do plugin (com trailing slash) |
| `APOLLO_EMAIL_URL` | `plugin_dir_url(__FILE__)` | URL do plugin |
| `APOLLO_EMAIL_BASENAME` | `plugin_basename(__FILE__)` | Basename para hooks de ativação |
| `APOLLO_EMAIL_SLUG` | `'apollo-email'` | Slug do plugin |
| `APOLLO_EMAIL_DB_VERSION` | `'1.0.0'` | Versão do schema do banco |
| `APOLLO_EMAIL_MIN_PHP` | `'8.1'` | PHP mínimo |
| `APOLLO_EMAIL_MIN_WP` | `'6.4'` | WordPress mínimo |
| `APOLLO_EMAIL_CRON_HOOK` | `'apollo_email_process_queue'` | Hook do cron para processar fila |
| `APOLLO_EMAIL_BATCH_SIZE` | `50` | Tamanho padrão do batch de envio |
| `APOLLO_EMAIL_MAX_RETRIES` | `3` | Máximo de tentativas por email |

---

## Serviços (Service Layer)

O `Plugin.php` é um **singleton** que inicializa e expõe todos os serviços via accessors:

| Serviço | Classe | Accessor | Descrição |
|---------|--------|----------|-----------|
| **Sender** | `Apollo\Email\Mailer\Sender` | `$plugin->sender()` | Envia emails (template ou raw HTML), aplica headers, loga resultado |
| **Queue** | `Apollo\Email\Mailer\Queue` | `$plugin->queue()` | Fila de envio com prioridade, retry, batch processing |
| **TemplateEngine** | `Apollo\Email\Template\TemplateEngine` | `$plugin->templates()` | Renderiza templates com merge tags `{{variavel}}`, branding, CSS inline |
| **Logger** | `Apollo\Email\Log\Logger` | `$plugin->logger()` | Registra sent/failed/opened/clicked no banco, estatísticas |
| **CPT** | `Apollo\Email\Core\CPT` | `$plugin->cpt()` | Registra o CPT `email_aprio` para templates editáveis no wp-admin |
| **Cron** | `Apollo\Email\Core\Cron` | `$plugin->cron()` | Agenda cron a cada 5 minutos para processar fila |
| **Message** | `Apollo\Email\Mailer\Message` | — | Builder fluente para construir objetos de email |
| **StyleInliner** | `Apollo\Email\Template\StyleInliner` | — | Converte `<style>` blocks em inline styles (static) |
| **AdminPage** | `Apollo\Email\Admin\AdminPage` | — | Páginas de administração no wp-admin |
| **EmailController** | `Apollo\Email\API\EmailController` | — | REST API controller |
| **EmailCommand** | `Apollo\Email\CLI\EmailCommand` | — | WP-CLI commands |

---

## Custom Post Type

| Propriedade | Valor |
|-------------|-------|
| **Slug** | `email_aprio` |
| **Show UI** | `true` |
| **Show in Menu** | `false` (gerenciado via AdminPage) |
| **Show in REST** | `true` (`rest_base: email-templates`) |
| **Supports** | `title`, `editor` |
| **Meta Keys** | `_email_subject`, `_email_type`, `_email_variables` |

### Templates Seedados na Ativação

| Slug | Título | Tipo | Variáveis |
|------|--------|------|-----------|
| `welcome` | Boas-vindas | transactional | `user_name`, `username`, `profile_url`, `site_name`, `site_url` |
| `password-reset` | Recuperar Senha | transactional | `user_name`, `reset_url`, `site_name`, `expires_in` |
| `verification` | Verificação de Email | transactional | `user_name`, `verify_url`, `site_name` |
| `notification` | Notificação Geral | transactional | `user_name`, `title`, `message`, `action_url`, `action_text`, `site_name` |
| `event-reminder` | Lembrete de Evento | transactional | `user_name`, `event_title`, `event_url`, `event_date`, `event_time`, `loc_name`, `site_name` |
| `digest` | Resumo Semanal | digest | `user_name`, `notifications`, `site_name`, `site_url` |

---

## Tabelas do Banco de Dados

### `{prefix}apollo_email_queue`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | PK |
| `to_email` | VARCHAR(255) | Email do destinatário |
| `to_name` | VARCHAR(255) | Nome do destinatário |
| `subject` | VARCHAR(500) | Assunto do email |
| `body` | LONGTEXT | HTML renderizado |
| `template` | VARCHAR(100) | Slug do template |
| `template_data` | JSON | Dados de merge tags |
| `priority` | INT (default 5) | Prioridade (1=mais alta, 10=mais baixa) |
| `status` | ENUM | `pending`, `processing`, `sent`, `failed`, `cancelled` |
| `attempts` | INT | Tentativas realizadas |
| `max_attempts` | INT (default 3) | Máximo de tentativas |
| `scheduled_at` | DATETIME | Agendamento de envio |
| `sent_at` | DATETIME | Quando foi enviado |
| `error_message` | TEXT | Mensagem de erro (se falhou) |
| `created_at` | DATETIME | Data de criação |

**Índices:** `idx_status`, `idx_scheduled`, `idx_priority` (priority + scheduled_at), `idx_template`

### `{prefix}apollo_email_log`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT | PK |
| `to_email` | VARCHAR(255) | Email do destinatário |
| `subject` | VARCHAR(500) | Assunto |
| `template` | VARCHAR(100) | Slug do template usado |
| `email_type` | ENUM | `transactional`, `marketing`, `digest` |
| `status` | ENUM | `sent`, `failed`, `bounced`, `opened`, `clicked` |
| `transport` | VARCHAR(50) | Transporte utilizado (wp_mail, smtp, ses, sendgrid) |
| `sent_at` | DATETIME | Timestamp de envio |
| `opened_at` | DATETIME | Timestamp de abertura |
| `clicked_at` | DATETIME | Timestamp de clique |
| `error_message` | TEXT | Erro (se falhou) |
| `meta` | JSON | Metadados extras |

**Índices:** `idx_email`, `idx_status`, `idx_sent`, `idx_type`, `idx_template`

---

## REST API Endpoints

Namespace: `apollo/v1`

### Admin (requer `manage_options`)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `POST` | `/email/send` | Enviar email (com template ou body HTML) |
| `POST` | `/email/test` | Enviar email de teste |
| `GET` | `/email/stats` | Dashboard stats (log + queue + templates count) |
| `GET` | `/email/queue` | Listar fila (filtro: `status`, paginação) |
| `POST` | `/email/queue/{id}/cancel` | Cancelar email pendente |
| `POST` | `/email/queue/{id}/retry` | Retentar email falho |
| `POST` | `/email/queue/purge` | Purgar fila antiga (`days` param, default 30) |
| `GET` | `/email/templates` | Listar todos os templates |
| `POST` | `/email/templates` | Criar novo template |
| `GET` | `/email/templates/{id}` | Ver template específico |
| `PUT` | `/email/templates/{id}` | Atualizar template |
| `DELETE` | `/email/templates/{id}` | Excluir template |
| `GET` | `/email/log` | Listar log (filtros: `status`, `email`, `template`, `date_from`, `date_to`) |
| `POST` | `/email/log/purge` | Purgar log antigo (`days` param, default 90) |

### Usuário autenticado (requer login)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/email/preferences` | Obter preferências de email do usuário |
| `PUT` | `/email/preferences` | Atualizar preferências (`marketing`, `digest`; `transactional` sempre true) |

---

## WP-CLI Commands

Registrado como: `wp apollo email <subcommand>`

| Comando | Descrição | Exemplo |
|---------|-----------|---------|
| `send <to> <subject>` | Enviar email | `wp apollo email send user@ex.com "Hello!" --template=welcome --data='{"username":"John"}'` |
| `test <to>` | Enviar email de teste | `wp apollo email test admin@ex.com` |
| `queue:process` | Processar fila de envio | `wp apollo email queue:process --batch=100` |
| `queue:stats` | Mostrar estatísticas da fila | `wp apollo email queue:stats` |
| `queue:purge` | Purgar itens antigos da fila | `wp apollo email queue:purge --days=30` |
| `log:stats` | Mostrar estatísticas do log | `wp apollo email log:stats --days=30` |
| `log:purge` | Purgar entradas antigas do log | `wp apollo email log:purge --days=90` |
| `templates` | Listar todos os templates de email | `wp apollo email templates` |

---

## Hooks — Actions Disparadas

| Hook | Parâmetros | Quando |
|------|-----------|--------|
| `apollo/email/loaded` | `Plugin $plugin` | Plugin totalmente carregado |
| `apollo/email/init` | `Plugin $plugin` | Todos os serviços inicializados |
| `apollo/email/before_send` | `array $email_data, Message $message` | Antes de enviar um email |
| `apollo/email/sent` | `int $log_id, Message $message` | Email enviado com sucesso |
| `apollo/email/failed` | `int $log_id, string $error, Message $message` | Falha no envio |
| `apollo/email/opened` | `int $log_id` | Email aberto (tracking pixel) |
| `apollo/email/queued` | `int $queue_id, string $to, string $template` | Email adicionado à fila |
| `apollo/email/queue_processed` | `int $count` | Batch da fila processado |

## Hooks — Filters

| Hook | Parâmetros | Descrição |
|------|-----------|-----------|
| `apollo/email/from_name` | `string $name` | Filtrar nome do remetente |
| `apollo/email/from_address` | `string $email` | Filtrar email do remetente |
| `apollo/email/headers` | `string[] $headers, string $template` | Filtrar headers do email |
| `apollo/email/template_data` | `array $data, string $template_slug` | Filtrar dados antes de renderizar template |

## Hooks — Actions Escutadas (Cross-Plugin)

| Hook de Origem | Handler | Ação |
|----------------|---------|------|
| `apollo/login/registered` | `onUserRegistered()` | Envia email de boas-vindas |
| `apollo/login/password_reset_requested` | `onPasswordResetRequested()` | Envia email de reset de senha |
| `apollo/login/verification_email` | `onVerificationEmail()` | Envia email de verificação |
| `apollo/event/reminder` | `onEventReminder()` | Envia lembrete de evento (batch, via fila) |
| `apollo/notif/digest` | `onNotifDigest()` | Envia digest de notificações |
| `apollo/email/digest/notifications` | `onDigestNotifications()` | Digest segmentado: notificações |
| `apollo/email/digest/fav_events` | `onDigestFavEvents()` | Digest segmentado: eventos salvos |
| `apollo/email/digest/event_match` | `onDigestEventMatch()` | Digest segmentado: match de som |
| `apollo/email/digest/chat` | `onDigestChat()` | Digest segmentado: chat |
| `apollo/email/digest/comuna` | `onDigestComuna()` | Digest segmentado: comunas (grupos) |
| `apollo/email/digest/news` | `onDigestNews()` | Digest segmentado: Apollo news |
| `apollo/email/digest/social` | `onDigestSocial()` | Digest segmentado: social updates |
| `apollo/membership/achievement_earned` | `onAchievementEarned()` | Notifica conquista desbloqueada |
| `apollo/groups/user_invited` | `onGroupInvitation()` | Envia convite para grupo |
| `apollo/chat/message_sent` | `onChatMessage()` | Notifica mensagem se destinatário offline |

---

## Funções Globais Helper

Definidas em `includes/functions.php` — disponíveis para qualquer plugin do ecossistema:

```php
// Enviar email imediatamente com template
apollo_send_email(string $to, string $subject, string $template, array $data = []): bool

// Adicionar email à fila (priority: 1=alta, 10=baixa)  
apollo_queue_email(string $to, string $subject, string $template, array $data = [], int $priority = 5): int|false

// Processar fila manualmente
apollo_process_email_queue(int $batch_size = 50): void

// Obter template por slug
apollo_get_email_template(string $template_slug): ?array

// Renderizar template com dados
apollo_render_email(string $template, array $data = []): string
```

---

## Cron

| Intervalo | Hook | Ação |
|-----------|------|------|
| `apollo_five_minutes` (300s) | `apollo_email_process_queue` | Processa batch da fila (`Queue::processNext()`) |
| `apollo_hourly` (3600s) | — | Registrado mas não utilizado diretamente |

O cron usa locking via transient (`apollo_email_queue_lock`, TTL 5min) para prevenir processamento concorrente.

---

## Configurações (`apollo_email_settings`)

### Geral

| Key | Tipo | Default | Descrição |
|-----|------|---------|-----------|
| `from_name` | string | Blog name | Nome do remetente |
| `from_email` | string | Admin email | Email do remetente |
| `reply_to` | string | Admin email | Endereço de resposta |

### Transporte

| Key | Tipo | Default | Descrição |
|-----|------|---------|-----------|
| `transport` | string | `'wp_mail'` | Método de envio (`wp_mail`, `smtp`, `ses`, `sendgrid`) |
| `smtp_host` | string | `''` | Host SMTP |
| `smtp_port` | int | `587` | Porta SMTP |
| `smtp_encryption` | string | `'tls'` | Criptografia SMTP (`tls` / `ssl`) |
| `smtp_username` | string | `''` | Usuário SMTP |
| `smtp_password` | string | `''` | Senha SMTP |
| `ses_region` | string | `'us-east-1'` | Região AWS SES |
| `ses_access_key` | string | `''` | AWS Access Key |
| `ses_secret_key` | string | `''` | AWS Secret Key |
| `sendgrid_api_key` | string | `''` | API Key SendGrid |

### Rastreamento

| Key | Tipo | Default | Descrição |
|-----|------|---------|-----------|
| `track_opens` | bool | `true` | Rastrear aberturas (tracking pixel) |
| `track_clicks` | bool | `true` | Rastrear cliques (link redirect) |

### Identidade Visual

| Key | Tipo | Default | Descrição |
|-----|------|---------|-----------|
| `brand_color` | string | `'#6C3BF5'` | Cor principal do brand nos emails |
| `brand_logo` | string | `''` | URL do logo |
| `footer_text` | string | `'© {year} Apollo Rio...'` | Texto do rodapé |
| `footer_address` | string | `'Rio de Janeiro, RJ — Brasil'` | Endereço no rodapé |

### Fila

| Key | Tipo | Default | Descrição |
|-----|------|---------|-----------|
| `batch_size` | int | `50` | Emails por batch |
| `max_retries` | int | `3` | Máximo de retentativas |
| `wp_mail_override` | bool | `false` | Sobrescrever wp_mail padrão |

### Bridge com `apollo-admin`

As configurações fazem fallback para `apollo_admin_settings` com prefixo `email_` (ex: `email_from_name`, `email_smtp_host`), permitindo configuração centralizada via CPanel do apollo-admin.

---

## WP-Admin Pages

Menu principal: **Email** (`dashicons-email-alt2`, position 58)

| Submenu | Slug | Descrição |
|---------|------|-----------|
| Dashboard | `apollo-email` | Visão geral: stats de envio, fila, templates |
| Templates | `apollo-email-templates` | Gerenciar templates de email |
| Fila de Envio | `apollo-email-queue` | Visualizar e gerenciar fila |
| Log de Envios | `apollo-email-log` | Histórico de emails enviados |
| Configurações | `apollo-email-settings` | SMTP, SES, SendGrid, tracking, branding |

---

## Shortcode

| Shortcode | Descrição |
|-----------|-----------|
| `[apollo_email_prefs]` | Formulário de preferências de email do usuário (transactional, marketing, digest) |

Requer login. O campo "Transacionais" é sempre obrigatório (desabilitado no form).

---

## User Meta

| Meta Key | Tipo | Descrição |
|----------|------|-----------|
| `_apollo_email_prefs` | `array` | Preferências de email: `transactional` (always true), `marketing`, `digest` |

---

## Fluxo de Envio

```
apollo_send_email() / apollo_queue_email()
       │
       ▼
  Message::fromTemplate()     ← Builder fluente
       │
       ▼
  Sender::send()
       │
       ├─ TemplateEngine::render()
       │       ├─ Busca conteúdo do CPT (email_aprio)
       │       ├─ Renderiza PHP template fallback
       │       ├─ Substitui merge tags {{variavel}}
       │       ├─ Wrapa no base.php (header/footer)
       │       └─ StyleInliner::inline() (CSS → inline)
       │
       ├─ Aplica From/ReplyTo defaults
       ├─ do_action('apollo/email/before_send')
       ├─ apply_filters('apollo/email/headers')
       │
       ├─ wp_mail() ← envio real
       │
       ├─ ✅ Logger::logSent() → do_action('apollo/email/sent')
       └─ ❌ Logger::logFailed() → do_action('apollo/email/failed')
```

### Fila (Queue)

```
apollo_queue_email()
       │
       ▼
  Queue::enqueue() → insere no banco → do_action('apollo/email/queued')
       │
  [Cron a cada 5min]
       │
       ▼
  Queue::processNext()
       ├─ Transient lock (5min)
       ├─ fetchPending() → ORDER BY priority ASC, scheduled_at ASC
       ├─ Para cada item: Sender::send()
       ├─ ✅ markSent() | ❌ retry ou markFailed()
       └─ do_action('apollo/email/queue_processed')
```

---

## Ativação / Desativação

### Ativação (`Activation::activate()`)

1. Verifica requisitos de PHP
2. Cria tabelas `apollo_email_queue` e `apollo_email_log`
3. Seed de configurações padrão em `apollo_email_settings`
4. Agenda cron `apollo_email_process_queue` (5 min)
5. Seed de 6 templates padrão como CPT `email_aprio`
6. Salva `apollo_email_db_version` e `apollo_email_installed_at`
7. `flush_rewrite_rules()`

### Desativação (`Deactivation::deactivate()`)

1. Remove evento cron agendado
2. Limpa transients (`apollo_email_activated`, `apollo_email_queue_lock`)
3. `flush_rewrite_rules()`

---

## Options no Banco

| Option | Tipo | Descrição |
|--------|------|-----------|
| `apollo_email_settings` | array | Todas as configurações do plugin |
| `apollo_email_db_version` | string | Versão do schema do banco |
| `apollo_email_installed_at` | string | Data/hora de instalação |
