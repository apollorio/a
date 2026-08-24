<?php
/**
 * /modera — frontend Apollo suite admin & moderation panel (shell orchestrator).
 *
 * 100% modular: every block of the approved layout base
 * (_dev web/admin/index.html) lives in its own part. Structure is a contract —
 * ids (#burger #ax-aside #ax-overlay #ic-act #ic-apps #ic-pf #apps-pop
 * #aside-pill #adm-nav-l1 #adm-nav-l2 #adm-subtabs #adm-views) are consumed
 * by the JS layer and MUST NOT change.
 *
 * Access: Router::handle_template() already gated to administrator (apollo →
 * role 'admin', sees all) or editor (MOD → role 'mod', subset).
 *
 * @var string $apollo_modera_role   'admin' | 'mod'
 * @var string $apollo_modera_plugin deep-linked plugin id ('' = default)
 * @var string $apollo_modera_sub    deep-linked sub id ('' = default)
 *
 * @package Apollo\Admin
 * @since   2.1.0
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

$apollo_modera_role   = isset( $apollo_modera_role ) ? $apollo_modera_role : \Apollo\Admin\Frontend\Router::modera_role();
$apollo_modera_plugin = isset( $apollo_modera_plugin ) ? $apollo_modera_plugin : '';
$apollo_modera_sub    = isset( $apollo_modera_sub ) ? $apollo_modera_sub : '';

if ( '' === $apollo_modera_role ) {
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

$apollo_modera_user = wp_get_current_user();
$apollo_modera_boot = \Apollo\Admin\Frontend\ModeraData::payload( $apollo_modera_role, $apollo_modera_plugin, $apollo_modera_sub );
$apollo_modera_dir  = __DIR__ . '/parts/';

// head.php owns the full <!DOCTYPE html><html>…<head>…</head> (Blank Canvas
// Apollo+ via apollo_render_blank_canvas_open) — do not print it again here.
require $apollo_modera_dir . 'head.php';
?>
<?php
/* /modera is a SPECIALISED Blank Canvas Apollo+ shell: it honours the full
   contract (#burger #ic-act #ic-apps #ic-pf #apps-pop #ax-overlay #ax-aside)
   but adds the moderation-only role-switch (Admin⇄Mod) and the multi-level
   L1⇄L2 drawer, which the generic app-shell has no equivalent for.
   Claim the shell slot so nothing can inject a second topbar over it. */
if ( ! \defined( 'APOLLO_APP_SHELL_LOADED' ) ) {
    \define( 'APOLLO_APP_SHELL_LOADED', true );
}
if ( ! \defined( 'APOLLO_NAVBAR_LOADED' ) ) {
    \define( 'APOLLO_NAVBAR_LOADED', true );
}
?>
<body class="ax-body<?php echo 'mod' === $apollo_modera_role ? ' is-mod' : ''; ?>">

<a href="#" id="apollo-report-anchor" data-apollo-report-trigger hidden aria-hidden="true"></a>

<?php require $apollo_modera_dir . 'topbar.php'; ?>

<div class="ax-overlay" id="ax-overlay" aria-hidden="true"></div>

<?php require $apollo_modera_dir . 'panel-activities.php'; ?>
<?php require $apollo_modera_dir . 'apps-pop.php'; ?>
<?php require $apollo_modera_dir . 'panel-profile.php'; ?>

<!-- ═══ SHELL ═══ -->
<div class="ax-shell">
<?php require $apollo_modera_dir . 'aside.php'; ?>
<?php require $apollo_modera_dir . 'main.php'; ?>
</div><!-- /.ax-shell -->

<?php require $apollo_modera_dir . 'modal.php'; ?>
<?php require $apollo_modera_dir . 'toast.php'; ?>
<?php
// scripts.php owns the behaviour <script> tags AND closes </body></html>
// (apollo_render_document_close) — do not print the closing tags again here.
require $apollo_modera_dir . 'scripts.php';
