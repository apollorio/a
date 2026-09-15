<?php

/**
 * Apollo Core - Helper Functions
 *
 * Global helper functions available across all Apollo plugins.
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CORE CHECK
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_core')) {
    /**
     * Check if Apollo Core is active
     */
    function apollo_core(): bool
    {
        return defined('APOLLO_CORE_VERSION');
    }
}

if (! function_exists('apollo_is_plugin_active')) {
    /**
     * Check if an Apollo plugin is active
     */
    function apollo_is_plugin_active(string $slug): bool
    {
        $active_plugins = get_option('active_plugins', array());
        return in_array($slug . '/' . $slug . '.php', $active_plugins, true);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// AUDIT LOGGING
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_audit_log_table_ready')) {
    /**
     * Whether the apollo_audit_log table exists (cached for the current HTTP request).
     */
    function apollo_audit_log_table_ready(): bool
    {
        static $ready = null;

        if (null !== $ready) {
            return $ready;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'apollo_audit_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $ready = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table);

        return $ready;
    }
}

if (! function_exists('apollo_log_audit')) {
    /**
     * Log an audit event
     *
     * @param string      $action      Action identifier (e.g., 'user:login', 'post:created')
     * @param string|null $object_type Object type (e.g., 'user', 'event', 'dj')
     * @param int|null    $object_id   Object ID
     * @param array       $details     Additional details as associative array
     */
    function apollo_log_audit(string $action, ?string $object_type = null, ?int $object_id = null, array $details = array()): void
    {
        if (! apollo_audit_log_table_ready()) {
            return;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'apollo_audit_log';

        $wpdb->insert(
            $table,
            array(
                'action'      => $action,
                'object_type' => $object_type,
                'object_id'   => $object_id,
                'user_id'     => get_current_user_id() ?: null,
                'user_ip'     => apollo_get_client_ip(),
                'user_agent'  => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
                'details'     => ! empty($details) ? wp_json_encode($details) : null,
                'created_at'  => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// IP ADDRESS HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_get_client_ip')) {
    /**
     * Get client IP address (respects proxy headers)
     */
    function apollo_get_client_ip(): string
    {
        $ip = '';

        // Check for proxy headers
        $headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ($headers as $header) {
            if (! empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                break;
            }
        }

        // Handle comma-separated IPs (X-Forwarded-For)
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip  = trim($ips[0]);
        }

        // Validate IP
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        return $ip;
    }
}

if (! function_exists('apollo_hash_ip')) {
    /**
     * Hash IP for privacy-safe storage
     */
    function apollo_hash_ip(string $ip): string
    {
        return wp_hash($ip . wp_salt('auth'));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// TOKEN GENERATION
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_generate_token')) {
    /**
     * Generate a secure random token
     */
    function apollo_generate_token(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}

if (! function_exists('apollo_generate_short_code')) {
    /**
     * Generate a short numeric/alphanumeric code
     */
    function apollo_generate_short_code(int $length = 6, bool $numeric_only = true): string
    {
        if ($numeric_only) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= random_int(0, 9);
            }
            return $code;
        }

        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code  = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// USER HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_get_user_display_role')) {
    /**
     * Get Apollo display role for user
     */
    function apollo_get_user_display_role(int $user_id = 0): string
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return 'guest';
        }

        $user = get_userdata($user_id);

        if (! $user) {
            return 'guest';
        }

        $roles = $user->roles;

        if (in_array('administrator', $roles, true)) {
            return 'apollo';
        }
        if (in_array('editor', $roles, true)) {
            return 'MOD';
        }
        if (in_array('author', $roles, true)) {
            return 'cena+';
        }
        if (in_array('contributor', $roles, true)) {
            return 'cena';
        }
        if (in_array('subscriber', $roles, true)) {
            return 'clubber';
        }

        return 'guest';
    }
}

if (! function_exists('apollo_membership_registry')) {
    /**
     * Membership slug registry accessor (static utility — do not instantiate).
     *
     * @return class-string<\Apollo\Core\Config\MembershipRegistry>
     */
    function apollo_membership_registry(): string
    {
        return \Apollo\Core\Config\MembershipRegistry::class;
    }
}

if (! function_exists('apollo_membership_assign')) {
    /**
     * Assign membership slugs via apollo-membership (only allowed write path).
     *
     * @param int               $user_id User ID.
     * @param array<int,string> $slugs   [profile_badge, ...access_slugs].
     * @return bool
     */
    function apollo_membership_assign(int $user_id, array $slugs): bool
    {
        if (! function_exists('apollo_membership_set_user_slugs')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                _doing_it_wrong(
                    __FUNCTION__,
                    'apollo-membership must be active to assign membership slugs.',
                    '6.1.0'
                );
            }
            return false;
        }

        return apollo_membership_set_user_slugs($user_id, $slugs, 0);
    }
}

if (! function_exists('apollo_get_membership_badge')) {
    /**
     * Get user's primary profile membership badge slug.
     */
    function apollo_get_membership_badge(int $user_id = 0): string
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return 'nao-verificado';
        }

        if (function_exists('apollo_membership_get_user_badge')) {
            return apollo_membership_get_user_badge($user_id);
        }

        $raw = get_user_meta($user_id, '_apollo_membership', true);

        if (is_array($raw) && ! empty($raw)) {
            return sanitize_key((string) $raw[0]);
        }

        if (is_string($raw) && '' !== $raw) {
            return \Apollo\Core\Config\MembershipRegistry::normalize_slug($raw);
        }

        return 'nao-verificado';
    }
}

if (! function_exists('apollo_get_form_level')) {
    /**
     * Get user's profile completion level
     */
    function apollo_get_form_level(int $user_id = 0): int
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }
        return (int) get_user_meta($user_id, '_apollo_profile_completed', true);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SOUND/TAXONOMY HELPERS (Critical for matchmaking)
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_get_sounds')) {
    /**
     * Get all sound/music genre terms
     */
    function apollo_get_sounds(bool $hierarchical = true): array
    {
        if (! class_exists('\Apollo\Core\TaxonomyRegistry')) {
            return array();
        }
        return \Apollo\Core\TaxonomyRegistry::get_sounds_for_matchmaking();
    }
}

