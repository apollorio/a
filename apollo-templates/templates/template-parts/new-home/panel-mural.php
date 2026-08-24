<?php
/**
 * Panel: Mural — Dynamic Single Page Viewer (UP)
 *
 * Universal single-page viewer that slides UP from center.
 * Loads content dynamically via REST API based on data attributes.
 * Available for BOTH guest and logged users.
 *
 * data-panel="mural" data-dir="up"
 *
 * Trigger format:
 *   <a data-to="mural" data-dir="up" data-type="dj" data-id="123">
 *
 * Supported types: event, dj, local, classified, group, post, profile, hub
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → panels.mural
 */
if (!defined('ABSPATH')) exit;

$rest_url = esc_url_raw(rest_url('apollo/v1/'));
$nonce    = wp_create_nonce('wp_rest');
?>
<section data-panel="mural" data-glyph="▲">

    <!-- ── Header ── -->
    <header class="pnl-head">
        <button class="pnl-head__back" onclick="ApolloSlider&&ApolloSlider.back()" aria-label="Voltar">
            <i class="ri-arrow-left-s-line"></i>
        </button>
        <h2 class="pnl-head__title" id="muralTitle"></h2>
        <div class="pnl-head__actions">
            <button class="pnl-btn pnl-btn--icon pnl-head__share" aria-label="Compartilhar" style="display:none">
                <i class="ri-share-forward-line"></i>
            </button>
        </div>
    </header>

    <!-- ── Loader ── -->
    <div class="pnl-loader" id="muralLoader">
        <div class="pnl-loader__ring"></div>
    </div>

    <!-- ── Dynamic Content ── -->
    <div class="pnl-body" id="muralBody" style="display:none">
        <div id="muralContent"></div>

        <?php
        /**
         * Hook: apollo/mural/after_content
         * Plugins inject additional UI below loaded content (related items, actions, etc.)
         */
        do_action('apollo/mural/after_content');
        ?>
    </div>
</section>

<script>
(function(){
    var REST    = <?php echo wp_json_encode($rest_url); ?>;
    var NONCE   = <?php echo wp_json_encode($nonce); ?>;
    var loader  = document.getElementById('muralLoader');
    var body    = document.getElementById('muralBody');
    var content = document.getElementById('muralContent');
    var title   = document.getElementById('muralTitle');
    var share   = document.querySelector('.pnl-head__share');

    var TYPE_MAP = {
        'event'     : { endpoint: 'events/',       label: 'Evento' },
        'dj'        : { endpoint: 'djs/',           label: 'DJ' },
        'local'     : { endpoint: 'locals/',         label: 'Espaço' },
        'classified': { endpoint: 'classifieds/',    label: 'Anúncio' },
        'group'     : { endpoint: 'groups/',         label: 'Grupo' },
        'post'      : { endpoint: 'feed/',           label: 'Post' },
        'profile'   : { endpoint: 'profile/',        label: 'Perfil' },
        'hub'       : { endpoint: 'hubs/',           label: 'Hub' }
    };

    function showLoader(){ loader.style.display = ''; body.style.display = 'none'; }
    function showContent(){ loader.style.display = 'none'; body.style.display = ''; }

    /* ── Listen for mural triggers ── */
    document.addEventListener('click', function(e){
        var trigger = e.target.closest('[data-to="mural"]');
        if (!trigger) return;

        var type = trigger.getAttribute('data-type');
        var id   = trigger.getAttribute('data-id');
        if (!type || !id) return;

        loadMural(type, id);
    });

    function loadMural(type, id){
        var config = TYPE_MAP[type];
        if (!config) return;

        title.textContent = config.label;
        showLoader();

        fetch(REST + config.endpoint + id, {
            headers: { 'X-WP-Nonce': NONCE }
        })
        .then(function(r){ return r.ok ? r.text() : Promise.reject(r.status); })
        .then(function(html){
            content.innerHTML = html;
            showContent();

            if (share) share.style.display = '';

            /* Animate entrance */
            if (typeof gsap !== 'undefined') {
                gsap.fromTo('#muralContent > *',
                    { opacity: 0, y: 12 },
                    { opacity: 1, y: 0, stagger: .06, duration: .4, ease: 'power3.out' }
                );
            }

            /* Dispatch loaded event for plugins */
            document.dispatchEvent(new CustomEvent('apollo:mural:loaded', {
                detail: { type: type, id: id }
            }));

            /* Fire render hooks per type */
            document.dispatchEvent(new CustomEvent('apollo:' + type + ':rendered', {
                detail: { id: id, container: content }
            }));
        })
        .catch(function(err){
            content.innerHTML = '<div class="pnl-empty"><i class="ri-error-warning-line pnl-empty__icon"></i><p>Erro ao carregar conteúdo</p></div>';
            showContent();
        });
    }

    /* ── Share handler ── */
    if (share) {
        share.addEventListener('click', function(){
            if (navigator.share) {
                navigator.share({ title: title.textContent, url: location.href });
            } else {
                navigator.clipboard.writeText(location.href);
            }
        });
    }
})();
</script>
