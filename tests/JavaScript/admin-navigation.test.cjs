const { test } = require('node:test');
const assert = require('node:assert/strict');
test('Back links retain their explicit destinations without a history interceptor', async () => {
    const { installAdminNavigation } = await import('../../resources/js/admin-navigation.js');
    global.document = { addEventListener() { assert.fail('Back links must not be intercepted'); } };
    try { installAdminNavigation(); } finally { delete global.document; }
});
