/**
 * Apollo Marketplace — Filter Toggle
 * Carousel ↔ grid for mockup .market-view-toggle and legacy .view-toggle.
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    function setView(carouselEl, stageEl, mode) {
        var items = carouselEl.querySelectorAll('.carousel-item');
        var isGrid = mode === 'grid';

        if (stageEl) {
            stageEl.classList.toggle('is-grid', isGrid);
        }

        if (isGrid) {
            if (AM.carousel) {
                AM.carousel.isActive = false;
            }
            carouselEl.classList.add('grid-view');
            carouselEl.classList.remove('carousel');
            items.forEach(function(item) {
                item.style.transform = '';
                item.style.opacity = '';
                item.style.zIndex = '';
                item.style.removeProperty('--active');
                item.style.removeProperty('--abs-active');
                item.style.removeProperty('--zIndex');
            });
        } else {
            if (AM.carousel) {
                AM.carousel.isActive = true;
            }
            carouselEl.classList.remove('grid-view');
            carouselEl.classList.add('carousel');
            if (AM.carousel && typeof AM.carousel.animate === 'function') {
                AM.carousel.animate();
            }
        }
    }

    AM.initFilterToggle = function() {
        var toggles = d.querySelectorAll('.view-toggle, .market-view-toggle');
        if (!toggles.length) {
            return;
        }

        toggles.forEach(function(toggle) {
            var carouselId = toggle.getAttribute('data-carousel') || toggle.getAttribute('data-target');
            var carouselEl = carouselId ? d.getElementById(carouselId) : null;
            if (!carouselEl) {
                return;
            }

            var stageEl = d.getElementById('classificadosStage');
            var pills = toggle.querySelectorAll('.filter-pill[data-view]');
            var icons = toggle.querySelectorAll('[data-view][role="button"], [data-view].filter-pill');

            function activate(mode) {
                setView(carouselEl, stageEl, mode);
                pills.forEach(function(p) {
                    p.classList.toggle('active', p.getAttribute('data-view') === mode);
                });
                icons.forEach(function(icon) {
                    var target = icon.getAttribute('data-view');
                    icon.setAttribute('aria-pressed', target === mode ? 'true' : 'false');
                    if (icon.classList.contains('ri-gallery-view-2') || icon.querySelector('.ri-gallery-view-2')) {
                        return;
                    }
                });
            }

            pills.forEach(function(pill) {
                pill.addEventListener('click', function() {
                    activate(pill.getAttribute('data-view') || 'carousel');
                });
            });

            icons.forEach(function(icon) {
                function flip() {
                    var next = carouselEl.classList.contains('grid-view') ? 'carousel' : 'grid';
                    activate(next);
                }
                icon.addEventListener('click', flip);
                icon.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        flip();
                    }
                });
            });
        });
    };

})(window, document);
