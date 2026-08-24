<?php

/**
 * Apollo Plugin Registry — discovers & tracks all Apollo plugins.
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

if (! defined('ABSPATH')) {
    exit;
}

final class Registry
{

    private static ?Registry $instance = null;

    /** @var array<string, array> Registered plugins keyed by slug */
    private array $plugins = array();

    public static function get_instance(): Registry
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Initialize — auto-discover Apollo plugins
     */
    public function init(): void
    {
        $this->discover();
        add_action('init', array($this, 'refresh_active_status'), 5);
    }

    /**
     * Auto-discover all apollo-* plugin directories
     */
    private function discover(): void
    {
        $cached = get_transient('apollo_admin_registry_cache');
        if (is_array($cached) && ! empty($cached)) {
            $this->plugins = $cached;
            return;
        }

        $plugins_dir = WP_PLUGIN_DIR;
        $active      = get_option('active_plugins', array());

        // All 27 plugins from the Apollo Registry
        $registry = self::get_registry_manifest();

        foreach ($registry as $slug => $meta) {
            $main_file = $plugins_dir . '/' . $slug . '/' . $slug . '.php';
            $exists    = file_exists($main_file);
            $is_active = in_array($slug . '/' . $slug . '.php', $active, true);

            $this->plugins[$slug] = array(
                'slug'        => $slug,
                'name'        => $meta['name'],
                'description' => $meta['description'],
                'layer'       => $meta['layer'],
                'layer_name'  => $meta['layer_name'],
                'icon'        => $meta['icon'],
                'installed'   => $exists,
                'active'      => $is_active,
                'version'     => $exists ? self::get_plugin_version($main_file) : '—',
            );
        }

        set_transient('apollo_admin_registry_cache', $this->plugins, HOUR_IN_SECONDS);
    }

    /**
     * Get plugin version from file header
     */
    private static function get_plugin_version(string $file): string
    {
        if (! function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $data = get_plugin_data($file, false, false);
        return $data['Version'] ?? '0.0.0';
    }

    /**
     * Refresh active status (called on init)
     */
    public function refresh_active_status(): void
    {
        $active = get_option('active_plugins', array());
        foreach ($this->plugins as $slug => &$meta) {
            $meta['active'] = in_array($slug . '/' . $slug . '.php', $active, true);
        }
    }

    /**
     * Get all registered plugins
     */
    public function all(): array
    {
        return $this->plugins;
    }

    /**
     * Get plugins grouped by layer
     */
    public function by_layer(): array
    {
        $layers = array();
        foreach ($this->plugins as $slug => $meta) {
            $layers[$meta['layer']][] = $meta;
        }
        ksort($layers);
        return $layers;
    }

    /**
     * Get a single plugin's info
     */
    public function get(string $slug): ?array
    {
        return $this->plugins[$slug] ?? null;
    }

    /**
     * Check if plugin is active
     */
    public function is_active(string $slug): bool
    {
        return ($this->plugins[$slug]['active'] ?? false);
    }

    /**
     * Clear registry cache
     */
    public function clear_cache(): void
    {
        delete_transient('apollo_admin_registry_cache');
        $this->plugins = array();
        $this->discover();
    }

    /**
     * The complete Apollo plugin manifest — all 27 plugins.
     * Derived from apollo-registry.json.
     *
     * @return array<string, array>
     */
    public static function get_registry_manifest(): array
    {
        return array(
            // ─── L0 Foundation ──────────────────────────────────────────
            'apollo-core'            => array(
                'name'        => 'Apollo Core',
                'description' => 'Master registry — CPTs, Taxonomies (sound bridge), Meta Keys, Tables, Hooks, Utilities',
                'layer'       => 'L0',
                'layer_name'  => 'Foundation',
                'icon'        => 'dashicons-superhero-alt',
            ),
            'apollo-hardening'       => array(
                'name'        => 'Apollo Hardening',
                'description' => 'Site-blindagem — desabilita XML-RPC, REST filtering, header cleanup, login lockdown',
                'layer'       => 'L0',
                'layer_name'  => 'Foundation',
                'icon'        => 'dashicons-shield',
            ),

            // ─── L1 Auth ────────────────────────────────────────────────
            'apollo-login'           => array(
                'name'        => 'Apollo Login',
                'description' => 'Auth: 7-Step Registration, Login, Password Reset, Quiz, WP Hide Ghost, Blank Canvas',
                'layer'       => 'L1',
                'layer_name'  => 'Auth',
                'icon'        => 'dashicons-lock',
            ),
            'apollo-users'           => array(
                'name'        => 'Apollo Users',
                'description' => 'Users: Roles, Capabilities, Profile (site/id/username), Radar, Matchmaking, Author Protection',
                'layer'       => 'L1',
                'layer_name'  => 'Auth',
                'icon'        => 'dashicons-admin-users',
            ),
            'apollo-membership'      => array(
                'name'        => 'Apollo Membership',
                'description' => 'Gamification — Achievements, Badges, Points, Ranks, Open Badge v2',
                'layer'       => 'L1',
                'layer_name'  => 'Auth',
                'icon'        => 'dashicons-awards',
            ),

            // ─── L2 Content ─────────────────────────────────────────────
            'apollo-dashboard'       => array(
                'name'        => 'Apollo Dashboard',
                'description' => 'User dashboard with posts, stats, moderation queue, quick publish, activity feed',
                'layer'       => 'L2',
                'layer_name'  => 'Content',
                'icon'        => 'dashicons-dashboard',
            ),
            'apollo-shortcodes'      => array(
                'name'        => 'Apollo Shortcodes',
                'description' => 'Shortcode library + shortcode manager',
                'layer'       => 'L2',
                'layer_name'  => 'Content',
                'icon'        => 'dashicons-shortcode',
            ),
            'apollo-templates'       => array(
                'name'        => 'Apollo Templates',
                'description' => 'Template system — page templates, archive templates, blank canvas, overrides',
                'layer'       => 'L2',
                'layer_name'  => 'Content',
                'icon'        => 'dashicons-layout',
            ),
            'apollo-djs'             => array(
                'name'        => 'Apollo DJs',
                'description' => 'DJs CPT: Profile pages, social links, sound genres, carousel/slider/grid views',
                'layer'       => 'L2',
                'layer_name'  => 'Content',
                'icon'        => 'dashicons-businessman',
            ),
            'apollo-loc'             => array(
                'name'        => 'Apollo Local',
                'description' => 'Locations CPT: Geocoding, maps, nearby search, area zones',
                'layer'       => 'L2',
                'layer_name'  => 'Content',
                'icon'        => 'dashicons-location',
            ),

            // ─── L3 Social ──────────────────────────────────────────────
            'apollo-social'          => array(
                'name'        => 'Apollo Social',
                'description' => 'Activity stream, seguir/bloquear, reactions, feed, notifications sociais',
                'layer'       => 'L3',
                'layer_name'  => 'Social',
                'icon'        => 'dashicons-share',
            ),
            'apollo-chat'            => array(
                'name'        => 'Apollo Chat',
                'description' => 'Real-time messaging — DMs, group chat, typing indicator, read receipts',
                'layer'       => 'L3',
                'layer_name'  => 'Social',
                'icon'        => 'dashicons-format-chat',
            ),
            'apollo-groups'          => array(
                'name'        => 'Apollo Groups',
                'description' => 'Community groups — create, manage, invite, moderate, group activity',
                'layer'       => 'L3',
                'layer_name'  => 'Social',
                'icon'        => 'dashicons-groups',
            ),
            'apollo-comment'         => array(
                'name'        => 'Apollo Comment',
                'description' => 'Comment system — threaded, reactions, moderation, notification',
                'layer'       => 'L3',
                'layer_name'  => 'Social',
                'icon'        => 'dashicons-admin-comments',
            ),
            'apollo-notif'           => array(
                'name'        => 'Apollo Notif',
                'description' => 'Notification center — in-app, email digest, push, bell icon',
                'layer'       => 'L3',
                'layer_name'  => 'Social',
                'icon'        => 'dashicons-bell',
            ),
            'apollo-fav'             => array(
                'name'        => 'Apollo Fav',
                'description' => 'Fav system — save posts, events, users',
                'layer'       => 'L3',
                'layer_name'  => 'Social',
                'icon'        => 'dashicons-heart',
            ),
            'apollo-wow'             => array(
                'name'        => 'Apollo WOW',
                'description' => 'Reactions system — like, love, fire, support, celebrate',
                'layer'       => 'L3',
                'layer_name'  => 'Social',
                'icon'        => 'dashicons-heart',
            ),

            // ─── L4 Communication ───────────────────────────────────────
            'apollo-email'           => array(
                'name'        => 'Apollo Email',
                'description' => 'Email system — templating, SMTP config, transactional, digest, queue',
                'layer'       => 'L4',
                'layer_name'  => 'Communication',
                'icon'        => 'dashicons-email',
            ),

            // ─── L5 Documents ───────────────────────────────────────────
            'apollo-docs'            => array(
                'name'        => 'Apollo Docs',
                'description' => 'Documentation / knowledge-base system',
                'layer'       => 'L5',
                'layer_name'  => 'Documents',
                'icon'        => 'dashicons-media-document',
            ),
            'apollo-secure-upload'   => array(
                'name'        => 'Apollo Secure Upload',
                'description' => 'Secure file uploads — malware scanning, type validation, size limits',
                'layer'       => 'L5',
                'layer_name'  => 'Documents',
                'icon'        => 'dashicons-upload',
            ),
            'apollo-webp-compressor' => array(
                'name'        => 'Apollo WebP Compressor',
                'description' => 'Auto-convert uploads to WebP format, optimize images',
                'layer'       => 'L5',
                'layer_name'  => 'Documents',
                'icon'        => 'dashicons-images-alt2',
            ),

            // ─── L6 Frontend ────────────────────────────────────────────
            'apollo-hub'             => array(
                'name'        => 'Apollo Hub',
                'description' => 'Frontend hub — landing page, search, directory, homepage',
                'layer'       => 'L6',
                'layer_name'  => 'Frontend',
                'icon'        => 'dashicons-admin-home',
            ),
            'apollo-cdn'             => array(
                'name'        => 'Apollo CDN',
                'description' => 'CDN & asset management — scripts CDN queuing, optimization',
                'layer'       => 'L6',
                'layer_name'  => 'Frontend',
                'icon'        => 'dashicons-performance',
            ),

            // ─── L7 Admin ───────────────────────────────────────────────
            'apollo-admin'           => array(
                'name'        => 'Apollo Admin',
                'description' => 'Painel admin unificado — configurações de TODOS os plugins Apollo',
                'layer'       => 'L7',
                'layer_name'  => 'Admin',
                'icon'        => 'dashicons-admin-generic',
            ),
            'apollo-mod'             => array(
                'name'        => 'Apollo Mod',
                'description' => 'Content moderation — reports, queues, auto-mod, bans',
                'layer'       => 'L7',
                'layer_name'  => 'Admin',
                'icon'        => 'dashicons-hammer',
            ),
            'apollo-statistics'      => array(
                'name'        => 'Apollo Statistics',
                'description' => 'Analytics & statistics dashboard — user growth, content metrics',
                'layer'       => 'L7',
                'layer_name'  => 'Admin',
                'icon'        => 'dashicons-chart-area',
            ),
            'apollo-coauthor'        => array(
                'name'        => 'Apollo CoAuthor',
                'description' => 'Co-authorship system for all Apollo CPTs — multi-author management',
                'layer'       => 'L7',
                'layer_name'  => 'Admin',
                'icon'        => 'dashicons-groups',
            ),

            // ─── L8 Industry ────────────────────────────────────────────
            'apollo-events'          => array(
                'name'        => 'Apollo Events',
                'description' => 'Event management — create, RSVP, calendar, tickets, locations',
                'layer'       => 'L8',
                'layer_name'  => 'Industry',
                'icon'        => 'dashicons-calendar-alt',
            ),
            'apollo-adverts'         => array(
                'name'        => 'Apollo Adverts',
                'description' => 'Classified ads / marketplace listings',
                'layer'       => 'L8',
                'layer_name'  => 'Industry',
                'icon'        => 'dashicons-megaphone',
            ),
            'apollo-suppliers'       => array(
                'name'        => 'Apollo Suppliers',
                'description' => 'Supplier directory & vendor management',
                'layer'       => 'L8',
                'layer_name'  => 'Industry',
                'icon'        => 'dashicons-store',
            ),

            // ─── L9 PWA ─────────────────────────────────────────────────
            'apollo-rio'             => array(
                'name'        => 'Apollo Rio',
                'description' => 'PWA & project-specific — apollo.rio.br configuration, PWA manifest, service worker',
                'layer'       => 'L9',
                'layer_name'  => 'PWA',
                'icon'        => 'dashicons-smartphone',
            ),
        );
    }
}
