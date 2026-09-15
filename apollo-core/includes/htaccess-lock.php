<?php
/**
 * Apollo root .htaccess lock — force production v3.1.0 to remain.
 *
 * Host/plugin writers (NFD EPC, WP insert_with_markers, hard flushes) race and
 * truncate the file mid-line → LiteSpeed 500. This module:
 *  1. Detects mutation / truncation / weak stubs.
 *  2. Restores the canonical body via atomic temp+rename.
 *  3. chmod 0444 so insert_with_markers / NFD cannot rewrite it.
 *  4. Forces soft-only flush_rewrite_rules.
 *
 * Manual edit: define('APOLLO_HTACCESS_UNLOCK', true) in wp-config.php, edit,
 * remove the constant (or set false), then load any page to re-lock.
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Path to bundled canonical .htaccess.
 */
function apollo_core_htaccess_canonical_path(): string
{
    return dirname(__DIR__) . '/config/htaccess-production.txt';
}

/**
 * @return string Canonical Apollo production .htaccess body (LF).
 */
function apollo_core_htaccess_canonical(): string
{
    static $cached = null;
    if (is_string($cached)) {
        return $cached;
    }
    $path = apollo_core_htaccess_canonical_path();
    if (! is_readable($path)) {
        $cached = '';
        return $cached;
    }
    $raw = (string) file_get_contents($path);
    $raw = str_replace("\r\n", "\n", $raw);
    $raw = str_replace("\r", "\n", $raw);
    $cached = rtrim($raw, "\n") . "\n";
    return $cached;
}

/**
 * True when contents is the complete Apollo production lock file.
 */
function apollo_core_htaccess_is_locked_apollo(string $contents): bool
{
    if ($contents === '') {
        return false;
    }
    return str_contains($contents, 'APOLLO  ·  .htaccess')
        && str_contains($contents, 'Version: 3.1.0')
        && str_contains($contents, 'ErrorDocument 500 /erro/500/')
        && str_contains($contents, '# ── END OF APOLLO .htaccess v3.1.0')
        && str_contains($contents, 'RewriteRule . /index.php')
        && ! apollo_core_htaccess_is_truncated($contents);
}

/**
 * Truncated / mid-write garbage (seen: cut inside FilesMatch package(-l…).
 */
function apollo_core_htaccess_is_truncated(string $contents): bool
{
    if ($contents === '') {
        return true;
    }
    if (! str_contains($contents, '# ── END OF APOLLO .htaccess v3.1.0')) {
        // Apollo header without end marker = partial write.
        if (str_contains($contents, 'APOLLO  ·  .htaccess')
            || str_contains($contents, 'Version: 3.1.0')
            || str_contains($contents, '§6  FILE ACCESS')
        ) {
            return true;
        }
    }
    // Unbalanced Apache containers.
    if (substr_count($contents, '<FilesMatch') !== substr_count($contents, '</FilesMatch>')) {
        return true;
    }
    if (substr_count($contents, '<Files ') !== substr_count($contents, '</Files>')) {
        return true;
    }
    if (substr_count($contents, '<IfModule') !== substr_count($contents, '</IfModule>')) {
        return true;
    }
    // Known double-write garbage.
    if (str_contains($contents, '_in|wptouch_switch_toggle')) {
        return true;
    }
    // Cut mid-token (user report: package(-l).
    if (preg_match('/package\(-l\s*$/', $contents) || preg_match('/package\(-l[^)]*$/', $contents)) {
        return true;
    }
    return false;
}

/**
 * True when file looks like the weak NFD/WP stub that causes 500s.
 */
function apollo_core_htaccess_is_weak_stub(string $contents): bool
{
    if ($contents === '') {
        return true;
    }
    if (apollo_core_htaccess_is_truncated($contents)) {
        return true;
    }
    $has_nfd = str_contains($contents, '# BEGIN NFD EPC');
    $has_wp  = str_contains($contents, '# BEGIN WordPress');
    $has_apollo_complete = apollo_core_htaccess_is_locked_apollo($contents);
    if ($has_apollo_complete) {
        return false;
    }
    // Any NFD/WP injection without a complete Apollo lock = hostile rewrite.
    if ($has_nfd || $has_wp) {
        return true;
    }
    if (strlen($contents) < 800) {
        return true;
    }
    return ! (bool) preg_match('/RewriteRule\s+\.\s+\/index\.php/i', $contents);
}

