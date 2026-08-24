/**
 * Apollo Marketplace — Filter Toggle
 * Toggles between Carousel mode and Grid mode.
 * Uses .view-toggle [data-view] pills from section-header.
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initFilterToggle = function() {
        var toggles = d.querySelectorAll('.view-toggle');
        if (!toggles.length) return;

        toggles.forEach(function(toggle) {
            var carouselId = toggle.getAttribute('data-carousel');
            var carouselEl = carouselId ? d.getElementById(carouselId) : null;
            if (!carouselEl) return;

            var pills = toggle.querySelectorAll('.filter-pill[data-view]');
            var items = carouselEl.querySelectorAll('.carousel-item');

            pills.forEach(function(pill) {
                pill.addEventListener('click', function() {
                    pills.forEach(function(p) { p.classList.remove('active'); });
                    pill.classList.add('active');

                    var view = pill.getAttribute('data-view');

                    if (view === 'carousel') {
                        if (AM.carousel) {
                            AM.carousel.isActive = true;
                        }
                        carouselEl.classList.remove('grid-view');
                        carouselEl.classList.add('carousel');
                        if (AM.carousel && typeof AM.carousel.animate === 'function') {
                            AM.carousel.animate();
                        }
                    } else {
                        if (AM.carousel) {
                            AM.carousel.isActive = false;
                        }
                        carouselEl.classList.add('grid-view');
                        carouselEl.classList.remove('carousel');

                        items.forEach(function(item) {
                            item.style.transform  = '';
                            item.style.opacity    = '';
                            item.style.zIndex     = '';
                            item.style.setProperty('--active', '');
                            item.style.setProperty('--abs-active', '');
                            item.style.setProperty('--zIndex', '');
                        });
                    }
                });
            });
        });
    };

})(window, document);
