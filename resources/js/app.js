import Alpine from 'alpinejs';
import { clearAll, purgeExpired, removeItem } from './offlineQueue';

/**
 * Notification bell. Asks the server for unread notifications at a fixed
 * interval and shows a desktop notification when a new one arrives, if the
 * user has allowed notifications in the browser.
 */
Alpine.data('notificationBell', (endpoint, intervalSeconds) => ({
    count: 0,
    items: [],
    open: false,
    seen: new Set(),
    firstLoad: true,

    init() {
        this.refresh();
        setInterval(() => {
            if (!document.hidden) this.refresh();
        }, intervalSeconds * 1000);

        // Refresh immediately when the user comes back to the tab instead of
        // waiting for the next tick, so hidden tabs cost the server nothing.
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) this.refresh();
        });
    },

    async refresh() {
        try {
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;

            const data = await response.json();
            this.count = data.count;
            this.items = data.latest;

            data.latest.forEach((item) => {
                if (!this.firstLoad && !this.seen.has(item.id)) this.showDesktopAlert(item);
                this.seen.add(item.id);
            });
            this.firstLoad = false;
        } catch {
            // A failed check is ignored. The next interval tries again.
        }
    },

    showDesktopAlert(item) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        const alert = new Notification(item.title, { body: item.message, icon: '/images/logo.png' });
        alert.onclick = () => { window.location.href = item.url; };
    },

    get canAskPermission() {
        return 'Notification' in window && Notification.permission === 'default';
    },

    askPermission() {
        Notification.requestPermission();
    },
}));

/**
 * Repeating rows, used for emergency contacts and medicines.
 */
Alpine.data('repeater', (initialRows, blankRow, maxRows = 15) => ({
    rows: initialRows.length ? initialRows : [{ ...blankRow }],
    add() {
        if (this.rows.length < maxRows) this.rows.push({ ...blankRow });
    },
    remove(index) {
        if (this.rows.length > 1) this.rows.splice(index, 1);
    },
}));

/**
 * Visit notes typed offline are kept on the device until the server confirms
 * it has them. The server adds the visit's id to the address it redirects to.
 */
const address = new URL(window.location.href);
const synced = address.searchParams.get('synced');
if (synced) {
    removeItem(synced);
    address.searchParams.delete('synced');
    window.history.replaceState(null, '', address.pathname + address.search + address.hash);
}

// Old scans are dropped when any page loads, so a shared device does not keep them forever.
purgeExpired();

/**
 * The queue, the offline shell, and cached patient pages are removed when
 * someone signs out, so the next person never sees what the previous one left behind.
 */
document.addEventListener('submit', (event) => {
    if (!(event.target instanceof HTMLFormElement) || !event.target.action.endsWith('/logout')) return;

    clearAll();
    if ('caches' in window) {
        caches.delete('dhp-shell-v2');
        caches.delete('dhp-shell-v3');
        caches.delete('dhp-patient-v1');
        caches.delete('dhp-patient-v2');
    }
});

window.Alpine = Alpine;
Alpine.start();

/**
 * PWA install prompt. When the browser offers to install the app, store the
 * deferred event and expose it through a global so a UI button can trigger it.
 */
let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('dhp:pwa-installable', { detail: { available: true } }));
});

window.DhpPwa = {
    isInstallable() {
        return deferredInstallPrompt !== null;
    },
    async install() {
        if (!deferredInstallPrompt) return false;
        deferredInstallPrompt.prompt();
        const { outcome } = await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        return outcome === 'accepted';
    },
    isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches;
    },
};

