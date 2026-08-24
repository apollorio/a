<?php

/**
 * Plugin Activation — creates database tables, schedules cron, seeds defaults.
 *
 * @package Apollo\Email
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email;

if (! defined('ABSPATH')) {
    exit;
}

class Activation
{

    /**
     * Run activation routine.
     */
    public static function activate(): void
    {
        self::checkRequirements();
        self::createTables();
        self::seedDefaults();
        self::scheduleCron();
        self::seedDefaultTemplates();

        update_option('apollo_email_db_version', APOLLO_EMAIL_DB_VERSION);
        update_option('apollo_email_installed_at', current_time('mysql'));
        set_transient('apollo_email_activated', true, 30);

        flush_rewrite_rules();
    }

    /**
     * Verify system requirements.
     */
    private static function checkRequirements(): void
    {
        if (version_compare(PHP_VERSION, APOLLO_EMAIL_MIN_PHP, '<')) {
            wp_die(
                sprintf(
                    'Apollo Email requer PHP %s+. Atual: %s',
                    APOLLO_EMAIL_MIN_PHP,
                    PHP_VERSION
                ),
                'Requisito não atendido',
                array('back_link' => true)
            );
        }
    }

    /**
     * Create database tables.
     */
    private static function createTables(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'apollo_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Email Queue ──────────────────────────────────────────
        $sql_queue = "CREATE TABLE {$prefix}email_queue (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            to_email VARCHAR(255) NOT NULL,
            to_name VARCHAR(255) DEFAULT '',
            subject VARCHAR(500) NOT NULL,
            body LONGTEXT NOT NULL,
            template VARCHAR(100) DEFAULT NULL,
            template_data JSON DEFAULT NULL,
            priority INT NOT NULL DEFAULT 5,
            status ENUM('pending','processing','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
            attempts INT NOT NULL DEFAULT 0,
            max_attempts INT NOT NULL DEFAULT 3,
            scheduled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_scheduled (scheduled_at),
            KEY idx_priority (priority, scheduled_at),
            KEY idx_template (template),
            PRIMARY KEY (id)
        ) {$charset};";

        // ── Email Log ────────────────────────────────────────────
        $sql_log = "CREATE TABLE {$prefix}email_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            to_email VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            template VARCHAR(100) DEFAULT NULL,
            email_type ENUM('transactional','marketing','digest') DEFAULT 'transactional',
            status ENUM('sent','failed','bounced','opened','clicked') NOT NULL DEFAULT 'sent',
            transport VARCHAR(50) DEFAULT 'wp_mail',
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            opened_at DATETIME DEFAULT NULL,
            clicked_at DATETIME DEFAULT NULL,
            track_token VARCHAR(64) DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            meta JSON DEFAULT NULL,
            KEY idx_email (to_email),
            KEY idx_status (status),
            KEY idx_sent (sent_at),
            KEY idx_type (email_type),
            KEY idx_template (template),
            PRIMARY KEY (id)
        ) {$charset};";

        dbDelta($sql_queue);
        dbDelta($sql_log);

        // ── Cron Execution Log ───────────────────────────────────
        // ONE-WAY SYSTEM RUN: Every cron execution (email queue, task reminders,
        // digests) gets a row here with timestamp, items processed, and recipient
        // details. This is the single source of truth for "what ran, when, and
        // for whom". Each row = one cron tick.
        $sql_cron_log = "CREATE TABLE {$prefix}cron_execution_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cron_hook VARCHAR(100) NOT NULL,
            execution_start DATETIME NOT NULL,
            execution_end DATETIME DEFAULT NULL,
            duration_ms INT UNSIGNED DEFAULT 0,
            status ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
            items_processed INT UNSIGNED DEFAULT 0,
            items_failed INT UNSIGNED DEFAULT 0,
            recipients TEXT DEFAULT NULL,
            error_log TEXT DEFAULT NULL,
            meta JSON DEFAULT NULL,
            KEY idx_hook (cron_hook),
            KEY idx_start (execution_start),
            KEY idx_status (status),
            PRIMARY KEY (id)
        ) {$charset};";

        dbDelta($sql_cron_log);
    }

    /**
     * Seed default settings.
     */
    private static function seedDefaults(): void
    {
        $defaults = array(
            'from_name'        => get_bloginfo('name'),
            'from_email'       => get_bloginfo('admin_email'),
            'reply_to'         => get_bloginfo('admin_email'),
            'transport'        => 'wp_mail',
            'smtp_host'        => '',
            'smtp_port'        => 587,
            'smtp_encryption'  => 'tls',
            'smtp_username'    => '',
            'smtp_password'    => '',
            'ses_region'       => 'us-east-1',
            'ses_access_key'   => '',
            'ses_secret_key'   => '',
            'sendgrid_api_key' => '',
            'track_opens'      => true,
            'track_clicks'     => true,
            'batch_size'       => 50,
            'max_retries'      => 3,
            'brand_color'      => '#6C3BF5',
            'brand_logo'       => 'https://assets.apollo.rio.br/img/logo/logo-apollo.png',
            'footer_text'      => '© ' . gmdate('Y') . ' Apollo Rio. Todos os direitos reservados.',
            'footer_address'   => 'Rio de Janeiro, RJ — Brasil',
            'wp_mail_override' => false,
        );

        if (! get_option('apollo_email_settings')) {
            update_option('apollo_email_settings', $defaults);
        }
    }

    /**
     * Schedule cron event for queue processing.
     */
    private static function scheduleCron(): void
    {
        // Register custom interval
        add_filter(
            'cron_schedules',
            function (array $schedules): array {
                $schedules['apollo_five_minutes'] = array(
                    'interval' => 300,
                    'display'  => __('A cada 5 minutos', 'apollo-email'),
                );
                return $schedules;
            }
        );

        if (! wp_next_scheduled(APOLLO_EMAIL_CRON_HOOK)) {
            wp_schedule_event(time(), 'apollo_five_minutes', APOLLO_EMAIL_CRON_HOOK);
        }
    }

    /**
     * Force-refresh all seeded email templates in the CPT (updates existing posts).
     *
     * Call this after updating templates/emails/*.php to push
     * the latest content to the database without re-activating the plugin.
     *
     * @return array{slug: string, status: string}[] Per-slug result list.
     */
    public static function refreshAllTemplates(): array {
        $results = array();

        foreach ( self::getTemplateDefinitions() as $tpl ) {
            $results[] = self::upsertTemplateFromPhp( $tpl['slug'], $tpl );
        }

        return $results;
    }

    /**
     * Re-seed all transactional templates to the white minimal shell (v2).
     */
    public static function refreshTransactionalTemplatesV2(): void {
        if ( get_option( 'apollo_email_transactional_shell_v2' ) ) {
            return;
        }

        foreach ( self::getTemplateDefinitions() as $tpl ) {
            self::upsertTemplateFromPhp( $tpl['slug'], $tpl );
        }

        update_option( 'apollo_email_transactional_shell_v2', '1', false );
    }

    /**
     * Re-seed auth email CPTs when href placeholders were baked as http://reset_url/ etc.
     */
    public static function repairBrokenAuthTemplatesIfNeeded(): void {
        if ( get_option( 'apollo_email_cpt_href_repair_v1' ) ) {
            return;
        }

        $targets = array( 'password-reset', 'verification' );

        foreach ( self::getTemplateDefinitions() as $tpl ) {
            if ( ! in_array( $tpl['slug'], $targets, true ) ) {
                continue;
            }

            $existing = get_posts(
                array(
                    'post_type'   => 'email_aprio',
                    'name'        => $tpl['slug'],
                    'post_status' => 'any',
                    'numberposts' => 1,
                )
            );

            if ( empty( $existing ) ) {
                self::upsertTemplateFromPhp( $tpl['slug'], $tpl );
                continue;
            }

            $content = (string) $existing[0]->post_content;
            if (
                str_contains( $content, 'http://reset_url' )
                || str_contains( $content, 'http://verify_url' )
            ) {
                self::upsertTemplateFromPhp( $tpl['slug'], $tpl );
            }
        }

        update_option( 'apollo_email_cpt_href_repair_v1', '1', false );
    }

    /**
     * Replace legacy SVG / old logo assets in settings and CPT-stored templates.
     */
    public static function migrateBrandLogoToPngIfNeeded(): void {
        if ( get_option( 'apollo_email_logo_png_v1' ) ) {
            return;
        }

        $settings = get_option( 'apollo_email_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        $current = (string) ( $settings['brand_logo'] ?? '' );
        if ( function_exists( 'apollo_email_brand_logo_url' ) ) {
            $normalized = apollo_email_brand_logo_url( $current );
            if ( $normalized !== $current ) {
                $settings['brand_logo'] = $normalized;
                update_option( 'apollo_email_settings', $settings );
            }
        }

        $png_url = function_exists( 'apollo_email_default_logo_url' )
            ? apollo_email_default_logo_url()
            : 'https://assets.apollo.rio.br/img/logo/logo-apollo.png';

        $legacy_markers = array( 'apollo-s.svg', 'apollo-s-email.png', 'apollo-email-logo' );

        $posts = get_posts(
            array(
                'post_type'   => 'email_aprio',
                'post_status' => 'any',
                'numberposts' => -1,
            )
        );

        foreach ( $posts as $post ) {
            $content = (string) $post->post_content;
            $legacy  = false;

            foreach ( $legacy_markers as $marker ) {
                if ( str_contains( $content, $marker ) ) {
                    $legacy = true;
                    break;
                }
            }

            if ( ! $legacy ) {
                continue;
            }

            $def = self::findTemplateDefinition( $post->post_name );
            if ( $def ) {
                self::upsertTemplateFromPhp( $post->post_name, $def );
                continue;
            }

            $updated = str_replace(
                array(
                    'https://assets.apollo.rio.br/i/apollo-s.svg',
                    'https://assets.apollo.rio.br/i/apollo-s-email.png',
                ),
                $png_url,
                $content
            );

            if ( $updated !== $content ) {
                wp_update_post(
                    array(
                        'ID'           => (int) $post->ID,
                        'post_content' => $updated,
                    )
                );
            }
        }

        update_option( 'apollo_email_logo_png_v1', '1', false );
    }

    /**
     * Seed default email templates as email_aprio CPT posts.
     */
    private static function seedDefaultTemplates(): void
    {
        foreach ( self::getTemplateDefinitions() as $tpl ) {
            self::upsertTemplateFromPhp( $tpl['slug'], $tpl );
        }
    }

    /**
     * Insert or update a single email_aprio CPT from templates/emails/{slug}.php.
     *
     * @param string              $slug   Template slug.
     * @param array<string,mixed> $tpl    Template definition.
     * @return array{slug: string, status: string, id?: int}
     */
    private static function upsertTemplateFromPhp( string $slug, array $tpl ): array {
        $variables = isset( $tpl['variables'] ) && is_array( $tpl['variables'] ) ? $tpl['variables'] : array();
        $content   = self::getSeedTemplateContent( $slug, $variables );

        if ( '' === $content ) {
            return array( 'slug' => $slug, 'status' => 'skipped_no_file' );
        }

        $existing = get_posts(
            array(
                'post_type'   => 'email_aprio',
                'name'        => $slug,
                'post_status' => 'any',
                'numberposts' => 1,
            )
        );

        if ( ! empty( $existing ) ) {
            $post_id = (int) $existing[0]->ID;
            $result  = wp_update_post(
                array(
                    'ID'           => $post_id,
                    'post_content' => $content,
                    'post_title'   => $tpl['title'] ?? $existing[0]->post_title,
                    'post_status'  => 'publish',
                )
            );

            if ( is_wp_error( $result ) ) {
                return array( 'slug' => $slug, 'status' => 'error' );
            }

            update_post_meta( $post_id, '_email_subject', $tpl['subject'] ?? '' );
            update_post_meta( $post_id, '_email_type', $tpl['type'] ?? 'transactional' );
            update_post_meta( $post_id, '_email_variables', $variables );

            return array( 'slug' => $slug, 'status' => 'updated', 'id' => $post_id );
        }

        $post_id = wp_insert_post(
            array(
                'post_type'    => 'email_aprio',
                'post_title'   => $tpl['title'] ?? $slug,
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_content' => $content,
            )
        );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return array( 'slug' => $slug, 'status' => 'insert_error' );
        }

        update_post_meta( $post_id, '_email_subject', $tpl['subject'] ?? '' );
        update_post_meta( $post_id, '_email_type', $tpl['type'] ?? 'transactional' );
        update_post_meta( $post_id, '_email_variables', $variables );

        return array( 'slug' => $slug, 'status' => 'created', 'id' => (int) $post_id );
    }

    /**
     * Render templates/emails/{slug}.php with merge-tag placeholders for CPT storage.
     *
     * @param string   $slug      Template slug.
     * @param string[] $variables Variable names used by the template.
     * @return string
     */
    private static function getSeedTemplateContent( string $slug, array $variables = array() ): string {
        $file = APOLLO_EMAIL_PATH . 'templates/emails/' . $slug . '.php';

        if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
            return '';
        }

        $data = array();
        foreach ( $variables as $variable ) {
            $data[ $variable ] = '{{' . $variable . '}}';
        }

        // Aliases used by some PHP templates.
        if ( in_array( 'verify_url', $variables, true ) ) {
            $data['confirmation_link'] = '{{verify_url}}';
        }
        if ( in_array( 'reset_url', $variables, true ) ) {
            $data['confirmation_link'] = '{{reset_url}}';
        }
        if ( in_array( 'action_url', $variables, true ) ) {
            $data['action_url'] = '{{action_url}}';
        }
        if ( in_array( 'profile_url', $variables, true ) ) {
            $data['profile_url'] = '{{profile_url}}';
        }
        if ( in_array( 'event_url', $variables, true ) ) {
            $data['event_url'] = '{{event_url}}';
        }
        if ( in_array( 'task_url', $variables, true ) ) {
            $data['task_url'] = '{{task_url}}';
        }
        if ( in_array( 'site_url', $variables, true ) ) {
            $data['site_url'] = '{{site_url}}';
        }

        $GLOBALS['apollo_email_seeding'] = true;

        extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

        ob_start();
        include $file;
        $content = (string) ob_get_clean();

        unset( $GLOBALS['apollo_email_seeding'] );

        return $content;
    }

    /**
     * Default template definitions keyed by slug (matches templates/emails/*.php).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getTemplateDefinitions(): array {
        return array(
            array(
                'slug'      => 'welcome',
                'title'     => 'Boas-vindas',
                'subject'   => 'Bem-vindo(a) ao {{site_name}}! 🎉',
                'type'      => 'transactional',
                'variables' => array( 'user_name', 'username', 'profile_url', 'site_name', 'site_url' ),
            ),
            array(
                'slug'      => 'password-reset',
                'title'     => 'Recuperar Senha',
                'subject'   => 'Sua nova chave de acesso — {{site_name}}',
                'type'      => 'transactional',
                'variables' => array( 'user_name', 'reset_url', 'site_name', 'expires_in' ),
            ),
            array(
                'slug'      => 'verification',
                'title'     => 'Verificação de Email',
                'subject'   => 'Verifique seu email — {{site_name}}',
                'type'      => 'transactional',
                'variables' => array( 'user_name', 'verify_url', 'site_name' ),
            ),
            array(
                'slug'      => 'notification',
                'title'     => 'Notificação Geral',
                'subject'   => '{{title}} — {{site_name}}',
                'type'      => 'transactional',
                'variables' => array( 'user_name', 'title', 'message', 'action_url', 'action_text', 'site_name' ),
            ),
            array(
                'slug'      => 'event-reminder',
                'title'     => 'Lembrete de Evento',
                'subject'   => 'Lembrete: {{event_title}} é hoje! 🎶',
                'type'      => 'transactional',
                'variables' => array( 'user_name', 'event_title', 'event_url', 'event_date', 'event_time', 'loc_name', 'site_name' ),
            ),
            array(
                'slug'      => 'digest',
                'title'     => 'Resumo Semanal',
                'subject'   => 'Seu resumo semanal — {{site_name}}',
                'type'      => 'digest',
                'variables' => array( 'user_name', 'notifications', 'site_name', 'site_url' ),
            ),
            array(
                'slug'      => 'task-deadline',
                'title'     => 'Lembrete de Tarefa',
                'subject'   => 'Lembrete: {{task_name}} — {{site_name}}',
                'type'      => 'transactional',
                'variables' => array( 'user_name', 'task_name', 'task_date', 'task_deadline_label', 'task_priority', 'task_project', 'task_project_url', 'task_assigned_by', 'assigned_by_url', 'task_url', 'site_name', 'site_url', 'current_year' ),
            ),
        );
    }

    /**
     * Find a template definition by slug.
     */
    private static function findTemplateDefinition( string $slug ): ?array {
        foreach ( self::getTemplateDefinitions() as $tpl ) {
            if ( $tpl['slug'] === $slug ) {
                return $tpl;
            }
        }

        return null;
    }
}
