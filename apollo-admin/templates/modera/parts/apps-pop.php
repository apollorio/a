<?php
/**
 * /modera part — Apps popup (jumps to plugin fathers).
 * "Sistema" is administrator-only (apollo-core settings) — never rendered
 * for a MOD (editor), mirroring the PLUGINS catalog's roles:['admin'] gate.
 *
 * @var string $apollo_modera_role 'admin' | 'mod'
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- ═══ APPS POPUP — jumps to plugin fathers ═══ -->
<div class="apps-pop" id="apps-pop" aria-hidden="true">
  <div class="apps-pop-hd"><p class="apps-pop-title">Apollo Suite</p><button type="button" class="apps-pop-close" data-close-apps aria-label="Fechar apps"><i class="ri-close-line"></i></button></div>
  <div class="apps-grid">
    <a class="app-cell" href="#" data-goto="membership"><div class="app-icon"><i class="ri-vip-crown-2-line"></i></div><span>Filiações</span></a>
    <a class="app-cell" href="#" data-goto="events"><div class="app-icon"><i class="ri-calendar-event-line"></i></div><span>Eventos</span></a>
    <a class="app-cell" href="#" data-goto="adverts"><div class="app-icon"><i class="ri-megaphone-line"></i></div><span>Anúncios</span></a>
    <a class="app-cell" href="#" data-goto="moderation"><div class="app-icon"><i class="ri-shield-check-line"></i></div><span>Moderação</span></a>
    <a class="app-cell" href="#" data-goto="apollodj"><div class="app-icon"><i class="ri-disc-line"></i></div><span>ApolloDJ</span></a>
    <?php if ( 'admin' === $apollo_modera_role ) : ?>
    <a class="app-cell" href="#" data-goto="system"><div class="app-icon"><i class="ri-settings-4-line"></i></div><span>Sistema</span></a>
    <?php endif; ?>
  </div>
</div>
