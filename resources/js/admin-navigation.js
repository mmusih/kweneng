// Screen ancestry is separate from browser history, which includes filters and
// POST redirects. Server-rendered destinations remain direct-entry fallbacks.
const stateKey = 'kwenengScreen';
const limit = 40;

export function internalUrl(value, base) {
    try {
        const url = new URL(value, base);
        return url.origin === new URL(base).origin && /^https?:$/.test(url.protocol)
            ? url.href : null;
    } catch { return null; }
}

export function cleanScreen(screen, base) {
    const url = screen && internalUrl(screen.url, base);
    if (!url) return null;
    return {
        url, label: String(screen.label || 'previous page').slice(0, 100),
        x: Math.max(0, Number(screen.x) || 0), y: Math.max(0, Number(screen.y) || 0),
    };
}

export function arrive(previous, current, referrer) {
    if (!previous || !referrer) return { ...current, trail: [] };
    const source = cleanScreen(previous, current.url);
    const ref = internalUrl(referrer, current.url);
    if (!source || !ref || source.url.split('#')[0] !== ref.split('#')[0]) {
        return { ...current, trail: [] };
    }
    const trail = (Array.isArray(previous.trail) ? previous.trail : [])
        .map(screen => cleanScreen(screen, current.url)).filter(Boolean).slice(-limit);
    const ancestor = trail.findLastIndex(screen => new URL(screen.url).pathname === new URL(current.url).pathname);
    if (ancestor !== -1) return { ...current, trail: trail.slice(0, ancestor), restore: trail[ancestor] };
    // Filters, pagination, tabs and refreshes are still the same screen.
    if (new URL(source.url).pathname === new URL(current.url).pathname) {
        return { ...current, trail };
    }
    return { ...current, trail: [...trail, source].slice(-limit) };
}

export function isBackLink(link) {
    return link.hasAttribute('data-admin-back') || link.hasAttribute('data-screen-back')
        || /^(?:[←‹«]\s*)?Back(?:\s|$)/iu.test(link.textContent.trim());
}

export function installAdminNavigation(win = window, doc = document) {
    const scope = doc.body?.dataset.screenNavigation;
    if (!scope) return;
    const storageKey = `kweneng-navigation:${scope}`;
    let storage;
    try {
        storage = win.sessionStorage;
        storage.setItem(`${storageKey}:probe`, '1');
        storage.removeItem(`${storageKey}:probe`);
    } catch { return; }
    const read = () => {
        try { return JSON.parse(storage.getItem(storageKey)); } catch { return null; }
    };
    const label = () => (doc.querySelector('[data-screen-label]')?.dataset.screenLabel
        || doc.querySelector('header h1, header h2, main h1, main h2, h1')?.textContent
        || doc.title || 'previous page').trim().replace(/\s+/g, ' ').slice(0, 100);
    const current = () => ({ url: win.location.href, label: label(), x: win.scrollX, y: win.scrollY });
    const saved = win.history.state?.[stateKey];
    let screen = saved?.scope === scope && saved.url === win.location.href
        ? { ...current(), trail: (saved.trail || []).map(item => cleanScreen(item, win.location.href)).filter(Boolean) }
        : arrive(read(), current(), doc.referrer);
    const remember = () => {
        screen = { ...screen, ...current() };
        try {
            storage.setItem(storageKey, JSON.stringify(screen));
            win.history.replaceState({ ...win.history.state, [stateKey]: { ...screen, scope } }, '', win.location.href);
        } catch { /* Storage restrictions must never block normal navigation. */ }
    };
    const updateBackLinks = () => {
        const parent = screen.trail.at(-1);
        if (!parent) return;
        for (const link of doc.querySelectorAll('a[href]')) {
            if (!isBackLink(link) || !internalUrl(link.href, win.location.href)) continue;
            link.href = parent.url;
            link.textContent = `← Back to ${parent.label}`;
            link.setAttribute('aria-label', `Back to ${parent.label}`);
            link.setAttribute('data-screen-back', '');
        }
        doc.querySelector('[data-origin-back-only]')?.removeAttribute('hidden');
    };
    updateBackLinks();
    remember();
    // Record query/hash changes and scroll at departure without intercepting
    // links or changing native browser Back/Forward behavior.
    doc.addEventListener('click', remember, true);
    doc.addEventListener('auxclick', remember, true);
    doc.addEventListener('submit', remember, true);
    win.addEventListener('pagehide', remember);
    win.addEventListener('pageshow', () => { remember(); updateBackLinks(); });
    if (screen.restore) {
        const { x, y } = screen.restore;
        delete screen.restore;
        win.requestAnimationFrame(() => win.scrollTo(x, y));
    }
}
