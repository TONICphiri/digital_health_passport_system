/*
 * PWA service worker for the Digital Health Passport.
 *
 * - STATIC: app shell assets (CSS, JS, images, fonts, manifest) — cached on install.
 * - SHELL:  the offline scan page — no patient data, safe to keep on the device.
 * - PATIENT: patient record pages — cached automatically the first time a
 *   health worker opens them while online. Read-only, never POST forms.
 *
 * Navigation requests try the network first, then fall back to cache.
 * Static assets use cache-first for speed on weak connections.
 * API and form POST requests are never cached.
 *
 * Auth-related routes (login, logout, password reset) are always passed
 * straight to the network so sessions behave correctly across browser tabs.
 */
const STATIC = 'dhp-static-v4';
const SHELL = 'dhp-shell-v3';
const PATIENT = 'dhp-patient-v2';
const MAX_PATIENT_PAGES = 50; // keep the device cache from growing without bound

const STATIC_PATHS = [
    '/manifest.webmanifest',
    '/build/assets/',
    '/images/',
];

const AUTH_PATHS = ['/login', '/logout', '/password', '/register'];

const scope = new URL(self.registration.scope);
const shellUrl = new URL('offline/scan', scope).href;

const isAuthPath = (pathname) => AUTH_PATHS.some((p) => pathname.startsWith(p));
const isPatientPage = (pathname) => pathname.startsWith('/patients/');

const cacheShell = async () => {
    const response = await fetch(shellUrl, { cache: 'no-store', credentials: 'same-origin' });
    if (!response.ok || response.redirected) return;
    await (await caches.open(SHELL)).put(shellUrl, response);
};

const cacheStatic = async () => {
    const cache = await caches.open(STATIC);
    await cache.addAll([
        '/manifest.webmanifest',
        '/images/logo.png',
        '/images/icon-192.svg',
        '/images/icon-512.svg',
        '/images/icon-maskable-512.svg',
    ]);
};

/*
 * Store a patient page in the PATIENT cache, enforcing a maximum count so
 * older entries are evicted before the device runs out of storage.
 */
const cachePatientPage = async (request, response) => {
    const cache = await caches.open(PATIENT);
    const cloned = response.clone();
    await cache.put(request, cloned);

    const keys = await cache.keys();
    if (keys.length > MAX_PATIENT_PAGES) {
        await cache.delete(keys[0]);
    }
};

self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([cacheShell(), cacheStatic()]).catch(() => {
            // The scan page primes the cache on its next visit instead.
        }),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) =>
                Promise.all(
                    keys.filter((key) => ![STATIC, SHELL, PATIENT].includes(key)).map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

const noConnection = () => new Response(
    '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
    + '<title>No connection</title><body style="font-family:sans-serif;max-width:28rem;margin:20vh auto;padding:0 1rem">'
    + '<h1>No connection</h1><p>This page could not be reached. Check the connection and try again.</p>'
    + '<p><button onclick="location.reload()">Try again</button></p></body>',
    { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } },
);

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    // Always let auth routes go straight to the network so login/logout
    // sessions work correctly across browser tabs and windows.
    if (isAuthPath(url.pathname)) return;

    // Navigation requests: network first, then cache, then shell.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Automatically cache patient pages for offline use.
                    if (isPatientPage(url.pathname) && response.ok) {
                        cachePatientPage(request, response).catch(() => { /* best effort */ });
                    }
                    return response;
                })
                .catch(async () => {
                    // Try the PATIENT cache next (offline viewing of a visited patient).
                    const cached = await caches.open(PATIENT).then((cache) => cache.match(request));
                    if (cached) return cached;

                    // Fall back to the offline scan shell.
                    const shell = await caches.open(SHELL).then((cache) => cache.match(shellUrl));
                    return shell ?? noConnection();
                }),
        );
        return;
    }

    // Static assets: cache first, then network.
    if (STATIC_PATHS.some((path) => url.pathname.includes(path))) {
        event.respondWith(
            caches.open(STATIC).then((cache) =>
                cache.match(request).then((cached) => {
                    if (cached) return cached;
                    return fetch(request).then((response) => {
                        if (response.ok) cache.put(request, response.clone());
                        return response;
                    });
                }),
            ),
        );
        return;
    }
});
