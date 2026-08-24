<?php
/**
 * Comunas — /comunas  ·  PHASE 004
 *
 * Blank Canvas Apollo+ screen. Like /feed, /portal and /anuncios, this file is
 * only a mount point: it opens the shared shell, renders the Comunas layout,
 * and closes.
 *
 * It previously delegated to groups-directory.php through render_blank_canvas(),
 * which produced the PLAIN Apollo canvas — no .ax-aside at all. That legacy
 * path is preserved below as the fallback for when apollo-templates is
 * inactive, so /comunas can never hard-fail on a missing shell.
 *
 * Registry: { slug: "comunas", template: "comunas.php", type: "virtual" }
 *
 * @package Apollo\Groups
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

$cmn_parts = __DIR__ . '/parts/cmn/';

ob_start();
require $cmn_parts . 'styles.php';
$cmn_head = ob_get_clean();

if ( function_exists( 'apollo_plus_open' ) ) {
	apollo_plus_open(
		array(
			'title'      => 'Comunas — Apollo::Rio',
			'extra_head' => $cmn_head,
			'screen'     => 'comunas',
		)
	);
	require $cmn_parts . 'layout.php';
	apollo_plus_close();
	return;
}

// ── Legacy fallback: apollo-templates inactive ──
$page_type = 'comunas';
require __DIR__ . '/groups-directory.php';
