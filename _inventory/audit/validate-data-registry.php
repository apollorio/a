<?php
/**
 * validate-data-registry.php
 *
 * Static structural validator for _inventory/data-registry.json.
 * No WordPress bootstrap required — pure PHP + JSON.
 *
 * Usage:  php validate-data-registry.php [path-to-data-registry.json]
 * Exit:   0 = all checks pass, 1 = one or more failures.
 *
 * Checks (plan Phase 16):
 *   - valid JSON
 *   - schema/version present
 *   - unique field ids
 *   - declared field_count == actual fields length (per cpt)
 *   - totals.* == recomputed totals
 *   - totals.gaps == gaps length
 *   - storage.kind in the allowed set
 *   - empty_behavior in the allowed set
 *   - required per-field properties present
 *   - no duplicate canonical storage.key within a cpt (unless kind=relation/derived/runtime)
 *
 * @package Apollo\Inventory\Audit
 */

declare(strict_types=1);

$path = $argv[1] ?? (__DIR__ . '/../data-registry.json');

$fail = 0;
$ok   = static function (string $m): void { fwrite(STDOUT, "  PASS  $m\n"); };
$err  = static function (string $m) use (&$fail): void { fwrite(STDOUT, "  FAIL  $m\n"); $fail++; };

if (! is_readable($path)) {
    fwrite(STDERR, "Cannot read: $path\n");
    exit(1);
}

$raw  = file_get_contents($path);
$data = json_decode($raw, true);
if (JSON_ERROR_NONE !== json_last_error()) {
    fwrite(STDERR, 'Invalid JSON: ' . json_last_error_msg() . "\n");
    exit(1);
}
$ok('valid JSON');

foreach (['$schema', '$version'] as $k) {
    isset($data[$k]) ? $ok("$k present") : $err("$k missing");
}

$allowed_kinds  = ['post', 'meta', 'taxonomy', 'attachment', 'relation', 'derived', 'runtime', 'table'];
$allowed_empty  = ['hide', 'fallback', 'omit', 'required'];
$required_props  = ['id', 'label', 'cpt', 'storage', 'empty_behavior'];

$cpts = $data['cpts'] ?? [];
if (! is_array($cpts) || 0 === count($cpts)) {
    $err('cpts missing/empty');
    exit(1);
}

$all_ids   = [];
$recomputed = [];

foreach ($cpts as $cpt_name => $cpt) {
    $fields = $cpt['fields'] ?? [];
    $n      = is_array($fields) ? count($fields) : 0;
    $recomputed[$cpt_name] = $n;

    $declared = $cpt['field_count'] ?? null;
    (null !== $declared && (int) $declared === $n)
        ? $ok("$cpt_name.field_count = $n")
        : $err("$cpt_name.field_count declared=" . var_export($declared, true) . " actual=$n");

    $seen_keys = [];
    foreach ($fields as $i => $f) {
        foreach ($required_props as $rp) {
            if (! isset($f[$rp])) {
                $err("$cpt_name.fields[$i] missing '$rp'");
            }
        }
        $id = $f['id'] ?? "($cpt_name#$i)";
        if (isset($all_ids[$id])) {
            $err("duplicate field id: $id");
        }
        $all_ids[$id] = true;

        $kind = $f['storage']['kind'] ?? null;
        if (null !== $kind && ! in_array($kind, $allowed_kinds, true)) {
            $err("$id invalid storage.kind '$kind'");
        }
        $eb = $f['empty_behavior'] ?? null;
        if (null !== $eb && ! in_array($eb, $allowed_empty, true)) {
            $err("$id invalid empty_behavior '$eb'");
        }

        // duplicate canonical key within cpt (persisted kinds only)
        if (in_array($kind, ['meta', 'post', 'taxonomy', 'attachment', 'table'], true)) {
            $key = $f['storage']['key'] ?? '';
            if ('' !== $key) {
                if (isset($seen_keys[$key])) {
                    $err("$cpt_name duplicate storage.key '$key' ($id and {$seen_keys[$key]})");
                } else {
                    $seen_keys[$key] = $id;
                }
            }
        }
    }
}

// totals
$totals = $data['totals'] ?? [];
$map    = ['event' => 'event_fields', 'dj' => 'dj_fields', 'local' => 'local_fields'];
foreach ($map as $cpt_name => $tk) {
    if (isset($recomputed[$cpt_name])) {
        $decl = $totals[$tk] ?? null;
        ((int) $decl === $recomputed[$cpt_name])
            ? $ok("totals.$tk = {$recomputed[$cpt_name]}")
            : $err("totals.$tk declared=" . var_export($decl, true) . " actual={$recomputed[$cpt_name]}");
    }
}

$gaps_n = is_array($data['gaps'] ?? null) ? count($data['gaps']) : 0;
((int) ($totals['gaps'] ?? -1) === $gaps_n)
    ? $ok("totals.gaps = $gaps_n")
    : $err('totals.gaps declared=' . var_export($totals['gaps'] ?? null, true) . " actual=$gaps_n");

fwrite(STDOUT, $fail === 0 ? "\nRESULT: PASS\n" : "\nRESULT: FAIL ($fail issue" . ($fail === 1 ? '' : 's') . ")\n");
exit($fail === 0 ? 0 : 1);
