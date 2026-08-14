// Service Worker untuk Tabungan Siswa - SDN 4 Rambatan Kulon
const CACHE_NAME = 'tabungan-siswa-v1';
const STATIC_ASSETS = [
    '/',
    '/dashboard',
    '/css/app.css',
    '/images/logo.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Caching static assets');
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.log('[SW] Failed to cache some assets:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate event - cleanup old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - Network first, fallback to cache
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) return;

    // Skip admin/API routes that need fresh data
    const url = new URL(event.request.url);
    const skipCache = ['/logout', '/login'];
    if (skipCache.some(path => url.pathname.startsWith(path))) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses for static assets
                if (response.ok) {
                    const isStatic = event.request.url.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2)$/);
                    if (isStatic) {
                        const clonedResponse = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, clonedResponse);
                        });
                    }
                }
                return response;
            })
            .catch(() => {
                // Fallback to cache if offline
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Return offline page for navigation requests
                    if (event.request.mode === 'navigate') {
                        return caches.match('/dashboard');
                    }
                });
            })
    );
});
