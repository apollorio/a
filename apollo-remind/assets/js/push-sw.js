/**
 * Apollo::Rio Remind — Push Service Worker
 * Handles incoming push events and notification clicks.
 */

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'Apollo::Rio', body: event.data.text() };
    }

    const options = {
        body:    data.body    || '',
        icon:    data.icon    || '/wp-content/plugins/apollo-remind/assets/icon-192.png',
        badge:   data.badge   || '/wp-content/plugins/apollo-remind/assets/badge-72.png',
        tag:     data.tag     || 'apollo-remind',
        data:    { url: data.url || '/' },
        vibrate: [200, 100, 200],
        actions: [
            { action: 'open', title: 'Abrir' },
            { action: 'dismiss', title: 'Dispensar' },
        ],
        requireInteraction: true,
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Apollo::Rio', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'dismiss') return;

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            // Focus existing tab if open
            for (const client of windowClients) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            // Open new tab
            return clients.openWindow(url);
        })
    );
});

self.addEventListener('notificationclose', (event) => {
    // Analytics hook — could POST to /apollo/v1/track/event
});
