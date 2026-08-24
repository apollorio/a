<?php

/**
 * Apollo Firewall — 7G SQL/Script Injection Protection
 *
 * Adapted from WP Ghost (Hide My WP) 7G Firewall logic.
 * Blocks malicious requests: SQL injection, script injection,
 * directory traversal, bad user agents, theme detectors, referrer abuse.
 *
 * @package Apollo\Login\Security
 */

declare(strict_types=1);

namespace Apollo\Login\Security;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Firewall class
 */
class Firewall
{


    /**
     * Malicious User Agents (hackers, scanners, theme detectors)
     *
     * @var string[]
     */
    private array $bad_user_agents = array(
        'nikto',
        'masscan',
        'sqlmap',
        'nmap',
        'openvas',
        'nessus',
        'skipfish',
        'w3af',
        'arachni',
        'grabber',
        'burpsuite',
        'autohttp',
        'libwww-perl',
        'lwp-request',
        'lwp-trivial',
        'wget',
        'python-requests',
        'python-urllib',
        'pycurl',
        'go-http-client',
        'zgrab',
        'nuclei',
        'gobuster',
        'dirbuster',
        'wpthemedetector',
        'builtwith',
        'isitwp',
        'wappalyzer',
        'whatcms',
        'gochyu',
        'wpdetector',
        'scanwp',
        'cmsdetect',
        // Legitimate SEO crawlers—removed from blocklist (semrushbot, dotbot, mj12bot, ahrefsbot, blexbot).
    );

    /**
     * IP blacklist transient key
     *
     * @var string
     */
    private const IP_BLACKLIST_KEY = 'apollo_firewall_blacklisted_ips';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Run as early as possible — before WordPress routing
        add_action('plugins_loaded', array($this, 'run'), 1);

