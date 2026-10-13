const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

test('polling updates cached off-page bookings without adding duplicate rows', () => {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/owner/view-bookings.blade.php'), 'utf8');
    const start = source.indexOf('function refreshBookings()');
    const end = source.indexOf('function poll()', start);
    const cells = () => Array.from({length: 8}, () => ({innerHTML: 'old'}));
    const cached = {getAttribute: () => '63', children: cells(), textContent: 'PHASE9-HARDCOPY'};
    const other = {getAttribute: () => '58', children: cells(), textContent: 'PHASE9-APPROVED'};
    const incoming = {getAttribute: () => '63', children: cells(), cloneNode: () => incoming};
    incoming.children[6].innerHTML = 'PHP 100.00';
    let appended = 0;
    const tbody = {appendChild: () => appended++};
    const instance = {tbody, rows: [cached, other], filteredRows: [cached], searchInput: {value: 'PHASE9-HARDCOPY'}, currentPage: 1, totalPages: 1, update: () => {}};
    const context = {
        pollInFlight: false,
        BOOKINGS_TABLE: '#bookingsTable tbody',
        DOMParser: class {parseFromString() {return {querySelector: () => ({querySelectorAll: () => [incoming, other]})};}},
        document: {querySelector: selector => selector === '#bookingsTable tbody' ? tbody : null, querySelectorAll: () => []},
        window: {location: {href: '/owner/view/bookings'}, PlatinumTable: {tables: [instance], refresh: () => {instance.filteredRows = [...instance.rows];}}},
        $: {ajax: () => ({done(callback) {callback('html'); return {always(callback) {callback();}};}})},
    };
    vm.runInNewContext(source.slice(start, end) + '\nrefreshBookings();', context);
    assert.equal(appended, 0, 'Pager already owns this booking; polling must not clone it');
    assert.equal(cached.children[6].innerHTML, 'PHP 100.00');
    assert.equal(instance.filteredRows.length, 1, 'Polling must preserve the active search');
    assert.equal(instance.filteredRows[0], cached);
});
