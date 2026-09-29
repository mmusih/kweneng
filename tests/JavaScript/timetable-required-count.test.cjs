const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/timetable/grid.blade.php'), 'utf8');
const AsyncFunction = Object.getPrototypeOf(async function() {}).constructor;
const body = source.match(/async saveRequiredCount\(undo = false\) \{([\s\S]*?)\n                    \},/)[1];
function state() {
    return {
        busy: false, grid: { setting: { id: 1 } }, endpoints: { requiredCount: '/count' },
        countEditor: { lesson_id: 2, required: 4, placed: 2, value: 3, version: 1 },
        requests: [], say() {}, clearSelection() {}, applyGrid(grid) { this.grid = grid; },
        async post(url, data, method) {
            this.requests.push({ data, method });
            return { grid: { setting: { id: 1, preparation_version: data.version + 1 } }, message: 'Saved' };
        },
        saveRequiredCount: new AsyncFunction('undo = false', body),
    };
}
test('count confirmation and undo carry the expected counts and current version', async () => {
    const s = state();
    await s.saveRequiredCount();
    assert.equal(s.requests[0].method, 'PUT');
    assert.equal(s.requests[0].data.required, 3);
    assert.equal(s.countEditor, null);
    await s.saveRequiredCount(true);
    assert.deepEqual(s.requests[1].data, { setting_id: 1, lesson_id: 2, required: 4, expected_required: 3, expected_placed: 2, version: 2 });
    assert.equal(s.countUndo, null);
});
test('invalid counts and failed saves preserve the editor and do not create undo', async () => {
    for (const value of [1, 2.5, 41, NaN]) {
        const s = state(); s.countEditor.value = value;
        await s.saveRequiredCount(); assert.equal(s.requests.length, 0);
    }
    const s = state(); s.post = async () => null;
    await s.saveRequiredCount();
    assert.ok(s.countEditor); assert.equal(s.countUndo, undefined);
});
