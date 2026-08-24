<?php

/**
 * Apollo Login — Comprehensive Test Suite
 * 
 * Run from HTTP: https://aprio.local/wp-content/plugins/apollo-login/tests/run-all-tests.php?secret=apollo-test-run
 */

namespace Apollo\Login\Tests;

if (php_sapi_name() !== 'cli') {
    $secret = isset($_GET['secret']) ? sanitize_key($_GET['secret']) : '';
    if ($secret !== 'apollo-test-run') {
        http_response_code(403);
        die('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once dirname(__DIR__) . '/includes/constants.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (function_exists('add_action')) {
    // Already in WP context
} else {
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

$results = array();
$passed = 0;
$failed = 0;
$start_time = microtime(true);

function run_test($name, $fn)
{
    global $results, $passed, $failed;
    try {
        ob_start();
        $result = call_user_func($fn);
        ob_end_clean();
        if ($result === true) {
            $passed++;
            $results[] = "  [PASS] {$name}";
        } else {
            $failed++;
            $results[] = "  [FAIL] {$name} => " . ($result === null ? 'null' : (string)$result);
        }
    } catch (\Throwable $e) {
        $failed++;
        $results[] = "  [ERR]  {$name} => " . $e->getMessage();
    }
}

function check($cond, $msg = '')
{
    return $cond ? true : ($msg ?: 'false');
}

echo "============================================\n";
echo "  APOLLO LOGIN — COMPREHENSIVE TEST SUITE\n";
echo "============================================\n\n";

echo "--- SECTION 1: CONSTANTS ---\n";
run_test('APOLLO_LOGIN_VERSION defined', function () {
    return check(defined('APOLLO_LOGIN_VERSION'));
});
run_test('REST_NAMESPACE = apollo/v1', function () {
    return check(APOLLO_LOGIN_REST_NAMESPACE === 'apollo/v1', 'got: ' . APOLLO_LOGIN_REST_NAMESPACE);
});
run_test('LOGIN_SLUG = acesso', function () {
    return check(APOLLO_LOGIN_CUSTOM_LOGIN_SLUG === 'acesso');
});
run_test('REGISTER_SLUG = registre', function () {
    return check(APOLLO_LOGIN_CUSTOM_REGISTER_SLUG === 'registre');
});
run_test('MAX_ATTEMPTS = 3', function () {
    return check(APOLLO_LOGIN_MAX_ATTEMPTS === 3);
});
run_test('LOCKOUT_DURATION = 900', function () {
    return check(APOLLO_LOGIN_LOCKOUT_DURATION === 900);
});
run_test('SLUG_ALIASES count = 4', function () {
    return check(count(APOLLO_LOGIN_LOGIN_SLUG_ALIASES) === 4);
});

echo "\n--- SECTION 2: URL HELPERS ---\n";
run_test('canonical_login_url', function () {
    $url = \Apollo\Login\apollo_login_canonical_login_url();
    return check(strpos($url, '/acesso/') !== false, $url);
});
run_test('register_url', function () {
    $url = \Apollo\Login\apollo_login_register_url();
    return check(strpos($url, '/registre/') !== false, $url);
});
run_test('password_recovery_url', function () {
    $url = \Apollo\Login\apollo_login_password_recovery_url();
    return check(strpos($url, 'recuperar-chave') !== false, $url);
});

echo "\n--- SECTION 3: CPF VALIDATION ---\n";
$valid = array('52998224725', '37472027872', '49678474881', '94782816754');
$invalid = array('11111111111', '00000000000', '12345678901', '123456', '', 'abcdefghijk');

foreach ($valid as $cpf) {
    run_test("CPF valid {$cpf}", function () use ($cpf) {
        return check(\Apollo\Login\apollo_validate_cpf($cpf) === true);
    });
}
foreach ($invalid as $cpf) {
    run_test("CPF invalid {$cpf}", function () use ($cpf) {
        return check(\Apollo\Login\apollo_validate_cpf($cpf) === false);
    });
}

echo "\n--- SECTION 4: TOKEN GENERATION ---\n";
run_test('generate_token 64 chars', function () {
    $t = \Apollo\Login\apollo_login_generate_token(64);
    return check(strlen($t) === 64 && ctype_xdigit($t));
});
run_test('generate_verification_token', function () {
    if (!function_exists('wp_create_nonce')) return false;
    $t = \Apollo\Login\apollo_generate_verification_token(1);
    return check(!empty($t));
});

echo "\n--- SECTION 5: DATABASE TABLES ---\n";
if (function_exists('wpdb')) {
    global $wpdb;
    $tables = array(
        'apollo_quiz_results',
        'apollo_simon_scores',
        'apollo_login_attempts',
        'apollo_url_rewrites'
    );
    foreach ($tables as $tbl) {
        run_test("Table {$tbl} exists", function () use ($wpdb, $tbl) {
            $full = $wpdb->prefix . $tbl;
            return check($wpdb->get_var("SHOW TABLES LIKE '{$full}'") === $full);
        });
    }
    run_test("JWT refresh table exists", function () use ($wpdb) {
        $full = $wpdb->prefix . 'apollo_jwt_refresh';
        return check($wpdb->get_var("SHOW TABLES LIKE '{$full}'") === $full);
    });
}

echo "\n--- SECTION 6: REST ENDPOINTS ---\n";
if (function_exists('rest_get_server')) {
    $routes = rest_get_server()->get_routes('apollo/v1');
    $endpoints = array(
        '/auth/login',
        '/auth/register',
        '/auth/logout',
        '/auth/reset-request',
        '/auth/reset-confirm',
        '/auth/verify-email',
        '/auth/resend-verification',
        '/auth/check-username',
        '/auth/check-email',
        '/auth/token',
        '/auth/token/refresh',
        '/quiz/questions',
        '/quiz/submit',
        '/simon/submit',
        '/simon/highscores',
        '/security/rewrites',
        '/security/attempts',
        '/app/auth',
        '/app/verify',
        '/app/revoke',
        '/dj/config',
        '/dj/permissions'
    );
    foreach ($endpoints as $ep) {
        run_test("REST {$ep}", function () use ($routes, $ep) {
            return check(isset($routes['/apollo/v1' . $ep]), "missing {$ep}");
        });
    }
}

echo "\n--- SECTION 7: REWRITE RULES ---\n";
if (function_exists('get_option')) {
    $rules = get_option('rewrite_rules');
    run_test("Query var apollo_login_page", function () {
        global $wp;
        return check(in_array('apollo_login_page', $wp->public_query_vars, true));
    });
    run_test("Rewrite /acesso", function () use ($rules) {
        foreach ((array)$rules as $rule => $q) {
            if (strpos($rule, 'acesso') !== false && strpos($q, 'apollo_login_page=login') !== false) return true;
        }
        return 'not found';
    });
    run_test("Rewrite /registre", function () use ($rules) {
        foreach ((array)$rules as $rule => $q) {
            if (strpos($rule, 'registre') !== false && strpos($q, 'apollo_login_page=register') !== false) return true;
        }
        return 'not found';
    });
}

echo "\n--- SECTION 8: SHORTCODES ---\n";
if (function_exists('shortcode_exists')) {
    $sc = array('apollo_login', 'apollo_register', 'apollo_quiz', 'apollo_simon', 'apollo_password_reset', 'apollo_verify_email');
    foreach ($sc as $s) {
        run_test("Shortcode {$s}", function () use ($s) {
            return check(shortcode_exists($s));
        });
    }
}

echo "\n--- SECTION 9: SECURITY CLASSES ---\n";
$classes = array(
    'Apollo\Login\Security\Firewall',
    'Apollo\Login\Security\SecurityHeaders',
    'Apollo\Login\Security\WPHardening',
    'Apollo\Login\Security\URLRewriter',
    'Apollo\Login\Security\RateLimiter',
    'Apollo\Login\Security\Lockout',
    'Apollo\Login\Security\JWTAuth',
);
foreach ($classes as $cls) {
    run_test("Class {$cls}", function () use ($cls) {
        return check(class_exists($cls));
    });
}
if (class_exists('Apollo\Login\Security\Firewall')) {
    run_test("Firewall::get_client_ip", function () {
        $ip = \Apollo\Login\Security\Firewall::get_client_ip();
        return check(!empty($ip) && $ip !== '0.0.0.0', $ip);
    });
}

echo "\n--- SECTION 10: QUIZ CLASSES ---\n";
run_test("QuizManager exists", function () {
    return check(class_exists('Apollo\Login\Quiz\QuizManager'));
});
run_test("SimonGame exists", function () {
    return check(class_exists('Apollo\Login\Quiz\SimonGame'));
});
if (class_exists('Apollo\Login\Quiz\QuizManager')) {
    run_test("Quiz questions pattern", function () {
        $q = \Apollo\Login\Quiz\QuizManager::get_questions('pattern');
        return check(is_array($q) && count($q) > 0);
    });
    run_test("Quiz questions ethics", function () {
        $q = \Apollo\Login\Quiz\QuizManager::get_questions('ethics');
        return check(is_array($q) && count($q) > 0);
    });
    run_test("Quiz questions reaction", function () {
        $q = \Apollo\Login\Quiz\QuizManager::get_questions('reaction');
        return check(is_array($q) && count($q) > 0);
    });
}

echo "\n--- SECTION 11: AUTH HANDLERS ---\n";
$auth_classes = array(
    'Apollo\Login\Auth\LoginHandler',
    'Apollo\Login\Auth\RegisterHandler',
    'Apollo\Login\Auth\PasswordReset',
    'Apollo\Login\Auth\EmailVerification',
    'Apollo\Login\API\AuthController',
    'Apollo\Login\API\QuizController',
    'Apollo\Login\API\SecurityController',
    'Apollo\Login\API\AppAuthController',
);
foreach ($auth_classes as $cls) {
    run_test("Auth class {$cls}", function () use ($cls) {
        return check(class_exists($cls));
    });
}
run_test("find_user_rest exists", function () {
    return check(method_exists('Apollo\Login\API\AuthController', 'find_user_rest'));
});

echo "\n--- SECTION 12: TEMPLATES ---\n";
$files = array(
    'templates/login.php',
    'templates/register.php',
    'templates/reset.php',
    'templates/verify-email.php',
    'templates/parts/new_login-form.php',
    'templates/parts/new_register-form.php',
    'templates/parts/new_aptitude-quiz.php',
    'templates/parts/password-recovery-overlay.php',
    'templates/parts/auth-head.php',
    'templates/parts/auth-scripts.php',
    'templates/parts/new_header.php',
    'templates/parts/new_footer.php',
    'includes/disable-conflicts.php',
    'assets/js/apollo-auth-scripts.js',
    'assets/css/apollo-auth-complete.css',
);
foreach ($files as $f) {
    run_test("File {$f}", function () use ($f) {
        return check(file_exists(APOLLO_LOGIN_DIR . $f), APOLLO_LOGIN_DIR . $f . ' missing');
    });
}
run_test("login.php.backup deleted", function () {
    return check(!file_exists(APOLLO_LOGIN_DIR . 'templates/login.php.backup'));
});

echo "\n--- SECTION 13: RATE LIMITER ---\n";
if (class_exists('Apollo\Login\Security\RateLimiter')) {
    run_test("RateLimiter::get_counter", function () {
        $c = \Apollo\Login\Security\RateLimiter::get_counter('test_bucket');
        return check(is_int($c), 'got ' . gettype($c));
    });
    run_test("RateLimiter::increment_counter", function () {
        \Apollo\Login\Security\RateLimiter::increment_counter('test_inc', 60);
        $c = \Apollo\Login\Security\RateLimiter::get_counter('test_inc');
        return check($c > 0, "count={$c}");
    });
    run_test("RateLimiter::clear_counter", function () {
        \Apollo\Login\Security\RateLimiter::clear_counter('test_inc');
        $c = \Apollo\Login\Security\RateLimiter::get_counter('test_inc');
        return check($c === 0, "count={$c}");
    });
}

echo "\n--- SECTION 14: AppAuth DJ CONFIG ---\n";
if (class_exists('Apollo\Login\API\AppAuthController')) {
    run_test("AppAuthController::dj_config", function () {
        $ctrl = new \Apollo\Login\API\AppAuthController();
        $req = new \WP_REST_Request('GET');
        $resp = $ctrl->dj_config($req);
        return check($resp instanceof \WP_REST_Response && $resp->get_status() === 200);
    });
}

echo "\n============================================\n";
$elapsed = round(microtime(true) - $start_time, 2);
echo "  RESULTS: {$passed} PASSED, {$failed} FAILED in {$elapsed}s\n";
echo "============================================\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n============================================\n";
echo "  TEST SUITE COMPLETE\n";
echo "============================================\n";

exit((int)($failed > 0));
