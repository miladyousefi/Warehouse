self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    const payload = event.data ? event.data.json() : {};
    const title = payload.title || 'New update';

    event.waitUntil(
        self.registration.showNotification(title, {
            body: payload.body || 'You have a new notification.',
            icon: '/apple-touch-icon.png',
            badge: '/apple-touch-icon.png',
            vibrate: [120, 60, 120],
            data: {
                url: payload.url || '/ai/chat',
            },
            tag: payload.tag || 'warehouse-update',
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/ai/chat';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }

            return undefined;
        }),
    );
});
