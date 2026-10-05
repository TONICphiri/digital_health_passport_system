import Alpine from 'alpinejs';
import { removeItem } from './offlineQueue';

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
        setInterval(() => this.refresh(), intervalSeconds * 1000);
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

/**
 * The page kept for offline use is removed when someone signs out, so the next
 * person never sees what the previous one left on a shared device.
 */
document.addEventListener('submit', (event) => {
    if (event.target instanceof HTMLFormElement && event.target.action.endsWith('/logout') && 'caches' in window) {
        caches.delete('dhp-shell');
    }
});

window.Alpine = Alpine;
Alpine.start();
