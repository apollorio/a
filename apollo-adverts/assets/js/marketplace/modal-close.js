/**
 * Apollo Marketplace — Modal Close
 * Handles closing the disclaimer modal.
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.closeModal = function() {
        var modal = d.getElementById('apollo-classifieds-modal');
        if (modal) {
            modal.classList.remove('open');
        }
    };

    AM.initModalClose = function() {
        var modal = d.getElementById('apollo-classifieds-modal');
        if (!modal) return;

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                AM.closeModal();
            }
        });

        d.addEventListener('click', function(e) {
            if (e.target.closest('.btn-close-modal')) {
                AM.closeModal();
            }
        });

        d.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                AM.closeModal();
            }
        });
    };

})(window, document);
