const assert = require('node:assert/strict');
const fs = require('node:fs');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/owner/view-bookings.blade.php', 'utf8');
const template = source.match(/<label class="text-muted small mb-1">Event Location<\/label>\s*<p class="mb-0 fw-medium">([^\n]+)<\/p>/)[1];
const escapeStart = source.indexOf('function escapeAssignmentText(');
const escapeEnd = source.indexOf('function renderAvailablePhotographers(', escapeStart);
const render = booking => vm.runInNewContext(source.slice(escapeStart, escapeEnd) + '\n`' + template + '`', {booking});

test('in-studio location does not render empty venue placeholders', () => {
    assert.equal(render({location_type: 'in-studio', province: 'Cavite'}), 'In-Studio');
});

test('on-location booking keeps supplied venue and address', () => {
    assert.equal(render({location_type: 'on-location', venue_name: 'Garden', city: 'Imus', province: 'Cavite'}), 'Garden, Imus, Cavite');
});

test('venue text cannot inject markup', () => {
    assert.equal(render({location_type: 'on-location', venue_name: '<img src=x>'}), '&lt;img src=x&gt;');
});
