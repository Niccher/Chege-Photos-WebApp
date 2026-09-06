const CACHE_NAME = 'chege-photos-shell-v2';
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

    // Skip all media streams, Range requests, videos, direct uploads, thumbnails, and vault streaming proxy
    // Letting the native browser network stack handle these prevents AbortError and Range 206 interception issues
    if (
        req.headers.has('range') ||
        url.pathname.match(/\.(mp4|webm|mov|mkv|avi|m4v|ogg|mp3|m4a|bin)$/i) ||
        url.pathname.startsWith('/uploads/') ||
        url.pathname.startsWith('/thumbnails/') ||
        url.pathname.startsWith('/vault/media/')
    ) {
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

    // Network-first for dynamic navigation and HTML pages
    event.respondWith(
        fetch(req).catch((err) => {
            // If the user aborted the request (e.g. paused/scrubbed video or navigated away), ignore cleanly
            if (err && err.name === 'AbortError') {
                return new Response(null, { status: 499, statusText: 'Client Closed Request' });
            }
            return caches.match(req);
        })
    );
});
