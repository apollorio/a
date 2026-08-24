/**
 * Apollo Marketplace — Carousel Drag (Mouse + Touch)
 * Handles drag physics for the horizontal card carousel.
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initCarouselDrag = function() {
        var c = AM.carousel;
        if (!c || !c.$el) {
            return;
        }

        var handleDown = function(e) {
            if (!c.isActive) return;
            if (!e.target.closest('.carousel')) return;
            c.isDown  = true;
            c.startX  = e.clientX || (e.touches && e.touches[0].clientX) || 0;
        };

        var handleMove = function(e) {
            if (!c.isActive || !c.isDown) return;
            var x = e.clientX || (e.touches && e.touches[0].clientX) || 0;
            var delta = (x - c.startX) * c.speedDrag;
            c.progress += delta;
            c.startX = x;
            c.animate();
        };

        var handleUp = function() {
            c.isDown = false;
        };

        c.$el.addEventListener('mousedown', handleDown);
        c.$el.addEventListener('touchstart', handleDown, { passive: true });

        w.addEventListener('mousemove', handleMove);
        w.addEventListener('touchmove', handleMove, { passive: true });

        w.addEventListener('mouseup', handleUp);
        w.addEventListener('touchend', handleUp);
    };

})(window, document);
