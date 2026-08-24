/**
 * Apollo::Rio Remind — Push Registration
 *
 * Registers the service worker and manages push subscription.
 * Depends on `apolloRemind` localized object from wp_localize_script.
 */
(function () {
    'use strict';

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    if (typeof apolloRemind === 'undefined') return;

    const { rest, nonce, vapidKey, swUrl } = apolloRemind;

    /* ── Register Service Worker ────────────────────────────── */
    navigator.serviceWorker.register(swUrl)
        .then((reg) => {
            return reg;
        })
        .catch((err) => {
            console.warn('[Apollo Remind] SW registration failed:', err);
        });

    /* ── Public API for frontend components ──────────────────── */
    window.ApolloRemindPush = {

        /**
         * Request notification permission and subscribe.
         * Call this from a user-initiated action (button click).
         * @returns {Promise<boolean>}
         */
        async subscribe() {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                return false;
            }

            const reg = await navigator.serviceWorker.ready;

            // Convert VAPID key from base64url to Uint8Array
            const vapidBytes = urlBase64ToUint8Array(vapidKey);

            const subscription = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: vapidBytes,
            });

            // Send subscription to server
            const subJSON = subscription.toJSON();
            const response = await fetch(rest + '/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({
                    endpoint: subJSON.endpoint,
                    keys: {
                        p256dh: subJSON.keys.p256dh,
                        auth: subJSON.keys.auth,
                    },
                }),
            });

            const data = await response.json();
            return data.subscribed === true;
        },

        /**
         * Unsubscribe from push notifications.
         * @returns {Promise<boolean>}
         */
        async unsubscribe() {
            const reg = await navigator.serviceWorker.ready;
            const subscription = await reg.pushManager.getSubscription();
            if (!subscription) return true;

            const endpoint = subscription.endpoint;
            await subscription.unsubscribe();

            await fetch(rest + '/push/unsubscribe', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({ endpoint }),
            });

            return true;
        },

        /**
         * Check current subscription status.
         * @returns {Promise<boolean>}
         */
        async isSubscribed() {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            return sub !== null;
        },
    };

    /* ── Utility: Base64URL to Uint8Array ────────────────────── */
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
})();
