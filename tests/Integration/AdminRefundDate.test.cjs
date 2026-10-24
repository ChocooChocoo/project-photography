const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

// Booking dates reach the page as UTC timestamps. Nov 28 in Manila is
// 2026-11-27T16:00:00Z, which the queue showed as Nov 27.
test('refund queue shows the event date in Manila time', () => {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/booking-refunds.blade.php'), 'utf8');
    const start = source.indexOf('function eventDate(value)');
    const end = source.indexOf('function paymentStatusBadge', start);
    const context = {};
    vm.runInNewContext(source.slice(start, end) + '\nthis.eventDate = eventDate;', context);

    assert.equal(context.eventDate('2026-11-27T16:00:00.000000Z'), 'Nov 28, 2026');
    assert.equal(context.eventDate('2026-11-28'), 'Nov 28, 2026');
    assert.equal(context.eventDate(null), 'N/A');
});
