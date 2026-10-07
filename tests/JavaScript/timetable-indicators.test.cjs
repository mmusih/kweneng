const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/timetable/grid.blade.php'), 'utf8');
function context(span = 2) {
    const state = { selected: { span }, drag: null, candidatesState: 'ready', verdicts: {} };
    for (const name of ['validStartsFor', 'clashesAt', 'indicatorState']) {
        const body = source.match(new RegExp(`${name}\\(slot\\) \\{([\\s\\S]*?)\\n                    \\},`))[1];
        state[name] = new Function('slot', body);
    }
    return state;
}
const slot = (period, day = 1) => ({ key: `${day}:${period}`, day, period });

test('hover inspection leaves the selected placement and candidates unchanged', () => {
    const body = source.match(/inspectCard\(subject\) \{([\s\S]*?)\n                    \},/)[1];
    const inspect = new Function('subject', body);
    const selected = { card_id: 1 };
    const state = { selected, inspected: null, dragging: false, verdicts: { ready: true } };
    const hovered = { card_id: 2 };
    inspect.call(state, hovered);
    assert.equal(state.inspected, hovered);
    assert.equal(state.selected, selected);
    assert.deepEqual(state.verdicts, { ready: true });
    state.dragging = true;
    inspect.call(state, selected);
    assert.equal(state.inspected, hovered);
});

test('room menu marks current and occupied rooms across the full double', () => {
    const body = source.match(/roomMenuStatus\(room\) \{([\s\S]*?)\n                    \},/)[1];
    const status = new Function('room', body);
    const state = {
        context: { subject: { card_id: 1, room_id: 10, day: 1, period: 2, span: 2 } },
        grid: { cards: [
            { card_ids: [1, 2], room_id: 10, day: 1, period: 2, span: 2 },
            { card_ids: [3], room_id: 20, day: 1, period: 3, span: 1, class_names: 'Form 5B' },
            { card_ids: [4], room_id: 30, day: 2, period: 2, span: 1 },
            { card_ids: [5], room_id: 40, day: 1, period: 4, span: 1 },
        ] },
    };
    assert.equal(status.call(state, { id: 10 }).symbol, '✓');
    assert.equal(status.call(state, { id: 20 }).symbol, '×');
    assert.match(status.call(state, { id: 20 }).label, /Form 5B/);
    assert.equal(status.call(state, { id: 30 }).occupied, false);
    assert.equal(status.call(state, { id: 40 }).occupied, false);
});

test('cancelled or superseded drag startup cannot leave drop targets covering cards', () => {
    const pending = [];
    const startBody = source.match(/startDrag\(event, subject\) \{([\s\S]*?)\n                    \},/)[1];
    const endBody = source.match(/endDrag\(\) \{([\s\S]*?)\n                    \},/)[1];
    const start = new Function('event', 'subject', 'setTimeout', startBody);
    const end = new Function(endBody);
    const state = { drag: null, dragging: false, $nextTick() {}, loadCandidates() {} };
    const event = { dataTransfer: { setData() {} } };
    const subject = { lesson_id: 1 };
    const defer = (callback) => pending.push(callback);
    start.call(state, event, subject, defer);
    assert.equal(state.dragging, false);
    end.call(state);
    start.call(state, event, subject, defer);
    pending.shift()();
    assert.equal(state.dragging, false);
    pending.shift()();
    assert.equal(state.dragging, true);
    end.call(state);
    assert.equal(state.dragging, false);
    assert.equal(state.drag, null);
});

test('double-card layout preserves its full span and reserves space for its outside divider', () => {
    const body = source.match(/cardStyle\(card, classId\) \{([\s\S]*?)\n                    \},/)[1];
    const style = new Function('card', 'classId', body);
    const state = {
        slotIndex: { '1:7': 6 },
        slotList: Array.from({ length: 8 }, (_, i) => ({ edgeClass: i === 7 ? 'tt-day-divider' : 'tt-period-divider' })),
        colourOf: () => '#facc15', inkOn: () => '#111827',
        grid: { classes: [] },
        optionShare: new Function('card', 'classId', source.match(/optionShare\(card, classId\) \{([\s\S]*?)\n                    \},/)[1]),
        cardStack: () => ({ index: 0, count: 1 }),
    };
    const result = style.call(state, { day: 1, period: 7, span: 2, class_ids: [1] }, 1);
    assert.equal(result.gridColumn, '7 / span 2');
    assert.equal(result.marginRight, '4px');
    assert.equal(result.backgroundColor, '#facc15');
    assert.equal(result.height, 'calc(100% * 1 / 1 - 3px)');
    state.slotList[7].edgeClass = 'tt-midday-divider';
    assert.equal(style.call(state, { day: 1, period: 7, span: 2, class_ids: [1, 2] }, 1).marginRight, '3px');
});