if (! function_exists('apollo_get_user_sound_preferences')) {
    /**
     * Get user's sound preferences (term IDs)
     */
    function apollo_get_user_sound_preferences(int $user_id = 0): array
    {
        if (! $user_id) {
            $user_id = get_current_user_id();
        }

        if (! $user_id) {
            return array();
        }

        $prefs = get_user_meta($user_id, '_apollo_sound_preferences', true);

        return is_array($prefs) ? $prefs : array();
    }
}

if (! function_exists('apollo_set_user_sound_preferences')) {
    /**
     * Set user's sound preferences
     */
    function apollo_set_user_sound_preferences(int $user_id, array $term_ids): bool
    {
        // Validate term IDs exist
        $valid_ids = array();
        foreach ($term_ids as $id) {
            $term = get_term((int) $id, 'sound');
            if ($term && ! is_wp_error($term)) {
                $valid_ids[] = (int) $id;
            }
        }

        return (bool) update_user_meta($user_id, '_apollo_sound_preferences', $valid_ids);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// REGISTRY HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_get_cpt_registry')) {
    /**
     * Get CPT Registry instance
     */
    function apollo_get_cpt_registry(): ?\Apollo\Core\CPTRegistry
    {
        if (! class_exists('\Apollo\Core\CPTRegistry')) {
            return null;
        }
        return \Apollo\Core\CPTRegistry::get_instance();
    }
}

if (! function_exists('apollo_get_taxonomy_registry')) {
    /**
     * Get Taxonomy Registry instance
     */
    function apollo_get_taxonomy_registry(): ?\Apollo\Core\TaxonomyRegistry
    {
        if (! class_exists('\Apollo\Core\TaxonomyRegistry')) {
            return null;
        }
        return \Apollo\Core\TaxonomyRegistry::get_instance();
    }
}

if (! function_exists('apollo_get_meta_registry')) {
    /**
     * Get Meta Registry instance
     */
    function apollo_get_meta_registry(): ?\Apollo\Core\MetaRegistry
    {
        if (! class_exists('\Apollo\Core\MetaRegistry')) {
            return null;
        }
        return \Apollo\Core\MetaRegistry::get_instance();
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// REST API HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_rest_response')) {
    /**
     * Create standardized REST response
     */
    function apollo_rest_response(array $data, int $status = 200): \WP_REST_Response
    {
        return new \WP_REST_Response(
            array(
                'success' => $status >= 200 && $status < 300,
                'data'    => $data,
            ),
            $status
        );
    }
}

if (! function_exists('apollo_rest_error')) {
    /**
     * Create standardized REST error response
     */
    function apollo_rest_error(string $code, string $message, int $status = 400, array $data = array()): \WP_Error
    {
        return new \WP_Error($code, $message, array_merge(array('status' => $status), $data));
    }
}

require_once __DIR__ . '/document-head.php';

// ═══════════════════════════════════════════════════════════════════════════
// CDN HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_cdn_q')) {
    /**
     * Cache-bust query for cdn.apollo.rio.br assets (change in constants.php + core.js CDN_Q).
     */
    function apollo_cdn_q(): string
    {
        return defined('APOLLO_CDN_Q') ? APOLLO_CDN_Q : 'v=Random.3.0';
    }
}

if (! function_exists('apollo_cdn_is_local_dev')) {
    /**
     * True on Local Sites / localhost — appends &dev=local to core.js and loads disk assets.
     * Override: define('APOLLO_CDN_DEV_LOCAL', false) in wp-config to force remote CDN on .local.
     */
    function apollo_cdn_is_local_dev(): bool
    {
        if (defined('APOLLO_CDN_DEV_LOCAL')) {
            return (bool) APOLLO_CDN_DEV_LOCAL;
        }
        if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
            return true;
        }
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        if ($host !== '' && preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host)) {
            return true;
        }
        if ($host !== '' && preg_match('/\.local(:\d+)?$/', $host)) {
            return true;
        }
        return defined('WP_LOCAL_DEV') && WP_LOCAL_DEV;
    }
}

