/**
 * Apollo Hub — Builder JS (hub-builder.js)
 *
 * CDN: cdn.apollo.rio.br — icon.min.js auto-renderiza <i class="ri-*">
 * Manifest: assets.apollo.rio.br/i/json/manifest-icon.min.json (3000+ ícones)
 *
 * Funcionalidades:
 *   - Troca de abas
 *   - Drag-drop nativo para reorganizar links
 *   - Adicionar / remover links
 *   - Seletor de ícones via modal (bottom-sheet) com <i> tags + busca
 *   - Carrega 3000+ ícones do manifest CDN
 *   - Contador de caracteres da bio
 *   - Seleção de tema
 *   - Seleção de avatar / capa via wp.media
 *   - Salvar via REST API (PUT)
 *   - Toast de feedback
 *   - Dual-panel mobile (controles ↔ preview) com swipe
 *   - 100% pt-BR
 *
 * @package Apollo\Hub
 */

/* global apolloHub, wp, gsap */

;( function () {
    'use strict';

    // ═══ Global namespace ═════════════════════════════════════════════
    window.HUB = window.HUB || {};

    // ═══ Estado ═══════════════════════════════════════════════════════
    var isDirty       = false;
    var isSaving      = false;
    var dragSrcEl     = null;
    var initialized   = false;
    var iconManifest  = null;
    var iconLoading   = false;
    var activePickerBtn = null;

    // ═══ Constants ════════════════════════════════════════════════════
    var MANIFEST_URL = 'https://assets.apollo.rio.br/i/json/manifest-icon.min.json';

    // ─── Ícones padrão (aparecem antes do manifest carregar) ──────────
    var DEFAULT_ICONS = [
        { cls: 'ri-link-m',              nome: 'Link' },
        { cls: 'ri-instagram-line',      nome: 'Instagram' },
        { cls: 'ri-twitter-x-line',      nome: 'Twitter/X' },
        { cls: 'ri-tiktok-line',         nome: 'TikTok' },
        { cls: 'ri-youtube-line',        nome: 'YouTube' },
        { cls: 'ri-spotify-line',        nome: 'Spotify' },
        { cls: 'ri-whatsapp-line',       nome: 'WhatsApp' },
        { cls: 'ri-telegram-line',       nome: 'Telegram' },
        { cls: 'ri-facebook-line',       nome: 'Facebook' },
        { cls: 'ri-twitch-line',         nome: 'Twitch' },
        { cls: 'ri-discord-line',        nome: 'Discord' },
        { cls: 'ri-linkedin-line',       nome: 'LinkedIn' },
        { cls: 'ri-github-line',         nome: 'GitHub' },
        { cls: 'ri-global-line',         nome: 'Website' },
        { cls: 'ri-mail-line',           nome: 'E-mail' },
        { cls: 'ri-phone-line',          nome: 'Telefone' },
        { cls: 'ri-star-line',           nome: 'Estrela' },
        { cls: 'ri-heart-line',          nome: 'Coração' },
        { cls: 'ri-fire-line',           nome: 'Fogo' },
        { cls: 'ri-rocket-line',         nome: 'Foguete' },
        { cls: 'ri-shopping-bag-line',   nome: 'Compras' },
        { cls: 'ri-calendar-line',       nome: 'Calendário' },
        { cls: 'ri-map-pin-line',        nome: 'Localização' },
        { cls: 'ri-music-line',          nome: 'Música' },
        { cls: 'ri-video-line',          nome: 'Vídeo' },
        { cls: 'ri-camera-line',         nome: 'Câmera' },
        { cls: 'ri-image-line',          nome: 'Imagem' },
        { cls: 'ri-book-open-line',      nome: 'Livro' },
        { cls: 'ri-briefcase-line',      nome: 'Trabalho' },
        { cls: 'ri-trophy-line',         nome: 'Troféu' },
        { cls: 'ri-gift-line',           nome: 'Presente' },
        { cls: 'ri-download-line',       nome: 'Download' },
        { cls: 'ri-external-link-line',  nome: 'Link externo' },
        { cls: 'ri-home-line',           nome: 'Início' },
        { cls: 'ri-user-line',           nome: 'Usuário' },
        { cls: 'ri-lock-line',           nome: 'Cadeado' },
        { cls: 'ri-search-line',         nome: 'Busca' },
        { cls: 'ri-headphone-line',      nome: 'Fone' },
        { cls: 'ri-mic-line',            nome: 'Microfone' },
        { cls: 'ri-ticket-line',         nome: 'Ingresso' },
        { cls: 'ri-medal-line',          nome: 'Medalha' },
        { cls: 'ri-vip-crown-line',      nome: 'Coroa' },
        { cls: 'ri-diamond-line',        nome: 'Diamante' },
        { cls: 'ri-cup-line',            nome: 'Café' },
        { cls: 'ri-gamepad-line',        nome: 'Jogo' },
        { cls: 'ri-paint-brush-line',    nome: 'Pincel' },
        { cls: 'ri-code-line',           nome: 'Código' },
        { cls: 'ri-flashlight-line',     nome: 'Raio' },
    ];


    // ═══════════════════════════════════════════════════════════════════
    // BOOT
    // ═══════════════════════════════════════════════════════════════════

    function boot() {
        if ( initialized ) return;
        initialized = true;

        initTabs();
        initLinkBuilder();
        initBioCounter();
        initThemePicker();
        initAvatarType();
        initMediaPickers();
        initSaveButton();
        initPreviewRefresh();
        initBeforeUnload();
        initIconModal();
        initPanelToggle();
        initAddBlockModal();
        initEditPopup();
        initPreviewModes();
    }

    window.addEventListener( 'apollo:ready', boot, { once: true } );

    // Fallback de segurança
    setTimeout( function () {
        if ( ! initialized ) {
            console.warn( '[HUB] CDN timeout — inicializando standalone.' );
            boot();
        }
    }, 5000 );


    // ═══════════════════════════════════════════════════════════════════
    // ABAS
    // ═══════════════════════════════════════════════════════════════════

    function initTabs() {
        var tabs   = document.querySelectorAll( '.hub-builder__tab' );
        var panels = document.querySelectorAll( '.hub-builder__tab-panel' );

        tabs.forEach( function ( tab ) {
            tab.addEventListener( 'click', function () {
                var target = tab.dataset.tab;

                tabs.forEach( function ( t ) {
                    t.classList.toggle( 'is-active', t === tab );
                    t.setAttribute( 'aria-selected', String( t === tab ) );
                } );

                panels.forEach( function ( panel ) {
                    var panelName = panel.id ? panel.id.replace( 'tab-', '' ) : '';
                    var isActive  = panelName === target;
                    panel.classList.toggle( 'is-active', isActive );
                    panel.setAttribute( 'aria-hidden', String( ! isActive ) );
                } );
            } );
        } );
    }


    // ═══════════════════════════════════════════════════════════════════
    // LINK BUILDER — Adicionar / Remover / Drag-drop
    // ═══════════════════════════════════════════════════════════════════

    function initLinkBuilder() {
        var list   = document.querySelector( '.js-hub-links-list' );
        var addBtn = document.querySelector( '.js-hub-link-add' );

        if ( ! list ) return;

        // Drag nos itens existentes
        list.querySelectorAll( '.js-hub-link-item' ).forEach( initDragItem );

        // Botão adicionar
        if ( addBtn ) {
            addBtn.addEventListener( 'click', function () {
                var maxLinks = 30;
                var currentCount = list.querySelectorAll( '.js-hub-link-item' ).length;

                if ( currentCount >= maxLinks ) {
                    showToast( 'Máximo de links atingido.', 'error' );
                    return;
                }

                var item = createLinkItem();
                list.appendChild( item );
                initDragItem( item );
                // CDN MutationObserver detecta novos <i class="ri-*">

                item.querySelector( '.js-link-title' )?.focus();
                updateLinkCount();
                markDirty();
            } );
        }

        // Delegação: remover link
        list.addEventListener( 'click', function ( e ) {
            var removeBtn = e.target.closest( '.js-hub-link-remove' );
            if ( removeBtn ) {
                var item = removeBtn.closest( '.js-hub-link-item' );
                if ( item ) {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(20px)';
                    item.style.transition = 'opacity .2s, transform .2s';
                    setTimeout( function () {
                        item.remove();
                        updateLinkCount();
                        markDirty();
                    }, 200 );
                }
            }
        } );

        // Delegação: toggle + inputs
        list.addEventListener( 'change', function ( e ) {
            if ( e.target.matches( '.js-link-active' ) ) markDirty();
        } );

        list.addEventListener( 'input', function ( e ) {
            if ( e.target.matches( 'input, select' ) ) markDirty();
        } );
    }

    function updateLinkCount() {
        var n = document.querySelectorAll( '.js-hub-link-item' ).length;

        var counter = document.querySelector( '.js-link-count' );
        if ( counter ) counter.textContent = n;

        // .cnt badge no tab de blocos
        var cntBadge = document.querySelector( '.js-link-count-badge' );
        if ( cntBadge ) cntBadge.textContent = n;
    }

    /**
     * Cria novo item de link.
     * ÍCONE: usa <i class="ri-*"> + botão picker, NÃO <select> nem <div>
     */
    function createLinkItem() {
        window._apolloHubLinkCounter = ( window._apolloHubLinkCounter || 0 ) + 1;
        var uid = 'hl-new-' + window._apolloHubLinkCounter;

        var el = document.createElement( 'li' );
        el.classList.add( 'hub-builder__link-item', 'block-card', 'js-hub-link-item' );
        el.setAttribute( 'draggable', 'true' );
        el.innerHTML = [
            '<span class="hub-builder__link-item__drag" title="Arrastar">',
            '  <i class="ri-drag-move-2-line" aria-hidden="true"></i>',
            '</span>',
            '<div class="hub-builder__link-item__fields">',
            '  <label class="sr-only" for="' + uid + '-title">T\u00edtulo</label>',
            '  <input type="text"',
            '    id="' + uid + '-title" name="hub_links[' + uid + '][title]"',
            '    class="hub-builder__input js-link-title"',
            '    placeholder="T\u00edtulo do link">',
            '  <label class="sr-only" for="' + uid + '-url">URL</label>',
            '  <input type="url"',
            '    id="' + uid + '-url" name="hub_links[' + uid + '][url]"',
            '    class="hub-builder__input js-link-url" placeholder="https://">',
            '  <button type="button" class="hub-builder__icon-picker-btn js-icon-picker-btn" data-current-icon="ri-link-m">',
            '    <i class="ri-link-m" aria-hidden="true"></i>',
            '    <span>\u00cdcone</span>',
            '    <i class="ri-arrow-down-s-line" aria-hidden="true"></i>',
            '  </button>',
            '  <input type="hidden" class="js-link-icon"',
            '    id="' + uid + '-icon" name="hub_links[' + uid + '][icon]" value="ri-link-m">',
            '</div>',
            '<div class="hub-builder__link-item__actions">',
            '  <button type="button" class="hub-btn-icon js-hub-link-edit"',
            '    data-tooltip="Editar" aria-label="Editar bloco">',
            '    <i class="ri-edit-line" aria-hidden="true"></i>',
            '  </button>',
            '  <label class="hub-builder__toggle" title="Ativo/Inativo">',
            '    <span class="sr-only">Link ativo</span>',
            '    <input type="checkbox"',
            '      id="' + uid + '-active" name="hub_links[' + uid + '][active]"',
            '      class="js-link-active" checked>',
            '    <span class="hub-builder__toggle-slider"></span>',
            '  </label>',
            '  <button class="hub-btn-icon hub-btn-icon--danger js-hub-link-remove"',
            '    type="button" data-tooltip="Remover" aria-label="Remover">',
            '    <i class="ri-delete-bin-7-line" aria-hidden="true"></i>',
            '  </button>',
            '</div>',
        ].join( '\n' );

        return el;
    }


    // ═══════════════════════════════════════════════════════════════════
    // ICON PICKER MODAL — bottom-sheet com <i> tags
    // ═══════════════════════════════════════════════════════════════════

    function initIconModal() {
        var modal = document.getElementById( 'icon-modal' );
        if ( ! modal ) return;

        // Fechar
        modal.querySelectorAll( '.js-icon-modal-close' ).forEach( function ( el ) {
            el.addEventListener( 'click', closeIconModal );
        } );

        // Busca
        var search = modal.querySelector( '.js-icon-search' );
        if ( search ) {
            search.addEventListener( 'input', function () {
                HUB.filterIcons( this.value );
            } );
        }

        // Seleção delegada — click no grid
        var grid = modal.querySelector( '.js-icon-grid' );
        if ( grid ) {
            grid.addEventListener( 'click', function ( e ) {
                var choice = e.target.closest( '.hub-icon-choice' );
                if ( ! choice ) return;
                selectIcon( choice.dataset.iconClass );
            } );
        }

        // Abrir icon picker — delegação no document
        document.addEventListener( 'click', function ( e ) {
            var pickerBtn = e.target.closest( '.js-icon-picker-btn' );
            if ( pickerBtn ) {
                e.preventDefault();
                activePickerBtn = pickerBtn;
                openIconModal( pickerBtn.dataset.currentIcon );
            }
        } );

        // Escape fecha modal
        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' && modal.classList.contains( 'is-open' ) ) {
                closeIconModal();
            }
        } );
    }

    function openIconModal( currentIcon ) {
        var modal = document.getElementById( 'icon-modal' );
        if ( ! modal ) return;

        modal.classList.add( 'is-open' );
        modal.setAttribute( 'aria-hidden', 'false' );
        document.body.style.overflow = 'hidden';

        // Reset busca
        var search = modal.querySelector( '.js-icon-search' );
        if ( search ) {
            search.value = '';
            setTimeout( function () { search.focus(); }, 350 );
        }

        // Carrega ícones
        loadIconGrid( currentIcon );
    }

    function closeIconModal() {
        var modal = document.getElementById( 'icon-modal' );
        if ( ! modal ) return;

        modal.classList.remove( 'is-open' );
        modal.setAttribute( 'aria-hidden', 'true' );
        document.body.style.overflow = '';
        activePickerBtn = null;
    }

    function selectIcon( iconClass ) {
        if ( ! activePickerBtn || ! iconClass ) return;

        // Atualiza botão picker
        activePickerBtn.dataset.currentIcon = iconClass;
        var iconEl = activePickerBtn.querySelector( 'i:first-child' );
        if ( iconEl ) {
            iconEl.className = iconClass;
            iconEl.setAttribute( 'aria-hidden', 'true' );
        }

        // Atualiza hidden input
        var linkItem = activePickerBtn.closest( '.js-hub-link-item' );
        if ( linkItem ) {
            var hidden = linkItem.querySelector( '.js-link-icon' );
            if ( hidden ) hidden.value = iconClass;
        }

        markDirty();
        closeIconModal();
    }

    function loadIconGrid( currentIcon ) {
        var grid = document.getElementById( 'icon-grid' );
        if ( ! grid ) return;

        // Se já tem manifest, renderiza direto
        if ( iconManifest ) {
            renderIconGrid( grid, iconManifest, currentIcon );
            return;
        }

        // Mostra defaults imediatamente
        var defaults = DEFAULT_ICONS.map( function ( d ) {
            return { cls: d.cls, nome: d.nome, search: d.nome.toLowerCase() + ' ' + d.cls };
        } );
        renderIconGrid( grid, defaults, currentIcon );

        // Fetch do manifest completo (3000+ ícones)
        if ( iconLoading ) return;
        iconLoading = true;

        fetch( MANIFEST_URL )
            .then( function ( r ) {
                if ( ! r.ok ) throw new Error( 'HTTP ' + r.status );
                return r.json();
            } )
            .then( function ( data ) {
                if ( ! Array.isArray( data ) ) return;

                iconManifest = [];
                data.forEach( function ( item ) {
                    // Manifest: "class-name": "ri-star-s star-s i-star-s"
                    var classNames = ( item['class-name'] || '' ).split( ' ' );
                    // Pega a primeira que começa com ri-
                    var riClass = '';
                    for ( var i = 0; i < classNames.length; i++ ) {
                        if ( classNames[i].indexOf( 'ri-' ) === 0 ) {
                            riClass = classNames[i];
                            break;
                        }
                    }
                    if ( ! riClass ) riClass = classNames[0] || '';
                    if ( ! riClass ) return;

                    var nome = item['nome-ptBR'] || item.name || riClass;
                    var tags = Array.isArray( item.tags ) ? item.tags.join( ' ' ) : '';
                    var group = item.group || '';

                    iconManifest.push( {
                        cls:    riClass,
                        nome:   nome,
                        search: ( nome + ' ' + tags + ' ' + group + ' ' + riClass ).toLowerCase(),
                    } );
                } );

                if ( iconManifest.length ) {
                    renderIconGrid( grid, iconManifest, currentIcon );
                }
                iconLoading = false;
            } )
            .catch( function ( err ) {
                console.warn( '[HUB] Erro ao carregar manifest:', err );
                iconLoading = false;
            } );
    }

    /**
     * Renderiza grid de ícones como <i class="ri-{key}"> (NUNCA como <div>)
     */
    function renderIconGrid( grid, icons, currentIcon ) {
        var html = [];
        var len  = icons.length;

        for ( var i = 0; i < len; i++ ) {
            var icon = icons[i];
            var selected = icon.cls === currentIcon ? ' is-selected' : '';
            html.push(
                '<div class="hub-icon-choice' + selected + '"'
                + ' data-icon-class="' + escAttr( icon.cls ) + '"'
                + ' data-search="' + escAttr( icon.search || '' ) + '"'
                + ' title="' + escAttr( icon.nome ) + '">'
                + '<i class="' + escAttr( icon.cls ) + '" aria-hidden="true"></i>'
                + '<span>' + escHtml( icon.nome ) + '</span>'
                + '</div>'
            );
        }

        grid.innerHTML = html.join( '' );
    }

    /**
     * Filtra ícones por texto de busca.
     * Exposta como HUB.filterIcons() para uso externo.
     */
    function filterIcons( query ) {
        var grid = document.getElementById( 'icon-grid' );
        if ( ! grid ) return;

        var q = ( query || '' ).toLowerCase().trim();
        var choices = grid.querySelectorAll( '.hub-icon-choice' );
        var visible = 0;

        // Limpa estado anterior
        var oldEmpty = grid.querySelector( '.hub-icon-grid__empty' );
        if ( oldEmpty ) oldEmpty.remove();

        for ( var i = 0; i < choices.length; i++ ) {
            var el = choices[i];
            var searchData = el.dataset.search || '';
            var match = ! q || searchData.indexOf( q ) !== -1;
            el.style.display = match ? '' : 'none';
            if ( match ) visible++;
        }

        // Mostra mensagem de vazio
        if ( ! visible ) {
            var emptyEl = document.createElement( 'div' );
            emptyEl.className = 'hub-icon-grid__empty';
            emptyEl.innerHTML = '<i class="ri-emotion-sad-line" aria-hidden="true"></i>'
                + '<span>Nenhum \u00edcone encontrado para "' + escHtml( q ) + '"</span>';
            grid.appendChild( emptyEl );
        }
    }

    HUB.filterIcons = filterIcons;


    // ═══════════════════════════════════════════════════════════════════
    // PANEL TOGGLE — Mobile dual-panel slider com swipe
    // ═══════════════════════════════════════════════════════════════════

    function initPanelToggle() {
        var toggle  = document.querySelector( '.js-panel-toggle' );
        var panels  = document.getElementById( 'hub-panels' );        if ( ! toggle || ! panels ) return;

        var isPreview = false;

        toggle.addEventListener( 'click', function () {
            isPreview = ! isPreview;
            panels.classList.toggle( 'is-preview', isPreview );
            syncToggleUI( toggle, isPreview );
        } );

        // ─── Touch swipe ──────────────────────────────────────────────
        var startX = 0;
        var currentX = 0;
        var swiping = false;

        panels.addEventListener( 'touchstart', function ( e ) {
            startX = currentX = e.touches[0].clientX;
            swiping = true;
            panels.style.transition = 'none';
        }, { passive: true } );

        panels.addEventListener( 'touchmove', function ( e ) {
            if ( ! swiping ) return;
            currentX = e.touches[0].clientX;
            var dx = currentX - startX;
            var base = isPreview ? -window.innerWidth : 0;
            var offset = base + dx;

            // Rubber band
            if ( offset > 0 ) offset *= 0.25;
            if ( offset < -window.innerWidth ) {
                offset = -window.innerWidth + ( offset + window.innerWidth ) * 0.25;
            }

            panels.style.transform = 'translateX(' + offset + 'px)';
        }, { passive: true } );

        panels.addEventListener( 'touchend', function () {
            if ( ! swiping ) return;
            swiping = false;
            panels.style.transition = '';
            panels.style.transform = '';

            var dx = currentX - startX;
            var threshold = window.innerWidth * 0.2;

            if ( isPreview && dx > threshold ) {
                isPreview = false;
                panels.classList.remove( 'is-preview' );
            } else if ( ! isPreview && dx < -threshold ) {
                isPreview = true;
                panels.classList.add( 'is-preview' );
            }

            syncToggleUI( toggle, isPreview );
        }, { passive: true } );
    }

    function syncToggleUI( toggle, isPreview ) {
        var icon = toggle?.querySelector( 'i' );

        if ( isPreview ) {
            if ( icon ) icon.className = 'ri-edit-line';
            toggle?.setAttribute( 'data-tooltip', 'Editar' );
        } else {
            if ( icon ) icon.className = 'ri-smartphone-line';
            toggle?.setAttribute( 'data-tooltip', 'Pré-visualizar' );
        }
    }


    // ═══════════════════════════════════════════════════════════════════
    // DRAG-AND-DROP nativo HTML5
    // ═══════════════════════════════════════════════════════════════════

    function initDragItem( item ) {
        item.setAttribute( 'draggable', 'true' );

        item.addEventListener( 'dragstart', function ( e ) {
            dragSrcEl = item;
            item.classList.add( 'is-dragging' );
            item.setAttribute( 'data-dragging', 'true' );
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData( 'text/plain', '' );
        } );

        item.addEventListener( 'dragend', function () {
            item.classList.remove( 'is-dragging' );
            item.removeAttribute( 'data-dragging' );
            document.querySelectorAll( '.js-hub-link-item' ).forEach( function ( i ) {
                i.classList.remove( 'drag-over' );
            } );
            markDirty();
        } );

        item.addEventListener( 'dragover', function ( e ) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if ( dragSrcEl && item !== dragSrcEl ) {
                item.classList.add( 'drag-over' );
            }
        } );

        item.addEventListener( 'dragleave', function () {
            item.classList.remove( 'drag-over' );
        } );

        item.addEventListener( 'drop', function ( e ) {
            e.stopPropagation();
            item.classList.remove( 'drag-over' );
            if ( dragSrcEl && item !== dragSrcEl ) {
                var list     = item.parentNode;
                var allItems = Array.from( list.querySelectorAll( '.js-hub-link-item' ) );
                var srcIdx   = allItems.indexOf( dragSrcEl );
                var tgtIdx   = allItems.indexOf( item );
                if ( srcIdx < tgtIdx ) {
                    list.insertBefore( dragSrcEl, item.nextSibling );
                } else {
                    list.insertBefore( dragSrcEl, item );
                }
                markDirty();
            }
        } );
    }


    // ═══════════════════════════════════════════════════════════════════
    // BIO COUNTER
    // ═══════════════════════════════════════════════════════════════════

    function initBioCounter() {
        var bio     = document.querySelector( '.js-hub-bio' );
        var counter = document.querySelector( '.js-bio-count' );
        var max     = parseInt( bio?.getAttribute( 'maxlength' ) || '280', 10 );

        if ( ! bio || ! counter ) return;

        function update() {
            counter.textContent = bio.value.length;
            if ( counter.parentElement ) {
                counter.parentElement.classList.toggle( 'is-over', bio.value.length > max );
            }
        }

        bio.addEventListener( 'input', function () { update(); markDirty(); } );
        update();
    }


    // ═══════════════════════════════════════════════════════════════════
    // TEMA
    // ═══════════════════════════════════════════════════════════════════

    function initThemePicker() {
        var btns = document.querySelectorAll( '.js-hub-theme-btn' );

        btns.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var theme = btn.dataset.theme;

                btns.forEach( function ( b ) {
                    b.classList.toggle( 'is-active', b === btn );
                    b.setAttribute( 'aria-checked', String( b === btn ) );
                } );

                document.documentElement.setAttribute( 'data-hub-theme', theme );
                refreshPreviewTheme( theme );
                markDirty();
            } );
        } );
    }

    function refreshPreviewTheme( theme ) {
        var iframe = document.querySelector( '.js-hub-preview-iframe' );
        if ( ! iframe ) return;

        try {
            var iframeDoc = iframe.contentDocument || iframe.contentWindow?.document;
            if ( iframeDoc ) {
                iframeDoc.documentElement.setAttribute( 'data-hub-theme', theme );
            }
        } catch ( e ) {
            // Cross-origin silently fail
        }
    }


    function initAvatarType() {
        var select = document.querySelector( '.js-avatar-type' );
        if ( ! select ) return;

        select.addEventListener( 'change', function () {
            markDirty();
            refreshPreviewAvatar();
        } );
    }

    function refreshPreviewAvatar() {
        var iframe = document.querySelector( '.js-hub-preview-iframe' );
        if ( ! iframe ) return;

        try {
            var iframeDoc = iframe.contentDocument || iframe.contentWindow?.document;
            if ( ! iframeDoc ) return;

            var avatarType = document.querySelector( '.js-avatar-type' )?.value || 'normal';
            var avatarId = parseInt( document.querySelector( '.js-hub-avatar-id' )?.value || '0', 10 );

            // Remover avatar anterior (se existir)
            var oldAvatar = iframeDoc.querySelector( '.hub-avatar, .avatar-morphism-container' );
            if ( oldAvatar ) oldAvatar.remove();

            if ( ! avatarId ) return;

            // Obter URL da imagem
            var avatarPreview = document.querySelector( '.js-avatar-preview' );
            var avatarUrl = '';

            if ( avatarPreview.tagName === 'IMG' ) {
                avatarUrl = avatarPreview.src;
            }

            if ( ! avatarUrl ) return;

            // Renderizar novo avatar
            var container = iframeDoc.querySelector( '.hub-header' );
            if ( ! container ) return;

            var newAvatarHTML = avatarType === 'morphism'
                ? renderAvatarMorphism( avatarUrl )
                : '<img src="' + escHtml( avatarUrl ) + '" class="hub-avatar" alt="Avatar" width="96" height="96">';

            var avatarWrap = iframeDoc.querySelector( '.hub-avatar-wrap' );
            if ( avatarWrap ) {
                avatarWrap.innerHTML = newAvatarHTML;
            }
        } catch ( e ) {
            console.warn( '[Hub] Erro ao atualizar preview do avatar:', e );
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // MEDIA PICKERS (wp.media)
    // ═══════════════════════════════════════════════════════════════════

    function initMediaPickers() {
        document.querySelectorAll( '.js-open-media' ).forEach( function ( btn ) {
            var target  = btn.dataset.target; // 'avatar' ou 'cover'
            var picker  = btn.closest( '.hub-builder__media-picker' );
            var idInput = picker?.querySelector( target === 'avatar' ? '.js-hub-avatar-id' : '.js-hub-cover-id' );
            var preview = picker?.querySelector( target === 'avatar' ? '.js-avatar-preview' : '.js-cover-preview' );

            var frame;

            btn.addEventListener( 'click', function ( e ) {
                e.preventDefault();

                if ( typeof wp === 'undefined' || ! wp.media ) {
                    var url = window.prompt( 'Cole a URL da imagem:' );
                    if ( url && preview ) {
                        if ( preview.tagName === 'IMG' ) preview.src = url;
                        markDirty();
                    }
                    return;
                }

                if ( frame ) { frame.open(); return; }

                frame = wp.media( {
                    title    : target === 'avatar' ? 'Escolher Avatar' : 'Escolher Capa',
                    button   : { text: 'Usar esta imagem' },
                    multiple : false,
                    library  : { type: 'image' },
                } );

                frame.on( 'select', function () {
                    var attachment = frame.state().get( 'selection' ).first().toJSON();
                    if ( idInput ) idInput.value = attachment.id;
                    if ( preview ) {
                        if ( preview.tagName === 'IMG' ) {
                            preview.src = attachment.sizes?.medium?.url || attachment.url;
                        } else {
                            var img = document.createElement( 'img' );
                            img.src       = attachment.sizes?.medium?.url || attachment.url;
                            img.className = preview.className.replace( 'hub-builder__media-placeholder', 'hub-builder__media-preview' );
                            img.alt       = target === 'avatar' ? 'Avatar' : 'Capa';
                            img.width     = target === 'avatar' ? 80 : 160;
                            img.height    = target === 'avatar' ? 80 : 60;
                            preview.replaceWith( img );
                        }
                    }
                    markDirty();
                } );

                frame.open();
            } );
        } );
    }


    // ═══════════════════════════════════════════════════════════════════
    // BOTÃO SALVAR
    // ═══════════════════════════════════════════════════════════════════

    function initSaveButton() {
        var btn = document.querySelector( '.js-hub-save' );
        if ( ! btn ) return;

        btn.addEventListener( 'click', function () {
            if ( isSaving ) return;
            saveHub();
        } );
    }

    function saveHub() {
        if ( isSaving ) return;
        isSaving = true;

        var btn       = document.querySelector( '.js-hub-save' );
        var builderEl = document.getElementById( 'hub-builder' );
        var username  = builderEl?.dataset?.username || '';
        var restUrl   = ( builderEl?.dataset?.restUrl || '' ).replace( /\/$/, '' );
        var nonce     = builderEl?.dataset?.nonce || '';

        if ( btn ) {
            btn.classList.add( 'is-loading' );
            btn.disabled = true;
        }

        if ( ! username ) {
            showToast( 'Usuário não encontrado.', 'error' );
            finishSave( btn );
            return;
        }

        var profileData = collectProfileData();
        var linksData   = collectLinksData();

        var headers = {
            'Content-Type' : 'application/json',
            'X-WP-Nonce'   : nonce,
        };

        // PUT perfil
        fetch( restUrl + '/hubs/' + username, {
            method      : 'PUT',
            credentials : 'same-origin',
            headers     : headers,
            body        : JSON.stringify( profileData ),
        } )
        .then( function ( res ) {
            if ( ! res.ok ) return res.json().then( function ( err ) { throw new Error( err.message || 'Erro ' + res.status ); } );
            return res.json();
        } )
        .then( function () {
            return fetch( restUrl + '/hubs/' + username + '/links', {
                method      : 'PUT',
                credentials : 'same-origin',
                headers     : headers,
                body        : JSON.stringify( { links: linksData } ),
            } );
        } )
        .then( function ( res ) {
            if ( ! res.ok ) return res.json().then( function ( err ) { throw new Error( err.message || 'Erro ' + res.status ); } );
            return res.json();
        } )
        .then( function () {
            isDirty = false;
            showToast( 'Hub salvo!', 'success' );
            refreshPreviewIframe();
        } )
        .catch( function ( err ) {
            showToast( 'Falha ao salvar: ' + err.message, 'error' );
        } )
        .finally( function () {
            finishSave( btn );
        } );
    }

    function finishSave( btn ) {
        isSaving = false;
        if ( btn ) {
            btn.classList.remove( 'is-loading' );
            btn.disabled = false;
        }
    }

    // ─── Coleta de dados ──────────────────────────────────────────────

    function collectProfileData() {
        var themeBtn = document.querySelector( '.js-hub-theme-btn.is-active' );

        return {
            bio         : document.querySelector( '.js-hub-bio' )?.value?.trim() || '',
            theme       : themeBtn?.dataset?.theme || 'dark',
            avatar      : parseInt( document.querySelector( '.js-hub-avatar-id' )?.value || '0', 10 ) || 0,
            avatar_type : document.querySelector( '.js-avatar-type' )?.value || 'normal',
            cover       : parseInt( document.querySelector( '.js-hub-cover-id' )?.value || '0', 10 ) || 0,
            socials     : collectSocialsData(),
            custom_css  : document.querySelector( '.js-hub-custom-css' )?.value?.trim() || '',
        };
    }

    function collectLinksData() {
        var items = document.querySelectorAll( '.js-hub-link-item' );
        var links = [];

        items.forEach( function ( item ) {
            var title  = item.querySelector( '.js-link-title' )?.value?.trim() || '';
            var url    = item.querySelector( '.js-link-url' )?.value?.trim() || '';
            var icon   = item.querySelector( '.js-link-icon' )?.value?.trim() || 'ri-link-m';
            var active = item.querySelector( '.js-link-active' )?.checked ?? true;

            if ( url ) {
                links.push( { title: title, url: url, icon: icon, active: active } );
            }
        } );

        return links;
    }

    function collectSocialsData() {
        var socials = {};
        document.querySelectorAll( '.js-social-url[data-network]' ).forEach( function ( input ) {
            var network = input.dataset.network;
            var val     = input.value.trim();
            if ( network && val ) {
                socials[ network ] = val;
            }
        } );
        return socials;
    }


    // ═══════════════════════════════════════════════════════════════════
    // PREVIEW IFRAME
    // ═══════════════════════════════════════════════════════════════════

    function initPreviewRefresh() {
        // Nada extra necessário — refresh via saveHub()
    }

    function refreshPreviewIframe() {
        var iframe = document.querySelector( '.js-hub-preview-iframe' );
        if ( ! iframe || ! iframe.src ) return;

        try {
            var url = new URL( iframe.src );
            url.searchParams.set( 't', Date.now() );
            iframe.src = url.toString();
        } catch ( e ) {
            iframe.src = iframe.src; // force reload
        }
    }


    // ═══════════════════════════════════════════════════════════════════
    // TOAST
    // ═══════════════════════════════════════════════════════════════════

    function showToast( message, type ) {
        type = type || 'success';
        var toast = document.querySelector( '.js-hub-toast' );

        if ( ! toast ) {
            toast = document.createElement( 'div' );
            toast.classList.add( 'hub-builder__toast', 'js-hub-toast' );
            toast.setAttribute( 'role', 'status' );
            toast.setAttribute( 'aria-live', 'polite' );
            document.body.appendChild( toast );
        }

        toast.className = 'hub-builder__toast js-hub-toast is-' + type + ' is-visible';
        toast.textContent = message;

        clearTimeout( toast._hideTimer );
        toast._hideTimer = setTimeout( function () {
            toast.classList.remove( 'is-visible' );
        }, 3500 );
    }


    // ═══════════════════════════════════════════════════════════════════
    // DIRTY — aviso ao sair sem salvar
    // ═══════════════════════════════════════════════════════════════════

    function markDirty() {
        isDirty = true;
    }

    function initBeforeUnload() {
        window.addEventListener( 'beforeunload', function ( e ) {
            if ( isDirty && ! isSaving ) {
                e.preventDefault();
                e.returnValue = '';
            }
        } );
    }


    // ═══════════════════════════════════════════════════════════════════
    // UTILS
    // ═══════════════════════════════════════════════════════════════════

    function escAttr( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /"/g, '&quot;' )
            .replace( /'/g, '&#39;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' );
    }

    function escHtml( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' );
    }

    /* Renderizar avatar morphism no preview */
    function renderAvatarMorphism( avatarImageUrl ) {
        if ( ! avatarImageUrl ) return '';
        return '<div class="avatar-morphism-container" style="margin: 0 auto;">' +
            '<div class="avatar-morphism-box">' +
            '<div class="avatar-morphism-spin">' +
            '<div class="avatar-morphism-shape" style="background-image: url(' + escHtml( avatarImageUrl ) + '); background-size: cover; background-position: center;">' +
            '<div class="avatar-morphism-image" style="background-image: url(' + escHtml( avatarImageUrl ) + ');"></div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
    }

    // ═══════════════════════════════════════════════════════════════════
    // ADD BLOCK MODAL
    // ═══════════════════════════════════════════════════════════════════

    function initAddBlockModal() {
        document.addEventListener( 'click', function ( e ) {

            // Abrir modal
            if ( e.target.closest( '.js-hub-add-block-trigger' ) ) {
                e.preventDefault();
                openAddBlockModal();
                return;
            }

            // Fechar via backdrop ou btn fechar
            if ( e.target.closest( '.js-add-modal-close' ) ) {
                closeAddBlockModal();
                return;
            }

            // Selecionar tipo de bloco
            var typeBtn = e.target.closest( '.js-add-block-type' );
            if ( typeBtn && document.getElementById( 'hub-add-modal' )?.classList.contains( 'is-open' ) ) {
                var type    = typeBtn.dataset.type || 'link';
                closeAddBlockModal();
                addNewBlock( type );
                return;
            }
        } );
    }

    function openAddBlockModal() {
        var modal = document.getElementById( 'hub-add-modal' );
        if ( ! modal ) return;
        modal.classList.add( 'is-open' );
        modal.setAttribute( 'aria-hidden', 'false' );
        document.body.style.overflow = 'hidden';
    }

    function closeAddBlockModal() {
        var modal = document.getElementById( 'hub-add-modal' );
        if ( ! modal ) return;
        modal.classList.remove( 'is-open' );
        modal.setAttribute( 'aria-hidden', 'true' );
        document.body.style.overflow = '';
    }

    function addNewBlock( type ) {
        var list = document.querySelector( '.js-hub-links-list' );
        if ( ! list ) return;

        var maxLinks    = 30;
        var currentCount = list.querySelectorAll( '.js-hub-link-item' ).length;

        if ( currentCount >= maxLinks ) {
            showToast( 'Máximo de blocos atingido.', 'error' );
            return;
        }

        var item = createLinkItem( type );
        list.appendChild( item );
        initDragItem( item );
        item.querySelector( '.js-link-title' )?.focus();
        updateLinkCount();
        markDirty();

        // Abre popup de edição imediatamente
        openEditPopup( item );
    }


    // ═══════════════════════════════════════════════════════════════════
    // BLOCK EDIT POPUP
    // ═══════════════════════════════════════════════════════════════════

    var _editingItem = null; // referência ao li sendo editado

    function initEditPopup() {
        // Delegação: botão editar em qualquer link item
        document.addEventListener( 'click', function ( e ) {

            var editBtn = e.target.closest( '.js-hub-link-edit' );
            if ( editBtn ) {
                e.preventDefault();
                var item = editBtn.closest( '.js-hub-link-item' );
                if ( item ) openEditPopup( item );
                return;
            }

            // Fechar via backdrop / botão fechar / cancelar
            if ( e.target.closest( '.js-edit-popup-close' ) ) {
                closeEditPopup( false );
                return;
            }

            // Aplicar alterações
            if ( e.target.closest( '.js-edit-popup-apply' ) ) {
                closeEditPopup( true );
                return;
            }
        } );
    }

    function openEditPopup( linkItem ) {
        var popup = document.getElementById( 'hub-edit-popup' );
        if ( ! popup || ! linkItem ) return;

        _editingItem = linkItem;

        // Ícone e título
        var iconEl = linkItem.querySelector( '.js-icon-picker-btn i:first-child' );
        var iconClass = iconEl ? iconEl.className : 'ri-link';
        var titleVal  = linkItem.querySelector( '.js-link-title' )?.value || 'Bloco';

        var popupIcon = popup.querySelector( '#hub-edit-popup-icon i' );
        if ( popupIcon ) popupIcon.className = iconClass;

        var popupTitle = popup.querySelector( '#hub-edit-popup-title' );
        if ( popupTitle ) popupTitle.textContent = titleVal || 'Editar bloco';

        // Clonar campos para o popup
        var body   = popup.querySelector( '#hub-edit-popup-body' );
        var fields = linkItem.querySelector( '.hub-builder__link-item__fields' );
        if ( body && fields ) {
            body.innerHTML = '';
            var clone = fields.cloneNode( true );
            // Adicionar labels pt-BR legíveis
            var titleInput = clone.querySelector( '.js-link-title' );
            var urlInput   = clone.querySelector( '.js-link-url' );

            if ( titleInput ) {
                var tLbl = document.createElement( 'label' );
                tLbl.className   = 'hub-builder__label';
                tLbl.textContent = 'Título';
                clone.insertBefore( tLbl, titleInput );
            }
            if ( urlInput ) {
                var uLbl = document.createElement( 'label' );
                uLbl.className   = 'hub-builder__label';
                uLbl.textContent = 'URL';
                clone.insertBefore( uLbl, urlInput );
            }

            body.appendChild( clone );
        }

        popup.classList.add( 'is-open' );
        popup.setAttribute( 'aria-hidden', 'false' );
        document.body.style.overflow = 'hidden';
    }

    function closeEditPopup( apply ) {
        var popup = document.getElementById( 'hub-edit-popup' );
        if ( ! popup ) return;

        if ( apply && _editingItem ) {
            // Copia valores do popup de volta para o item original
            var body = popup.querySelector( '#hub-edit-popup-body' );
            if ( body && _editingItem ) {
                var srcTitle = body.querySelector( '.js-link-title' );
                var srcUrl   = body.querySelector( '.js-link-url' );
                var srcIcon  = body.querySelector( '.js-link-icon' );

                if ( srcTitle ) {
                    var dstTitle = _editingItem.querySelector( '.js-link-title' );
                    if ( dstTitle ) dstTitle.value = srcTitle.value;
                }
                if ( srcUrl ) {
                    var dstUrl = _editingItem.querySelector( '.js-link-url' );
                    if ( dstUrl ) dstUrl.value = srcUrl.value;
                }
                if ( srcIcon ) {
                    var dstIcon = _editingItem.querySelector( '.js-link-icon' );
                    if ( dstIcon ) {
                        dstIcon.value = srcIcon.value;
                        // Atualiza ícone no botão picker
                        var pickerBtn = _editingItem.querySelector( '.js-icon-picker-btn' );
                        if ( pickerBtn ) {
                            pickerBtn.dataset.currentIcon = srcIcon.value;
                            var pickerIconEl = pickerBtn.querySelector( 'i:first-child' );
                            if ( pickerIconEl ) pickerIconEl.className = srcIcon.value;
                        }
                    }
                }
                markDirty();
            }
        }

        popup.classList.remove( 'is-open' );
        popup.setAttribute( 'aria-hidden', 'true' );
        document.body.style.overflow = '';
        _editingItem = null;
    }


    // ═══════════════════════════════════════════════════════════════════
    // PREVIEW MODES — Mobile phone / PC fullscreen
    // ═══════════════════════════════════════════════════════════════════

    function initPreviewModes() {
        var builderEl = document.getElementById( 'hub-builder' );
        var hubUrl    = builderEl?.dataset?.hubUrl || '';

        document.addEventListener( 'click', function ( e ) {

            // Botão preview mobile (na toolbar)
            if ( e.target.closest( '.js-preview-mobile' ) ) {
                openPreviewPane( 'mobile', hubUrl );
                return;
            }

            // Botão preview PC (na toolbar)
            if ( e.target.closest( '.js-preview-pc' ) ) {
                openPreviewPane( 'pc', hubUrl );
                return;
            }

            // Switch dentro do pane
            var switchBtn = e.target.closest( '.js-preview-pane-switch' );
            if ( switchBtn ) {
                var mode = switchBtn.dataset.mode || 'mobile';
                openPreviewPane( mode, hubUrl );
                return;
            }

            // Fechar pane
            if ( e.target.closest( '.js-preview-pane-close' ) ) {
                closePreviewPane();
                return;
            }
        } );

        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' ) {
                closePreviewPane();
                closeAddBlockModal();
                closeEditPopup( false );
                closeIconModal();
            }
        } );
    }

    function openPreviewPane( mode, hubUrl ) {
        var pane   = document.getElementById( 'hub-preview-pane' );
        var mock   = document.getElementById( 'hub-preview-mock' );
        var iframe = pane?.querySelector( '.js-preview-pane-iframe' );
        var label  = document.getElementById( 'hub-preview-pane-label' );

        if ( ! pane ) return;

        // Atualiza modo visual
        pane.classList.toggle( 'hub-preview-pane--pc', mode === 'pc' );

        if ( label ) {
            label.textContent = mode === 'pc' ? 'Preview — Computador' : 'Preview — Mobile';
        }

        // Carrega iframe se necessário
        if ( iframe ) {
            var url = hubUrl ? hubUrl + ( hubUrl.indexOf( '?' ) > -1 ? '&' : '?' ) + 't=' + Date.now() : '';
            iframe.src = url;
        }

        pane.classList.add( 'is-open' );
        pane.setAttribute( 'aria-hidden', 'false' );
        document.body.style.overflow = 'hidden';
    }

    function closePreviewPane() {
        var pane   = document.getElementById( 'hub-preview-pane' );
        var iframe = pane?.querySelector( '.js-preview-pane-iframe' );
        if ( ! pane ) return;

        pane.classList.remove( 'is-open' );
        pane.setAttribute( 'aria-hidden', 'true' );
        document.body.style.overflow = '';

        // Libera iframe para parar carregamento
        if ( iframe ) { iframe.src = 'about:blank'; }
    }

} )();
