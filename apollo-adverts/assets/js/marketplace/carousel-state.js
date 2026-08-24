/**
 * Apollo Marketplace — Carousel State
 * Manages progress, items, and the core animate function.
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.carousel = {
        progress: 50,
        isActive: true,
        speedWheel: 0.05,
        speedDrag: -0.15,
        isDown: false,
        startX: 0,
        $items: null,
        $el: null,

        init: function() {
            this.$el    = d.getElementById('ticketCarousel');
            this.$items = d.querySelectorAll('#ticketCarousel .carousel-item');

            if (!this.$el || this.$items.length === 0) {
                return;
            }
            this.animate();
        },

        getZindex: function(array, index) {
            return array.map(function(_, i) {
                return (index === i) ? array.length : array.length - Math.abs(index - i);
            });
        },

        animate: function() {
            if (!this.$items || this.$items.length === 0) {
                return;
            }

            this.progress = Math.max(0, Math.min(this.progress, 100));
            var activeFloat = (this.progress / 100) * Math.max(1, this.$items.length - 1);
            var activeIndex = Math.round(activeFloat);
            var zIndexes = this.getZindex([].slice.call(this.$items), activeIndex);

            this.$items.forEach(function(item, index) {
                item.style.setProperty('--zIndex', zIndexes[index]);
                item.style.setProperty('--active', index - activeFloat);
                item.style.setProperty('--abs-active', Math.abs(index - activeFloat));
            });
        }
    };

})(window, document);