if (! function_exists('apollo_cdn_local_root_url')) {
    /**
     * Web URL to wp-content/plugins/_dev web/cdn.apollo.rio.br/v1.0.0/ for core.js local mode.
     */
    function apollo_cdn_local_root_url(): string
    {
        if (defined('APOLLO_CDN_LOCAL_ROOT')) {
            return trailingslashit((string) APOLLO_CDN_LOCAL_ROOT);
        }
        $disk = WP_CONTENT_DIR . '/plugins/_dev web/cdn.apollo.rio.br/v1.0.0/';
        if (! is_dir($disk)) {
            return '';
        }
        return trailingslashit(content_url('plugins/_dev web/cdn.apollo.rio.br/v1.0.0'));
    }
}

if (! function_exists('apollo_cdn_suffix')) {
    /**
     * Cache-bust suffix query for core.js (sync with APOLLO_CDN_SUFFIX + core.js CDN_SUFFIX).
     */
    function apollo_cdn_suffix(): string
    {
        return defined('APOLLO_CDN_SUFFIX') ? (string) APOLLO_CDN_SUFFIX : '000';
    }
}

if (! function_exists('apollo_cdn_append_query')) {
    /**
     * Append one query fragment to a URL, once.
     *
     * Small, but it exists so the append rule is declared in a single place: three call
     * sites in apollo_cdn_core_js_url() previously each re-implemented "is there a ? yet,
     * and is this fragment already present" — and the copies had already drifted apart.
     *
     * @since 6.4.5
     * @param string $url      Absolute or relative URL.
     * @param string $fragment Query fragment such as 'v=t0x7'. Leading ? or & is tolerated.
     * @return string
     */
    function apollo_cdn_append_query(string $url, string $fragment): string
    {
        $fragment = ltrim(trim($fragment), '?&');

        if ($fragment === '' || strpos($url, $fragment) !== false) {
            return $url;
        }

        return $url . (strpos($url, '?') !== false ? '&' : '?') . $fragment;
    }
}

