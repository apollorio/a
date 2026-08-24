/**
 * Apollo Marketplace — GSAP Scroll Reveal
 * Animates .reveal-up elements on scroll.
 */
(function(w) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initReveal = function() {
        if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
            return;
        }

        gsap.registerPlugin(ScrollTrigger);

        gsap.utils.toArray('.reveal-up').forEach(function(elem) {
            gsap.fromTo(elem,
                { y: 50, opacity: 0 },
                {
                    y: 0,
                    opacity: 1,
                    duration: 1,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: elem,
                        start: 'top 90%'
                    }
                }
            );
        });
    };

})(window);
