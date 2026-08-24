<?php
/**
 * /modera part — topbar. Icons are the exact design-system SVGs.
 * Role switch (admin ⇄ mod preview) renders ONLY for administrators;
 * a MOD (editor) gets a static chip — the server never ships the admin
 * role entry to mods, so the client cannot escalate either way.
 *
 * @var string   $apollo_modera_role 'admin' | 'mod'
 * @var \WP_User $apollo_modera_user
 * @var array    $apollo_modera_boot
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

$apollo_modera_initials = isset( $apollo_modera_boot['currentUser']['initials'] ) ? $apollo_modera_boot['currentUser']['initials'] : 'AP';
?>
<!-- ═══ TOPBAR — icons are the exact design-system SVGs ═══ -->
<div class="ax-top-blur" aria-hidden="true"></div>
<header class="ax-top" role="banner">
  <div class="ax-top-l">
    <button class="ax-burger" id="burger" aria-label="Menu"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M3 5h18v2H3V5Zm0 6h18v2H3v-2Zm0 6h18v2H3v-2Z"/></svg></button>
    <div class="ax-top-brand" aria-label="apollo::rio"><i class="ri-apollo-fill"></i></div>
    <?php if ( 'admin' === $apollo_modera_role ) : ?>
    <div class="role-switch" id="role-switch" role="group" aria-label="Perfil de acesso">
      <button type="button" data-role="admin" class="is-on"><i class="ri-shield-star-line"></i>Admin</button>
      <button type="button" data-role="mod"><i class="ri-shield-user-line"></i>Mod</button>
    </div>
    <?php else : ?>
    <div class="role-switch" id="role-switch" role="group" aria-label="Perfil de acesso">
      <button type="button" data-role="mod" class="is-on"><i class="ri-shield-user-line"></i>Mod</button>
    </div>
    <?php endif; ?>
  </div>
  <div class="ax-top-r">
    <button class="ax-ic" id="ic-act" aria-label="Atividades"><span class="ax-dot"></span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M6.11629 20.0868C3.62137 18.2684 2 15.3236 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 15.3236 20.3786 18.2684 17.8837 20.0868L16.8692 18.348C18.7729 16.8856 20 14.5861 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 14.5861 5.2271 16.8856 7.1308 18.348L6.11629 20.0868ZM8.14965 16.6018C6.83562 15.5012 6 13.8482 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 13.8482 17.1644 15.5012 15.8503 16.6018L14.8203 14.8365C15.549 14.112 16 13.1087 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 13.1087 8.45105 14.112 9.17965 14.8365L8.14965 16.6018ZM11 13H13L14 22H10L11 13Z"></path></svg></button>
    <button class="ax-ic" id="ic-apps" aria-label="Apps"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M21 17C21 19.2091 19.2091 21 17 21C14.7909 21 13 19.2091 13 17C13 14.7909 14.7909 13 17 13C19.2091 13 21 14.7909 21 17ZM11 7C11 9.20914 9.20914 11 7 11C4.79086 11 3 9.20914 3 7C3 4.79086 4.79086 3 7 3C9.20914 3 11 4.79086 11 7ZM21 7C21 9.20914 19.2091 11 17 11C16.2584 11 15.5634 10.7972 14.9678 10.4453L10.4453 14.9678C10.7972 15.5634 11 16.2584 11 17C11 19.2091 9.20914 21 7 21C4.79086 21 3 19.2091 3 17C3 14.7909 4.79086 13 7 13C7.74116 13 8.43593 13.2022 9.03125 13.5537L13.5537 9.03125C13.2022 8.43593 13 7.74116 13 7C13 4.79086 14.7909 3 17 3C19.2091 3 21 4.79086 21 7Z"></path></svg></button>
    <button class="ax-avb" id="ic-pf" aria-label="Usuário"><span class="ax-avb-init"><?php echo esc_html( $apollo_modera_initials ); ?></span></button>
  </div>
</header>