if (! function_exists('apollo_cdn_core_js_url')) {
    /**
     * Full URL to Apollo CDN core.js with cache-bust (+ dev=local + local disk on Local Sites).
     */
    function apollo_cdn_core_js_url(): string
    {
        $q       = apollo_cdn_q();
        $suffix  = 'suffix=' . rawurlencode(apollo_cdn_suffix());
        $is_local = apollo_cdn_is_local_dev();

        if ($is_local) {
            $root = apollo_cdn_local_root_url();
            if ($root !== '' && defined('APOLLO_CDN_FORCE_LOCAL') && APOLLO_CDN_FORCE_LOCAL) {
                return $root . 'core.js?vers=d3v&dev=local&versao=bb';
            }
        }

        if (defined('APOLLO_CDN_CORE_JS') && ! $is_local) {
            $url = APOLLO_CDN_CORE_JS;
            // Ensure cache-bust flag versao=bb is always present.
            if (strpos($url, 'versao=') === false) {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . 'versao=bb';
            }

            // ── The cache-bust used to stop here, and that was the bug. Fixed 2026-08-25.
            //
            //    constants.php says of APOLLO_CDN_Q: "Bump one character to bust CDN/browser
            //    cache." For core.js that was never true. APOLLO_CDN_CORE_JS is always
            //    defined, so this branch always wins, and it returned a URL that is byte-for-
            //    byte identical on every request: the fixed literal ending in ?versao=bb.
            //    $q and $suffix were computed at the top of this function and then thrown
            //    away — $suffix was never used in ANY branch.
            //
            //    core.js is not a small asset to get wrong. It injects the entire design
            //    system: ~31 KB of inline CSS, 160+ tokens including --safe-* and the whole
            //    neutral ramp, three Google families and the Has The Right face. The site's
            //    root .htaccess caches JavaScript for a year. A returning visitor could
            //    therefore be handed a year-old token set while today's markup assumes the
            //    current one — which does not fail loudly, it just renders slightly wrong
            //    forever. Bumping APOLLO_CDN_Q now actually reaches the browser.
            //
            //    Verified against the live CDN before shipping: it serves the file normally
            //    with these extra parameters present.
            $url = apollo_cdn_append_query($url, $q);
            $url = apollo_cdn_append_query($url, $suffix);

            return $url;
        }

        $url = 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb';
        if ($q !== '' && strpos($url, $q) === false) {
            $url .= '&' . ltrim($q, '?&');
        }
        if ($is_local && strpos($url, 'dev=local') === false && defined('APOLLO_CDN_FORCE_LOCAL') && APOLLO_CDN_FORCE_LOCAL) {
            $url .= '&dev=local';
        }
        return $url;
    }
}

