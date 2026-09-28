const { test } = require('node:test');
const assert = require('node:assert/strict');
const { webcrypto } = require('node:crypto');
global.crypto = webcrypto;

async function setup() {
    const { timetablePreparation } = await import('../../resources/js/timetable-preparation.js');
    return timetablePreparation({ version: 0, classes: [{ id: 1, name: '2A', groups: [] }, { id: 2, name: '2B', groups: [] }], rooms: [], units: [], assignments: [
        { key: '1:1:1', class_id: 1, subject_id: 1, teacher_id: 1, subject: 'Chemistry', teacher: 'Dube' },
        { key: '1:2:2', class_id: 1, subject_id: 2, teacher_id: 2, subject: 'Biology', teacher: 'Tau' },
        { key: '2:1:1', class_id: 2, subject_id: 1, teacher_id: 1, subject: 'Chemistry', teacher: 'Dube' },
    ] }, '/save');
}

test('class selection initializes once; mixed pattern creates two doubles and a single', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    state.patterns['1:1:1'] = { singles: 1, doubles: 2 }; state.applyPattern('1:1:1');
    const before = state.units.map(u => u.key); state.chooseClass();
    assert.deepEqual(state.units.map(u => u.key), before);
    assert.deepEqual(state.occurrences('1:1:1').map(u => u.span), [1, 2, 2]);
});

test('whole assignment links matching mixed occurrences and undo restores their arrangement', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    for (const key of ['1:1:1', '1:2:2']) { state.patterns[key] = { singles: 1, doubles: 2 }; state.applyPattern(key); }
    state.select('assignment', '1:1:1'); state.addSplit();
    state.select('assignment', '1:2:2'); state.addSplit(state.splitRows()[0].key);
    assert.equal(state.splitRows().length, 3);
    assert.ok(state.splitRows().every(r => r.units.length === 2 && r.units[0].span === r.units[1].span));
    state.undo(); assert.ok(state.splitRows().every(r => r.units.length === 1));
});

test('only a selected double joins another class and remains in its split', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    state.patterns['1:1:1'] = { singles: 1, doubles: 2 }; state.applyPattern('1:1:1');
    const double = state.occurrences('1:1:1').find(u => u.span === 2);
    double.split_key = state.id(); const split = double.split_key;
    state.select('unit', double.key); state.openJoint(); state.joint.target = '2:1:1'; state.join();
    assert.equal(double.sources.length, 2); assert.equal(double.split_key, split);
    assert.equal(state.occurrences('2:1:1').length, 1);
    assert.equal(state.occurrences('1:1:1').length, 3);
    state.unjoin(double); assert.equal(double.sources.length, 1);
    assert.equal(state.occurrences('2:1:1').length, 1);
});

test('mismatched whole patterns do not silently discard or partly link occurrences', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    state.patterns['1:2:2'] = { singles: 0, doubles: 1 }; state.applyPattern('1:2:2');
    state.select('assignment', '1:1:1'); state.addSplit();
    state.select('assignment', '1:2:2'); state.addSplit(state.splitRows()[0].key);
    assert.match(state.error, /Patterns differ/);
    assert.equal(state.occurrences('1:2:2')[0].split_key, null);
});

test('reducing a linked or placed pattern preserves occurrences', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    const unit = state.occurrences('1:1:1')[0]; unit.placed = true;
    state.patterns['1:1:1'] = { singles: 0, doubles: 0 }; state.applyPattern('1:1:1');
    assert.equal(state.occurrences('1:1:1').length, 1); assert.ok(state.error);
});

test('bulk pattern changes undo together and a blocked reduction leaves every assignment intact', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    state.patterns['1:1:1'] = { singles: 1, doubles: 2 }; state.applyToClass('1:1:1');
    assert.equal(state.units.length, 6);
    state.undo(); assert.equal(state.units.length, 2);
    state.occurrences('1:2:2')[0].placed = true;
    state.patterns['1:1:1'] = { singles: 0, doubles: 0 }; state.applyToClass('1:1:1');
    assert.equal(state.units.length, 2); assert.ok(state.error);
});

test('dragging an occurrence back clears its split without breaking its joint attendance', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    const [a, b] = state.units, split = state.id(); a.split_key = split; b.split_key = split;
    a.sources.push({key: '2:1:1', group_id: null});
    state.dragging = { kind: 'unit', key: a.key }; state.returnToLessons({preventDefault() {}});
    assert.equal(a.split_key, null); assert.equal(b.split_key, null); assert.equal(a.sources.length, 2);
});

test('increasing counts preserves placed cards and reductions remove free cards first', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    const placed = state.occurrences('1:1:1')[0]; placed.placed = true;
    state.patterns['1:1:1'] = { singles: 3, doubles: 1 };
    assert.equal(state.applyPattern('1:1:1'), true);
    assert.equal(state.occurrences('1:1:1').length, 4);
    assert.equal(state.units.find(u => u.key === placed.key), placed);
    state.units.reverse();
    state.patterns['1:1:1'] = { singles: 1, doubles: 1 };
    assert.equal(state.applyPattern('1:1:1'), true);
    assert.ok(state.units.includes(placed));
});

test('existing individual cards count toward targets and only the increase is created', async () => {
    const state = await setup(); state.classId = 1;
    state.assignments[0].existing = true;
    state.assignments[0].manual = { singles: 2, doubles: 1, cards: 3, periods: 4, placed: 2 };
    state.chooseClass();
    assert.deepEqual(state.pattern('1:1:1'), { singles: 2, doubles: 1 });
    state.patterns['1:1:1'] = { singles: 3, doubles: 2 };
    state.applyPattern('1:1:1');
    assert.deepEqual(state.occurrences('1:1:1').map(u => u.span), [1, 2]);
    state.applyPattern('1:1:1');
    assert.equal(state.occurrences('1:1:1').length, 2);
    assert.match(state.summary('1:1:1'), /5 cards · 2 placed · 1 in tray · \+2 on save/);
});

test('duplicate assignment rows are shown once and saved zero counts are not recreated', async () => {
    const state = await setup(); state.classId = 1; state.version = 1;
    state.assignments.push({ ...state.assignments[0] });
    assert.equal(state.visibleAssignments().length, 2);
    state.chooseClass();
    assert.equal(state.units.length, 0);
});

test('save applies all pending counts without an Apply click and refreshes the embedded grid', async () => {
    const state = await setup(); state.classId = 1; state.chooseClass();
    state.patterns['1:1:1'] = { singles: 2, doubles: 1 };
    state.patterns['1:2:2'] = { singles: 3, doubles: 0 };
    state.embedded = true;
    const events = []; state.$dispatch = (...args) => events.push(args);
    const oldFetch = global.fetch, oldDocument = global.document;
    global.document = { querySelector: () => ({ content: 'csrf' }) };
    let submitted;
    global.fetch = async (_, options) => {
        submitted = JSON.parse(options.body);
        return { ok: true, json: async () => ({ ...state, units: submitted.units, version: 1, grid: { cards: [] }, message: 'Saved' }) };
    };
    try {
        await state.save(true);
        assert.equal(submitted.units.length, 6);
        assert.equal(state.dirty, false);
        assert.deepEqual(events.map(e => e[0]), ['timetable-prepared', 'preparation-close']);
    } finally { global.fetch = oldFetch; global.document = oldDocument; }
});
