/**
 * Apollo Hub — JS Público (hub.js)
 *
 * Integrado com Apollo CDN (cdn.apollo.rio.br):
 *   - Aguarda evento 'apollo:ready' antes de inicializar
 *   - Ícones via CDN icon runtime (SVG mask-image) — NÃO icon-font
 *   - GSAP 3.13 carregado pelo CDN (não importar separadamente)
 *   - ApolloTrack analytics carregado pelo CDN
 *
 * Funcionalidades:
 *   - Share panel toggle
 *   - Copiar link para clipboard
 *   - GSAP reveal animations para links e eventos
 *   - ApolloTrack eventos de analytics
 *
 * @package Apollo\Hub
 */

/* global apolloHub, ApolloTrack, gsap */

( function () {
    'use strict';

    // ─── Aguarda CDN apollo:ready OU DOMContentLoaded (safety fallback) ──
    var initialized = false;

    function boot() {
        if ( initialized ) return;
        initialized = true;

        revealAnimations();
        initSharePanels();
        initCopyLinks();
        initLinkTracking();
    }

    // Prioridade: evento CDN (icons + GSAP já disponíveis)
    window.addEventListener( 'apollo:ready', boot, { once: true } );

    // Fallback de segurança (5s timeout caso CDN falhe)
    setTimeout( function () {
        if ( ! initialized ) {
            console.warn( '[Apollo Hub] CDN timeout — inicializando standalone.' );
            boot();
        }
    }, 5000 );

    // ─────────────────────────────────────────────────────────────────────
    // GSAP REVEAL ANIMATIONS
    // ─────────────────────────────────────────────────────────────────────

    function revealAnimations() {
        var items = document.querySelectorAll( '[data-reveal-up]' );
        if ( ! items.length ) return;

        if ( typeof gsap !== 'undefined' ) {
            gsap.fromTo(
                items,
                { autoAlpha: 0, y: 20 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.5,
                    stagger: 0.07,
                    ease: 'power2.out',
                    clearProps: 'all',
                }
            );

            // Header + socials
            gsap.fromTo(
                '.hub-header',
                { autoAlpha: 0, y: -12 },
                { autoAlpha: 1, y: 0, duration: 0.6, ease: 'power2.out', delay: 0.1 }
            );

            gsap.fromTo(
                '.hub-socials',
                { autoAlpha: 0, y: 10 },
                { autoAlpha: 1, y: 0, duration: 0.4, ease: 'power2.out', delay: 0.25 }
            );

            // Glass card entrance
            gsap.fromTo(
                '.hub-card',
                { autoAlpha: 0.6, y: 30 },
                { autoAlpha: 1, y: 0, duration: 0.7, ease: 'power3.out' }
            );
        } else {
            // Fallback CSS quando GSAP não está disponível
            items.forEach( function ( el ) {
                el.classList.add( 'is-visible' );
            } );
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // SHARE PANELS
    // ─────────────────────────────────────────────────────────────────────

    function initSharePanels() {
        var shareToggle = document.querySelector( '.js-hub-page-share' );
        var sharePanel  = document.querySelector( '.js-hub-share-panel' );

        if ( shareToggle && sharePanel ) {
            shareToggle.addEventListener( 'click', function () {
                var isOpen = sharePanel.classList.toggle( 'is-open' );
                sharePanel.setAttribute( 'aria-hidden', String( ! isOpen ) );

                if ( isOpen && typeof ApolloTrack !== 'undefined' ) {
                    ApolloTrack.event( 'hub_share_open', {
                        username: getHubUsername(),
                    } );
                }
            } );

            // Fechar ao clicar fora
            document.addEventListener( 'click', function ( e ) {
                if (
                    sharePanel.classList.contains( 'is-open' ) &&
                    ! sharePanel.contains( e.target ) &&
                    ! shareToggle.contains( e.target )
                ) {
                    sharePanel.classList.remove( 'is-open' );
                    sharePanel.setAttribute( 'aria-hidden', 'true' );
                }
            } );
        }

        // Share rápido individual de cada link
        document.querySelectorAll( '.js-hub-share-toggle' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function ( e ) {
                e.preventDefault();
                openNativeShare( btn.dataset.url || '', btn.dataset.text || '' );
            } );
        } );
    }

    /**
     * Web Share API nativa → fallback WhatsApp (mobile) / Twitter (desktop)
     */
    function openNativeShare( url, text ) {
        if ( navigator.share ) {
            navigator.share( { title: text, url: url } ).catch( function () {} );
        } else {
            var shareUrl = /Android|iPhone|iPad/i.test( navigator.userAgent )
                ? 'https://wa.me/?text=' + encodeURIComponent( text + ' ' + url )
                : 'https://x.com/intent/tweet?url=' + encodeURIComponent( url ) + '&text=' + encodeURIComponent( text );
            window.open( shareUrl, '_blank', 'noopener,noreferrer,width=580,height=460' );
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // COPY LINK
    // ─────────────────────────────────────────────────────────────────────

    function initCopyLinks() {
        document.querySelectorAll( '.js-hub-copy-link' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                copyToClipboard( btn.dataset.url || window.location.href, btn );
            } );
        } );
    }

    function copyToClipboard( url, btn ) {
        var i18n = ( typeof apolloHub !== 'undefined' && apolloHub.i18n ) ? apolloHub.i18n : {};

        if ( navigator.clipboard && navigator.clipboard.writeText ) {
            navigator.clipboard.writeText( url ).then( function () {
                showCopyFeedback( btn );
            } ).catch( function () {
                fallbackCopy( url );
                showCopyFeedback( btn );
            } );
        } else {
            fallbackCopy( url );
            showCopyFeedback( btn );
        }
    }

    function fallbackCopy( text ) {
        var el = document.createElement( 'textarea' );
        el.value = text;
        el.style.cssText = 'position:fixed;left:-9999px;top:-9999px;';
        document.body.appendChild( el );
        el.focus();
        el.select();
        try { document.execCommand( 'copy' ); } catch ( e ) {}
        document.body.removeChild( el );
    }

    function showCopyFeedback( btn ) {
        var originalHTML = btn.innerHTML;
        // CDN MutationObserver detecta o novo <i> automaticamente
        btn.innerHTML = '<i class="ri-check-line" aria-hidden="true"></i>';
        btn.style.color = '#00ffaa';

        setTimeout( function () {
            btn.innerHTML = originalHTML;
            btn.style.color = '';
        }, 1800 );
    }

    // ─────────────────────────────────────────────────────────────────────
    // LINK TRACKING — ApolloTrack (carregado pelo CDN)
    // ─────────────────────────────────────────────────────────────────────

    function initLinkTracking() {
        if ( typeof ApolloTrack === 'undefined' ) return;

        document.querySelectorAll( '[data-apollo-track="hub_link_click"]' ).forEach( function ( el ) {
            el.addEventListener( 'click', function () {
                ApolloTrack.event( 'hub_link_click', {
                    username : el.dataset.hubUsername || getHubUsername(),
                    url      : el.href,
                    title    : el.querySelector( '.hub-link-btn__title' )?.textContent?.trim() || '',
                } );
            } );
        } );
    }

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────

    function getHubUsername() {
        var root = document.getElementById( 'hub-root' );
        return root ? ( root.dataset.username || '' ) : '';
    }

} )();
