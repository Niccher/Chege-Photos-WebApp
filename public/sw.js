const CACHE_NAME = 'chege-photos-shell-v1';
const STATIC_ASSETS = [
    '/css/bootstrap.min.css',
    '/js/bootstrap.bundle.min.js',
    '/js/jquery.min.js',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/favicon.ico',
    '/manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('Some static assets failed to precache:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    // Only handle GET requests
    if (req.method !== 'GET') {
        return;
    }

    // Skip API, authentication, and WebSocket calls
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/auth/')) {
        return;
    }

    // Cache-first for core static icons and fonts
    if (url.pathname.startsWith('/icons/') || url.pathname.endsWith('.woff2') || url.pathname.endsWith('.ttf')) {
        event.respondWith(
            caches.match(req).then((cached) => {
                return cached || fetch(req).then((response) => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Network-first for dynamic navigation and media
    event.respondWith(
        fetch(req).catch(() => {
            return caches.match(req);
        })
    );
});
