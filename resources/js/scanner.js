import { Html5Qrcode } from 'html5-qrcode';
import { addItem, flatten, hasDraft, listFor, nest, removeItem, setDraft } from './offlineQueue';

/**
 * Reads the QR code on a health passport card with the device camera and
 * opens the passport. Scanning is the first step of every visit.
 *
 * Without a connection the card is still read and kept in this browser. The
 * health worker can type the visit notes or a vaccination for it. As soon as
 * the server can be reached the passport is opened first, then the notes are
 * added, so nothing bypasses the scan.
 */
document.addEventListener('DOMContentLoaded', () => {
    const region = document.getElementById('qr-reader');
    const form = document.getElementById('scan-form');
    const input = document.getElementById('code');
    const status = document.getElementById('scanner-status');
    const startButton = document.getElementById('start-scan');
    const panel = document.getElementById('pending-scan');
    const list = document.getElementById('pending-list');
    const syncForm = document.getElementById('sync-form');
    const draftPanel = document.getElementById('draft-panel');

    if (!region || !form || !input) return;

    // The cached offline page has no account in it, so it reads the worker the page last saw online.
    const USER_KEY = 'dhp.user';
    let userId = form.dataset.userId;
    try {
        if (userId) localStorage.setItem(USER_KEY, userId);
        else userId = localStorage.getItem(USER_KEY) ?? '';
    } catch { /* storage blocked */ }

    const limits = { openMinutes: Number(form.dataset.keepMinutes || 30), draftHours: Number(form.dataset.draftHours || 24) };
    const scanUrl = form.dataset.scanUrl;
    const scanner = new Html5Qrcode('qr-reader');
    let running = false;
    let retryTimer = null;
    let draftTarget = null;

    const setStatus = (text) => { if (status) status.textContent = text; };
    const items = () => (userId ? listFor(Number(userId), limits) : []);

    /* ---- Keep static files and the offline page on this device ---- */

    const keepOffline = async () => {
        if (!('serviceWorker' in navigator) || !form.dataset.worker) return;

        try {
            await navigator.serviceWorker.register(form.dataset.worker);
            await navigator.serviceWorker.ready;

            const files = [...document.querySelectorAll('script[src], link[rel="stylesheet"], link[rel="modulepreload"]')]
                .map((element) => element.src || element.href)
                .filter((url) => url.includes('/build/assets/'));

            const statics = await caches.open('dhp-static-v1');

            // Scripts import each other, so those files are found by reading the scripts.
            const seen = new Set(files);
            const queue = files.filter((url) => url.endsWith('.js'));
            while (queue.length) {
                const script = queue.pop();
                const text = await (await fetch(script)).text();
                for (const match of text.matchAll(/["'](\.\/[^"']+\.js)["']/g)) {
                    const dependency = new URL(match[1], script).href;
                    if (!seen.has(dependency)) {
                        seen.add(dependency);
                        queue.push(dependency);
                    }
                }
            }

            await statics.addAll([...seen]);

            // Fonts are named inside the stylesheet, so they are found there.
            for (const css of files.filter((url) => url.endsWith('.css'))) {
                const text = await (await fetch(css)).text();
                const fonts = [...text.matchAll(/url\(([^)]+\.woff2)\)/g)].map((match) => new URL(match[1].replace(/['"]/g, ''), css).href);
                await statics.addAll(fonts);
            }

            if (form.dataset.shell) await (await caches.open('dhp-shell')).add(form.dataset.shell);
        } catch { /* the page still works online */ }
    };

    /* ---- Connection ---- */

    const reachable = async () => {
        if (!navigator.onLine) return false;

        try {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 4000);
            const response = await fetch(scanUrl, { method: 'HEAD', cache: 'no-store', credentials: 'same-origin', signal: controller.signal });
            clearTimeout(timer);
            return response.ok || response.redirected;
        } catch {
            return false;
        }
    };

    /** The page may have been open for a while, so ask for a fresh form token. */
    const freshToken = async () => {
        try {
            const response = await fetch(scanUrl, { credentials: 'same-origin', cache: 'no-store' });

            if (response.redirected && !response.url.startsWith(new URL(scanUrl, location.href).href)) {
                location.href = response.url;
                return null;
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            return page.querySelector('#scan-form input[name="_token"]')?.value ?? null;
        } catch {
            return '';
        }
    };

    const send = async (item) => {
        const token = await freshToken();
        if (token === null) return;

        if (!hasDraft(item)) {
            // A card alone is safe to send twice, so the copy is dropped first.
            removeItem(item.id);
            input.value = item.code;
            const field = form.querySelector('input[name="_token"]');
            if (token && field) field.value = token;
            setStatus('Opening the passport.');
            form.submit();
            return;
        }

        // Notes stay on the device until the server confirms it has them.
        syncForm.querySelector('input[name="code"]').value = item.code;
        syncForm.querySelector('input[name="sync_id"]').value = item.id;
        if (token) syncForm.querySelector('input[name="_token"]').value = token;

        const fields = document.getElementById('sync-fields');
        fields.replaceChildren();
        flatten(item.drafts, 'draft').forEach(([name, value]) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.value = value;
            fields.append(hidden);
        });

        setStatus('Sending the visit.');
        syncForm.submit();
    };

    /* ---- The waiting list ---- */

    const clock = (item) => new Date(item.savedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const button = (label, handler, className = 'btn-secondary btn-sm') => {
        const element = document.createElement('button');
        element.type = 'button';
        element.className = className;
        element.textContent = label;
        element.addEventListener('click', handler);
        return element;
    };

    const render = () => {
        const waiting = items();
        if (!panel) return;

        panel.hidden = waiting.length === 0;
        list.replaceChildren();

        waiting.forEach((item) => {
            const row = document.createElement('li');
            row.className = 'flex flex-wrap items-center justify-between gap-2 border-b border-gold-600/30 px-3 py-2 last:border-b-0';

            const label = document.createElement('span');
            const kinds = Object.keys(item.drafts ?? {}).map((kind) => (kind === 'encounter' ? 'visit notes' : 'vaccination'));
            label.textContent = `Card read at ${clock(item)}${kinds.length ? `, ${kinds.join(' and ')} kept` : ''}`;

            const actions = document.createElement('span');
            actions.className = 'flex flex-wrap gap-2';
            actions.append(
                button('Visit notes', () => openDraft(item, 'encounter')),
                button('Vaccination', () => openDraft(item, 'vaccination')),
                button('Send now', async () => { if (await reachable()) send(item); else setStatus('Still no connection. It will be sent when you are back online.'); }, 'btn-primary btn-sm'),
                button('Remove', () => {
                    if (hasDraft(item) && !confirm('Remove this card and the notes kept for it?')) return;
                    removeItem(item.id);
                    render();
                }),
            );

            row.append(label, actions);
            list.append(row);
        });
    };

    /* ---- Visit notes ---- */

    const openDraft = (item, kind) => {
        if (item.drafts?.[kind] && !confirm('Notes are already kept for this card. Replace them?')) return;

        draftTarget = item;
        document.getElementById('draft-time').textContent = clock(item);
        draftPanel.hidden = false;

        document.querySelectorAll('[data-draft-kind]').forEach((element) => {
            element.hidden = element.dataset.draftKind !== kind;
        });

        const formElement = document.querySelector(`[data-draft-kind="${kind}"]`);

        formElement.reset();
        formElement.querySelectorAll('[data-default-today]').forEach((field) => { field.value = todayLocal(); });
        formElement.querySelectorAll('[data-max-today]').forEach((field) => { field.max = todayLocal(); });
        formElement.querySelectorAll('[data-min-days]').forEach((field) => { field.min = todayLocal(Number(field.dataset.minDays)); });

        formElement.querySelector('input, select, textarea')?.focus();
    };

    const todayLocal = (offsetDays = 0) => {
        const date = new Date();
        date.setDate(date.getDate() + offsetDays);
        return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    };

    document.querySelectorAll('[data-draft-kind]').forEach((formElement) => {
        formElement.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!draftTarget) return;

            const values = nest([...new FormData(formElement)].filter(([, value]) => value !== ''));

            if (!setDraft(draftTarget.id, formElement.dataset.draftKind, values)) {
                setStatus('The notes could not be kept on this device.');
                return;
            }

            draftPanel.hidden = true;
            draftTarget = null;
            setStatus('Notes kept. They will be sent with the card.');
            render();
            scheduleRetry();
        });
    });

    document.getElementById('draft-close')?.addEventListener('click', () => { draftPanel.hidden = true; draftTarget = null; });

    /* ---- Sending when the connection returns ---- */

    const scheduleRetry = () => {
        clearTimeout(retryTimer);
        if (items().length) retryTimer = setTimeout(trySend, 8000);
    };

    const trySend = async () => {
        const waiting = items();
        render();
        if (!waiting.length) return;

        // One card is sent on its own. With several, the worker chooses which to send first.
        if (waiting.length === 1 && !draftTarget && await reachable()) {
            await send(waiting[0]);
        } else {
            scheduleRetry();
        }
    };

    /* ---- Camera ---- */

    const start = async () => {
        if (running) return;

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                async (decoded) => {
                    await scanner.stop();
                    running = false;
                    startButton.disabled = false;
                    setStatus('Card read.');

                    if (await reachable()) {
                        input.value = decoded;
                        setStatus('Opening the passport.');
                        const token = await freshToken();
                        const field = form.querySelector('input[name="_token"]');
                        if (token && field) field.value = token;
                        if (token !== null) form.submit();
                        return;
                    }

                    if (!userId || !addItem(decoded, Number(userId))) {
                        setStatus('There is no connection and the card could not be kept. Scan it again when you are back online.');
                        return;
                    }

                    setStatus('No connection. The card was read and will open when you are back online.');
                    render();
                    scheduleRetry();
                    start();
                },
                () => {},
            );
            running = true;
            startButton.disabled = true;
            setStatus('Hold the card in front of the camera.');
        } catch {
            setStatus('The camera could not be started. Allow camera access and try again.');
        }
    };

    window.addEventListener('online', trySend);
    startButton?.addEventListener('click', start);

    keepOffline();
    render();

    if (items().length) trySend();
    start();
});
