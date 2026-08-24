#!/usr/bin/env php
<?php
/**
 * Apollo LocalWP smoke — runs apollo-statistics TestPanel battery via WP bootstrap.
 *
 * Usage (LocalWP aprio):
 *   "C:\...\php.exe" wp-content/plugins/apollo-statistics/bin/local-smoke.php
 *
 * Env:
 *   APOLLO_WP_LOAD  — path to wp-load.php (auto-detected if omitted)
 *   APOLLO_SMOKE_BASE — http://aprio.local (external curl probes)
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

if ('' === $wp_load || ! is_readable($wp_load)) {
    fwrite(STDERR, "wp-load.php not found. Set APOLLO_WP_LOAD.\n");
    exit(1);
}

define('WP_USE_THEMES', false);

$base = rtrim(getenv('APOLLO_SMOKE_BASE') ?: 'http://aprio.local', '/');

echo "Apollo Local Smoke — {$base}\n";
echo str_repeat('─', 60) . "\n";

/**
 * HTTP probes when WP CLI bootstrap unavailable (LocalWP php.exe often lacks mysqli).
 *
 * @return array<int, array{label:string,ok:bool,detail:string}>
 */
function apollo_smoke_http_probes(string $base): array {
    $ua   = 'apolloDJ/2.0.0';
    $out  = array();

    $probe = static function (string $label, string $method, string $path, int $expect, ?string $body = null) use ($base, $ua, &$out): void {
        $url = rtrim($base, '/') . $path;
        $headers = "User-Agent: {$ua}\r\n";
        if ('POST' === $method && null !== $body) {
            $headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
        }
        $ctx = stream_context_create(array(
            'http' => array(
                'method'        => $method,
                'timeout'       => 20,
                'header'        => $headers,
                'content'       => null !== $body ? $body : '',
                'ignore_errors' => true,
            ),
        ));
        @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }
        $ok = $code === $expect;
        $out[] = array(
            'label'  => $label,
            'ok'     => $ok,
            'detail' => $ok ? "HTTP {$code} ✓" : "HTTP {$code} (expected {$expect})",
        );
    };

    $probe('GET /dj/config', 'GET', '/wp-json/apollo/v1/dj/config', 200);
    $probe('GET /shortcodes', 'GET', '/wp-json/apollo/v1/shortcodes', 200);
    $probe('POST /app/auth invalid app', 'POST', '/wp-json/apollo/v1/app/auth', 403, 'username=x&password=y&app_id=unknown');
    $probe('POST /app/auth bad creds', 'POST', '/wp-json/apollo/v1/app/auth', 401, 'username=smoke_nonexistent&password=bad&app_id=apollodj');
    $probe('GET /app/verify no token', 'GET', '/wp-json/apollo/v1/app/verify?app_id=apollodj', 401);
    $probe('Wrong namespace /a/v1/', 'GET', '/wp-json/a/v1/dj/config', 404);

    return $out;
}

$http = apollo_smoke_http_probes($base);
foreach ($http as $row) {
    echo ($row['ok'] ? '[OK]  ' : '[FAIL]') . ' ' . $row['label'] . ' — ' . $row['detail'] . "\n";
}

$wp_boot_ok = extension_loaded('mysqli') && is_readable($wp_load);

if (! $wp_boot_ok) {
    echo str_repeat('─', 60) . "\n";
    echo "Static plugin checks (no DB)…\n";
    $plugins_root = dirname(__DIR__, 2);
    $static = array(
        array(
            'label' => 'apollo-membership user-edit hook',
            'ok'    => is_readable($plugins_root . '/apollo-membership/includes/user.php')
                && str_contains((string) file_get_contents($plugins_root . '/apollo-membership/includes/user.php'), "add_action( 'edit_user_profile', 'apollo_membership_user_profile_data'"),
            'detail'=> 'edit_user_profile → apollo_membership_user_profile_data',
        ),
        array(
            'label' => 'apollo-dj-sync no Feature Gates',
            'ok'    => is_readable($plugins_root . '/apollo-dj-sync/src/Admin/DJUserAdmin.php')
                && ! str_contains((string) file_get_contents($plugins_root . '/apollo-dj-sync/src/Admin/DJUserAdmin.php'), 'render_meta_box'),
            'detail'=> 'DJUserAdmin settings-only',
        ),
        array(
            'label' => 'apollo-login membership reader',
            'ok'    => is_readable($plugins_root . '/apollo-login/src/API/AppAuthController.php')
                && str_contains((string) file_get_contents($plugins_root . '/apollo-login/src/API/AppAuthController.php'), "in_array('app-apollodj', \$memberships"),
            'detail'=> 'AppAuthController reads _apollo_membership',
        ),
        array(
            'label' => 'membership slug helpers',
            'ok'    => is_readable($plugins_root . '/apollo-membership/includes/functions.php')
                && str_contains((string) file_get_contents($plugins_root . '/apollo-membership/includes/functions.php'), 'function apollo_membership_get_user_slugs'),
            'detail'=> 'get/set user slugs API',
        ),
    );
    foreach ($static as $row) {
        echo ($row['ok'] ? '[OK]  ' : '[FAIL]') . ' ' . $row['label'] . ' — ' . $row['detail'] . "\n";
    }

    echo str_repeat('─', 60) . "\n";
    echo "WP bootstrap skipped (CLI PHP missing mysqli). HTTP + static smoke only.\n";
    echo "Full TestPanel (49 tests): Local → aprio → Open site shell → run local-smoke.php\n";
    $fail_http   = count(array_filter($http, static fn(array $r): bool => ! $r['ok']));
    $fail_static = count(array_filter($static, static fn(array $r): bool => ! $r['ok']));
    $fail        = $fail_http + $fail_static;
    echo $fail > 0 ? "\nSMOKE FAILED\n" : "\nSMOKE PASSED (HTTP + static probes)\n";
    exit($fail > 0 ? 1 : 0);
}

require $wp_load;

echo str_repeat('─', 60) . "\n";
echo "Internal WP tests (TestPanel)…\n\n";

if (! class_exists(\Apollo\Statistics\Admin\TestPanel::class)) {
    fwrite(STDERR, "apollo-statistics TestPanel not loaded — activate plugin.\n");
    exit(1);
}

$panel  = new \Apollo\Statistics\Admin\TestPanel();
$suite  = $panel->run_cli_suite();
$sum    = $suite['summary'];

foreach ($suite['results'] as $id => $row) {
    $tag = strtoupper($row['status']);
    $pad = str_pad($tag, 4);
    echo "[{$pad}] {$id}\n       {$row['detail']}\n";
}

echo str_repeat('─', 60) . "\n";
printf(
    "SUMMARY: %d ok · %d warn · %d fail · %d total\n",
    $sum['ok'],
    $sum['warn'],
    $sum['fail'],
    $sum['total']
);

echo $sum['fail'] > 0 ? "\nSMOKE FAILED\n" : "\nSMOKE PASSED (warnings allowed for local dev)\n";

exit($sum['fail'] > 0 ? 1 : 0);
