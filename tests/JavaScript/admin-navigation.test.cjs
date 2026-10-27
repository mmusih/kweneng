const { test } = require('node:test');
const assert = require('node:assert/strict');
const modulePromise = import('../../resources/js/admin-navigation.js');
const base = 'https://school.test';
const view = (path, label = path) => ({ url: base + path, label, x: 0, y: 240 });

test('timetable → clubs → members returns one level at a time with original filters and scroll', async () => {
    const { arrive } = await modulePromise;
    const timetable = { ...view('/admin/timetable/7/grid?term=3&tab=afternoon#classes', 'afternoon timetable'), trail: [] };
    const clubs = arrive(timetable, view('/clubs?academic_year_id=2', 'Clubs'), timetable.url);
    const members = arrive(clubs, view('/clubs/5', 'Members'), clubs.url);
    assert.equal(members.trail.at(-1).url, clubs.url);
    const backClubs = arrive(members, view(clubs.url.slice(base.length)), members.url);
    assert.deepEqual(backClubs.trail, [view(timetable.url.slice(base.length), 'afternoon timetable')]);
    const backTimetable = arrive(backClubs, view(timetable.url.slice(base.length)), backClubs.url);
    assert.deepEqual(backTimetable.trail, []);
    assert.equal(backTimetable.restore.y, 240);
});

test('filters, pagination and save redirects do not add return steps', async () => {
    const { arrive } = await modulePromise;
    const origin = { ...view('/admin/timetable/7/grid?setting=7'), trail: [] };
    let clubs = arrive(origin, view('/clubs?academic_year_id=2'), origin.url);
    for (const destination of ['/clubs?academic_year_id=3', '/clubs?page=2', '/clubs']) {
        clubs = arrive(clubs, view(destination), clubs.url);
        assert.deepEqual(clubs.trail, [view('/admin/timetable/7/grid?setting=7')]);
    }
    const members = arrive(clubs, view('/clubs/5?tab=members'), clubs.url);
    const edit = arrive(members, view('/clubs/5/edit'), members.url);
    const saved = arrive(edit, view('/clubs/5'), edit.url);
    assert.deepEqual(saved.trail, members.trail);
});

test('direct entry, external entry and untrusted ancestry use safe fallbacks', async () => {
    const { arrive, internalUrl } = await modulePromise;
    const previous = { ...view('/clubs'), trail: [{ url: 'https://evil.test' }, { url: 'javascript:alert(1)' }] };
    assert.deepEqual(arrive(previous, view('/clubs/1'), '').trail, []);
    assert.deepEqual(arrive(previous, view('/clubs/1'), 'https://evil.test').trail, []);
    assert.equal(arrive(previous, view('/clubs/1'), previous.url).trail.length, 1);
    assert.equal(internalUrl('javascript:alert(1)', base), null);
    assert.equal(internalUrl('//evil.test', base), null);
});

function browser(path, { previous = null, referrer = '', state = null, scope = '12:admin', blocked = false } = {}) {
    const listeners = {};
    const store = new Map(previous ? [[`kweneng-navigation:${scope}`, JSON.stringify(previous)]] : []);
    const anchor = (text, href, attrs = {}) => ({
        textContent: text, href: base + href, attrs,
        hasAttribute(name) { return name in this.attrs; },
        setAttribute(name, value) { this.attrs[name] = value; },
    });
    const back = anchor('← Back to Dashboard', '/admin/dashboard');
    const dashboard = anchor('Dashboard', '/admin/dashboard');
    const doc = {
        body: { dataset: { screenNavigation: scope } }, referrer, title: 'Clubs',
        querySelector() { return null; }, querySelectorAll() { return [back, dashboard]; },
        addEventListener(type, handler) { listeners[type] = handler; },
    };
    const win = {
        location: { href: base + path }, scrollX: 0, scrollY: 0,
        history: { state, replaceState(value) { this.state = value; } },
        get sessionStorage() {
            if (blocked) throw new Error('Storage blocked');
            return { getItem: key => store.get(key), setItem: (key, value) => store.set(key, value), removeItem: key => store.delete(key) };
        },
        addEventListener(type, handler) { listeners[type] = handler; },
        requestAnimationFrame(fn) { fn(); }, scrollTo(x, y) { this.scrollX = x; this.scrollY = y; },
    };
    return { win, doc, back, dashboard, listeners, store };
}

test('shared controller updates Back and its accessible label, leaving Dashboard links alone', async () => {
    const { installAdminNavigation } = await modulePromise;
    const previous = { ...view('/admin/timetable/7/grid?term=3', 'afternoon timetable'), trail: [] };
    const page = browser('/clubs', { previous, referrer: previous.url });
    installAdminNavigation(page.win, page.doc);
    assert.equal(page.back.href, previous.url);
    assert.equal(page.back.textContent, '← Back to afternoon timetable');
    assert.equal(page.back.attrs['aria-label'], 'Back to afternoon timetable');
    assert.equal(page.dashboard.href, base + '/admin/dashboard');
    page.win.scrollY = 560;
    page.listeners.pagehide();
    assert.equal(JSON.parse(page.store.get('kweneng-navigation:12:admin')).y, 560);
});

test('reload and browser history retain ancestry; another account cannot inherit it', async () => {
    const { installAdminNavigation } = await modulePromise;
    const previous = { ...view('/admin/timetable/7/grid', 'afternoon timetable'), trail: [] };
    const page = browser('/clubs', { previous, referrer: previous.url });
    installAdminNavigation(page.win, page.doc);
    const reload = browser('/clubs', { state: page.win.history.state });
    installAdminNavigation(reload.win, reload.doc);
    assert.equal(reload.back.href, previous.url);
    reload.listeners.pageshow();
    assert.equal(reload.back.href, previous.url);
    const other = browser('/clubs', { scope: '13:teacher', state: page.win.history.state });
    installAdminNavigation(other.win, other.doc);
    assert.equal(other.back.href, base + '/admin/dashboard');
});

test('direct links and blocked storage retain explicit server destinations', async () => {
    const { installAdminNavigation } = await modulePromise;
    for (const blocked of [true, false]) {
        const page = browser('/clubs', { blocked });
        installAdminNavigation(page.win, page.doc);
        assert.equal(page.back.href, base + '/admin/dashboard');
    }
});