/**
 * Debug NDJSON (session 161c5c).
 *
 * @param array<string, mixed> $data
 */
function apollo_core_htaccess_log(string $message, array $data, string $hypothesisId = 'H5'): void
{
    $payload = array(
        'sessionId'    => '161c5c',
        'runId'        => 'htaccess-lock-v2',
        'hypothesisId' => $hypothesisId,
        'location'     => 'apollo-core/htaccess-lock.php',
        'message'      => $message,
        'data'         => $data,
        'timestamp'    => (int) round(microtime(true) * 1000),
    );
    $line = function_exists('wp_json_encode')
        ? wp_json_encode($payload)
        : json_encode($payload);
    if (! is_string($line)) {
        return;
    }
    $line .= "\n";
    $targets = array();
    if (defined('WP_CONTENT_DIR')) {
        $targets[] = WP_CONTENT_DIR . '/debug-161c5c.log';
        $targets[] = WP_CONTENT_DIR . '/plugins/debug-161c5c.log';
    }
    // Local workspace mirror when plugins live under the monorepo.
    $targets[] = dirname(__DIR__, 3) . '/debug-161c5c.log';
    foreach (array_unique($targets) as $log) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        @file_put_contents($log, $line, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Whether unlock mode is on (operator editing .htaccess manually).
 */
function apollo_core_htaccess_unlocked(): bool
{
    return defined('APOLLO_HTACCESS_UNLOCK') && APOLLO_HTACCESS_UNLOCK;
}

/**
 * Atomic write + optional read-only lock.
 */
function apollo_core_htaccess_atomic_write(string $path, string $body): bool
{
    $dir = dirname($path);
    $tmp = $dir . '/.htaccess.apollo.' . getmypid() . '.' . bin2hex(random_bytes(4)) . '.tmp';

    // Ensure we can replace a previously locked file.
    if (file_exists($path) && ! is_writable($path)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        @chmod($path, 0644);
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    $written = @file_put_contents($tmp, $body, LOCK_EX);
    if ($written === false || (int) $written !== strlen($body)) {
        @unlink($tmp);
        apollo_core_htaccess_log(
            'atomic write failed',
            array(
                'tmp'      => $tmp,
                'written'  => $written,
                'expected' => strlen($body),
            ),
            'H5'
        );
        return false;
    }

    // Prefer atomic replace (Linux hosting).
    if (! @rename($tmp, $path)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        $fallback = @file_put_contents($path, $body, LOCK_EX);
        @unlink($tmp);
        if ($fallback === false) {
            return false;
        }
    }

    clearstatcache(true, $path);

    // Verify full body on disk before locking.
    $verify = is_readable($path) ? (string) file_get_contents($path) : '';
    $verify = str_replace("\r\n", "\n", str_replace("\r", "\n", $verify));
    if ($verify !== $body) {
        apollo_core_htaccess_log(
            'post-write verify mismatch',
            array(
                'expected' => strlen($body),
                'got'      => strlen($verify),
                'tail'     => substr($verify, -80),
            ),
            'H5'
        );
        return false;
    }

    if (! apollo_core_htaccess_unlocked()) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        @chmod($path, 0444);
    }

    return true;
}

/**
 * Enforce canonical Apollo .htaccess. Restores when mutated/truncated.
 *
 * @return bool True when a restore write happened.
 */
function apollo_core_htaccess_enforce(): bool
{
    if (! defined('ABSPATH')) {
        return false;
    }

    if (apollo_core_htaccess_unlocked()) {
        apollo_core_htaccess_log('unlock mode — skip enforce', array(), 'H5');
        return false;
    }

    $path = ABSPATH . '.htaccess';
    $canonical = apollo_core_htaccess_canonical();
    if ($canonical === '') {
        apollo_core_htaccess_log('canonical missing', array('path' => apollo_core_htaccess_canonical_path()), 'H5');
        return false;
    }

    $current = is_readable($path) ? (string) file_get_contents($path) : '';
    $current_norm = str_replace("\r\n", "\n", $current);
    $current_norm = str_replace("\r", "\n", $current_norm);

    $is_apollo   = apollo_core_htaccess_is_locked_apollo($current_norm);
    $is_trunc    = apollo_core_htaccess_is_truncated($current_norm);
    $is_weak     = apollo_core_htaccess_is_weak_stub($current_norm);
    $needs       = (! $is_apollo) || $is_trunc || $is_weak;

    apollo_core_htaccess_log(
        'enforce gate',
        array(
            'bytes'     => strlen($current_norm),
            'is_apollo' => $is_apollo,
            'is_trunc'  => $is_trunc,
            'is_weak'   => $is_weak,
            'needs'     => $needs,
            'writable'  => file_exists($path) ? is_writable($path) : null,
            'mode'      => file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : null,
            'tail'      => substr($current_norm, -60),
        ),
        $is_trunc ? 'H5' : 'H3'
    );

    if (! $needs) {
        // Still ensure read-only so NFD/WP cannot inject later.
        if (file_exists($path) && is_writable($path)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
            @chmod($path, 0444);
            apollo_core_htaccess_log('re-applied 0444', array('path' => $path), 'H6');
        }
        return false;
    }

    $ok = apollo_core_htaccess_atomic_write($path, $canonical);
    apollo_core_htaccess_log(
        'restored canonical',
        array(
            'wrote'     => $ok,
            'new_bytes' => strlen($canonical),
            'old_bytes' => strlen($current_norm),
            'mode'      => file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : null,
        ),
        'H5'
    );

    return $ok;
}

/**
 * Soft-only rewrite flushes — never let WP/NFD rewrite root .htaccess.
 *
 * @param bool $hard Whether WP wants a hard flush.
 */
function apollo_core_htaccess_block_hard_flush(bool $hard): bool
{
    if ($hard) {
        apollo_core_htaccess_log('blocked hard flush_rewrite_rules', array('hard' => true), 'H4');
    }
    return false;
}

/**
 * Short-circuit WP's .htaccess writer even if something calls it directly.
 *
 * @param mixed $pre Null to continue, non-null to short-circuit (WP unused; we use filters around callers).
 */
function apollo_core_htaccess_block_save_mod_rewrite($pre = null)
{
    apollo_core_htaccess_log('blocked save_mod_rewrite_rules path', array(), 'H6');
    // Returning false from the action-style path is handled via hard-flush filter;
    // this exists for hosts that hook the same name.
    return false;
}

/**
 * Install / refresh MU-plugin so lock runs before regular plugins.
 */
function apollo_core_htaccess_install_mu_plugin(): void
{
    if (! defined('WPMU_PLUGIN_DIR')) {
        return;
    }
    $src = dirname(__DIR__) . '/mu-plugins/apollo-htaccess-lock.php';
    if (! is_readable($src)) {
        return;
    }
    if (! is_dir(WPMU_PLUGIN_DIR)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
        @mkdir(WPMU_PLUGIN_DIR, 0755, true);
    }
    $dest = WPMU_PLUGIN_DIR . '/apollo-htaccess-lock.php';
    $src_body  = (string) file_get_contents($src);
    $dest_body = is_readable($dest) ? (string) file_get_contents($dest) : '';
    if ($src_body !== '' && $src_body !== $dest_body) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        $ok = @file_put_contents($dest, $src_body, LOCK_EX);
        apollo_core_htaccess_log(
            'mu-plugin install',
            array('ok' => $ok !== false, 'dest' => $dest),
            'H6'
        );
    }
}

// Boot immediately when this file is included (apollo-core / MU load early).
if (! defined('APOLLO_HTACCESS_LOCK_BOOTED')) {
    define('APOLLO_HTACCESS_LOCK_BOOTED', true);

    apollo_core_htaccess_enforce();

    add_action('plugins_loaded', 'apollo_core_htaccess_install_mu_plugin', 0);
    add_action('plugins_loaded', 'apollo_core_htaccess_enforce', 0);
    add_action('plugins_loaded', 'apollo_core_htaccess_enforce', 999);
    add_action('init', 'apollo_core_htaccess_enforce', 0);
    add_action('init', 'apollo_core_htaccess_enforce', 9999);
    add_action(
        'shutdown',
        static function (): void {
            apollo_core_htaccess_enforce();
        },
        0
    );

    // Kill hard .htaccess rewrites from WP + host plugins.
    add_filter('flush_rewrite_rules_hard', 'apollo_core_htaccess_block_hard_flush', 0);

    // Some hosts/plugins call save_mod_rewrite_rules() without going through flush.
    // WP has no filter inside it; readonly chmod is the real barrier. Log attempts
    // when the function is about to be used via the hard-flush path only above.
}
