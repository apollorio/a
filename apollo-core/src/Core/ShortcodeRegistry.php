<?php

/**
 * Shortcode Registry — Apollo Core
 *
 * Central PSR-4 singleton that aggregates shortcode metadata for all Apollo plugins.
 * Provides documentation, conflict detection, admin reference page and editor modal.
 *
 * Migrated from apollo-shortcodes/includes/class-apollo-shortcode-registry.php (FASE 2).
 *
 * Individual plugins register their shortcode DOCS by hooking into:
 *   add_action('apollo/core/shortcodes/register', function(ShortcodeRegistry $r) { ... });
 *
 * @package Apollo\Core
 * @since   6.4.1
 */

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

class ShortcodeRegistry
{

    // ─────────────────────────────────────────────────────────────────────────
    // SINGLETON
    // ─────────────────────────────────────────────────────────────────────────

    private static ?ShortcodeRegistry $instance = null;

    public static function get_instance(): ShortcodeRegistry
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function init(): void
    {
        self::get_instance();
    }

	// ─────────────────────────────────────────────────────────────────────────
	// STATE
	// ─────────────────────────────────────────────────────────────────────────

    /** @var array<string, array> */
    private array $shortcodes = array();

    /** @var array<string, array> */
    private array $groups = array();

    /** @var array */
    private array $conflicts = array();

    // ─────────────────────────────────────────────────────────────────────────
    // CONSTRUCTOR
    // ─────────────────────────────────────────────────────────────────────────