test('both halves of valid doubles are green, including the final period', () => {
    const state = context();
    state.verdicts = {
        '1:1': { day: 1, period: 1, ok: true },
        '1:7': { day: 1, period: 7, ok: true },
        '1:8': { day: 1, period: 8, ok: false, conflicts: [{ kind: 'structure', period: 8 }] },
    };
    for (const period of [1, 2, 7, 8]) assert.equal(state.indicatorState(slot(period)), 'available');
    assert.equal(state.indicatorState(slot(1, 2)), 'unavailable');
});

test('whole-class cards fill every scheduled cell, including joint lessons', () => {
    const body = source.match(/cardStyle\(card, classId\) \{([\s\S]*?)\n                    \},/)[1];
    const style = new Function('card', 'classId', body);
    const stackBody = source.match(/cardStack\(card, classId\) \{([\s\S]*?)\n                    \},/)[1];
    const state = {
        slotIndex: { '1:1': 0 },
        slotList: Array.from({ length: 8 }, () => ({ edgeClass: 'tt-period-divider' })),
        colourOf: () => '#facc15', inkOn: () => '#111827',
        grid: { classes: [] },
        optionShare: new Function('card', 'classId', source.match(/optionShare\(card, classId\) \{([\s\S]*?)\n                    \},/)[1]),
        cardStack: new Function('card', 'classId', stackBody),
    };
    for (const span of [1, 2, 3]) {
        for (const class_ids of [[1], [1, 2]]) {
            const card = { key: 'whole', day: 1, period: 1, span, class_ids,
                groups: class_ids.map(class_id => ({ class_id, entire_class: true })) };
            state.cardsFor = () => [card];
            for (const classId of class_ids) {
                const result = style.call(state, card, classId);
                assert.equal(result.height, 'calc(100% * 1 / 1 - 3px)');
                assert.equal(result.top, 'calc(100% * 0 / 1 + 1px)');
                assert.equal(result.gridColumn, `1 / span ${span}`);
            }
            card.groups = [];
            assert.equal(style.call(state, card, 1).height, 'calc(100% * 1 / 1 - 3px)');
        }
    }
    const mixed = { key: 'mixed', day: 1, period: 1, span: 2, class_ids: [1, 2],
        groups: [{ class_id: 1, entire_class: true }, { class_id: 2, entire_class: false }] };
    state.cardsFor = () => [mixed];
    assert.equal(style.call(state, mixed, 1).height, 'calc(100% * 1 / 1 - 3px)');
    assert.equal(style.call(state, mixed, 2).height, 'calc(100% * 1 / 2 - 3px)');
    const parallel = { ...mixed, key: 'parallel', class_ids: [2], groups: [{ class_id: 2, entire_class: false }] };
    state.cardsFor = () => [mixed, parallel];
    assert.equal(style.call(state, mixed, 2).height, 'calc(100% * 1 / 2 - 3px)');
    const second = style.call(state, parallel, 2);
    assert.equal(second.height, 'calc(100% * 1 / 2 - 3px)');
    assert.equal(second.top, 'calc(100% * 1 / 2 + 1px)');
});

test('red identifies the actual clash period, not a free first half or a structural refusal', () => {
    const state = context();
    state.verdicts = {
        '1:2': { day: 1, period: 2, ok: false, conflicts: [{ kind: 'teacher', period: 3 }] },
        '1:4': { day: 1, period: 4, ok: false, conflicts: [{ kind: 'structure', period: 4 }] },
    };
    assert.equal(state.indicatorState(slot(2)), 'unavailable');
    assert.equal(state.indicatorState(slot(3)), 'clash');
    assert.equal(state.indicatorState(slot(4)), 'unavailable');
});

test('valid coverage wins over a rejected alternative placement', () => {
    const state = context();
    state.verdicts = {
        '1:1': { day: 1, period: 1, ok: true },
        '1:2': { day: 1, period: 2, ok: false, conflicts: [{ kind: 'room', period: 2 }] },
    };
    assert.equal(state.indicatorState(slot(2)), 'available');
});

