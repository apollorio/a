<?php
/**
 * Meus Núcleos — /nucleos  ·  PHASE 010
 *
 * Blank Canvas Apollo+ screen, same mount-point shape as phases 001-009.
 *
 * WHY THIS REPLACES THE OLD /nucleos BODY
 * ---------------------------------------
 * /nucleos used to render templates/nucleos.php → groups-directory.php through
 * render_blank_canvas(), i.e. the PLAIN Apollo canvas with no .ax-aside — the
 * same gap PHASE 004 closed for /comunas. Worse for this route specifically:
 * núcleos are private work teams, but the directory listed them as a public
 * catalogue (it filtered on `type = 'nucleo'` only, never on membership) and
 * printed a site-wide "total_nucleos" count. The shared aside labels this route
 * "Meus Núcleos", which is what it should always have been.
 *
 * Scope is therefore MEMBERSHIP: only núcleos the viewer belongs to or founded,
 * enforced in SQL by apollo_grp_mine_list(). The old directory template is left
 * in place, unedited, as the fallback for when apollo-templates is inactive.
 *
 * @package Apollo\Groups
 * @since   3.2.0
 * @see     parts/mine/{data,styles,layout}.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'apollo_plus_open' ) ) {
	// apollo-templates inactive — fall back to the legacy directory rather than
	// hard-failing the route.
	$page_type = 'nucleos';
	require __DIR__ . '/groups-directory.php';
	return;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/acesso?redirect=' . rawurlencode( home_url( '/nucleos' ) ) ) );
	exit;
}

$gmi_parts = __DIR__ . '/parts/mine/';

ob_start();
require $gmi_parts . 'styles.php';
$gmi_head = ob_get_clean();

apollo_plus_open(
	array(
		'title'      => get_bloginfo( 'name' ) . ' — Meus Núcleos',
		'extra_head' => $gmi_head,
		'screen'     => 'nucleos',
	)
);

$gmi_cfg = array(
	'type'         => 'nucleo',
	'manage_only'  => false,
	'kicker'       => __( 'Time de trabalho', 'apollo-groups' ),
	'title'        => __( 'Meus Núcleos', 'apollo-groups' ),
	'icon'         => 'ri-briefcase-4-fill',
	'notice'       => __( 'Núcleos são privados: só quem participa vê. Para entrar em um, peça o convite a quem já está dentro — ou fale com o', 'apollo-groups' ),
	'empty_icon'   => 'ri-briefcase-4-line',
	'empty_text'   => __( 'Você ainda não faz parte de nenhum núcleo. Núcleos são times privados de trabalho — entra-se por convite.', 'apollo-groups' ),
	'create_url'   => '',
	'create_label' => '',
);
require $gmi_parts . 'layout.php';

apollo_plus_close();
