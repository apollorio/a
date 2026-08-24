<?php
/**
 * /modera part — behaviour scripts + close of Blank Canvas Apollo+.
 *
 * Load order is a contract:
 *   core.js (already in <head>, auto-injects script.theme.js — the shell's
 *   drawer/panels/apps-pop/modal/dark-pill/copy-toast; DO NOT re-link it)
 *     → data.defaults.js   (demo dataset; APOLLO_ADMIN global — swap
 *                            section-by-section for REST in follow-ups)
 *     → inline APOLLO_MODERA_BOOT (server truth: real user, role-gated
 *                            roles matrix, cheap real counts, deep-link)
 *     → data.boot.js        (merges BOOT into APOLLO_ADMIN, keeping keys)
 *     → modera.behavior.js   (admin-specific extras core's shell lacks:
 *                            dynamic toast, L1⇄L2 aside slide)
 *     → roles.js  → render.js → views.js → app.admin.js
 *
 * @var string $apollo_modera_role   'admin' | 'mod'
 * @var array  $apollo_modera_boot
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

$apollo_modera_js_base = APOLLO_ADMIN_URL . 'assets/modera/js/';
$apollo_modera_ver      = defined( 'APOLLO_ADMIN_VERSION' ) ? APOLLO_ADMIN_VERSION : '2.1.0';

ob_start();
?>
<script src="<?php echo esc_url( $apollo_modera_js_base . 'data.defaults.js' ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>"></script>
<script>window.APOLLO_MODERA_BOOT = <?php echo wp_json_encode( $apollo_modera_boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?>;</script>
<script src="<?php echo esc_url( $apollo_modera_js_base . 'data.boot.js' ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>"></script>
<script src="<?php echo esc_url( $apollo_modera_js_base . 'modera.behavior.js' ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>"></script>
<script src="<?php echo esc_url( $apollo_modera_js_base . 'roles.js' ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>"></script>
<script src="<?php echo esc_url( $apollo_modera_js_base . 'render.js' ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>"></script>
<script src="<?php echo esc_url( $apollo_modera_js_base . 'views.js' ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>"></script>
<script src="<?php echo esc_url( $apollo_modera_js_base . 'app.admin.js' ); ?>?v=<?php echo esc_attr( $apollo_modera_ver ); ?>"></script>
<?php
$apollo_modera_extra_body = ob_get_clean();

apollo_render_document_close( $apollo_modera_extra_body );
