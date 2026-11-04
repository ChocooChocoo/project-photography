const assert = require('node:assert/strict');
const fs = require('node:fs');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/client/view-my-bookings.blade.php', 'utf8');
const start = source.indexOf('function formatPackageDuration(');
assert.notEqual(start, -1, 'Duration formatter is missing');
const end = source.indexOf('// Render booking details', start);
assert.notEqual(end, -1, 'Duration formatter end marker is missing');
const format = new Function('pkg', 'booking', `${source.slice(start, end)}\nreturn formatPackageDuration(pkg, booking);`);

test('flexible package shows booked hours, not null', () => {
    assert.equal(format({duration: null}, {start_time: '16:00', end_time: '21:00'}), 'Flexible duration (5 hours booked)');
});

test('flexible package without times stays plain', () => {
    assert.equal(format({duration: null}, {}), 'Flexible duration');
});

test('fixed package keeps its matching package duration', () => {
    assert.equal(format({duration: 3}, {start_time: '10:00', end_time: '13:00'}), '3 hours');
});

test('stored default that differs from the booked window shows booked hours', () => {
    assert.equal(format({duration: 8}, {start_time: '10:00', end_time: '15:00'}), '5 hours (booked)');
});

test('overnight window is handled', () => {
    assert.equal(format({duration: 3}, {start_time: '22:00', end_time: '01:00'}), '3 hours');
});
