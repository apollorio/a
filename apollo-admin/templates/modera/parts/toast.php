<?php
/**
 * /modera part — toast.
 * Dynamic messages (msg + icon) come from modera.behavior.js's toast(),
 * called throughout app.admin.js for every moderation/grant action. Shares
 * the #toast-host/#toast-x ids with core.js's own copy-to-clipboard toast
 * pattern — safe to coexist since this page has no #toast-btn (core's
 * show/loading path is never triggered; only its harmless #toast-x
 * hide-on-click also fires, redundantly but idempotently, alongside ours).
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- TOAST -->
<div id="toast-host" role="status" aria-live="polite" aria-atomic="true">
  <div class="toast-demo" id="toast-panel" style="display:none">
    <span class="toast-icon" aria-hidden="true"><i class="ri-checkbox-circle-line"></i></span>
    <span class="toast-text" id="toast-text">—</span>
    <button type="button" class="toast-close" id="toast-x" aria-label="Fechar"><i class="ri-close-line"></i></button>
  </div>
</div>