        // Admin: allow manual IP blacklist management
        add_action('wp_ajax_apollo_blacklist_ip', array($this, 'ajax_blacklist_ip'));
        add_action('wp_ajax_apollo_unblacklist_ip', array($this, 'ajax_unblacklist_ip'));
    }

    /**
     * Run firewall checks on every request
     *
     * @return void
     */
    public function run(): void
    {
        // Skip for CLI / cron / REST API
        if (PHP_SAPI === 'cli' || (defined('DOING_CRON') && DOING_CRON)) {
            return;
        }

        // Skip for REST API requests (REST_REQUEST constant is defined after plugins_loaded)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if ( false !== strpos( $uri, '/wp-json/' ) || ! empty( $_GET['rest_route'] ) ) {
            return;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        $ip         = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $query_str  = $_SERVER['QUERY_STRING'] ?? '';
        $request    = $_SERVER['REQUEST_URI'] ?? '';
        $referrer   = $_SERVER['HTTP_REFERER'] ?? '';
        $method     = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // 1 — IP Blacklist
        if ($this->is_ip_blacklisted($ip)) {
            $this->block('IP Blacklist');
        }

        // 2 — Bad User Agents
        if ($user_agent && $this->is_bad_user_agent($user_agent)) {
            $this->block('Bad User Agent');
        }

        // 3 — Theme detector bots
        if ($user_agent && $this->is_theme_detector($user_agent)) {
            $this->block('Theme Detector');
        }

        // 4 — Bad Referrers (spam referrers)
        if ($referrer && $this->is_bad_referrer($referrer)) {
            $this->block('Bad Referrer');
        }

        // 5 — 7G Firewall: User Agent checks
        if ($user_agent) {
            if (
                preg_match('/([a-z0-9]{2000,})/i', $user_agent) ||
                preg_match('/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i', $user_agent) ||
                preg_match('/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i', $user_agent)
            ) {
                $this->block('7G Firewall (User-Agent)');
            }
        }

        // 6 — 7G Firewall: Query String checks
        if ($query_str) {
            $this->check_query_string_7g($query_str);
        }

        // 7 — 7G Firewall: Request URI checks
        if ($request) {
            $this->check_request_uri_7g($request);
        }

        // 8 — Block direct wp-content/plugins and wp-content/themes directory browsing
        $this->block_directory_browsing($request);
    }

    /**
     * 7G firewall rules on query string
     *
     * @param string $qs
     * @return void
     */
    private function check_query_string_7g(string $qs): void
    {
        if (
            preg_match('/([a-z0-9]{2000,})/i', $qs) ||
            preg_match('/(\/|%2f)(:|%3a)(\/|%2f)/i', $qs) ||
            preg_match('/order(\s|%20)+by(\s|%20)*[0-9]+(--)?/i', $qs) ||
            preg_match('/(\/|%2f)(\*|%2a)(\*|%2a)(\/|%2f)/i', $qs) ||
            preg_match('/(ckfinder|fckeditor|fullclick)/i', $qs) ||
            preg_match('/(`|<|>|\^|\|\\|0x00|%00|%0d%0a)/i', $qs) ||
            preg_match('/((.*)header:|(.*)set-cookie:(.*)=)/i', $qs) ||
            preg_match('/(localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1)/i', $qs) ||
            preg_match('/(cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20)/i', $qs) ||
            preg_match('/(globals|mosconfig([a-z_]{1,22})|request)(=|\[)/i', $qs) ||
            preg_match('/(\/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php)/i', $qs) ||
            preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i', $qs) ||
            preg_match('/(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)/i', $qs) ||
            preg_match('/(s)?(ftp|inurl|php)(s)?(:(%2f|%u2215)(%2f|%u2215))/i', $qs) ||
            preg_match('/((boot|win)((\.|%2e)ini)|etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $qs) ||
            preg_match('/(benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29)/i', $qs) ||
            preg_match('/(<|%3C).*script.*(>|%3E)/i', $qs) ||
            preg_match('/(<|%3C).*iframe.*(>|%3E)/i', $qs) ||
            preg_match('/(<|%3C).*object.*(>|%3E)/i', $qs) ||
            preg_match('/(<|%3C).*embed.*(>|%3E)/i', $qs) ||
            preg_match('/(union)(.*)(select)(.*)(\(|%28)/i', $qs) ||
            preg_match('/(concat|eval)(.*)(\(|%28)/i', $qs) ||
            preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $qs) ||
            preg_match('/(base64_(en|de)code[^(]*\([^)]*\))/i', $qs) ||
            preg_match('/(NULL|OUTFILE|LOAD_FILE)/i', $qs) ||
            preg_match('/(GLOBALS(=|\[|%[0-9A-Z]{0,2}))/i', $qs) ||
            preg_match('/(_REQUEST(=|\[|%[0-9A-Z]{0,2}))/i', $qs) ||
            preg_match('/(etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $qs) ||
            preg_match('/(sp_executesql)/i', $qs) ||
            preg_match('/(fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname)/i', $qs) ||
            preg_match('/(passthru|phpinfo|popen|proc_open|shell_exec|system|user_func_array)/i', $qs)
        ) {
            $this->block('7G Firewall (Query String)');
        }
    }

    /**
     * 7G firewall rules on request URI
     *
     * @param string $uri
     * @return void
     */
    private function check_request_uri_7g(string $uri): void
    {
        if (
            preg_match('/(\^|`|<|>|\\|\|)/i', $uri) ||
            preg_match('/([a-z0-9]{2000,})/i', $uri) ||
            preg_match('/(\/)(\*|\"|\'|\.|,|&|&amp;?)\/?$/i', $uri) ||
            preg_match('/\/((.*)header:|(.*)set-cookie:(.*)=)/i', $uri) ||
            preg_match('/(\/)(ckfinder|fck|fckeditor|fullclick)/i', $uri) ||
            preg_match('/(\.(s?ftp-?)config|(s?ftp-?)config\.)/i', $uri) ||
            preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)(\.php)/i', $uri) ||
            preg_match('/(\/\/\/|\?\?|\/&&|\/:\/|\\\\|0x00|%00|%0d%0a)/i', $uri) ||
            preg_match('/(\/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(\/)/i', $uri) ||
            preg_match('/(\/)(etc|var)(\/)(hidden|secret|shadow|ninja|passwd|tmp)(\/)?$/i', $uri) ||
            preg_match('/(s)?(ftp|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i', $uri) ||
            preg_match('/(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(\/)?$/i', $uri) ||
            preg_match('/(\(null\)|\{\$itemURL\}|cAsT\(0x|echo(.*)kae|etc\/passwd|eval\(|self\/environ)/i', $uri) ||
            preg_match('/(\/)(awstats|(c99|php|web)shell|document_root|error_log|remoteview|sqlpatch)/i', $uri) ||
            preg_match('/(\/)((php|web)?shell|crossdomain|fileditor|r57|remview|sshphp|webadmin)(.*)(\.|\()/i', $uri) ||
            preg_match('/(base64_(en|de)code|benchmark|eval|function|fwrite|passthru|phpinfo|shell_exec|system)(.*)(\()(.*)(\))/i', $uri) ||
            preg_match('/(\/)(^$|00.temp00|0day|3xp|70bex?|admin_events|bkht|(php|web)?shell|c99|config(\.)?bak|curltest|db|dompdf|hmei7|index\.php\/index\.php\/index|jahat|kcrew|sql|vuln|(web-?|wp-)?config(uration)?|xertive)(\.php)/i', $uri) ||
            preg_match('/(\.)(ab4|ace|afm|ashx|aspx?|bash|ba?k?|bin|bz2|cfg|cfml?|conf|env|et2|inc|inv|jsp|lqd|mbf|mdb|mmw|mny|module|old|orig|out|passwd|psd|pst|ptdb|pwd|py|qbb|qdf|rdf|save|sdb|sh|soa|svn|swp|tgz|theme|tls|ya?ml)$/i', $uri)
        ) {
            $this->block('7G Firewall (Request URI)');
        }
    }

    /**
     * Block direct access to /wp-content/plugins/ and /wp-content/themes/ URL paths
     *
     * @param string $uri
     * @return void
     */
    private function block_directory_browsing(string $uri): void
    {
        // Block direct browsing of plugin/theme directories (no specific file requested)
        $patterns = array(
            '#^/wp-content/plugins/?$#i',
            '#^/wp-content/themes/?$#i',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                $this->block('Directory Browsing');
            }
        }
    }

    /**
     * Check if a User Agent contains known bad scanners
     *
     * @param string $user_agent
     * @return bool
     */
    private function is_bad_user_agent(string $user_agent): bool
    {
        $ua_lower = strtolower($user_agent);
        foreach ($this->bad_user_agents as $bad) {
            if (str_contains($ua_lower, $bad)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the User Agent is a known WordPress theme detector
     *
     * @param string $user_agent
     * @return bool
     */
    private function is_theme_detector(string $user_agent): bool
    {
        return (bool) preg_match(
            '/(wpthemedetector|builtwith|isitwp|wappalyzer|Wappalyzer|mShots|WhatCMS|gochyu|wpdetector|scanwp)/i',
            $user_agent
        );
    }

    /**
     * Check for known spam/bad referrers
     *
     * @param string $referrer
     * @return bool
     */
    private function is_bad_referrer(string $referrer): bool
    {
        $bad_referrers = array(
            'semalt.com',
            'buttons-for-website.com',
            'get-cheap-hosting.com',
            'best-seo-offer.com',
            'anticrawler.org',
            'ranking-server.com',
            'darodar.com',
            'hulfingtonpost.com',
            'webmonetizer.net',
        );

        $ref_lower = strtolower($referrer);
        foreach ($bad_referrers as $bad) {
            if (str_contains($ref_lower, $bad)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if IP is blacklisted
     *
     * @param string $ip
     * @return bool
     */
    public function is_ip_blacklisted(string $ip): bool
    {
        // Check permanent blacklist in options
        $blacklist = get_option('apollo_firewall_blacklist', array());
        if (in_array($ip, (array) $blacklist, true)) {
            return true;
        }

        // Check temporary transient list (auto-added by rate limiter)
        $temp_blacklist = get_transient(self::IP_BLACKLIST_KEY);
        if (is_array($temp_blacklist) && in_array($ip, $temp_blacklist, true)) {
            return true;
        }

        return false;
    }

    /**
     * Temporarily blacklist an IP (via transient) — called by RateLimiter
     *
     * @param string $ip
     * @param int    $duration  Seconds. Default 1 hour.
     * @return void
     */
    public static function temp_blacklist_ip(string $ip, int $duration = 3600): void
    {
        $key  = self::IP_BLACKLIST_KEY;
        $list = get_transient($key);
        if (! is_array($list)) {
            $list = array();
        }
        $list[] = $ip;
        $list   = array_unique($list);
        set_transient($key, $list, $duration);
    }

    /**
     * Permanently blacklist an IP
     *
     * @param string $ip
     * @return void
     */
    public static function blacklist_ip(string $ip): void
    {
        $current = get_option('apollo_firewall_blacklist', array());
        if (! is_array($current)) {
            $current = array();
        }
        $current[] = sanitize_text_field($ip);
        $current   = array_unique($current);
        update_option('apollo_firewall_blacklist', $current);
    }

    /**
     * Remove an IP from the blacklist
     *
     * @param string $ip
     * @return void
     */
    public static function unblacklist_ip(string $ip): void
    {
        $current = get_option('apollo_firewall_blacklist', array());
        if (! is_array($current)) {
            return;
        }
        $current = array_filter($current, fn($i) => $i !== $ip);
        update_option('apollo_firewall_blacklist', array_values($current));
    }

    /**
     * AJAX handler — blacklist IP
     *
     * @return void
     */
    public function ajax_blacklist_ip(): void
    {
        check_ajax_referer('apollo_firewall_nonce', 'nonce');

        if (! current_user_can('administrator')) {
            wp_send_json_error('Unauthorized');
        }

        $ip = sanitize_text_field(wp_unslash($_POST['ip'] ?? ''));
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            self::blacklist_ip($ip);
            wp_send_json_success("IP {$ip} blacklisted.");
        }

        wp_send_json_error('Invalid IP address.');
    }

    /**
     * AJAX handler — remove from blacklist
     *
     * @return void
     */
    public function ajax_unblacklist_ip(): void
    {
        check_ajax_referer('apollo_firewall_nonce', 'nonce');

        if (! current_user_can('administrator')) {
            wp_send_json_error('Unauthorized');
        }

        $ip = sanitize_text_field(wp_unslash($_POST['ip'] ?? ''));
        self::unblacklist_ip($ip);
        wp_send_json_success("IP {$ip} removed from blacklist.");
    }

    /**
     * Block the current request with a 403 response
     *
     * @param string $reason
     * @return void
     */
    private function block(string $reason): void
    {
        // Log before blocking
        $ip = $this->get_client_ip();
        do_action('apollo/firewall/blocked', $ip, $reason, $_SERVER['REQUEST_URI'] ?? '');

        status_header(403);
        nocache_headers();
        header('Content-Type: text/html; charset=UTF-8');

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Forbidden</title><meta name="robots" content="noindex,nofollow"></head>';
        echo '<body style="font-family:sans-serif;text-align:center;padding:80px;"><h1 style="font-size:6rem;margin:0;">403</h1>';
        echo '<p>Access denied.</p></body></html>';
        exit;
    }

    /**
     * Get real client IP, respecting common proxy headers
     *
     * @return string
     */
    public static function get_client_ip(): string
    {
        $headers = array(
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        );

        foreach ($headers as $h) {
            $val = $_SERVER[$h] ?? '';
            if ($val) {
                // X-Forwarded-For may contain multiple IPs
                $ips = array_map('trim', explode(',', $val));
                $ip  = $ips[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Alias for static use in RateLimiter
     *
     * @return string
     */
    private function get_client_ip_local(): string
    {
        return self::get_client_ip();
    }
}
