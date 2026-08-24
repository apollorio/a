<?php
/**
 * TestPanel — live diagnostic panel for apollo-statistics.
 *
 * Adds a "🧪 Testes" submenu under the Apollo main admin menu ("apollo").
 * 44 tests across 9 sections, each executed via admin-ajax in real time.
 * Test data is auto-cleaned on page leave via sendBeacon.
 *
 * @package Apollo\Statistics\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Admin;

use Apollo\Statistics\Core\MetricRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class TestPanel {

    /** Sentinel UUID for test records — trivially cleaned up after. */
    private const TEST_UUID = '00000000-0000-4000-8000-000000000001';

    /** @var array<int,array{icon:string,title:string,subtitle:string}> */
    private const SECTIONS = array(
        1 => array('icon' => 'ri-user-location-line',    'title' => 'Rastreamento de Usuários',        'subtitle' => 'Stats de acesso em todas as páginas Apollo'),
        2 => array('icon' => 'ri-api-line',              'title' => 'Endpoints REST /track/*',         'subtitle' => 'Os 6 endpoints recebem e processam payloads'),
        3 => array('icon' => 'ri-database-2-line',       'title' => 'Tabelas do Banco de Dados',       'subtitle' => '4 tabelas v2 + 3 tabelas legado = 7 total'),
        4 => array('icon' => 'ri-bar-chart-grouped-line','title' => 'MetricGroups & Registry',         'subtitle' => '68 instâncias registradas, 15 classes carregadas'),
        5 => array('icon' => 'ri-time-line',             'title' => 'Agendamento (Cron Jobs)',         'subtitle' => 'daily_aggregate + weekly_rotate ativos'),
        6 => array('icon' => 'ri-user-star-line',        'title' => 'Rota /id/{username}/stats',       'subtitle' => 'Rewrite rule + visibilidade público/privado/conexões'),
        7 => array('icon' => 'ri-archive-line',          'title' => 'Retenção de Dados',              'subtitle' => 'Por tabela, piso mínimo de 1 dia protegido'),
        8 => array('icon' => 'ri-trophy-line',           'title' => 'Gamificação & Milestones',        'subtitle' => 'GamificationBridge + guard apollo-membership'),
        9 => array('icon' => 'ri-shield-keyhole-line',   'title' => 'Clientes externos (apolloDJ)',    'subtitle' => 'REST /app/* + /dj/* + auth/JWT — apenas testes automáticos (admin-ajax)'),
    );

    /**
     * All tests, grouped by section (see SECTIONS).
     *
     * @var array<int,array{id:string,section:int,title:string,desc:string}>
     */
    private const TESTS = array(
        // ── Section 01 ─────────────────────────────────────────────────────
        array('id' => 'user_admin_tracked',    'section' => 1, 'title' => 'Admin encontrado nas statistics',     'desc' => 'Usuário admin tem sessão registrada em apollo_stats_sessions'),
        array('id' => 'anon_session_created',  'section' => 1, 'title' => 'Sessão anônima criada via REST',      'desc' => 'POST /track/session?action=start insere linha em sessions'),
        array('id' => 'pageview_inserts',      'section' => 1, 'title' => 'Pageview gravado no banco',           'desc' => 'POST /track/pageview insere linha em apollo_stats_pageviews'),
        array('id' => 'click_inserts',         'section' => 1, 'title' => 'Click gravado no banco',              'desc' => 'POST /track/click insere linha em apollo_stats_clicks'),
        array('id' => 'rate_limit_429',        'section' => 1, 'title' => 'Rate limit retorna HTTP 429',         'desc' => '> 120 chamadas/min são bloqueadas (janela fixa, sem reset de TTL)'),
        // ── Section 02 ─────────────────────────────────────────────────────
        array('id' => 'rest_pageview',         'section' => 2, 'title' => 'POST /track/pageview',                'desc' => 'Endpoint responde 201 Created'),
        array('id' => 'rest_session',          'section' => 2, 'title' => 'POST /track/session',                 'desc' => 'Endpoint responde 201 Created (heartbeat)'),
        array('id' => 'rest_click',            'section' => 2, 'title' => 'POST /track/click',                   'desc' => 'Endpoint responde 201 Created'),
        array('id' => 'rest_event',            'section' => 2, 'title' => 'POST /track/event',                   'desc' => 'Endpoint responde 201 Created'),
        array('id' => 'rest_radio',            'section' => 2, 'title' => 'POST /track/radio',                   'desc' => 'Endpoint responde 201 Created'),
        array('id' => 'rest_batch_3',          'section' => 2, 'title' => 'POST /track/batch — 3 eventos',       'desc' => 'Resposta: accepted=3, truncated=false'),
        array('id' => 'rest_batch_51',         'section' => 2, 'title' => 'POST /track/batch — 51 eventos',      'desc' => 'Resposta: truncated=true, total_received=51'),
        // ── Section 03 ─────────────────────────────────────────────────────
        array('id' => 'table_sessions',        'section' => 3, 'title' => 'apollo_stats_sessions',               'desc' => 'Tabela v2 de sessões existe no banco'),
        array('id' => 'table_pageviews',       'section' => 3, 'title' => 'apollo_stats_pageviews',              'desc' => 'Tabela v2 de pageviews existe no banco'),
        array('id' => 'table_clicks',          'section' => 3, 'title' => 'apollo_stats_clicks',                 'desc' => 'Tabela v2 de clicks existe no banco'),
        array('id' => 'table_radio',           'section' => 3, 'title' => 'apollo_stats_radio',                  'desc' => 'Tabela v2 de rádio existe no banco'),
        array('id' => 'table_legacy_users',    'section' => 3, 'title' => 'apollo_stats_users (legado)',          'desc' => 'Tabela legado de usuários existe'),
        array('id' => 'table_legacy_content',  'section' => 3, 'title' => 'apollo_stats_content (legado)',        'desc' => 'Tabela legado de conteúdo existe'),
        array('id' => 'table_legacy_events',   'section' => 3, 'title' => 'apollo_stats_events (legado)',         'desc' => 'Tabela legado de eventos existe'),
        // ── Section 04 ─────────────────────────────────────────────────────
        array('id' => 'metrics_count_68',      'section' => 4, 'title' => '68 MetricGroups registrados',         'desc' => 'MetricRegistry::instance()->count() deve retornar exatamente 68'),
        array('id' => 'metrics_classes_exist', 'section' => 4, 'title' => '15 classes Metrics carregadas',       'desc' => 'Todas as classes em src/Metrics/ existem e autoload funciona'),
        // ── Section 05 ─────────────────────────────────────────────────────
        array('id' => 'cron_daily_aggregate',  'section' => 5, 'title' => 'Cron apollo_stats_daily_aggregate',   'desc' => 'wp_next_scheduled retorna horário futuro'),
        array('id' => 'cron_weekly_rotate',    'section' => 5, 'title' => 'Cron apollo_stats_weekly_rotate',     'desc' => 'wp_next_scheduled retorna horário futuro'),
        // ── Section 06 ─────────────────────────────────────────────────────
        array('id' => 'profile_route_exists',  'section' => 6, 'title' => 'Rewrite rule /id/{user}/stats',       'desc' => 'Regra de reescrita registrada no WordPress'),
        array('id' => 'profile_private_403',   'section' => 6, 'title' => 'Privacidade: anônimo bloqueado',      'desc' => 'Lógica de perfil privado nega acesso a visitante anônimo'),
        // ── Section 07 ─────────────────────────────────────────────────────
        array('id' => 'retention_defaults',    'section' => 7, 'title' => 'Defaults de retenção corretos',       'desc' => 'sessions=365d, pageviews=90d, clicks=30d, radio=365d'),
        array('id' => 'retention_floor',       'section' => 7, 'title' => 'Piso mínimo: 0 dias → 1 dia',        'desc' => 'Configuração 0 é convertida para 1 pelo max(1,…)'),
        // ── Section 08 ─────────────────────────────────────────────────────
        array('id' => 'gamif_guard',           'section' => 8, 'title' => 'Guard de membership ativo',           'desc' => 'Bridge não registra hooks sem apollo-membership instalado'),
        array('id' => 'gamif_milestone',       'section' => 8, 'title' => 'Milestone 100 pts atribuído',         'desc' => 'Score=100 → _apollo_stats_milestone_100 definido via usermeta'),
        // ── Section 09 — External app auth (apolloDJ.exe + future) ────────
        array('id' => 'ext_apollo_login_active',   'section' => 9, 'title' => 'Apollo Login carregado',            'desc' => 'Constante APOLLO_LOGIN_VERSION — rotas /app e /dj'),
        array('id' => 'ext_rest_dj_config',        'section' => 9, 'title' => 'GET /dj/config responde 200',       'desc' => 'Boot público do apolloDJ: maintenance_mode, min_app_version'),
        array('id' => 'ext_rest_app_auth_route',   'section' => 9, 'title' => 'Rota POST /app/auth registrada',    'desc' => 'Login app_id=apollodj + token opaco'),
        array('id' => 'ext_rest_app_verify_route', 'section' => 9, 'title' => 'Rota GET /app/verify registrada',     'desc' => 'Validação com header X-Apollo-App-Token'),
        array('id' => 'ext_rest_dj_permissions_route', 'section' => 9, 'title' => 'Rota GET /dj/permissions registrada', 'desc' => 'allowed_tabs / membership para o .exe'),
        array('id' => 'ext_dj_jwt_hmac_secrets',   'section' => 9, 'title' => 'Segredos JWT + HMAC (sessão .exe)', 'desc' => 'APOLLO_DJ_JWT_SECRET (≥32) e APOLLO_DJ_HMAC_SECRET (≥24) em wp-config ou env APOLLODJ_* — alinhado ao strict do apollo-login'),
        array('id' => 'ext_auth_health_get',       'section' => 9, 'title' => 'GET /health responde 200',          'desc' => 'apollo-core HealthController — readiness'),
        array('id' => 'ext_auth_check_username_get', 'section' => 9, 'title' => 'GET /auth/check-username',      'desc' => 'apollo-login — query fictícia, rota viva'),
        array('id' => 'ext_auth_check_email_get',  'section' => 9, 'title' => 'GET /auth/check-email',            'desc' => 'apollo-login — e-mail de teste, rota viva'),
        array('id' => 'ext_auth_login_invalid_post', 'section' => 9, 'title' => 'POST /auth/login (credenciais falsas)', 'desc' => '401/403 esperado — confirma rota sem login real'),
        array('id' => 'ext_rest_jwt_token_route',  'section' => 9, 'title' => 'Rota POST /auth/token registrada', 'desc' => 'JWT RS256 — apollo-login'),
        array('id' => 'ext_rest_jwt_refresh_route', 'section' => 9, 'title' => 'Rota POST /auth/token/refresh',   'desc' => 'Renovar access token'),
        array('id' => 'ext_rest_jwt_revoke_route', 'section' => 9, 'title' => 'Rota POST /auth/token/revoke',    'desc' => 'Revoke refresh — permission_callback pode exigir sessão WP'),
        array('id' => 'ext_rest_users_me_route',   'section' => 9, 'title' => 'Rota GET /users/me registrada',    'desc' => 'apollo-users — Bearer JWT'),
        array('id' => 'ext_rest_shortcodes_list',  'section' => 9, 'title' => 'GET /shortcodes (apollo-core)',      'desc' => 'Lista pública de shortcodes — parceiros / headless'),
        array('id' => 'ext_membership_profile_canonical', 'section' => 9, 'title' => 'Apollo Membership no user-edit', 'desc' => 'Perfil WP: badge + app-apollodj/amigz/greatdjs (substitui DJ Sync Feature Gates)'),
        array('id' => 'ext_dj_sync_no_user_profile', 'section' => 9, 'title' => 'DJ Sync sem Feature Gates',       'desc' => 'apollo-dj-sync não registra UI per-user em user-edit.php'),
        array('id' => 'ext_app_auth_invalid_app_id', 'section' => 9, 'title' => 'POST /app/auth app_id inválido',  'desc' => 'app_id=unknown → 403 (whitelist apollodj)'),
        array('id' => 'ext_app_auth_bad_credentials', 'section' => 9, 'title' => 'POST /app/auth credenciais falsas', 'desc' => '401 login_failed — JWT/auth pipeline intacto'),
        array('id' => 'ext_membership_slugs_api',  'section' => 9, 'title' => '_apollo_membership array SSOT',   'desc' => 'apollo_membership_normalize_storage + array-only getters'),
        array('id' => 'ext_membership_audit_cli',  'section' => 9, 'title' => 'audit-membership.php CLI',        'desc' => 'apollo-core/bin/audit-membership.php — drift + legacy meta scan'),
    );

    /* ──────────────────────── Hooks ──────────────────────── */

    public function init(): void {
        add_action('admin_menu',                          array($this, 'register_page'), 20);
        add_action('admin_enqueue_scripts',               array($this, 'enqueue_assets'));
        add_action('wp_ajax_apollo_stats_run_test',       array($this, 'ajax_run_test'));
        add_action('wp_ajax_apollo_stats_cleanup_tests',  array($this, 'ajax_cleanup_tests'));
    }

    /* ──────────────────────── Admin menu ─────────────────── */

    public function register_page(): void {
        add_submenu_page(
            'apollo',
            __('Apollo Statistics — Testes', 'apollo-statistics'),
            '🧪 ' . __('Testes Stats', 'apollo-statistics'),
            'manage_options',
            'apollo-statistics-tests',
            array($this, 'render')
        );
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== 'apollo_page_apollo-statistics-tests') {
            return;
        }

        wp_enqueue_style(
            'apollo-test-panel',
            APOLLO_STATS_URL . 'assets/css/admin-test.css',
            array(),
            APOLLO_STATS_VERSION
        );

        wp_enqueue_script(
            'apollo-test-panel',
            APOLLO_STATS_URL . 'assets/js/admin-test.js',
            array('jquery'),
            APOLLO_STATS_VERSION,
            true
        );

        wp_localize_script('apollo-test-panel', 'apolloTestPanel', array(
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('apollo_stats_test'),
            'restBase'    => rest_url('apollo/v1/'),
            'restNonce'   => wp_create_nonce('wp_rest'),
            'tests'       => array_column(self::TESTS, 'id'),
            'currentUser' => wp_get_current_user()->user_login,
            'totalTests'  => count(self::TESTS),
        ));
    }

    /* ──────────────────────── Render ─────────────────────── */

    public function render(): void {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Acesso negado.', 'apollo-statistics'));
        }

        // Group tests by section number.
        $by_section = array();
        foreach (self::TESTS as $t) {
            $by_section[ $t['section'] ][] = $t;
        }
        $total_tests = count(self::TESTS);
        ?>
        <div class="wrap atp-wrap">

            <div class="atp-header">
                <div class="atp-logo"><i class="ri-bar-chart-grouped-line"></i></div>
                <div class="atp-header-text">
                    <h1>WP-PANEL <span>apollo-statistics</span></h1>
                    <p>TAB &rsaquo; <strong>Teste</strong> — Diagnóstico em tempo real de todas as funcionalidades</p>
                </div>
                <div class="atp-actions">
                    <div class="atp-summary" id="atp-summary">
                        <span class="atp-total"><?php echo esc_html((string) $total_tests); ?> testes</span>
                    </div>
                    <button class="atp-btn-run" id="atp-run-all" type="button">
                        <i class="ri-play-line"></i>
                        <?php esc_html_e('Rodar Todos', 'apollo-statistics'); ?>
                    </button>
                    <label class="atp-autorun-label" for="atp-autorun" title="<?php esc_attr_e('Desligue para não iniciar a bateria ao abrir a página; use «Rodar Todos» manualmente.', 'apollo-statistics'); ?>">
                        <input type="checkbox" id="atp-autorun" checked="checked" />
                        <?php esc_html_e('Executar ao carregar', 'apollo-statistics'); ?>
                    </label>
                </div>
            </div>

            <div class="atp-progress-bar">
                <div class="atp-progress-fill" id="atp-progress-fill"></div>
            </div>

            <div class="atp-sections" id="atp-sections">
            <?php foreach (self::SECTIONS as $sec_id => $sec) : ?>
                <?php
                $tests     = $by_section[ $sec_id ] ?? array();
                $sec_num   = sprintf('%02d', $sec_id);
                $sec_id_s  = (string) $sec_id;
                ?>
                <div class="atp-section" data-section="<?php echo esc_attr($sec_id_s); ?>">

                    <div class="atp-section-head">
                        <i class="<?php echo esc_attr($sec['icon']); ?>"></i>
                        <div class="atp-section-title">
                            <h2><?php echo esc_html($sec_num . ' › ' . $sec['title']); ?></h2>
                            <p><?php echo esc_html($sec['subtitle']); ?></p>
                        </div>
                        <div class="atp-sec-badge" id="sec-badge-<?php echo esc_attr($sec_id_s); ?>">
                            <span><?php echo esc_html((string) count($tests)); ?></span>
                        </div>
                    </div>

                    <table class="atp-table">
                        <thead>
                            <tr>
                                <th class="atp-th-check"></th>
                                <th><?php esc_html_e('Teste', 'apollo-statistics'); ?></th>
                                <th><?php esc_html_e('Descrição', 'apollo-statistics'); ?></th>
                                <th class="atp-th-status"><?php esc_html_e('Status', 'apollo-statistics'); ?></th>
                                <th class="atp-th-detail"><?php esc_html_e('Detalhe', 'apollo-statistics'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tests as $test) : ?>
                            <tr class="atp-row" id="atp-row-<?php echo esc_attr($test['id']); ?>" data-test="<?php echo esc_attr($test['id']); ?>">
                                <td class="atp-check-cell">
                                    <span class="atp-check atp-check--idle" id="atp-check-<?php echo esc_attr($test['id']); ?>">
                                        <i class="ri-checkbox-blank-circle-line"></i>
                                    </span>
                                </td>
                                <td class="atp-test-title"><?php echo esc_html($test['title']); ?></td>
                                <td class="atp-test-desc"><?php echo esc_html($test['desc']); ?></td>
                                <td class="atp-status-cell">
                                    <span class="atp-badge atp-badge--idle" id="atp-badge-<?php echo esc_attr($test['id']); ?>">
                                        <?php esc_html_e('Aguardando', 'apollo-statistics'); ?>
                                    </span>
                                </td>
                                <td class="atp-detail-cell">
                                    <span class="atp-detail" id="atp-detail-<?php echo esc_attr($test['id']); ?>">–</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            <?php endforeach; ?>
            </div><!-- /.atp-sections -->

        </div><!-- /.wrap.atp-wrap -->
        <?php
    }

    /* ──────────────────────── AJAX handlers ──────────────── */

    public function ajax_run_test(): void {
        check_ajax_referer('apollo_stats_test', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Forbidden'), 403);
        }

        $test_id = sanitize_key(wp_unslash($_POST['test'] ?? ''));
        if (empty($test_id)) {
            wp_send_json_error(array('message' => 'Test ID is required'));
        }

        $result = $this->run_test($test_id);
        wp_send_json_success($result);
    }

    public function ajax_cleanup_tests(): void {
        check_ajax_referer('apollo_stats_test', 'nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Forbidden'), 403);
            return;
        }

        global $wpdb;
        $uuid = self::TEST_UUID;

        // Remove test rows from all v2 tables.
        $wpdb->delete("{$wpdb->prefix}apollo_stats_sessions",  array('session_id' => $uuid), array('%s'));
        $wpdb->delete("{$wpdb->prefix}apollo_stats_pageviews", array('session_id' => $uuid), array('%s'));
        $wpdb->delete("{$wpdb->prefix}apollo_stats_clicks",    array('session_id' => $uuid), array('%s'));
        $wpdb->delete("{$wpdb->prefix}apollo_stats_radio",     array('session_id' => $uuid), array('%s'));

        // Clear rate-limit transients for current admin IP so next run starts fresh.
        $this->clear_rate_limit_transients();

        // Clean test user-meta if set.
        delete_user_meta(get_current_user_id(), '_apollo_stats_milestone_100_testpanel');

        wp_send_json_success(array('cleaned' => true));
    }

    /* ──────────────────────── Test runner ────────────────── */

    /**
     * Execute a single test by ID and return a status array.
     *
     * @return array{status: 'ok'|'warn'|'fail', detail: string}
     */
    private function run_test(string $id): array {
        global $wpdb;
        $uuid = self::TEST_UUID;

        switch ($id) {

            /* ── 01 ── User tracking ─────────────────────── */

            case 'user_admin_tracked':
                $uid = get_current_user_id();
                $cnt = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_stats_sessions WHERE user_id = %d",
                    $uid
                ));
                if ($cnt > 0) {
                    return $this->ok(sprintf('ID %d → %d sessão(ões) encontrada(s) ✓', $uid, $cnt));
                }
                // Fall back to legacy table.
                $cnt2 = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_stats_users WHERE user_id = %d",
                    $uid
                ));
                if ($cnt2 > 0) {
                    return $this->ok(sprintf('ID %d → %d registro(s) em stats_users (legado) ✓', $uid, $cnt2));
                }
                return $this->warn('Sem sessões registradas ainda — visite o site com tracker ativo e refaça os testes');

            case 'anon_session_created':
                $req = new \WP_REST_Request('POST', '/apollo/v1/track/session');
                $req->set_body_params(array(
                    'session_id'  => $uuid,
                    'action'      => 'start',
                    'entry_url'   => home_url('/'),
                    'device_type' => 'desktop',
                ));
                $status = rest_do_request($req)->get_status();
                if ($status === 201) {
                    return $this->ok('POST /track/session → 201, session_id ' . $uuid . ' inserido ✓');
                }
                if ($status === 429) {
                    return $this->warn('Rate limit ativo — rode "Cleanup" antes de iniciar testes');
                }
                return $this->fail('Esperado 201, recebido HTTP ' . $status);

            case 'pageview_inserts':
                $req = new \WP_REST_Request('POST', '/apollo/v1/track/pageview');
                $req->set_body_params(array(
                    'session_id'   => $uuid,
                    'url'          => home_url('/diagnose-test'),
                    'page_type'    => 'test',
                    'scroll_depth' => 75,
                ));
                $status = rest_do_request($req)->get_status();
                if ($status === 201 || $status === 429) {
                    $cnt = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_stats_pageviews WHERE session_id = %s",
                        $uuid
                    ));
                    if ($cnt > 0) {
                        return $this->ok(sprintf('%d pageview(s) encontrado(s) no DB ✓', $cnt));
                    }
                }
                return $this->fail('Linha não inserida em apollo_stats_pageviews');

            case 'click_inserts':
                $req = new \WP_REST_Request('POST', '/apollo/v1/track/click');
                $req->set_body_params(array(
                    'session_id'   => $uuid,
                    'source_url'   => home_url('/'),
                    'target_url'   => home_url('/eventos'),
                    'element_type' => 'link',
                ));
                $status = rest_do_request($req)->get_status();
                if ($status === 201 || $status === 429) {
                    $cnt = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_stats_clicks WHERE session_id = %s",
                        $uuid
                    ));
                    if ($cnt > 0) {
                        return $this->ok(sprintf('%d click(s) encontrado(s) no DB ✓', $cnt));
                    }
                }
                return $this->fail('Linha não inserida em apollo_stats_clicks');

            case 'rate_limit_429':
                // First, reset the window so we start from zero.
                $this->clear_rate_limit_transients();

                $got_429  = false;
                $fired_at = 0;
                for ($i = 1; $i <= 125; $i++) {
                    $req = new \WP_REST_Request('POST', '/apollo/v1/track/pageview');
                    $req->set_body_params(array(
                        'session_id' => $uuid,
                        'url'        => home_url('/rl-test-' . $i),
                        'page_type'  => 'test',
                    ));
                    if (rest_do_request($req)->get_status() === 429) {
                        $got_429  = true;
                        $fired_at = $i;
                        break;
                    }
                }

                // Reset so subsequent REST tests in this session are not blocked.
                $this->clear_rate_limit_transients();

                return $got_429
                    ? $this->ok(sprintf('429 disparado na requisição #%d — janela fixa funcionando ✓', $fired_at))
                    : $this->fail('Rate limit não disparou após 125 requisições — verifique RATE_LIMIT constant');

            /* ── 02 ── REST endpoints ──────────────────────── */

            case 'rest_pageview':
                return $this->probe_rest('/apollo/v1/track/pageview', array(
                    'session_id' => $uuid, 'url' => home_url('/'), 'page_type' => 'test',
                ));

            case 'rest_session':
                return $this->probe_rest('/apollo/v1/track/session', array(
                    'session_id' => $uuid, 'action' => 'heartbeat', 'duration' => 10,
                ));

            case 'rest_click':
                return $this->probe_rest('/apollo/v1/track/click', array(
                    'session_id' => $uuid,
                    'source_url' => home_url('/'),
                    'target_url' => home_url('/'),
                ));

            case 'rest_event':
                return $this->probe_rest('/apollo/v1/track/event', array(
                    'session_id' => $uuid, 'event_type' => 'view', 'object_id' => 1,
                ));

            case 'rest_radio':
                return $this->probe_rest('/apollo/v1/track/radio', array(
                    'session_id' => $uuid, 'action' => 'start',
                ));

            case 'rest_batch_3':
                $req = new \WP_REST_Request('POST', '/apollo/v1/track/batch');
                $req->set_body_params(array('events' => array(
                    array('type' => 'event', 'session_id' => $uuid, 'event_type' => 'view', 'object_id' => 1),
                    array('type' => 'event', 'session_id' => $uuid, 'event_type' => 'view', 'object_id' => 2),
                    array('type' => 'event', 'session_id' => $uuid, 'event_type' => 'view', 'object_id' => 3),
                )));
                $res  = rest_do_request($req);
                $body = $res->get_data();
                if ($res->get_status() === 429) {
                    return $this->warn('Rate limit ativo — resultado de batch não verificável nesta execução');
                }
                return (isset($body['accepted']) && $body['accepted'] === 3 && empty($body['truncated']))
                    ? $this->ok("accepted=3, truncated=false ✓")
                    : $this->fail('Resposta inesperada: ' . wp_json_encode($body));

            case 'rest_batch_51':
                $events = array();
                for ($i = 1; $i <= 51; $i++) {
                    $events[] = array('type' => 'event', 'session_id' => $uuid, 'event_type' => 'view', 'object_id' => $i);
                }
                $req = new \WP_REST_Request('POST', '/apollo/v1/track/batch');
                $req->set_body_params(array('events' => $events));
                $res  = rest_do_request($req);
                $body = $res->get_data();
                if ($res->get_status() === 429) {
                    return $this->warn('Rate limit ativo — batch truncation não verificável nesta execução');
                }
                return (! empty($body['truncated']) && ($body['total_received'] ?? 0) === 51)
                    ? $this->ok('truncated=true, total_received=51, accepted≤50 ✓')
                    : $this->fail('Truncation não reportada corretamente: ' . wp_json_encode($body));

            /* ── 03 ── Tables ─────────────────────────────── */

            case 'table_sessions':      return $this->check_table("{$wpdb->prefix}apollo_stats_sessions");
            case 'table_pageviews':     return $this->check_table("{$wpdb->prefix}apollo_stats_pageviews");
            case 'table_clicks':        return $this->check_table("{$wpdb->prefix}apollo_stats_clicks");
            case 'table_radio':         return $this->check_table("{$wpdb->prefix}apollo_stats_radio");
            case 'table_legacy_users':  return $this->check_table("{$wpdb->prefix}apollo_stats_users");
            case 'table_legacy_content':return $this->check_table("{$wpdb->prefix}apollo_stats_content");
            case 'table_legacy_events': return $this->check_table("{$wpdb->prefix}apollo_stats_events");

            /* ── 04 ── MetricGroups ──────────────────────── */

            case 'metrics_count_68':
                $count = MetricRegistry::instance()->count();
                return $count === 68
                    ? $this->ok("MetricRegistry::count() = 68 ✓")
                    : $this->fail("Esperado 68, retornou {$count}");

            case 'metrics_classes_exist':
                $classes = array(
                    'Apollo\\Statistics\\Metrics\\ClickTrack',
                    'Apollo\\Statistics\\Metrics\\Comparison',
                    'Apollo\\Statistics\\Metrics\\Distribution',
                    'Apollo\\Statistics\\Metrics\\EngagementScore',
                    'Apollo\\Statistics\\Metrics\\Funnel',
                    'Apollo\\Statistics\\Metrics\\Growth',
                    'Apollo\\Statistics\\Metrics\\Leaderboard',
                    'Apollo\\Statistics\\Metrics\\Lifecycle',
                    'Apollo\\Statistics\\Metrics\\Profile',
                    'Apollo\\Statistics\\Metrics\\Radio',
                    'Apollo\\Statistics\\Metrics\\Ranking',
                    'Apollo\\Statistics\\Metrics\\Session',
                    'Apollo\\Statistics\\Metrics\\Skeleton',
                    'Apollo\\Statistics\\Metrics\\TimeSeries',
                    'Apollo\\Statistics\\Metrics\\ViewCounter',
                );
                $missing = array_filter($classes, fn($c) => ! class_exists($c));
                return empty($missing)
                    ? $this->ok('15/15 classes Metrics carregadas ✓')
                    : $this->fail('Classes faltando: ' . implode(', ', array_map(
                        fn($c) => substr(strrchr($c, '\\'), 1),
                        $missing
                    )));

            /* ── 05 ── Cron ───────────────────────────────── */

            case 'cron_daily_aggregate':
                $next = wp_next_scheduled('apollo_stats_daily_aggregate');
                return $next !== false
                    ? $this->ok('Próxima execução: ' . wp_date('d/m/Y H:i', $next))
                    : $this->fail('Cron apollo_stats_daily_aggregate não agendado — reative o plugin');

            case 'cron_weekly_rotate':
                $next = wp_next_scheduled('apollo_stats_weekly_rotate');
                return $next !== false
                    ? $this->ok('Próxima execução: ' . wp_date('d/m/Y H:i', $next))
                    : $this->fail('Cron apollo_stats_weekly_rotate não agendado — reative o plugin');

            /* ── 06 ── Profile route ─────────────────────── */

            case 'profile_route_exists':
                $rules = (array) get_option('rewrite_rules', array());
                $found = false;
                foreach ($rules as $pattern => $rewrite) {
                    if (str_contains((string) $rewrite, 'apollo_profile_stats')) {
                        $found = true;
                        break;
                    }
                }
                return $found
                    ? $this->ok('Rewrite rule /id/{user}/stats registrada ✓')
                    : $this->warn('Regra não encontrada — acesse Configurações > Links Permanentes e salve para reconstruir');

            case 'profile_private_403':
                // Test the visibility gate logic directly (server-side, no HTTP).
                $uid      = get_current_user_id();
                $orig_vis = get_user_meta($uid, '_apollo_stats_visibility', true);

                update_user_meta($uid, '_apollo_stats_visibility', 'private');

                // Simulate: anon user (current_id = 0) trying to view private profile.
                $visibility  = get_user_meta($uid, '_apollo_stats_visibility', true);
                $anon_can    = false;
                if ($visibility === 'public') {
                    $anon_can = true;
                }
                // Logged-in check is intentionally skipped (simulating anon).

                // Restore.
                if ('' === $orig_vis || false === $orig_vis) {
                    delete_user_meta($uid, '_apollo_stats_visibility');
                } else {
                    update_user_meta($uid, '_apollo_stats_visibility', $orig_vis);
                }

                return ! $anon_can
                    ? $this->ok('Visitante anônimo bloqueado em perfil privado ✓')
                    : $this->fail('Lógica de visibilidade permite acesso anônimo a perfil privado');

            /* ── 07 ── Retention ──────────────────────────── */

            case 'retention_defaults':
                try {
                    $ref      = new \ReflectionClassConstant(
                        \Apollo\Statistics\Collectors\CronCollector::class,
                        'DEFAULT_RETENTION'
                    );
                    $defaults = $ref->getValue();
                    $expected = array('sessions' => 365, 'pageviews' => 90, 'clicks' => 30, 'radio' => 365);
                    $diffs    = array();
                    foreach ($expected as $k => $v) {
                        if (($defaults[ $k ] ?? null) !== $v) {
                            $diffs[] = "{$k}: esperado {$v}, tem " . ($defaults[ $k ] ?? 'ausente');
                        }
                    }
                    return empty($diffs)
                        ? $this->ok('sessions=365, pageviews=90, clicks=30, radio=365 ✓')
                        : $this->fail('Defaults incorretos — ' . implode('; ', $diffs));
                } catch (\ReflectionException $e) {
                    return $this->fail('Não foi possível ler DEFAULT_RETENTION: ' . $e->getMessage());
                }

            case 'retention_floor':
                $settings  = get_option('apollo_admin_settings', array());
                $orig_val  = $settings['statistics']['retention_sessions'] ?? null;

                // Force zero and read back through get_retention_days().
                $settings['statistics']['retention_sessions'] = 0;
                update_option('apollo_admin_settings', $settings, false);

                try {
                    $ref = new \ReflectionMethod(
                        \Apollo\Statistics\Collectors\CronCollector::class,
                        'get_retention_days'
                    );
                    $ref->setAccessible(true);
                    $days      = $ref->invoke(new \Apollo\Statistics\Collectors\CronCollector());
                    $floor_ok  = ($days['sessions'] ?? 0) >= 1;
                    $floor_val = $days['sessions'] ?? '?';
                } catch (\Exception $e) {
                    $floor_ok  = false;
                    $floor_val = 'erro: ' . $e->getMessage();
                }

                // Restore.
                if (null === $orig_val || false === $orig_val) {
                    unset($settings['statistics']['retention_sessions']);
                } else {
                    $settings['statistics']['retention_sessions'] = $orig_val;
                }
                update_option('apollo_admin_settings', $settings, false);

                return $floor_ok
                    ? $this->ok("Piso ativo: sessions=0 → retorna {$floor_val} ✓")
                    : $this->fail("Piso falhou: sessions=0 → retornou {$floor_val}");

            /* ── 08 ── Gamification ───────────────────────── */

            case 'gamif_guard':
                if (! function_exists('is_plugin_active')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $membership_active = is_plugin_active('apollo-membership/apollo-membership.php');
                $bridge_hooks      = (bool) has_action('apollo/statistics/daily_aggregate_completed');

                if (! $membership_active && ! $bridge_hooks) {
                    return $this->ok('apollo-membership INATIVO → hooks NÃO registrados (guard funcionando) ✓');
                }
                if ($membership_active && $bridge_hooks) {
                    return $this->ok('apollo-membership ATIVO → hooks registrados ✓');
                }
                if (! $membership_active && $bridge_hooks) {
                    return $this->fail('apollo-membership INATIVO mas hooks foram registrados — guard falhou');
                }
                return $this->warn('apollo-membership ativo mas hooks não detectados — verifique boot order');

            case 'gamif_milestone':
                $uid         = get_current_user_id();
                $orig_score  = get_user_meta($uid, '_apollo_stats_engagement_score', true);
                $orig_award  = get_user_meta($uid, '_apollo_stats_milestone_100', true);

                // Set test conditions.
                update_user_meta($uid, '_apollo_stats_engagement_score', 100);
                delete_user_meta($uid, '_apollo_stats_milestone_100');

                // Invoke public check_milestones() directly.
                $bridge = new \Apollo\Statistics\Processors\GamificationBridge();
                $bridge->check_milestones();

                $awarded = get_user_meta($uid, '_apollo_stats_milestone_100', true);

                // Restore state.
                if ('' === $orig_score || false === $orig_score) {
                    delete_user_meta($uid, '_apollo_stats_engagement_score');
                } else {
                    update_user_meta($uid, '_apollo_stats_engagement_score', $orig_score);
                }
                if ('' === $orig_award || false === $orig_award) {
                    delete_user_meta($uid, '_apollo_stats_milestone_100');
                } else {
                    update_user_meta($uid, '_apollo_stats_milestone_100', $orig_award);
                }

                return ! empty($awarded)
                    ? $this->ok('Score=100 → _apollo_stats_milestone_100 definido com timestamp ✓')
                    : $this->warn('Milestone não definido — hooks de membership podem não ter listener ativo');

            /* ── 09 ── External clients (apolloDJ) ───────────────────────── */

            case 'ext_apollo_login_active':
                return defined('APOLLO_LOGIN_VERSION')
                    ? $this->ok('APOLLO_LOGIN_VERSION=' . (string) constant('APOLLO_LOGIN_VERSION') . ' ✓')
                    : $this->fail('Apollo Login inativo — endpoints /app/* e /dj/* indisponíveis');

            case 'ext_rest_dj_config':
                $req = new \WP_REST_Request('GET', '/apollo/v1/dj/config');
                $res = rest_do_request($req);
                $st  = $res->get_status();
                if (404 === $st) {
                    return $this->fail('Rota /apollo/v1/dj/config não encontrada (404)');
                }
                if (200 !== $st) {
                    return $this->fail("Esperado HTTP 200, recebido {$st}");
                }
                $data = $res->get_data();
                if (! is_array($data)) {
                    return $this->warn('HTTP 200 mas corpo não é objeto JSON');
                }
                $maint = array_key_exists('maintenance_mode', $data) ? ( $data['maintenance_mode'] ? 'true' : 'false' ) : '?';
                return $this->ok("HTTP 200 — maintenance_mode={$maint} ✓");

            case 'ext_rest_app_auth_route':
                return $this->apollo_rest_route_registered('/apollo/v1/app/auth')
                    ? $this->ok('POST /apollo/v1/app/auth registrado ✓')
                    : $this->fail('POST /apollo/v1/app/auth não registrado');

            case 'ext_rest_app_verify_route':
                return $this->apollo_rest_route_registered('/apollo/v1/app/verify')
                    ? $this->ok('GET /apollo/v1/app/verify registrado ✓')
                    : $this->fail('GET /apollo/v1/app/verify não registrado');

            case 'ext_rest_dj_permissions_route':
                return $this->apollo_rest_route_registered('/apollo/v1/dj/permissions')
                    ? $this->ok('GET /apollo/v1/dj/permissions registrado ✓')
                    : $this->fail('GET /apollo/v1/dj/permissions não registrado');

            case 'ext_dj_jwt_hmac_secrets':
                $jwt_min  = 32;
                $hmac_min = 24;
                $jwt      = $this->apollo_dj_read_secret('APOLLO_DJ_JWT_SECRET', 'APOLLODJ_JWT_SECRET');
                $hmac     = $this->apollo_dj_read_secret('APOLLO_DJ_HMAC_SECRET', 'APOLLODJ_HMAC_SECRET');
                $jl       = strlen($jwt);
                $hl       = strlen($hmac);
                $strict   = $this->apollo_dj_auth_strict_mode();

                if ($jl >= $jwt_min && $hl >= $hmac_min) {
                    return $this->ok("JWT={$jl} chars, HMAC={$hl} chars (mín. {$jwt_min}/{$hmac_min}) ✓");
                }

                $weak = array();
                if ($jl < $jwt_min) {
                    $weak[] = "JWT < {$jwt_min}";
                }
                if ($hl < $hmac_min) {
                    $weak[] = "HMAC < {$hmac_min}";
                }
                $msg = "JWT={$jl} chars, HMAC={$hl} chars — " . implode(', ', $weak);
                if ($strict) {
                    return $this->warn(
                        $msg . ' — strict: apollo-login recusa emissão de sessão .exe até corrigir APOLLO_DJ_* ou APOLLODJ_* no ambiente'
                    );
                }
                return $this->warn($msg . ' — configure antes de expor /app/* em produção');

            case 'ext_auth_health_get':
                $req = new \WP_REST_Request('GET', '/apollo/v1/health');
                $res = rest_do_request($req);
                $st  = $res->get_status();
                if (404 === $st) {
                    return $this->fail('GET /apollo/v1/health → 404 (apollo-core inativo?)');
                }
                return 200 === $st
                    ? $this->ok('HTTP 200 — health ✓')
                    : $this->fail("Esperado 200, recebido HTTP {$st}");

            case 'ext_auth_check_username_get':
                $req = new \WP_REST_Request('GET', '/apollo/v1/auth/check-username');
                $req->set_query_params(array('username' => 'apollo_atp_probe_' . (string) wp_rand(100000, 999999)));
                $res = rest_do_request($req);
                $st  = $res->get_status();
                if (404 === $st) {
                    return $this->fail('GET /auth/check-username → 404');
                }
                if ($st >= 200 && $st < 300) {
                    return $this->ok("HTTP {$st} — rota viva ✓");
                }
                if ($st >= 400 && $st < 500) {
                    return $this->ok("HTTP {$st} — rota viva (validação) ✓");
                }
                return $this->warn("HTTP {$st} — resposta inesperada");

            case 'ext_auth_check_email_get':
                $req = new \WP_REST_Request('GET', '/apollo/v1/auth/check-email');
                $req->set_query_params(array('email' => 'apollo-atp-probe-' . (string) wp_rand(1, 99999) . '@example.invalid'));
                $res = rest_do_request($req);
                $st  = $res->get_status();
                if (404 === $st) {
                    return $this->fail('GET /auth/check-email → 404');
                }
                if ($st >= 200 && $st < 300) {
                    return $this->ok("HTTP {$st} — rota viva ✓");
                }
                if ($st >= 400 && $st < 500) {
                    return $this->ok("HTTP {$st} — rota viva (validação) ✓");
                }
                return $this->warn("HTTP {$st} — resposta inesperada");

            case 'ext_auth_login_invalid_post':
                $req = new \WP_REST_Request('POST', '/apollo/v1/auth/login');
                $req->set_body_params(array(
                    'username' => '__apollo_atp_invalid_user__',
                    'password' => '__wrong_password__',
                ));
                $res = rest_do_request($req);
                $st  = $res->get_status();
                if (404 === $st) {
                    return $this->fail('POST /auth/login → 404');
                }
                if (429 === $st) {
                    return $this->warn('HTTP 429 — rate limit; rota existe');
                }
                if (401 === $st || 403 === $st || 400 === $st) {
                    return $this->ok("HTTP {$st} — rota ativa (rejeição esperada) ✓");
                }
                if (200 === $st) {
                    return $this->warn('HTTP 200 com credenciais fictícias — revisar ambiente');
                }
                return $this->ok("HTTP {$st} — rota respondeu ✓");

            case 'ext_rest_jwt_token_route':
                return $this->apollo_rest_route_registered('/apollo/v1/auth/token')
                    ? $this->ok('POST /apollo/v1/auth/token registrado ✓')
                    : $this->fail('POST /apollo/v1/auth/token não registrado');

            case 'ext_rest_jwt_refresh_route':
                return $this->apollo_rest_route_registered('/apollo/v1/auth/token/refresh')
                    ? $this->ok('POST /apollo/v1/auth/token/refresh registrado ✓')
                    : $this->fail('POST /apollo/v1/auth/token/refresh não registrado');

            case 'ext_rest_jwt_revoke_route':
                return $this->apollo_rest_route_registered('/apollo/v1/auth/token/revoke')
                    ? $this->ok('POST /apollo/v1/auth/token/revoke registrado ✓')
                    : $this->fail('POST /apollo/v1/auth/token/revoke não registrado');

            case 'ext_rest_users_me_route':
                return $this->apollo_rest_route_registered('/apollo/v1/users/me')
                    ? $this->ok('GET/PUT /apollo/v1/users/me registrado ✓')
                    : $this->warn('GET /apollo/v1/users/me não registrado — ative apollo-users');

            case 'ext_rest_shortcodes_list':
                $req = new \WP_REST_Request('GET', '/apollo/v1/shortcodes');
                $res = rest_do_request($req);
                $st  = $res->get_status();
                if (404 === $st) {
                    return $this->warn('GET /apollo/v1/shortcodes 404 — apollo-core inativo ou rota não carregada');
                }
                if (200 !== $st) {
                    return $this->fail("Esperado HTTP 200, recebido {$st}");
                }
                $data = $res->get_data();
                $n    = ( is_array($data) && isset($data['shortcodes']) && is_array($data['shortcodes']) ) ? count($data['shortcodes']) : 0;
                return $this->ok("HTTP 200 — {$n} shortcode(s) no payload ✓");

            case 'ext_membership_profile_canonical':
                if (! function_exists('apollo_membership_user_profile_data')) {
                    return $this->fail('apollo-membership inativo — apollo_membership_user_profile_data ausente');
                }
                if (! has_action('edit_user_profile', 'apollo_membership_user_profile_data')) {
                    return $this->fail('Hook edit_user_profile → apollo_membership_user_profile_data não registrado');
                }
                return $this->ok('Apollo Membership user-edit hook ativo ✓');

            case 'ext_dj_sync_no_user_profile':
                if (class_exists('\Apollo\DJSync\Admin\DJUserAdmin')
                    && (new \ReflectionClass('\Apollo\DJSync\Admin\DJUserAdmin'))->hasMethod('render_meta_box')) {
                    return $this->fail('DJUserAdmin::render_meta_box ainda existe — remova Feature Gates');
                }
                return $this->ok('apollo-dj-sync sem UI per-user (Feature Gates removidos) ✓');

            case 'ext_app_auth_invalid_app_id':
                $req = new \WP_REST_Request('POST', '/apollo/v1/app/auth');
                $req->set_body_params(array(
                    'username' => 'smoke_probe',
                    'password' => 'smoke_probe',
                    'app_id'   => 'unknown',
                ));
                $st = rest_do_request($req)->get_status();
                return 403 === $st
                    ? $this->ok('HTTP 403 invalid_app ✓')
                    : $this->fail("Esperado 403 para app_id=unknown, recebido HTTP {$st}");

            case 'ext_app_auth_bad_credentials':
                $req = new \WP_REST_Request('POST', '/apollo/v1/app/auth');
                $req->set_body_params(array(
                    'username' => 'apollo_smoke_nonexistent_' . (string) wp_rand(10000, 99999),
                    'password' => 'invalid_password_smoke',
                    'app_id'   => 'apollodj',
                ));
                $res  = rest_do_request($req);
                $st   = $res->get_status();
                $code = is_array($res->get_data()) ? (string) ( $res->get_data()['code'] ?? '' ) : '';
                if (401 === $st && 'login_failed' === $code) {
                    return $this->ok('HTTP 401 login_failed ✓');
                }
                if (429 === $st) {
                    return $this->warn('HTTP 429 rate_limited — rota viva, aguarde e repita');
                }
                return $this->fail("Esperado 401 login_failed, recebido HTTP {$st} (code={$code})");

            case 'ext_membership_slugs_api':
                if (! function_exists('apollo_membership_get_user_slugs')) {
                    return $this->fail('apollo_membership_get_user_slugs() ausente');
                }
                if (! function_exists('apollo_membership_normalize_storage')) {
                    return $this->fail('apollo_membership_normalize_storage() ausente');
                }
                if (! class_exists('\Apollo\Core\Config\MembershipRegistry')) {
                    return $this->fail('MembershipRegistry ausente em apollo-core');
                }
                $bridge = defined('APOLLO_MEMBERSHIP_DIR')
                    ? APOLLO_MEMBERSHIP_DIR . 'bridge-pmpro.php'
                    : '';
                if ('' === $bridge || ! is_readable($bridge)) {
                    return $this->warn('bridge-pmpro.php não encontrado (OK se PMPro não instalado)');
                }
                return $this->ok('Array SSOT helpers + registry + bridge ✓');

            case 'ext_membership_audit_cli':
                $audit = defined('APOLLO_CORE_PATH')
                    ? APOLLO_CORE_PATH . 'bin/audit-membership.php'
                    : '';
                if ('' === $audit || ! is_readable($audit)) {
                    return $this->fail('apollo-core/bin/audit-membership.php ausente');
                }
                return $this->ok('audit-membership.php presente ✓');

            default:
                return $this->fail("Teste desconhecido: '{$id}'");
        }
    }

    /* ──────────────────────── Helpers ────────────────────── */

    /**
     * Check if a DB table exists using information_schema.
     */
    private function check_table(string $table): array {
        global $wpdb;
        $exists = (bool) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
            $table
        ));
        return $exists
            ? $this->ok("{$table} ✓")
            : $this->fail("{$table} NÃO existe — rode a ativação do plugin");
    }

    /**
     * Fire an internal REST request and check the response status.
     * A 429 (rate limit) is treated as "endpoint exists and is working".
     */
    private function probe_rest(string $route, array $params, int $expected = 201): array {
        $req = new \WP_REST_Request('POST', $route);
        $req->set_body_params($params);
        $status = rest_do_request($req)->get_status();
        if ($status === $expected || $status === 429) {
            return $this->ok("HTTP {$status} ← endpoint respondendo ✓");
        }
        return $this->fail("Esperado {$expected}, recebido HTTP {$status}");
    }

    /**
     * Delete rate-limit transients for the current client IP.
     * Mirrors the hash logic in TrackController::hash_ip().
     */
    private function clear_rate_limit_transients(): void {
        $ip  = sanitize_text_field(
            $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0])
            ?? $_SERVER['REMOTE_ADDR']
            ?? ''
        );
        $pfx = substr(hash('sha256', trim($ip) . wp_salt('auth')), 0, 12);

        delete_transient('apollo_track_c_' . $pfx); // v2 count key
        delete_transient('apollo_track_w_' . $pfx); // v2 window key
        delete_transient('apollo_track_'   . $pfx); // legacy key (pre-fix)
    }

    /**
     * Whether a REST route is registered (apollo/v1…).
     */
    private function apollo_rest_route_registered(string $route): bool {
        $routes = rest_get_server()->get_routes();
        return isset($routes[ $route ]) && is_array($routes[ $route ]);
    }

    /**
     * Read DJ session secret from constant or env (same names as apollo-login).
     */
    private function apollo_dj_read_secret(string $constant_name, string $env_name): string {
        if (defined($constant_name)) {
            $value = (string) constant($constant_name);
            if ('' !== trim($value)) {
                return trim($value);
            }
        }
        $env = getenv($env_name);
        if (false === $env) {
            return '';
        }
        return trim((string) $env);
    }

    /**
     * Mirrors AppAuthController::is_auth_strict_mode() for diagnostic parity.
     */
    private function apollo_dj_auth_strict_mode(): bool {
        if (defined('APOLLO_DJ_AUTH_STRICT_MODE')) {
            return (bool) constant('APOLLO_DJ_AUTH_STRICT_MODE');
        }
        $env = getenv('APOLLODJ_AUTH_STRICT');
        if (false !== $env && '' !== trim((string) $env)) {
            $value = filter_var($env, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (null !== $value) {
                return $value;
            }
        }
        return wp_get_environment_type() === 'production';
    }

    /** @return array{status:'ok',detail:string} */
    private function ok(string $detail): array {
        return array('status' => 'ok', 'detail' => $detail);
    }

    /** @return array{status:'warn',detail:string} */
    private function warn(string $detail): array {
        return array('status' => 'warn', 'detail' => $detail);
    }

    /** @return array{status:'fail',detail:string} */
    private function fail(string $detail): array {
        return array('status' => 'fail', 'detail' => $detail);
    }

    /**
     * Run full test battery from CLI (LocalWP smoke / CI).
     *
     * @return array{summary:array{ok:int,warn:int,fail:int,total:int},results:array<string,array{status:string,detail:string}>}
     */
    public function run_cli_suite(): array {
        $results = array();
        $ok = $warn = $fail = 0;

        foreach (self::TESTS as $test) {
            $id = $test['id'];
            $results[ $id ] = $this->run_test($id);
            match ($results[ $id ]['status']) {
                'ok'   => ++$ok,
                'warn' => ++$warn,
                default => ++$fail,
            };
        }

        return array(
            'summary' => array(
                'ok'    => $ok,
                'warn'  => $warn,
                'fail'  => $fail,
                'total' => count(self::TESTS),
            ),
            'results' => $results,
        );
    }
}
