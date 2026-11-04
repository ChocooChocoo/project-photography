const assert = require('node:assert/strict');
const fs = require('node:fs');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/client/view-my-bookings.blade.php', 'utf8');
const template = source.match(/<label class="text-muted small mb-1">Duration<\/label>\s*<p class="mb-0 fw-medium">([^\n]+)<\/p>/)[1];
const render = duration => vm.runInNewContext('`' + template + '`', {pkg: {duration}});

test('flexible booking duration does not display null hours', () => {
    assert.equal(render(null), 'Flexible duration');
});

test('fixed booking duration keeps its hours', () => {
    assert.equal(render(3), '3 hours');
});
