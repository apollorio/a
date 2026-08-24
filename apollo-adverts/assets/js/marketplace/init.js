/**
 * Apollo Marketplace — Init (Entry Point)
 *
 * Bootstraps all marketplace modules on DOMContentLoaded.
 * Depends on: all marketplace/*.js modules loaded before this.
 *
 * @package Apollo\Adverts
 */
(function (w) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    function bootstrap() {
        /* ── GSAP reveal ── */
        if (typeof AM.initReveal === 'function') {
            AM.initReveal();
        }

        /* ── Carousel (only when wrapper exists) ── */
        if (document.getElementById('ticketCarousel')) {
            if (AM.carousel && typeof AM.carousel.init === 'function') {
                AM.carousel.init();
            }
            if (typeof AM.initCarouselDrag === 'function') {
                AM.initCarouselDrag();
            }
            if (typeof AM.initCarouselWheel === 'function') {
                AM.initCarouselWheel();
            }
            if (typeof AM.initCarouselClick === 'function') {
                AM.initCarouselClick();
            }
        }

        /* ── Filter toggle (carousel ↔ grid) ── */
        if (typeof AM.initFilterToggle === 'function') {
            AM.initFilterToggle();
        }

        /* ── Modal system ── */
        if (typeof AM.initModalOpen === 'function') {
            AM.initModalOpen();
        }
        if (typeof AM.initModalConsent === 'function') {
            AM.initModalConsent();
        }
        if (typeof AM.initModalClose === 'function') {
            AM.initModalClose();
        }
        if (typeof AM.initModalChat === 'function') {
            AM.initModalChat();
        }
    }

    /* Run on DOMContentLoaded or immediately if already ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }

})(window);
