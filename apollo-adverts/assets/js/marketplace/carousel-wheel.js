/**
 * Apollo Marketplace — Carousel Wheel
 * Handles scroll-wheel navigation in carousel mode.
 */
(function(w) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initCarouselWheel = function() {
        var c = AM.carousel;
        if (!c || !c.$el) {
            return;
        }

        c.$el.addEventListener('wheel', function(e) {
            if (!c.isActive) return;
            if (!e.target.closest('.carousel')) return;
            e.preventDefault();
            c.progress += e.deltaY * c.speedWheel;
            c.animate();
        }, { passive: false });
    };

})(window);
