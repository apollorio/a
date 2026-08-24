<?php
/**
 * /modera part — <head>.
 *
 * "Blank Canvas Apollo+" — uses the ecosystem-wide canonical helper
 * (apollo-core/includes/blank-canvas-templates.php: apollo_render_blank_canvas_open)
 * which prints the EXACT mandatory fixed head contract: charset, preconnects,
 * core.js (single source of tokens/theme/icons), empty :root, viewport+PWA
 * metas, OG + Twitter Card tags, title. Deliberately self-contained (does
 * NOT delegate to apollo-seo's `apollo/seo/head`, whose virtual-route branch
 * forces robots=index,follow — wrong for an authenticated admin panel).
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

$apollo_modera_css = APOLLO_ADMIN_URL . 'assets/modera/css/modera.css';
$apollo_modera_ver  = defined( 'APOLLO_ADMIN_VERSION' ) ? APOLLO_ADMIN_VERSION : '2.1.0';
$apollo_modera_url  = home_url( '/modera/' . ( '' !== $apollo_modera_plugin ? $apollo_modera_plugin . '/' . ( '' !== $apollo_modera_sub ? $apollo_modera_sub . '/' : '' ) : '' ) );

ob_start();
?>
<?php
// Canonical USER-ROLE map — hidden documentation comment (apollo-core).
if ( \function_exists( 'apollo_roles_map_print_comment' ) ) {
    apollo_roles_map_print_comment();
}
?>
<!-- page extras ONLY (tokens/shell/components come from core.js) -->
<link rel="stylesheet" href="<?php echo esc_url( $apollo_modera_css ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>">
<?php
$apollo_modera_extra_head = ob_get_clean();

apollo_render_blank_canvas_open(
    array(
        'title'          => 'apollo::rio · Admin & Moderação',
        'og_title'       => 'Painel Apollo — Admin & Moderação',
        'og_description' => 'Painel administrativo e de moderação do ecossistema Apollo.',
        'url'            => $apollo_modera_url,
        'robots'         => 'noindex, nofollow', // private, authenticated panel — never indexed.
        'lang'           => 'pt-BR',
        'theme'          => 'light',
        'html_class'     => 'is-logged',
        'extra_head'     => $apollo_modera_extra_head,
    )
);
?>
