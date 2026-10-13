const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

test('fallback names and conflict text are escaped before HTML rendering', () => {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/owner/view-bookings.blade.php'), 'utf8');
    const start = source.indexOf('function escapeAssignmentText(');
    assert.notEqual(start, -1, 'Assignment renderer must escape server-supplied text');
    const end = source.indexOf('function renderAvailablePhotographers(', start);
    const scope = {};
    vm.runInNewContext(source.slice(start, end), scope);
    assert.equal(scope.escapeAssignmentText('<img src=x onerror="alert(1)">&\''), '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;&amp;&#39;');
    assert.equal(scope.escapeAssignmentText(null), '');
    const renderer = source.slice(end, source.indexOf('function updateConfirmButtonState(', end));
    assert.match(renderer, /escapeAssignmentText\(photographer\.name\)/);
    assert.match(renderer, /escapeAssignmentText\(conflict\.message/);
});
