/**
 * Apollo Marketplace — Modal Consent
 * Checkbox toggle: unlocks the proceed button.
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initModalConsent = function() {
        var checkbox   = d.getElementById('modal-consent-check');
        var proceedBtn = d.getElementById('btn-proceed-chat');
        if (!checkbox || !proceedBtn) return;

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                proceedBtn.classList.add('active');
            } else {
                proceedBtn.classList.remove('active');
            }
        });
    };

})(window, document);
