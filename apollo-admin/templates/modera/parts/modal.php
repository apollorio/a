<?php
/**
 * /modera part — confirm modal.
 * No custom JS needed: core.js's built-in shell already handles
 * [data-modal]/[data-modal-close]/click-outside generically (confirmed
 * unused-but-scaffolded in the reference mockup — no view currently opens
 * it programmatically; when a future view needs a dynamic confirm, trigger
 * it declaratively with a `data-modal="adm-modal"` element).
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- MODAL -->
<div class="modal-backdrop" id="adm-modal">
  <div class="modal">
    <div class="modal-title" id="adm-modal-title">Confirmar</div>
    <p class="txt-secondary text-sm modal-lead" id="adm-modal-body">—</p>
    <div class="modal-actions"><button class="btn btn-secondary" data-modal-close>Cancelar</button><button class="btn btn-primary" id="adm-modal-ok">Confirmar</button></div>
  </div>
</div>
