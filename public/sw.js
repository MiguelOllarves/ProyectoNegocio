/**
 * TuInventario — Service Worker v2.0
 * Estrategia: Cache-first para estáticos (CDN, íconos, fonts)
 *             Network-first para páginas dinámicas (PHP/HTML)
 *             Fallback offline graceful
 */
const CACHE_NAME = 'tu-inventario-v13';
const STATIC_ASSETS = [
    '/offline.html',
    '/icons/icon-512x512.png'
];

// CDN resources to pre-cache on install for ultra-fast loads
const CDN_ASSETS = [
    'https://cdn.tailwindcss.com',
    'https://unpkg.com/htmx.org@1.9.11',
    'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap'
];

// ============================================================
// Install: Pre-cache static and CDN assets
// ============================================================
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            const staticPromise = cache.addAll(STATIC_ASSETS).catch(() => {});
            const cdnPromise = Promise.allSettled(
                CDN_ASSETS.map(url => 
                    fetch(url, { mode: 'cors' })
                        .then(res => { if (res.ok) cache.put(url, res); })
                        .catch(() => {})
                )
            );
            return Promise.all([staticPromise, cdnPromise]);
        })
    );
    self.skipWaiting();
});

// ============================================================
// Activate: Clean old cache versions
// ============================================================
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

// ============================================================
// Fetch: Smart caching strategies
// ============================================================
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip non-GET requests (POST, PUT, DELETE)
    if (event.request.method !== 'GET') return;

    // Skip non-HTTP protocols
    if (!url.protocol.startsWith('http')) return;

    // ------- STRATEGY 1: Cache-first for CDN resources -------
    if (url.origin !== location.origin) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                });
            }).catch(() => caches.match(event.request).then(res => res || new Response('', { status: 408, statusText: 'Request Timeout' })))
        );
        return;
    }

    // ------- STRATEGY 2: Cache-first for local assets (Images, CSS, JS) -------
    if (url.pathname.match(/\.(png|jpg|jpeg|gif|webp|svg|ico|woff2?|css|js)$/)) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                });
            }).catch(() => caches.match(event.request).then(res => res || new Response('', { status: 408, statusText: 'Request Timeout' })))
        );
        return;
    }

    // ------- STRATEGY 3: Network-first for everything else (Dynamic pages, HTMX, JSON) -------
    // Don't cache API or HTMX / JSON requests
    const isApiOrDynamic = url.pathname.includes('/api/') || 
                           url.pathname.includes('/list') || 
                           url.pathname.includes('/edit') || 
                           url.searchParams.has('json') || 
                           event.request.headers.get('Accept')?.includes('application/json') ||
                           event.request.headers.get('HX-Request') === 'true';

    if (isApiOrDynamic) {
        return; // Bypass Service Worker completely for dynamic routes
    }

    event.respondWith(
        fetch(event.request)
            .then(response => {
                if (response.ok && !isApiOrDynamic) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone)).catch(() => {});
                }
                return response;
            })
            .catch(() => {
                // Offline fallback
                return caches.match(event.request).then(cached => {
                    if (cached) return cached;
                    if (!isApiOrDynamic && event.request.headers.get('Accept')?.includes('text/html')) {
                        return caches.match('/offline.html').then(offlineCached => {
                            if (offlineCached) return offlineCached;
                            return new Response(
                                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sin Conexión</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;background:#0f172a;color:white;text-align:center;}h1{color:#10b981;}</style></head><body><div><h1>📦 TuInventario</h1><p>Sin conexión. Verifica tu internet e intenta de nuevo.</p></div></body></html>',
                                { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                            );
                        });
                    }
                    return new Response('', { status: 408, statusText: 'Request Timeout' });
                });
            })
    );
});

// ============================================================
// Web Push Notifications
// ============================================================
self.addEventListener('push', event => {
    let payload = { title: 'TuInventario', body: 'Nueva notificación.' };
    
    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            payload.body = event.data.text();
        }
    }
    
    const options = {
        body: payload.body,
        icon: payload.icon || '/icons/icon-192x192.png',
        badge: payload.badge || '/icons/icon-72x72.png', // Small monochrome icon
        vibrate: [200, 100, 200, 100, 200, 100, 200],
        data: {
            url: payload.url || '/'
        },
        requireInteraction: true,
        silent: false
    };
    
    event.waitUntil(
        self.registration.showNotification(payload.title, options).then(() => {
            return clients.matchAll({ type: 'window', includeUncontrolled: true });
        }).then(windowClients => {
            for (let i = 0; i < windowClients.length; i++) {
                if (windowClients[i].visibilityState === 'visible' || windowClients[i].focused) {
                    windowClients[i].postMessage({ type: 'PLAY_NOTIFICATION_SOUND' });
                }
            }
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
            const urlToOpen = new URL(event.notification.data.url, self.location.origin).href;
            
            // Check if there is already a window/tab open with the target URL
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, open a new window
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

