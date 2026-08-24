<?php

/**
 * Shared App-Shell — Top blur + topbar
 *
 * @package Apollo\Event
 */
if (! defined('ABSPATH')) {
    exit;
}

/* Canonical Apollo app-shell owns the topbar. This local copy stays only as a
   fallback for when apollo-templates is unavailable — it must never render
   twice. Same contract ids either way (#burger #ic-act #ic-apps #ic-pf). */
if (defined('APOLLO_APP_SHELL_LOADED')) {
    return;
}
?>
<!-- Top Blur Background -->
<div class="ax-top-blur"></div>

<!-- ═══ TOPBAR (estrutura exata do showcase) ═══ -->
<header class="ax-top" role="banner">
    <div class="ax-top-l">
        <button class="ax-burger" id="burger" aria-label="Menu"><i class="ri-menu-line"></i></button>
        <div class="ax-top-brand" aria-label="apollo::rio">
            <div class="ax-top-logo"><i class="ri-apollo-fill"></i></div>
            <span class="ax-top-wm">apollo<b>::</b>rio</span>
        </div>
    </div>
    <div class="ax-top-r">
        <button class="ax-ic" id="ic-act" aria-label="Atividades"><span class="ax-dot"></span><i class="ri-pulse-line"></i></button>
        <button class="ax-ic" id="ic-apps" aria-label="Apps"><i class="ri-apps-2-line"></i></button>
        <button class="ax-avb" id="ic-pf" aria-label="Usuário"><span class="ax-avb-init"><?php echo esc_html(apollo_event_user_initials($current_user->display_name ?? '')); ?></span></button>
    </div>
</header>