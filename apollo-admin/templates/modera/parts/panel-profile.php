<?php
/**
 * /modera part — profile panel. Pre-filled with real WP user data so there is
 * no flash of placeholder content before app.admin.js's reflectRole() runs
 * on boot (it overwrites #pf-role/#pf-badge/#aside-role from the same
 * server-gated D.roles[role] the moment the page loads).
 *
 * @var string $apollo_modera_role 'admin' | 'mod'
 * @var array  $apollo_modera_boot
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

$apollo_modera_cu       = isset( $apollo_modera_boot['currentUser'] ) ? $apollo_modera_boot['currentUser'] : array();
$apollo_modera_initials = isset( $apollo_modera_cu['initials'] ) ? $apollo_modera_cu['initials'] : 'AP';
$apollo_modera_name     = isset( $apollo_modera_cu['name'] ) ? $apollo_modera_cu['name'] : '';
$apollo_modera_title    = isset( $apollo_modera_cu['title'] ) ? $apollo_modera_cu['title'] : 'Núcleo Apollo';
$apollo_modera_is_mod   = 'mod' === $apollo_modera_role;
$apollo_modera_badge    = $apollo_modera_is_mod ? 'Mod' : 'Admin';
/* mirrors app.admin.js reflectRole(): pfBadge.className = 'tag ' + (isMod ? 'tag' : 'tag-accent') */
$apollo_modera_badge_cl = 'tag ' . ( $apollo_modera_is_mod ? 'tag' : 'tag-accent' );
?>
<!-- ═══ PROFILE PANEL ═══ -->
<div class="panel-r" id="panel-profile" aria-label="Usuário" aria-hidden="true">
  <div class="panel-r-hd"><span class="panel-r-title">Usuá::rio</span><button class="ax-ic" data-close="panel-profile" aria-label="Fechar"><i class="ri-close-line"></i></button></div>
  <div class="panel-r-body">
    <div class="profile-panel-top">
      <div class="profile-panel-av"><?php echo esc_html( $apollo_modera_initials ); ?></div>
      <p class="profile-panel-name"><?php echo esc_html( $apollo_modera_name ); ?></p>
      <p class="profile-panel-role" id="pf-role"><?php echo esc_html( $apollo_modera_title ); ?></p>
      <div class="flex-row" style="justify-content:center;margin-top:10px;gap:5px;"><span class="<?php echo esc_attr( $apollo_modera_badge_cl ); ?>" id="pf-badge"><?php echo esc_html( $apollo_modera_badge ); ?></span></div>
    </div>
    <div class="profile-panel-actions">
      <div class="profile-action"><i class="ri-id-card-line"></i>Meu perfil</div>
      <button type="button" class="profile-action" onclick="var p=document.getElementById('aside-pill');if(p)p.click();"><i class="ri-palette-line"></i>Aparência</button>
      <a class="profile-action danger" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><i class="ri-door-open-line"></i>Sair</a>
    </div>
  </div>
</div>
