const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/pages/session-token.js'), 'utf8');

function deferred() {
    const done = [];
    const fail = [];
    let state = null;
    let args = [];
    const api = {
        done(cb) { state === 'resolved' ? cb(...args) : done.push(cb); return api; },
        fail(cb) { state === 'rejected' ? cb(...args) : fail.push(cb); return api; },
        resolve(...a) { state = 'resolved'; args = a; done.forEach(cb => cb(...a)); },
        reject(...a) { state = 'rejected'; args = a; fail.forEach(cb => cb(...a)); },
        promise() { return api; },
    };
    return api;
}

function load(fetchResult) {
    const meta = {value: 'stale', getAttribute: () => meta.value, setAttribute: (_, v) => { meta.value = v; }};
    const sent = [];
    let prefilter = null;
    const $ = {
        Deferred: deferred,
        extend: Object.assign,
        ajaxPrefilter: fn => { prefilter = fn; },
        ajax: settings => { sent.push(settings); return deferred(); },
    };
    $.fn = {};
    const jq = Object.assign(() => ({ajaxError() {}}), $);
    const context = {
        window: {jQuery: jq, FormData: class {}},
        document: {querySelector: () => meta, head: {appendChild() {}}},
        fetch: () => fetchResult,
    };
    vm.runInNewContext(source, context);
    return {meta, sent, prefilter: () => prefilter, FormData: context.window.FormData};
}

const flush = () => new Promise(resolve => setImmediate(resolve));

test('a 419 stays hidden from the caller and the retry uses the caller handlers', async () => {
    const env = load(Promise.resolve({ok: true, json: () => ({token: 'fresh'})}));
    const calls = [];
    const headers = {};
    const settings = {
        type: 'PUT',
        data: 'status=cancelled&_token=stale',
        success: () => calls.push('success'),
        error: () => calls.push('error'),
        complete: () => calls.push('complete'),
    };

    env.prefilter()(settings, {}, {setRequestHeader: (k, v) => { headers[k] = v; }});
    assert.equal(headers['X-CSRF-TOKEN'], 'stale');

    settings.error({status: 419});
    settings.complete({status: 419});
    await flush();

    assert.deepEqual(calls, [], 'the caller must not see the 419');
    assert.equal(env.sent.length, 1, 'the request is sent one more time');
    const retry = env.sent[0];
    assert.equal(retry.type, 'POST');
    assert.equal(retry.data, 'status=cancelled&_token=fresh&_method=PUT');
    assert.equal(retry.headers['X-CSRF-TOKEN'], 'fresh');
    assert.equal(retry.platinumRetried, true);

    retry.success();
    retry.complete();
    assert.deepEqual(calls, ['success', 'complete']);
});

test('other errors reach the caller at once', () => {
    const env = load(Promise.resolve({ok: true, json: () => ({token: 'fresh'})}));
    const calls = [];
    const settings = {type: 'POST', error: () => calls.push('error'), complete: () => calls.push('complete')};

    env.prefilter()(settings, {}, {setRequestHeader() {}});
    settings.error({status: 422});
    settings.complete({status: 422});

    assert.deepEqual(calls, ['error', 'complete']);
    assert.equal(env.sent.length, 0);
});

test('a failed token refresh passes the 419 to the caller', async () => {
    const env = load(Promise.resolve({ok: false, status: 500}));
    const calls = [];
    const settings = {type: 'POST', error: () => calls.push('error'), complete: () => calls.push('complete')};

    env.prefilter()(settings, {}, {setRequestHeader() {}});
    settings.error({status: 419});
    settings.complete({status: 419});
    await flush();

    assert.deepEqual(calls, ['error', 'complete']);
    assert.equal(env.sent.length, 0);
});

test('GET requests are left alone', () => {
    const env = load(Promise.resolve({ok: true, json: () => ({})}));
    const error = () => {};
    const settings = {type: 'GET', error};

    env.prefilter()(settings, {}, {setRequestHeader: () => assert.fail('no header on GET')});
    assert.equal(settings.error, error);
});

// Some hosts (production on Hostinger) drop the body of PUT, PATCH and DELETE
// requests, so the token and fields never reach Laravel. Those requests leave
// as POST with _method, which Laravel reads as the real method.
test('form-encoded PUT, PATCH and DELETE are sent as POST with _method', () => {
    const env = load(Promise.resolve({ok: true, json: () => ({token: 'fresh'})}));
    const run = settings => { env.prefilter()(settings, {}, {setRequestHeader() {}}); return settings; };

    const put = run({type: 'PUT', data: 'status=in_progress&_token=t'});
    assert.equal(put.type, 'POST');
    assert.equal(put.data, 'status=in_progress&_token=t&_method=PUT');

    const del = run({type: 'DELETE'});
    assert.equal(del.type, 'POST');
    assert.equal(del.data, '_method=DELETE');

    const formData = new Map();
    const patch = run({type: 'PATCH', processData: false, contentType: false, data: Object.assign(new env.FormData(), {set: (k, v) => formData.set(k, v)})});
    assert.equal(patch.type, 'POST');
    assert.equal(formData.get('_method'), 'PATCH');

    const json = run({type: 'PUT', contentType: 'application/json', data: '{"status":"x"}'});
    assert.equal(json.type, 'PUT', 'JSON bodies reach Laravel, so they stay as they are');
    assert.equal(json.data, '{"status":"x"}');

    const get = run({type: 'GET', data: 'q=1'});
    assert.equal(get.type, 'GET');
});
