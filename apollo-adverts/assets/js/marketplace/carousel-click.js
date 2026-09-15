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
                var last = Math.max(1, c.$items.length - 1);
                var activeIndex = Math.round((c.progress / 100) * last);
                if (c.$items.length > 1 && i !== activeIndex) {
                    e.preventDefault();
                    e.stopPropagation();
                    c.progress = (i / last) * 100;
                    c.animate();
                }
            });
        });
    };

})(window);