test('single cards, loading, failure and locked cards do not imply false availability', () => {
    const state = context(1);
    state.verdicts = { '1:1': { day: 1, period: 1, ok: true } };
    assert.equal(state.indicatorState(slot(1)), 'available');
    assert.equal(state.indicatorState(slot(2)), 'unavailable');
    state.candidatesState = 'loading';
    assert.equal(state.indicatorState(slot(2)), 'loading');
    state.candidatesState = 'error';
    assert.equal(state.indicatorState(slot(2)), 'unavailable');
    state.selected.locked = true;
    assert.equal(state.indicatorState(slot(1)), 'unavailable');
});

function gridMethod(name, args) {
    return new Function(...args, source.match(new RegExp(`${name}\\([^\\n]*\\) \\{([\\s\\S]*?)\\n                    \\},`))[1]);
}
test('three-way options reserve stable thirds before peers are placed, in grid and tray', () => {
    const state = {
        grid: { classes: [{ id: 1, divisions: [{ id: 10, groups: [{ id: 1 }, { id: 2 }, { id: 3 }] }] }] },
        classFilter: '', optionShare: gridMethod('optionShare', ['card', 'classId']),
        trayShare: gridMethod('trayShare', ['item']),
        cardStack: () => ({ index: 0, count: 1 }),
        slotIndex: { '1:1': 0 }, slotList: [{ edgeClass: 'tt-period-divider' }],
        colourOf: () => '#ffffff', inkOn: () => '#000000',
    };
    const card = { day: 1, period: 1, span: 1, class_ids: [1], groups: [{ id: 3, class_id: 1, division_id: 10 }] };
    assert.deepEqual(state.trayShare(card), { count: 3, index: 2, size: 1 });
    const style = gridMethod('cardStyle', ['card', 'classId']).call(state, card, 1);
    assert.equal(style.height, 'calc(100% * 1 / 3 - 3px)');
    assert.equal(style.top, 'calc(100% * 2 / 3 + 1px)');
    assert.equal(style.transform, undefined);
});
test('joining or changing class defaults to its matching split, with whole class still selectable', () => {
    const first = { class_id: 1, group_id: '11' };
    const row = { class_id: 2, group_id: '' };
    const state = { editor: { form: { attendance: [first, row] } }, groupsForClass(id) {
        return [{ id: id === 1 ? 11 : 21, shared_key: 'french', name: 'French', division: 'Languages' }];
    } };
    gridMethod('defaultAttendanceGroup', ['row']).call(state, row);
    assert.equal(row.group_id, '21');
    row.group_id = '';
    assert.equal(row.group_id, '');
    first.group_id = '';
    gridMethod('defaultAttendanceGroup', ['row']).call(state, row);
    assert.equal(row.group_id, '');
});
test('capacity preview counts each parallel group once and replaces the edited lesson', () => {
    const state = {
        grid: { setting: { capacity: 48 }, editor: { lesson_presets: [
            { lesson_id: 1, class_ids: [1], groups: [], cards_per_cycle: 40, periods_per_card: 1 },
            { lesson_id: 2, class_ids: [1], groups: [{ id: 11, class_id: 1 }], cards_per_cycle: 4, periods_per_card: 2 },
        ] } },
        editor: { mode: 'create', form: { cards_per_cycle: 8, periods_per_card: 1, attendance: [{ class_id: 1, group_id: '12' }] } },
        groupsForClass: () => [{ id: 11, division_id: 1 }, { id: 12, division_id: 1 }],
        uniqueAttendanceClassIds: () => [1],
        projectedCapacity: gridMethod('projectedCapacity', ['classId']),
        exceedsCapacity: gridMethod('exceedsCapacity', []),
    };
    assert.equal(state.projectedCapacity(1), 48);
    assert.equal(state.exceedsCapacity(), false);
    state.editor.form.cards_per_cycle = 9;
    assert.equal(state.exceedsCapacity(), true);
    state.editor.mode = 'edit'; state.editor.subject = { lesson_id: 2 };
    state.editor.form.cards_per_cycle = 7; state.editor.form.attendance[0].group_id = '11';
    assert.equal(state.projectedCapacity(1), 47);
});
