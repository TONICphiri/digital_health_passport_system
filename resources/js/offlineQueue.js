/**
 * Cards scanned without a connection, and the visit notes typed for them.
 * Everything stays in this browser, belongs to the signed in health worker,
 * and is removed when it expires or once the server has taken it over.
 */
const KEY = 'dhp.offline-queue';
const MAX_ITEMS = 10;

/** A card alone is dropped after a few minutes. Notes are kept for hours. */
const BARE_CARD_MS = 5 * 60 * 1000;

/** Matches the scan page defaults, used when no page values are at hand. */
const DEFAULT_LIMITS = { openMinutes: 30, draftHours: 24 };

const read = (storage) => {
    try {
        const items = JSON.parse(storage.getItem(KEY) ?? '[]');
        return Array.isArray(items) ? items.filter((item) => item && typeof item.code === 'string' && item.id) : [];
    } catch {
        return [];
    }
};

const write = (items, storage) => {
    try {
        if (items.length) storage.setItem(KEY, JSON.stringify(items));
        else storage.removeItem(KEY);
        return true;
    } catch {
        return false;
    }
};

export const hasDraft = (item) => Boolean(item.drafts && Object.keys(item.drafts).length);

/** A card alone is kept only a few minutes. Notes are kept for hours. */
export const lifetime = (item, limits) => (hasDraft(item) ? (limits ?? DEFAULT_LIMITS).draftHours * 3600000 : BARE_CARD_MS);

export const newId = () => (globalThis.crypto?.randomUUID?.() ?? 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = Math.floor(Math.random() * 16);
    return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
}));

/** Removes expired items for everyone and returns the ones that remain. */
export function purgeExpired(now = Date.now(), limits = DEFAULT_LIMITS, storage = globalThis.localStorage) {
    const all = read(storage);
    const alive = all.filter((item) => now - item.savedAt <= lifetime(item, limits));

    if (alive.length !== all.length) write(alive, storage);

    return alive;
}

/** Removes everything a worker left in this queue, for example when signing out. */
export function clearAll(storage = globalThis.localStorage) {
    try {
        storage.removeItem(KEY);

        return true;
    } catch {
        return false;
    }
}

/** Removes expired items for everyone, then returns the items of this worker. */
export function listFor(userId, limits, now = Date.now(), storage = globalThis.localStorage) {
    return purgeExpired(now, limits, storage).filter((item) => item.userId === userId);
}

export function addItem(code, userId, now = Date.now(), storage = globalThis.localStorage) {
    const items = read(storage);

    // The same card scanned twice is one visit.
    const existing = items.find((item) => item.userId === userId && item.code === code);
    if (existing) return existing;

    if (items.filter((item) => item.userId === userId).length >= MAX_ITEMS) return null;

    const item = { id: newId(), code, userId, savedAt: now, drafts: {} };

    return write([...items, item], storage) ? item : null;
}

export function setDraft(id, kind, values, now = Date.now(), storage = globalThis.localStorage) {
    const items = read(storage);
    const item = items.find((entry) => entry.id === id);
    if (!item) return false;

    item.drafts = { ...item.drafts, [kind]: values };
    // Notes outlive the card alone, so the clock starts again when they are saved.
    item.savedAt = now;

    return write(items, storage);
}

export function removeItem(id, storage = globalThis.localStorage) {
    return write(read(storage).filter((item) => item.id !== id), storage);
}

export function removeDraft(id, kind, storage = globalThis.localStorage) {
    const items = read(storage);
    const item = items.find((entry) => entry.id === id);
    if (!item) return false;

    delete item.drafts[kind];

    return write(items, storage);
}

/** Turns nested values into the field names a form post uses, for example draft[encounter][reason]. */
export function flatten(value, prefix) {
    if (value === null || value === undefined) return [];

    if (typeof value === 'object') {
        return Object.entries(value).flatMap(([key, inner]) => flatten(inner, `${prefix}[${key}]`));
    }

    return [[prefix, String(value)]];
}

/** Builds nested values from form fields named like medications[0][name]. */
export function nest(pairs) {
    const result = {};

    for (const [name, raw] of pairs) {
        const path = name.replace(/\]/g, '').split('[');
        let node = result;

        path.forEach((part, index) => {
            if (index === path.length - 1) node[part] = raw;
            else node = node[part] ??= {};
        });
    }

    return result;
}