    private function __construct()
    {
        $this->init_groups();
        $this->register_hooks();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GROUPS
    // ─────────────────────────────────────────────────────────────────────────

    private function init_groups(): void
    {
        $this->groups = array(
            'events'       => array(
                'label'       => __('Eventos', 'apollo-core'),
                'description' => __('Shortcodes para exibição de eventos.', 'apollo-core'),
                'plugin'      => 'apollo-events',
                'icon'        => 'ri-calendar-event-line',
            ),
            'social'       => array(
                'label'       => __('Social', 'apollo-core'),
                'description' => __('Shortcodes para recursos sociais.', 'apollo-core'),
                'plugin'      => 'apollo-social',
                'icon'        => 'ri-group-line',
            ),
            'marketplace'  => array(
                'label'       => __('Marketplace', 'apollo-core'),
                'description' => __('Shortcodes para anúncios e marketplace.', 'apollo-core'),
                'plugin'      => 'apollo-adverts',
                'icon'        => 'ri-store-2-line',
            ),
            'users'        => array(
                'label'       => __('Usuários', 'apollo-core'),
                'description' => __('Shortcodes de perfil e dashboard.', 'apollo-core'),
                'plugin'      => 'apollo-users',
                'icon'        => 'ri-user-line',
            ),
            'membership'   => array(
                'label'       => __('Membership', 'apollo-core'),
                'description' => __('Shortcodes de planos e assinatura.', 'apollo-core'),
                'plugin'      => 'apollo-membership',
                'icon'        => 'ri-vip-crown-line',
            ),
            'performance'  => array(
                'label'       => __('Performance', 'apollo-core'),
                'description' => __('Shortcodes para otimização de performance.', 'apollo-core'),
                'plugin'      => 'apollo-runtime',
                'icon'        => 'ri-speed-line',
            ),
            'core'         => array(
                'label'       => __('Core', 'apollo-core'),
                'description' => __('Shortcodes base do sistema.', 'apollo-core'),
                'plugin'      => 'apollo-core',
                'icon'        => 'ri-compasses-2-line',
            ),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HOOKS
    // ─────────────────────────────────────────────────────────────────────────

    private function register_hooks(): void
    {
        // Register built-in shortcodes after all plugins are loaded.
        add_action('init', array($this, 'register_builtin_shortcodes'), 5);

        // Let plugins register their own shortcode docs.
        add_action('init', array($this, 'fire_register_action'), 10);

        // Conflict detection.
        add_action('init', array($this, 'detect_conflicts'), 999);

        // Admin.
        add_action('admin_notices', array($this, 'display_conflict_notices'));
        // Priority 20: apollo-admin registers the root menu at the default 10.
        add_action('admin_menu', array($this, 'add_admin_page'), 20);
        add_action('admin_footer', array($this, 'render_shortcode_finder_modal'));
    }

	// ─────────────────────────────────────────────────────────────────────────
	// REGISTRATION
	// ─────────────────────────────────────────────────────────────────────────

    /**
     * Register shortcode documentation (metadata only — does NOT call add_shortcode()).
     *
     * @param string $tag        Shortcode tag.
     * @param array  $definition Definition array.
     */
    public function register(string $tag, array $definition): void
    {
        $defaults = array(
            'tag'         => $tag,
            'group'       => 'core',
            'label'       => $tag,
            'description' => '',
            'callback'    => null,
            'attributes'  => array(),
            'examples'    => array(),
            'supports'    => array(),
            'deprecated'  => false,
            'replacement' => '',
            'version'     => '1.0.0',
        );

        $this->shortcodes[$tag] = wp_parse_args($definition, $defaults);
    }

    /**
     * Register built-in Apollo shortcode documentation.
     * Fired on WordPress `init` at priority 5.
     */
    public function register_builtin_shortcodes(): void
    {

        // ── EVENTS ────────────────────────────────────────────────────────────

        $this->register('apollo_events', array(
            'group'       => 'events',
            'label'       => __('Lista de Eventos', 'apollo-core'),
            'description' => __('Exibe uma grade/lista/carrossel de eventos.', 'apollo-core'),
            'attributes'  => array(
                'limit'    => array('type' => 'number', 'default' => 6, 'description' => __('Número máximo de eventos.', 'apollo-core')),
                'category' => array('type' => 'string', 'default' => '', 'description' => __('Slug de categoria.', 'apollo-core')),
                'type'     => array('type' => 'string', 'default' => '', 'description' => __('Slug do tipo de evento.', 'apollo-core')),
                'sound'    => array('type' => 'string', 'default' => '', 'description' => __('Gênero musical.', 'apollo-core')),
                'orderby'  => array('type' => 'select', 'default' => 'event_date', 'options' => array('event_date', 'date', 'title', 'rand'), 'description' => __('Campo de ordenação.', 'apollo-core')),
                'order'    => array('type' => 'select', 'default' => 'ASC', 'options' => array('ASC', 'DESC'), 'description' => __('Direção.', 'apollo-core')),
                'layout'   => array('type' => 'select', 'default' => 'grid', 'options' => array('grid', 'list', 'carousel'), 'description' => __('Layout.', 'apollo-core')),
                'columns'  => array('type' => 'number', 'default' => 3, 'description' => __('Colunas (1-4).', 'apollo-core')),
                'featured' => array('type' => 'boolean', 'default' => false, 'description' => __('Apenas eventos em destaque.', 'apollo-core')),
                'upcoming' => array('type' => 'boolean', 'default' => true, 'description' => __('Apenas eventos futuros.', 'apollo-core')),
            ),
            'examples' => array('[apollo_events limit="6" layout="grid"]', '[apollo_events category="festa" sound="techno"]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_event_single', array(
            'group'       => 'events',
            'label'       => __('Evento Único', 'apollo-core'),
            'description' => __('Exibe detalhes de um evento.', 'apollo-core'),
            'attributes'  => array(
                'id'     => array('type' => 'number', 'default' => 0, 'description' => __('ID do evento.', 'apollo-core')),
                'layout' => array('type' => 'select', 'default' => 'full', 'options' => array('full', 'compact', 'hero'), 'description' => __('Layout.', 'apollo-core')),
            ),
            'examples' => array('[apollo_event_single id="123"]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_event_calendar', array(
            'group'       => 'events',
            'label'       => __('Calendário de Eventos', 'apollo-core'),
            'description' => __('Exibe eventos em formato de calendário.', 'apollo-core'),
            'attributes'  => array(
                'category' => array('type' => 'string', 'default' => '', 'description' => __('Filtrar por categoria.', 'apollo-core')),
                'type'     => array('type' => 'string', 'default' => '', 'description' => __('Filtrar por tipo.', 'apollo-core')),
                'months'   => array('type' => 'number', 'default' => 3, 'description' => __('Meses a exibir.', 'apollo-core')),
            ),
            'examples' => array('[apollo_event_calendar months="3"]'),
            'version'  => '2.0.0',
        ));

        // ── SOCIAL ────────────────────────────────────────────────────────────

        $this->register('apollo_social_feed', array(
            'group'       => 'social',
            'label'       => __('Feed Social', 'apollo-core'),
            'description' => __('Exibe um feed de atividades sociais.', 'apollo-core'),
            'attributes'  => array(
                'user_id'   => array('type' => 'number', 'default' => 0, 'description' => __('ID do usuário. 0 para feed global.', 'apollo-core')),
                'limit'     => array('type' => 'number', 'default' => 10, 'description' => __('Número de itens.', 'apollo-core')),
                'type'      => array('type' => 'select', 'default' => 'all', 'options' => array('all', 'post', 'photo', 'event', 'share'), 'description' => __('Tipo de atividade.', 'apollo-core')),
                'layout'    => array('type' => 'select', 'default' => 'timeline', 'options' => array('timeline', 'grid', 'compact'), 'description' => __('Layout.', 'apollo-core')),
                'show_form' => array('type' => 'boolean', 'default' => true, 'description' => __('Mostrar formulário de postagem.', 'apollo-core')),
            ),
            'examples' => array('[apollo_social_feed]', '[apollo_social_feed user_id="5" limit="20"]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_social_share', array(
            'group'       => 'social',
            'label'       => __('Botões de Compartilhamento', 'apollo-core'),
            'description' => __('Botões para compartilhar em redes sociais.', 'apollo-core'),
            'attributes'  => array(
                'url'      => array('type' => 'string', 'default' => '', 'description' => __('URL a compartilhar.', 'apollo-core')),
                'title'    => array('type' => 'string', 'default' => '', 'description' => __('Título a compartilhar.', 'apollo-core')),
                'networks' => array('type' => 'string', 'default' => 'facebook,twitter,whatsapp,linkedin,telegram', 'description' => __('Redes (vírgula).', 'apollo-core')),
                'style'    => array('type' => 'select', 'default' => 'icons', 'options' => array('icons', 'buttons', 'minimal'), 'description' => __('Estilo.', 'apollo-core')),
                'size'     => array('type' => 'select', 'default' => 'md', 'options' => array('sm', 'md', 'lg'), 'description' => __('Tamanho.', 'apollo-core')),
            ),
            'examples' => array('[apollo_social_share]', '[apollo_social_share networks="facebook,whatsapp"]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_user_profile', array(
            'group'       => 'users',
            'label'       => __('Perfil de Usuário', 'apollo-core'),
            'description' => __('Exibe perfil completo de um usuário.', 'apollo-core'),
            'attributes'  => array(
                'user_id'     => array('type' => 'number', 'default' => 0, 'description' => __('ID do usuário. 0 para usuário atual.', 'apollo-core')),
                'show_cover'  => array('type' => 'boolean', 'default' => true, 'description' => __('Mostrar capa.', 'apollo-core')),
                'show_bio'    => array('type' => 'boolean', 'default' => true, 'description' => __('Mostrar bio.', 'apollo-core')),
                'show_stats'  => array('type' => 'boolean', 'default' => true, 'description' => __('Mostrar estatísticas.', 'apollo-core')),
                'show_social' => array('type' => 'boolean', 'default' => true, 'description' => __('Mostrar links sociais.', 'apollo-core')),
            ),
            'examples' => array('[apollo_user_profile]', '[apollo_user_profile user_id="5"]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_profile_card', array(
            'group'       => 'users',
            'label'       => __('Card de Perfil', 'apollo-core'),
            'description' => __('Card compacto de perfil de usuário.', 'apollo-core'),
            'attributes'  => array(
                'user_id' => array('type' => 'number', 'default' => 0, 'required' => true, 'description' => __('ID do usuário.', 'apollo-core')),
                'size'    => array('type' => 'select', 'default' => 'md', 'options' => array('sm', 'md', 'lg'), 'description' => __('Tamanho.', 'apollo-core')),
            ),
            'examples' => array('[apollo_profile_card user_id="5"]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_user_dashboard', array(
            'group'       => 'users',
            'label'       => __('Painel do Usuário', 'apollo-core'),
            'description' => __('Painel com estatísticas e gerenciamento.', 'apollo-core'),
            'attributes'  => array(
                'show_events'      => array('type' => 'boolean', 'default' => true, 'description' => __('Aba de eventos.', 'apollo-core')),
                'show_classifieds' => array('type' => 'boolean', 'default' => true, 'description' => __('Aba de anúncios.', 'apollo-core')),
                'show_activity'    => array('type' => 'boolean', 'default' => true, 'description' => __('Aba de atividade.', 'apollo-core')),
            ),
            'examples' => array('[apollo_user_dashboard]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_fav_dashboard', array(
            'group'       => 'users',
            'label'       => __('Painel de Favoritos', 'apollo-core'),
            'description' => __('Lista de conteúdos favoritados pelo usuário.', 'apollo-core'),
            'attributes'  => array(
                'user_id' => array('type' => 'number', 'default' => 0, 'description' => __('ID do usuário.', 'apollo-core')),
                'limit'   => array('type' => 'number', 'default' => 12, 'description' => __('Máximo de itens.', 'apollo-core')),
            ),
            'examples' => array('[apollo_fav_dashboard]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_follow_button', array(
            'group'       => 'social',
            'label'       => __('Botão Seguir', 'apollo-core'),
            'description' => __('Botão para seguir um usuário.', 'apollo-core'),
            'attributes'  => array(
                'user_id' => array('type' => 'number', 'default' => 0, 'required' => true, 'description' => __('ID do usuário a seguir.', 'apollo-core')),
                'size'    => array('type' => 'select', 'default' => 'md', 'options' => array('sm', 'md', 'lg'), 'description' => __('Tamanho.', 'apollo-core')),
            ),
            'examples' => array('[apollo_follow_button user_id="5"]'),
            'version'  => '2.0.0',
        ));

        // ── MARKETPLACE ──────────────────────────────────────────────────────

        $this->register('apollo_classifieds', array(
            'group'       => 'marketplace',
            'label'       => __('Anúncios Classificados', 'apollo-core'),
            'description' => __('Lista de anúncios do marketplace.', 'apollo-core'),
            'attributes'  => array(
                'limit'  => array('type' => 'number', 'default' => 12, 'description' => __('Número de anúncios.', 'apollo-core')),
                'type'   => array('type' => 'select', 'default' => '', 'options' => array('', 'ticket_sell', 'rent_space', 'general'), 'description' => __('Tipo de anúncio.', 'apollo-core')),
                'layout' => array('type' => 'select', 'default' => 'grid', 'options' => array('grid', 'list'), 'description' => __('Layout.', 'apollo-core')),
            ),
            'examples' => array('[apollo_classifieds]', '[apollo_classifieds type="ticket_sell" limit="6"]'),
            'version'  => '2.0.0',
        ));

        $this->register('apollo_classified_form', array(
            'group'       => 'marketplace',
            'label'       => __('Formulário de Anúncio', 'apollo-core'),
            'description' => __('Formulário multi-step para publicar anúncios.', 'apollo-core'),
            'attributes'  => array(),
            'examples'    => array('[apollo_classified_form]'),
            'version'     => '2.0.0',
        ));

        $this->register('apollo_classifieds_sell_ticket', array(
            'group'       => 'marketplace',
            'label'       => __('Revender Ingresso', 'apollo-core'),
            'description' => __('Formulário para revenda de ingressos.', 'apollo-core'),
            'attributes'  => array(),
            'examples'    => array('[apollo_classifieds_sell_ticket]'),
            'version'     => '1.0.0',
        ));

        $this->register('apollo_classifieds_rent_space', array(
            'group'       => 'marketplace',
            'label'       => __('Anunciar Hospedagem', 'apollo-core'),
            'description' => __('Formulário para anunciar quarto, sofá ou espaço.', 'apollo-core'),
            'attributes'  => array(),
            'examples'    => array('[apollo_classifieds_rent_space]'),
            'version'     => '1.0.0',
        ));

        // ── MEMBERSHIP ───────────────────────────────────────────────────────

        $this->register('apollo_membership_button', array(
            'group'       => 'membership',
            'label'       => __('Botão de Assinatura', 'apollo-core'),
            'description' => __('Botão para assinar/cancelar plano.', 'apollo-core'),
            'attributes'  => array(
                'plan_id' => array('type' => 'number', 'default' => 0, 'required' => true, 'description' => __('ID do plano.', 'apollo-core')),
                'label'   => array('type' => 'string', 'default' => '', 'description' => __('Texto do botão.', 'apollo-core')),
            ),
            'examples' => array('[apollo_membership_button plan_id="1"]'),
            'version'  => '2.0.0',
        ));

        // ── PERFORMANCE ──────────────────────────────────────────────────────

        $this->register('apollo_rio_lazy', array(
            'group'       => 'performance',
            'label'       => __('Lazy Load', 'apollo-core'),
            'description' => __('Carrega conteúdo quando visível.', 'apollo-core'),
            'attributes'  => array(
                'placeholder' => array('type' => 'select', 'default' => 'skeleton', 'options' => array('skeleton', 'spinner', 'pulse', 'none'), 'description' => __('Placeholder.', 'apollo-core')),
                'animation'   => array('type' => 'select', 'default' => 'fade', 'options' => array('fade', 'slide', 'none'), 'description' => __('Animação.', 'apollo-core')),
            ),
            'supports' => array('enclosing'),
            'examples' => array('[apollo_rio_lazy]Conteúdo pesado[/apollo_rio_lazy]'),
            'version'  => '1.0.0',
        ));

        $this->register('apollo_rio_skeleton', array(
            'group'       => 'performance',
            'label'       => __('Skeleton Loader', 'apollo-core'),
            'description' => __('Placeholder animado durante carregamento.', 'apollo-core'),
            'attributes'  => array(
                'type'  => array('type' => 'select', 'default' => 'text', 'options' => array('text', 'card', 'content', 'avatar', 'image'), 'description' => __('Tipo.', 'apollo-core')),
                'lines' => array('type' => 'number', 'default' => 3, 'description' => __('Linhas.', 'apollo-core')),
            ),
            'examples' => array('[apollo_rio_skeleton type="card"]'),
            'version'  => '1.0.0',
        ));
    }

    /**
     * Fire `apollo/core/shortcodes/register` and back-compat `apollo_register_shortcodes`
     * so individual plugins can register their own shortcode documentation.
     */
    public function fire_register_action(): void
    {
        do_action('apollo/core/shortcodes/register', $this);
        // Back-compat with apollo-shortcodes action.
        do_action('apollo_register_shortcodes', $this);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONFLICT DETECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function detect_conflicts(): void
    {
        global $shortcode_tags;

        $this->conflicts = array();

        foreach ($this->shortcodes as $tag => $definition) {
            if (isset($shortcode_tags[$tag])) {
                $cb       = $shortcode_tags[$tag];
                $is_apollo = false;

                if (is_array($cb) && is_object($cb[0])) {
                    $is_apollo = str_contains(get_class($cb[0]), 'Apollo');
                } elseif (is_string($cb)) {
                    $is_apollo = str_starts_with($cb, 'apollo_');
                }

                if (! $is_apollo) {
                    $this->conflicts[] = array(
                        'tag'             => $tag,
                        'apollo_plugin'   => $definition['group'],
                        'conflict_source' => $this->identify_callback_source($cb),
                    );
                }
            }
        }
    }

    private function identify_callback_source(mixed $callback): string
    {
        if (is_array($callback) && is_object($callback[0])) {
            return get_class($callback[0]);
        }
        if (is_array($callback) && is_string($callback[0])) {
            return $callback[0];
        }
        if (is_string($callback)) {
            return $callback;
        }
        return __('Desconhecido', 'apollo-core');
    }

    public function display_conflict_notices(): void
    {
        if (empty($this->conflicts) || ! current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-warning is-dismissible"><p><strong>' . esc_html__('Apollo Core:', 'apollo-core') . '</strong> ' . esc_html__('Conflitos de shortcode detectados:', 'apollo-core') . '</p><ul style="margin-left:20px;list-style:disc;">';
        foreach ($this->conflicts as $c) {
            echo '<li><code>[' . esc_html($c['tag']) . ']</code> — ' . esc_html(sprintf(__('Registrado por: %s', 'apollo-core'), $c['conflict_source'])) . '</li>';
        }
        echo '</ul></div>';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GETTERS
    // ─────────────────────────────────────────────────────────────────────────

    public function get_all(): array
    {
        return $this->shortcodes;
    }

    public function get(string $tag): ?array
    {
        return $this->shortcodes[$tag] ?? null;
    }

    public function get_by_group(string $group): array
    {
        return array_filter($this->shortcodes, fn($s) => $s['group'] === $group);
    }

    public function get_groups(): array
    {
        return $this->groups;
    }

    public function get_conflicts(): array
    {
        return $this->conflicts;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOCUMENTATION GENERATION
    // ─────────────────────────────────────────────────────────────────────────

    public function generate_documentation(): string
    {
        $doc = "# Apollo Shortcodes Reference\n\n_Documentação gerada automaticamente._\n\n---\n\n";

        foreach ($this->groups as $group_key => $group) {
            $shortcodes = $this->get_by_group($group_key);
            if (empty($shortcodes)) {
                continue;
            }

            $doc .= "## {$group['label']}\n\n{$group['description']}\n\n";

            foreach ($shortcodes as $tag => $definition) {
                $doc .= "### `[{$tag}]`\n\n{$definition['description']}\n\n";

                if (! empty($definition['attributes'])) {
                    $doc .= "**Atributos:**\n\n| Atributo | Tipo | Padrão | Descrição |\n|----------|------|--------|----------|\n";
                    foreach ($definition['attributes'] as $attr_name => $attr) {
                        $default = $attr['default'] ?? '';
                        if (is_bool($default)) {
                            $default = $default ? 'true' : 'false';
                        }
                        $doc .= "| `{$attr_name}` | {$attr['type']} | `{$default}` | {$attr['description']} |\n";
                    }
                    $doc .= "\n";
                }

                if (! empty($definition['examples'])) {
                    $doc .= "**Exemplos:**\n\n";
                    foreach ($definition['examples'] as $example) {
                        $doc .= "```\n{$example}\n```\n\n";
                    }
                }

                $doc .= "---\n\n";
            }
        }

        return $doc;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN PAGE
    // ─────────────────────────────────────────────────────────────────────────

    public function add_admin_page(): void
    {
        // Moved off Tools 2026-08-25 — see apollo_admin_parent_slug().
        add_submenu_page(
            function_exists('apollo_admin_parent_slug') ? apollo_admin_parent_slug() : 'tools.php',
            __('Apollo Shortcodes', 'apollo-core'),
            __('Shortcodes', 'apollo-core'),
            'manage_options',
            'apollo-shortcodes',
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page(): void
    {
?>
        <div class="wrap">
            <h1><?php esc_html_e('Apollo Shortcodes Reference', 'apollo-core'); ?></h1>

            <?php if (! empty($this->conflicts)) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Conflitos detectados. Verifique as notificações acima.', 'apollo-core'); ?></p>
                </div>
            <?php endif; ?>

            <p>
                <a href="<?php echo esc_url(rest_url('apollo/v1/shortcodes')); ?>" target="_blank" class="button button-secondary">
                    <span class="dashicons dashicons-admin-links" style="margin-top:3px;"></span>
                    REST API
                </a>
            </p>

            <div class="nav-tab-wrapper" id="apolloScTabs" style="margin-bottom:0;">
                <?php foreach ($this->groups as $group_key => $group) : ?>
                    <?php $sc = $this->get_by_group($group_key); ?>
                    <?php if (! empty($sc)) : ?>
                        <a href="#sc-group-<?php echo esc_attr($group_key); ?>" class="nav-tab">
                            <?php echo esc_html($group['label']); ?> <span class="count">(<?php echo count($sc); ?>)</span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php foreach ($this->groups as $group_key => $group) : ?>
                <?php $sc = $this->get_by_group($group_key); ?>
                <?php if (! empty($sc)) : ?>
                    <div id="sc-group-<?php echo esc_attr($group_key); ?>" class="apollo-sc-group">
                        <h2 style="margin-top:24px;"><?php echo esc_html($group['label']); ?></h2>
                        <p class="description"><?php echo esc_html($group['description']); ?></p>

                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Shortcode', 'apollo-core'); ?></th>
                                    <th><?php esc_html_e('Descrição', 'apollo-core'); ?></th>
                                    <th><?php esc_html_e('Exemplo', 'apollo-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sc as $tag => $definition) : ?>
                                    <tr>
                                        <td><code>[<?php echo esc_html($tag); ?>]</code></td>
                                        <td><?php echo esc_html($definition['description']); ?></td>
                                        <td><?php if (! empty($definition['examples'][0])) : ?><code><?php echo esc_html($definition['examples'][0]); ?></code><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHORTCODE FINDER MODAL (Editor)
    // ─────────────────────────────────────────────────────────────────────────

    public function render_shortcode_finder_modal(): void
    {
        $screen = get_current_screen();
        if (! $screen || ! in_array($screen->base, array('post', 'page'), true)) {
            return;
        }
    ?>
        <div id="apollo-shortcode-finder" style="display:none;">
            <div class="apollo-sf-overlay"></div>
            <div class="apollo-sf-modal">
                <div class="apollo-sf-header">
                    <h2><?php esc_html_e('Apollo Shortcodes', 'apollo-core'); ?></h2>
                    <input type="search" id="apollo-sf-search" placeholder="<?php esc_attr_e('Buscar...', 'apollo-core'); ?>">
                    <button type="button" class="apollo-sf-close">&times;</button>
                </div>
                <div class="apollo-sf-body">
                    <?php foreach ($this->groups as $group_key => $group) : ?>
                        <?php $sc = $this->get_by_group($group_key); ?>
                        <?php if (! empty($sc)) : ?>
                            <div class="apollo-sf-group">
                                <h3><?php echo esc_html($group['label']); ?></h3>
                                <div class="apollo-sf-list">
                                    <?php foreach ($sc as $tag => $definition) : ?>
                                        <button type="button" class="apollo-sf-item" data-shortcode="<?php echo esc_attr($tag); ?>">
                                            <span class="apollo-sf-tag">[<?php echo esc_html($tag); ?>]</span>
                                            <span class="apollo-sf-desc"><?php echo esc_html($definition['description']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <style>
            .apollo-sf-overlay {
                position: fixed;
                inset: 0;
                background: rgba(var(--rgb-d), .5);
                z-index: 100000
            }

            .apollo-sf-modal {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #fff;
                border-radius: 8px;
                width: 600px;
                max-height: 80vh;
                overflow: hidden;
                z-index: 100001;
                box-shadow: 0 10px 40px rgba(var(--rgb-d), .2)
            }

            .apollo-sf-header {
                padding: 16px;
                border-bottom: 1px solid #ddd;
                display: flex;
                align-items: center;
                gap: 12px
            }

            .apollo-sf-header h2 {
                margin: 0;
                flex-shrink: 0
            }

            .apollo-sf-header input {
                flex: 1;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px
            }

            .apollo-sf-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 0 8px
            }

            .apollo-sf-body {
                padding: 16px;
                overflow-y: auto;
                max-height: calc(80vh - 70px)
            }

            .apollo-sf-group h3 {
                margin: 0 0 8px;
                font-size: 13px;
                text-transform: uppercase;
                color: #666
            }

            .apollo-sf-list {
                display: grid;
                gap: 8px;
                margin-bottom: 16px
            }

            .apollo-sf-item {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fff;
                cursor: pointer;
                text-align: left;
                transition: all .2s
            }

            .apollo-sf-item:hover {
                border-color: #2271b1;
                background: #f0f7fc
            }

            .apollo-sf-tag {
                font-family: monospace;
                font-weight: 600;
                color: #2271b1
            }

            .apollo-sf-desc {
                font-size: 12px;
                color: #666;
                margin-top: 4px
            }
        </style>
<?php
    }
}