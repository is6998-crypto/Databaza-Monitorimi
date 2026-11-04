/*
 * CourtWatch monitor app service worker (scope /m).
 * - App shell (the /m page, built assets, icons) is cached so the app opens offline.
 * - API calls are never cached here: drafts and the case list live in IndexedDB.
 */
const CACHE = 'courtwatch-monitor-v2';
const STATIC = ['/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png', '/icons/favicon.svg'];

/**
 * Caches the app page and every built asset it references (their names are hashed by Vite,
 * so they are read from the page itself).
 */
async function cacheShell() {
    const cache = await caches.open(CACHE);
    await Promise.all(STATIC.map((url) => cache.add(url).catch(() => null)));

    const response = await fetch('/m', { credentials: 'same-origin' });

    if (!response.ok || response.redirected) {
        return;
    }

    const html = await response.clone().text();
    await cache.put('/m', response);

    const assets = [...html.matchAll(/(?:href|src)="([^"]*\/build\/assets\/[^"]+)"/g)].map((match) => match[1]);
    await Promise.all(assets.map((url) => cache.add(url).catch(() => null)));
}

self.addEventListener('install', (event) => {
    event.waitUntil(cacheShell().catch(() => null));
    self.skipWaiting();
});

// The page asks for a refresh of the cached shell after each online start (new deploys).
self.addEventListener('message', (event) => {
    if (event.data === 'refresh-shell') {
        event.waitUntil(cacheShell().catch(() => null));
    }
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key.startsWith('courtwatch-monitor') && key !== CACHE).map((key) => caches.delete(key)))),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // The app page: network first (fresh CSRF token and user), cached copy when offline.
    if (request.mode === 'navigate' && url.pathname.replace(/\/$/, '') === '/m') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok && !response.redirected) {
                        const copy = response.clone();
                        caches.open(CACHE).then((cache) => cache.put('/m', copy));
                    }
                    return response;
                })
                .catch(() => caches.match('/m')),
        );
        return;
    }

    // Versioned build assets and icons: cache first.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname === '/manifest.webmanifest') {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            })),
        );
    }
});
