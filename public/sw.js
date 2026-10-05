/*
 * Keeps the scanner's script, styles, fonts and logo so the scan page opens
 * quickly on a weak connection, and keeps one plain page that lets a health
 * worker scan cards when the connection is down. Other pages are never stored,
 * because they can contain patient information.
 */
const STATIC = 'dhp-static-v1';
const SHELL = 'dhp-shell';
const STATIC_PATHS = ['/build/assets/', '/images/'];

const scope = new URL(self.registration.scope);
const scanPath = new URL('passports/open', scope).pathname;
const shellUrl = new URL('offline/scan', scope).href;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => ![STATIC, SHELL].includes(key)).map((key) => caches.delete(key))))
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

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => {
                if (url.pathname === scanPath) {
                    const shell = await caches.open(SHELL).then((cache) => cache.match(shellUrl));
                    if (shell) return shell;
                }

                return noConnection();
            }),
        );
        return;
    }

    if (STATIC_PATHS.some((path) => url.pathname.includes(path))) {
        event.respondWith(
            caches.open(STATIC).then((cache) => cache.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) cache.put(request, response.clone());
                return response;
            }))),
        );
    }
});