if (! function_exists('apollo_cdn_url')) {
    /**
     * Get CDN URL for an asset
     */
    function apollo_cdn_url(string $path): string
    {
        $cdn_enabled = get_option('apollo_cdn_enabled', true);
        $cdn_url     = defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';

        if (! $cdn_enabled) {
            return plugins_url('assets/' . ltrim($path, '/'), APOLLO_CORE_FILE);
        }

        return trailingslashit($cdn_url) . ltrim($path, '/');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SANITIZATION HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_sanitize_username')) {
    /**
     * Sanitize username with Apollo rules
     */
    function apollo_sanitize_username(string $username): string
    {
        $username = strtolower($username);
        $username = preg_replace('/[^a-z0-9_]/', '', $username);
        $username = substr($username, 0, 20);
        return $username;
    }
}

if (! function_exists('apollo_sanitize_phone')) {
    /**
     * Sanitize phone number (Brazilian format)
     */
    function apollo_sanitize_phone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 11) {
            $phone = '55' . $phone;
        } elseif (strlen($phone) === 10) {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// DATE/TIME HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_format_date')) {
    /**
     * Format date for Brazilian locale
     */
    function apollo_format_date(string $date, string $format = 'd/m/Y'): string
    {
        $timestamp = strtotime($date);
        return $timestamp ? date_i18n($format, $timestamp) : '';
    }
}

if (! function_exists('apollo_format_datetime')) {
    /**
     * Format datetime for Brazilian locale
     */
    function apollo_format_datetime(string $datetime, string $format = 'd/m/Y H:i'): string
    {
        $timestamp = strtotime($datetime);
        return $timestamp ? date_i18n($format, $timestamp) : '';
    }
}

if (! function_exists('apollo_time_ago')) {
    /**
     * Apollo standard compact time-ago string.
     *
     * Returns a numeric abbreviation with NO "atrás" suffix.
     * Unit abbreviations: s=seconds, min=minutes, h=hours,
     *                     d=days, w=weeks, y=years.
     *
     * Examples: "53min", "2h", "7d", "3w", "1y", "0s"
     *
     * For the full HTML block with icon + spans use apollo_time_ago_html().
     *
     * @param string $datetime MySQL datetime or any strtotime-compatible string.
     * @return string Numeric abbreviation.
     */
    function apollo_time_ago(string $datetime): string
    {
        if (empty($datetime)) {
            return '';
        }
        $diff = max(0, (int) current_time('timestamp') - (int) strtotime($datetime));
        if ($diff < 60) {
            return $diff . 's';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'min';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . 'd';
        }
        if ($diff < 31536000) {
            return floor($diff / 604800) . 'w';
        }
        return floor($diff / 31536000) . 'y';
    }
}

if (! function_exists('apollo_time_ago_html')) {
    /**
     * Apollo standard time-ago HTML block.
     *
     * Output format (classes only — never duplicate IDs):
     *   <i class="tempo-v"></i>&nbsp;<span class="time-ago">N</span><span class="when-ago">unit</span>
     *
     * Icon: tempo-v  — clock icon from Apollo CDN icon set.
     * time-ago span  — the numeric value (e.g. 53, 2, 7).
     * when-ago span  — the unit abbreviation (min, h, d, w, y, s).
     *
     * Usage in templates: echo wp_kses_post( apollo_time_ago_html( $datetime ) );
     *
     * @param string $datetime MySQL datetime or any strtotime-compatible string.
     * @return string Safe HTML string.
     */
    function apollo_time_ago_html(string $datetime): string
    {
        $str = apollo_time_ago($datetime);
        if ('' === $str) {
            return '';
        }
        preg_match('/^(\d+)(\w+)$/', $str, $m);
        $num  = esc_html($m[1] ?? $str);
        $unit = esc_html($m[2] ?? '');
        // data-timestamp enables ApolloTextFX.initTimeAgoRefresh() to auto-update the display.
        $ts   = esc_attr($datetime);
        return '<span class="time-ago-wrap" data-timestamp="' . $ts . '">'
             . '<i class="tempo-v"></i>&nbsp;'
             . '<span class="time-ago">' . $num . '</span>'
             . '<span class="when-ago">' . $unit . '</span>'
             . '</span>';
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SETTINGS HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_get_setting')) {
    /**
     * Get Apollo setting
     */
    function apollo_get_setting(string $key, $default = null)
    {
        return get_option('apollo_' . $key, $default);
    }
}

if (! function_exists('apollo_update_setting')) {
    /**
     * Update Apollo setting
     */
    function apollo_update_setting(string $key, $value): bool
    {
        return update_option('apollo_' . $key, $value);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// DEBUG HELPERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_debug_log')) {
    /**
     * Log debug message (only if APOLLO_DEBUG is true)
     */
    function apollo_debug_log(string $message, array $context = array()): void
    {
        if (! defined('APOLLO_DEBUG') || ! APOLLO_DEBUG) {
            return;
        }

        $log_message = '[Apollo Debug] ' . $message;

        if (! empty($context)) {
            $log_message .= ' | Context: ' . wp_json_encode($context);
        }

        error_log($log_message);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN MENU PARENT — one declaration, so every Apollo screen lands in one place
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_admin_parent_slug')) {
    /**
     * The slug every Apollo admin screen should hang off.
     *
     * Before 2026-08-25 there was no answer to this question, so each plugin
     * guessed. Three screens guessed `tools.php` — Apollo Health, Apollo
     * Shortcodes and Apollo Modelo — which is how an operator ended up hunting
     * for the ecosystem's own health report under WordPress's Tools menu while
     * every other Apollo screen sat under Apollo. Health is the screen the plans
     * keep asking for and it was the hardest one to find.
     *
     * Returns `apollo` once apollo-admin has registered the root menu, and falls
     * back to `tools.php` when it has not — a deactivated apollo-admin should
     * hide a screen from its usual place, never delete it from the admin.
     *
     * CALLERS MUST HOOK admin_menu AT PRIORITY 20 OR LATER. apollo-admin registers
     * the root at the default 10; asking this question at 10 races it and the
     * answer is then wrong in a way nothing reports.
     *
     * @since 6.4.4
     * @return string 'apollo' or 'tools.php'
     */
    function apollo_admin_parent_slug(): string
    {
        return isset($GLOBALS['admin_page_hooks']['apollo']) ? 'apollo' : 'tools.php';
    }
}
