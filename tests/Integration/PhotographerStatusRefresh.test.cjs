const assert = require('node:assert/strict');
const fs = require('node:fs');
const test = require('node:test');
const vm = require('node:vm');

test('successful assignment updates refresh the visible table badge immediately', () => {
    const source = fs.readFileSync('resources/views/studio-photographer/view-assigned-booking.blade.php', 'utf8');
    const start = source.indexOf('function refreshAssignmentRow(');
    assert.notEqual(start, -1, 'Status update needs a row-refresh function');
    const end = source.indexOf('// Helper functions', start);
    const badge = {className: '', textContent: ''};
    let deadlineRemoved = false;
    const context = {
        document: {querySelector: selector => {
            if (selector === 'tr[data-assignment-id="42"] .response-deadline') {
                return {remove: () => { deadlineRemoved = true; }};
            }
            assert.equal(selector, 'tr[data-assignment-id="42"] td:nth-child(8) .badge');
            return badge;
        }},
        window: {},
    };
    vm.runInNewContext(source.slice(start, end) + '\nrefreshAssignmentRow(42, "cancelled");', context);
    assert.equal(badge.textContent, 'cancelled');
    assert.match(badge.className, /badge-soft-danger/);
    assert.equal(deadlineRemoved, true, 'Cancelled assignments must not retain a response deadline');
    assert.match(source, /refreshAssignmentRow\(currentAssignmentId, status\)/);
});
