<?php
/**
 * /modera part — main content: horizontal subtab strip + view host.
 * Both are rendered entirely client-side by app.admin.js (buildL2 mirrors
 * into #adm-subtabs; renderView fills #adm-views per active plugin/sub).
 *
 * @package Apollo\Admin
 */

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- MAIN = full-width content + horizontal subtab strip -->
<main class="ax-main adm-main">
  <div class="adm-content">

    <div class="adm-subtabs" id="adm-subtabs" aria-label="Seções do plugin"></div>
    <div id="adm-views"><!-- rendered by app.admin.js --></div>
  </div>
</main>
