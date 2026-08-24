<?php
/**
 * /modera part — aside (multi-level sliding burger drawer).
 * L1 = PLUGINS APOLLO (fathers, client-rendered into #adm-nav-l1 by
 * app.admin.js/render.js from the role-gated D.plugins list) → tap slides
 * L2 = that plugin's sub-links (#adm-nav-l2). Ids are a JS contract — do
 * not rename (core.js's built-in shell binds #ax-aside/#aside-pill; the
 * L1⇄L2 slide + #nav-back are owned by modera.behavior.js/app.admin.js).
 *
 * Footer user row is pre-filled with real WP user data so there's no
 * flash of placeholder content before app.admin.js's reflectRole() runs.
 *
 * @var array $apollo_modera_boot
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

$apollo_modera_cu       = isset( $apollo_modera_boot['currentUser'] ) ? $apollo_modera_boot['currentUser'] : array();
$apollo_modera_initials = isset( $apollo_modera_cu['initials'] ) ? $apollo_modera_cu['initials'] : 'AP';
$apollo_modera_name     = isset( $apollo_modera_cu['name'] ) ? $apollo_modera_cu['name'] : '';
$apollo_modera_role_lbl = isset( $apollo_modera_boot['roles'][ $apollo_modera_role ]['label'] )
    ? $apollo_modera_boot['roles'][ $apollo_modera_role ]['label']
    : ( 'admin' === $apollo_modera_role ? 'apollo' : 'MOD' );
?>
<!-- ASIDE = MULTI-LEVEL SLIDING BURGER DRAWER
     L1 = PLUGINS APOLLO (fathers) → tap slides left to L2 = that plugin's sub-links -->
<aside class="ax-aside" id="ax-aside" aria-label="Plugins Apollo">
  <div class="s">
    <div class="nav-stack" id="nav-stack">
      <!-- ── LEVEL 1 · PLUGINS APOLLO ── -->
      <div class="nav-pane">
        <div class="hd"><div class="lg"><i class="ri-apollo-fill"></i></div><span class="wm">apollo<b>::</b>rio</span>
          <button type="button" class="ax-aside-close" aria-label="Fechar menu"><i class="ri-close-line"></i></button></div>
        <nav class="ax-aside-scroll"><div class="nb" id="adm-nav-l1"><!-- father plugins --></div></nav>
      </div>
      <!-- ── LEVEL 2 · SUB-LINKS ── -->
      <div class="nav-pane">
        <div class="nav-l2-hd">
          <button type="button" class="nav-back" id="nav-back" aria-label="Voltar aos plugins"><i class="ri-arrow-left-s-line"></i></button>
          <span class="nav-l2-eyebrow" id="nav-l2-name">plugin</span>
          <span class="nav-l2-title" id="nav-l2-title">Seções</span>
        </div>
        <nav class="ax-aside-scroll"><div class="nb" id="adm-nav-l2"><!-- sub-links --></div></nav>
      </div>
    </div>
    <div class="ft">
      <div class="adj" id="aside-prefs">
        <button type="button" class="adjl"><i class="ri-equalizer-line"></i><span class="sn">Preferências</span></button>
        <button type="button" class="pill" id="aside-pill" aria-label="Alternar modo escuro" aria-pressed="false"><div class="th"></div></button>
      </div>
      <div class="urow">
        <div class="avw"><div class="av"><?php echo esc_html( $apollo_modera_initials ); ?></div><div class="aon"></div></div>
        <div class="ui"><div class="un"><?php echo esc_html( $apollo_modera_name ); ?></div><div class="uro" id="aside-role"><?php echo esc_html( $apollo_modera_role_lbl ); ?></div></div>
        <div class="um"><i class="ri-more-2-line"></i></div>
      </div>
    </div>
  </div>
</aside>
