<?php
/**
 * Minhas Comunas — /comunas/gestao  ·  PHASE 009
 *
 * Blank Canvas Apollo+ screen, same mount-point shape as phases 001-008: opens
 * the shared shell, renders the layout, closes. This is the route the shared
 * aside has always linked to (aside.php's gestor group, slug 'comunas/gestao')
 * — it was a dead link until now.
 *
 * Scope is MANAGEMENT: only comunas where the viewer is the founder, an admin
 * or a MOD. The public directory at /comunas (comunas.php, PHASE 004) is
 * untouched and keeps listing every public comuna.
 *
 * @package Apollo\Groups
 * @since   3.2.0
 * @see     parts/mine/{data,styles,layout}.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/acesso?redirect=' . rawurlencode( home_url( '/comunas/gestao' ) ) ) );
	exit;
}

if ( ! function_exists( 'apollo_plus_open' ) ) {
	wp_die( esc_html__( 'Apollo Templates: shell API indisponível.', 'apollo-groups' ) );
}

$gmi_parts = __DIR__ . '/parts/mine/';

ob_start();
require $gmi_parts . 'styles.php';
$gmi_head = ob_get_clean();

apollo_plus_open(
	array(
		'title'      => get_bloginfo( 'name' ) . ' — Minhas Comunas',
		'extra_head' => $gmi_head,
		'screen'     => 'comunas/gestao',
	)
);

$gmi_cfg = array(
	'type'         => 'comuna',
	'manage_only'  => true,
	'kicker'       => __( 'Gestão', 'apollo-groups' ),
	'title'        => __( 'Minhas Comunas', 'apollo-groups' ),
	'icon'         => 'ri-group-3-fill',
	'notice'       => __( 'Aparecem aqui só as comunas em que você é fundador, admin ou MOD. Precisa de ajuda com a gestão? Fale com o', 'apollo-groups' ),
	'empty_icon'   => 'ri-group-3-line',
	'empty_text'   => __( 'Você ainda não administra nenhuma comuna. Crie a sua ou peça a um fundador para te promover a admin.', 'apollo-groups' ),
	'create_url'   => home_url( '/criar-comuna' ),
	'create_label' => __( 'Criar Comuna', 'apollo-groups' ),
);
require $gmi_parts . 'layout.php';

apollo_plus_close();
