/**
 * Apollo Marketplace — Modal Chat Redirect
 *
 * After consent, opens /mensagens in a NEW TAB (target="_blank")
 * with the seller's user_id + classified context so apollo-chat
 * can find/create the conversation thread.
 *
 * Route: /mensagens?to={user_id}&context=classified&ref={classified_id}
 * Registry: apollo/v1/chat/thread-for-context (GET)
 */
(function(w, d) {
    'use strict';

    var AM = w.ApolloMarketplace = w.ApolloMarketplace || {};

    AM.initModalChat = function() {
        var proceedBtn = d.getElementById('btn-proceed-chat');
        if (!proceedBtn) return;

        proceedBtn.addEventListener('click', function() {
            if (!this.classList.contains('active')) return;

            var userId       = this.getAttribute('data-target-user')  || '';
            var classifiedId = this.getAttribute('data-classified-id') || '';

            if (!userId) return;

            this.textContent = 'Conectando...';
            this.classList.remove('active');

            var chatUrl = '/mensagens'
                + '?to=' + encodeURIComponent(userId)
                + '&context=classified'
                + '&ref=' + encodeURIComponent(classifiedId);

            w.open(chatUrl, '_blank', 'noopener,noreferrer');

            var self = this;
            setTimeout(function() {
                AM.closeModal();
                self.textContent = 'INICIAR CHAT';
            }, 800);
        });
    };

})(window, document);
