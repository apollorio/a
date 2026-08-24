/**
 * Apollo Marketplace — Carousel Click
 * Clicking a carousel card snaps it to center.
 */
(function(w) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initCarouselClick = function() {
        var c = AM.carousel;
        if (!c || !c.$items || c.$items.length === 0) {
            return;
        }

        c.$items.forEach(function(item, i) {
            item.addEventListener('click', function(e) {
                if (!c.isActive) return;
                if (e.target.closest('button') || e.target.closest('a')) return;
                c.progress = (i / Math.max(1, c.$items.length - 1)) * 100;
                c.animate();
            });
        });
    };

})(window);
