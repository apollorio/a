#!/usr/bin/env php
<?php
/**
 * Audit _apollo_membership SSOT compliance across Apollo plugins.
 *
 * Usage:
 *   php wp-content/plugins/apollo-core/bin/audit-membership.php
 *
 * Env:
 *   APOLLO_WP_LOAD — path to wp-load.php (auto-detected if omitted)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$wp_load = getenv('APOLLO_WP_LOAD') ?: '';
if ('' === $wp_load) {
    $candidates = array(
        dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'wp-load.php',
        dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'wp-load.php',
    );
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            $wp_load = $path;
            break;
        }
    }
}

$bootstrapped = false;
if ('' !== $wp_load && is_readable($wp_load)) {
    define('WP_USE_THEMES', false);
    require $wp_load;
    $bootstrapped = true;
}

// Bootstrap apollo-core registry without full WP when possible.
if (! class_exists('\Apollo\Core\Config\MembershipRegistry')) {
    if (! defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR);
    }
    $core_src = dirname(__DIR__) . '/src/Config';
    if (is_readable($core_src . '/ConfigLoader.php')) {
        require_once $core_src . '/ConfigLoader.php';
        require_once $core_src . '/MembershipRegistry.php';
    }
}

echo "Apollo Membership SSOT Audit\n";
echo str_repeat('─', 60) . "\n";

$critical = 0;
$warnings = 0;

function audit_fail(string $msg): void {
    global $critical;
    ++$critical;
    echo "[CRITICAL] {$msg}\n";
}

function audit_warn(string $msg): void {
    global $warnings;
    ++$warnings;
    echo "[WARN] {$msg}\n";
}

function audit_ok(string $msg): void {
    echo "[OK] {$msg}\n";
}

// ── 1. Registry loaded ─────────────────────────────────────────────
if (! class_exists('\Apollo\Core\Config\MembershipRegistry')) {
    audit_fail('MembershipRegistry class not found — apollo-core required.');
} else {
    $slugs = \Apollo\Core\Config\MembershipRegistry::all_valid_slugs();
    audit_ok('Registry loaded: ' . count($slugs) . ' valid slugs.');
}

// ── 2. DB storage shape (requires WP) ──────────────────────────────
if ($bootstrapped && function_exists('apollo_membership_normalize_storage')) {
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results(
        "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = '_apollo_membership'"
    );

    $string_count = 0;
    $bad_shape    = 0;
    $unknown      = 0;
    $deprecated   = 0;

    $valid = class_exists('\Apollo\Core\Config\MembershipRegistry')
        ? \Apollo\Core\Config\MembershipRegistry::all_valid_slugs()
        : array();

    $access = class_exists('\Apollo\Core\Config\MembershipRegistry')
        ? \Apollo\Core\Config\MembershipRegistry::all_access_slugs()
        : array();

    $profile = class_exists('\Apollo\Core\Config\MembershipRegistry')
        ? \Apollo\Core\Config\MembershipRegistry::all_profile_slugs()
        : array();

    if (is_array($rows)) {
        foreach ($rows as $row) {
            $uid = (int) ($row->user_id ?? 0);
            $raw = maybe_unserialize($row->meta_value ?? '');

            if (! is_array($raw)) {
                ++$string_count;
                continue;
            }

            $normalized = apollo_membership_normalize_storage($raw);
            $badge      = $normalized[0] ?? '';

            if (! in_array($badge, $profile, true)) {
                ++$bad_shape;
            }

            foreach ($normalized as $i => $slug) {
                if ($i > 0 && ! in_array($slug, $access, true)) {
                    ++$bad_shape;
                }
                if (! in_array($slug, $valid, true)) {
                    ++$unknown;
                }
                if (class_exists('\Apollo\Core\Config\MembershipRegistry')
                    && \Apollo\Core\Config\MembershipRegistry::is_deprecated($slug)) {
                    ++$deprecated;
                }
            }
        }
    }

    if ($string_count > 0) {
        audit_fail("{$string_count} users still have string _apollo_membership (run migration).");
    } else {
        audit_ok('All stored _apollo_membership values are arrays.');
    }

    if ($bad_shape > 0) {
        audit_fail("{$bad_shape} malformed slug shape(s) detected.");
    }

    if ($unknown > 0) {
        audit_fail("{$unknown} unknown slug(s) in user meta.");
    }

    if ($deprecated > 0) {
        audit_warn("{$deprecated} deprecated slug assignment(s) (host/business-pers).");
    }

    // Legacy meta keys
    $legacy_keys = class_exists('\Apollo\Core\Config\MembershipRegistry')
        ? \Apollo\Core\Config\MembershipRegistry::deprecated_user_meta_keys()
        : array('_apollo_verified', '_apollo_team', '_apollo_cenario');

    foreach ($legacy_keys as $key) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s",
                $key
            )
        );
        if ($count > 0) {
            audit_warn("Legacy meta {$key} still set on {$count} user(s).");
        }
    }
} else {
    audit_warn('WP bootstrap unavailable — skipping DB checks.');
}

// ── 3. Static scan: direct writes outside allowlist ────────────────
$plugins_root = dirname(__DIR__, 2);
$allowlist    = array(
    'apollo-membership' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php',
    'apollo-membership' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Migration' . DIRECTORY_SEPARATOR . 'MembershipStorageMigration.php',
);

$write_violations = 0;
$iterator         = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($plugins_root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || ! $file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if (! str_ends_with($path, '.php')) {
        continue;
    }
    if (str_contains($path, 'phpcs-logs') || str_contains($path, 'vendor')) {
        continue;
    }

    $rel = str_replace($plugins_root . DIRECTORY_SEPARATOR, '', $path);
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, $rel);

    $content = file_get_contents($path);
    if (false === $content) {
        continue;
    }

    if (! preg_match("/update_user_meta\s*\([^)]*['_\"]_apollo_membership['\"]/", $content)
        && ! preg_match("/add_user_meta\s*\([^)]*['_\"]_apollo_membership['\"]/", $content)) {
        continue;
    }

    $allowed = false;
    foreach ($allowlist as $allowed_rel) {
        if (str_ends_with(str_replace('\\', DIRECTORY_SEPARATOR, $rel), str_replace('\\', DIRECTORY_SEPARATOR, $allowed_rel))) {
            $allowed = true;
            break;
        }
    }

    if (! $allowed) {
        ++$write_violations;
        audit_fail("Direct _apollo_membership write in {$rel}");
    }
}

if (0 === $write_violations) {
    audit_ok('No direct _apollo_membership writes outside allowlist.');
}

// ── Summary ────────────────────────────────────────────────────────
echo str_repeat('─', 60) . "\n";
echo "Critical: {$critical} | Warnings: {$warnings}\n";

exit($critical > 0 ? 1 : 0);
