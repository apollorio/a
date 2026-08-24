#!/usr/bin/env php
<?php
/**
 * Audit apollo-pages-rest.json templates for canonical head usage.
 * Writes NDJSON to wp-content/debug-c0df4e.log (session c0df4e).
 */
$wp_content = dirname(__DIR__, 2);
$registry   = $wp_content . '/apollo-pages-rest.json';
$log        = $wp_content . '/debug-c0df4e.log';

$data = json_decode((string) file_get_contents($registry), true);
if (! is_array($data)) {
    fwrite(STDERR, "Cannot read registry\n");
    exit(1);
}

$plugins_dir = $wp_content . '/plugins';
$checked     = 0;
$compliant   = 0;
$missing     = 0;

foreach ($data as $plugin => $block) {
    if (! is_array($block) || str_starts_with((string) $plugin, '_')) {
        continue;
    }
    foreach ($block['pages'] ?? array() as $page) {
        if (! is_array($page) || empty($page['template'])) {
            continue;
        }
        $type = $page['type'] ?? '';
        if (in_array($type, array('redirect', 'redirect_301', 'admin'), true)) {
            continue;
        }
        $tpl = (string) $page['template'];
        $owners = array((string) $plugin);
        if (! empty($page['owner'])) {
            $owners[] = (string) $page['owner'];
        }
        $paths = array();
        foreach (array_unique($owners) as $owner) {
            $paths[] = $plugins_dir . '/' . $owner . '/' . $tpl;
            $paths[] = $plugins_dir . '/' . $owner . '/templates/' . basename($tpl);
        }
        $file = null;
        foreach ($paths as $p) {
            if (is_readable($p)) {
                $file = $p;
                break;
            }
        }
        $checked++;
        if ($file === null) {
            $missing++;
            $entry = array(
                'sessionId'    => 'c0df4e',
                'hypothesisId' => 'C',
                'location'     => 'audit-pages-rest-head.php',
                'message'      => 'template file missing',
                'data'         => array('plugin' => $plugin, 'slug' => $page['slug'] ?? '', 'template' => $tpl),
                'timestamp'    => (int) round(microtime(true) * 1000),
            );
            file_put_contents($log, json_encode($entry) . "\n", FILE_APPEND);
            continue;
        }
        $src = (string) file_get_contents($file);
        $ok  = str_contains($src, 'apollo_render_document_open')
            || str_contains($src, 'apollo_render_document_head')
            || str_contains($src, 'PersistentUI::head')
            || preg_match('/require\s+\$parts\s*\.\s*[\'"]head\.php[\'"]/', $src)
            || preg_match('/require\s+.*sig-head\.php/', $src)
            || preg_match('/groups-directory\.php/', $src);
        if ($ok) {
            $compliant++;
        }
        $entry = array(
            'sessionId'    => 'c0df4e',
            'hypothesisId' => $ok ? 'D' : 'E',
            'location'     => 'audit-pages-rest-head.php',
            'message'      => $ok ? 'template uses canonical head' : 'template missing canonical head',
            'data'         => array(
                'plugin'   => $plugin,
                'slug'     => $page['slug'] ?? '',
                'template' => $tpl,
                'file'     => str_replace($wp_content, 'wp-content', $file),
            ),
            'timestamp'    => (int) round(microtime(true) * 1000),
        );
        file_put_contents($log, json_encode($entry) . "\n", FILE_APPEND);
    }
}

$summary = array(
    'sessionId'    => 'c0df4e',
    'hypothesisId' => 'SUM',
    'location'     => 'audit-pages-rest-head.php',
    'message'      => 'audit complete',
    'data'         => array('checked' => $checked, 'compliant' => $compliant, 'missing_files' => $missing),
    'timestamp'    => (int) round(microtime(true) * 1000),
);
file_put_contents($log, json_encode($summary) . "\n", FILE_APPEND);

echo "Checked: {$checked}, compliant: {$compliant}, missing files: {$missing}\n";
