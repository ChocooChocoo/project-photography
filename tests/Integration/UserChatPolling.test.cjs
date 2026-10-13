const assert = require('node:assert/strict');
const test = require('node:test');
const path = require('node:path');
const fs = require('node:fs');
const vm = require('node:vm');

test('visible polling stops and aborts when hidden, then resumes without overlap', async () => {
    const module = {exports: {}};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/assets/js/user-chat.js'), 'utf8'), {module, AbortController});
    const {createVisiblePoller} = module.exports;
    let listener;
    const document = {hidden: false, addEventListener: (_, callback) => listener = callback, removeEventListener() {}};
    const timers = new Map(); let key = 0; let calls = 0; let signal;
    const scheduler = {setTimeout(callback) {timers.set(++key, callback); return key;}, clearTimeout(id) {timers.delete(id);}};
    let finish;
    const poller = createVisiblePoller(async current => {calls++; signal = current; await new Promise(resolve => finish = resolve);}, {document, scheduler});
    poller.start();
    assert.equal(calls, 1);
    poller.refresh();
    assert.equal(calls, 1);
    document.hidden = true; listener();
    assert.equal(signal.aborted, true);
    finish(); await new Promise(setImmediate);
    assert.equal(timers.size, 0);
    document.hidden = false; listener();
    assert.equal(calls, 2);
    poller.stop(); finish();
});

test('chat output uses text nodes and send does not advance fetched-message cursor', () => {
    const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/user-chat.js'), 'utf8');
    assert.match(source, /body\.textContent = message\.body/);
    assert.doesNotMatch(source, /innerHTML/);
    const send = source.slice(source.indexOf('composer.addEventListener'));
    assert.doesNotMatch(send, /cursor\s*=/);
});
