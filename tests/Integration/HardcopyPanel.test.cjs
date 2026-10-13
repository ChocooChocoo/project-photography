const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

test('reopening booking details replaces the hardcopy submit handler', async () => {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/hardcopy-script.blade.php'), 'utf8')
        .replace(/^<script>\s*/, '').replace(/<\/script>\s*$/, '');
    const listeners = [];
    const element = {
        textContent: '',
        innerHTML: '',
        querySelector: () => null,
        addEventListener: (event, handler) => listeners.push(handler),
    };
    const requests = [];
    const context = {
        window: {},
        fetch: async (url) => {
            requests.push(url);
            return {ok: true, text: async () => '<p>No orders</p>'};
        },
    };
    vm.runInNewContext(source, context);
    context.window.mountHardcopy(element, 1, true);
    context.window.mountHardcopy(element, 2, true);
    await new Promise(resolve => setImmediate(resolve));

    const handlerCount = listeners.length + (typeof element.onsubmit === 'function' ? 1 : 0);
    assert.equal(handlerCount, 1, 'Mounting twice must not submit to both bookings');
    assert.deepEqual(requests, ['/owner/bookings/1/hardcopy', '/owner/bookings/2/hardcopy']);
});

test('a late response from a previous booking cannot replace the current panel', async () => {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/hardcopy-script.blade.php'), 'utf8')
        .replace(/^<script>\s*/, '').replace(/<\/script>\s*$/, '');
    const responses = [];
    const element = {textContent: '', innerHTML: '', querySelector: () => null, addEventListener: () => {}};
    const context = {window: {}, fetch: () => new Promise(resolve => responses.push(resolve))};
    vm.runInNewContext(source, context);
    context.window.mountHardcopy(element, 1, true);
    context.window.mountHardcopy(element, 2, true);
    responses[1]({ok: true, text: async () => 'Booking 2'});
    await new Promise(resolve => setImmediate(resolve));
    responses[0]({ok: true, text: async () => 'Booking 1'});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(element.innerHTML, 'Booking 2');
});
