/**
 * ASENA Enterprise - Progressive Web App (PWA) Service Worker
 * Version: 1.0.0
 */

const CACHE_NAME = 'asena-enterprise-v1.0.2';
const STATIC_ASSETS = [
    './offline.html',
    './assets/css/style.css',
    './assets/css/vazirmatn.css',
    './assets/css/geist.css',
    './assets/css/material-symbols.css',
    './assets/css/enterprise-ui.css',
    './assets/images/logo.png',
    './favicon.ico',
    './site.webmanifest'
];


// 1. Install Event - Pre-cache critical application shell
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[PWA ServiceWorker] Pre-caching partial warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// 2. Activate Event - Clean up stale cache versions
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch Event - Multi-Tier Strategy
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle GET requests and same-origin / specific CDN requests
    if (request.method !== 'GET') {
        return;
    }

    // A. Static Assets (CSS, JS, Fonts, Images) -> Cache-First with Network Update
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'font' ||
        request.destination === 'image' ||
        url.pathname.startsWith('/assets/')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Update cache in background
                    fetch(request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            caches.open(CACHE_NAME).then((cache) => cache.put(request, networkResponse));
                        }
                    }).catch(() => {});
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const clone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // B. HTML / Navigation Requests -> Always Network-First (never cache dynamic authenticated HTML)
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match('./offline.html').then((cached) => {
                    return cached || caches.match('/offline.html');
                });
            })
        );
        return;
    }

    // C. Default: Network with Cache Fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// 4. Push Notification Event (Chewy/Amazon Benchmark Alerts)
self.addEventListener('push', (event) => {
    let data = {
        title: 'آسنا - همراه سلامت حیوانات خانگی',
        body: 'یک به‌روزرسانی جدید در سفارش یا پرونده سلامت پت شما ثبت گردید.',
        icon: '/assets/images/logo.png',
        badge: '/assets/images/favicon-32x32.png',
        url: '/profile.php'
    };

    if (event.data) {
        try {
            data = Object.assign(data, event.data.json());
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        data: { url: data.url },
        vibrate: [100, 50, 100],
        actions: [
            { action: 'open', title: 'مشاهده جزئیات' },
            { action: 'close', title: 'بستن' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// 5. Notification Click Action
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.action === 'close') return;

    const targetUrl = event.notification.data?.url || '/profile.php';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
