/**
 * Apollo Marketplace — Modal Open
 * Opens the disclaimer modal, storing target user/classified data.
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initModalOpen = function() {
        var modal = d.getElementById('apollo-classifieds-modal');
        if (!modal) return;

        d.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-open-modal');
            if (!btn) return;
            e.preventDefault();

            var userId       = btn.getAttribute('data-a-user')       || '';
            var classifiedId = btn.getAttribute('data-classified-id') || '';
            var username     = btn.getAttribute('data-username')      || '';

            var proceedBtn = d.getElementById('btn-proceed-chat');
            if (proceedBtn) {
                proceedBtn.setAttribute('data-target-user', userId);
                proceedBtn.setAttribute('data-classified-id', classifiedId);
                proceedBtn.setAttribute('data-username', username);
            }

            var checkbox = d.getElementById('modal-consent-check');
            if (checkbox) {
                checkbox.checked = false;
            }
            if (proceedBtn) {
                proceedBtn.classList.remove('active');
                proceedBtn.textContent = 'INICIAR CHAT';
            }

            modal.classList.add('open');
        });
    };

})(window, document);
