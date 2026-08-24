<?php
/**
 * /modera part — right panel: activities (notifications / messages).
 * Feeds are rendered client-side into #notif-feed / #msgs-feed.
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- ═══ ACTIVITIES PANEL ═══ -->
<div class="panel-r" id="panel-act" aria-label="Atividades" aria-hidden="true">
  <div class="panel-r-hd"><span class="panel-r-title">Atividades</span><button class="ax-ic" data-close="panel-act" aria-label="Fechar"><i class="ri-close-line"></i></button></div>
  <div class="panel-tabs-row" role="tablist">
    <button class="panel-tab active" data-ptab="notif-pane" role="tab" aria-selected="true">Notificações</button>
    <button class="panel-tab" data-ptab="msgs-pane" role="tab" aria-selected="false">Mensagens</button>
  </div>
  <div class="panel-r-body">
    <div class="panel-tab-pane active" id="notif-pane" role="tabpanel"><div class="panel-feed" id="notif-feed"></div></div>
    <div class="panel-tab-pane" id="msgs-pane" role="tabpanel" hidden><div class="panel-feed" id="msgs-feed"></div></div>
  </div>
</div>
