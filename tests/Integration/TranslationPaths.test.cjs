const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function manager(pagePath, language = null, options = {}) {
    const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/app.js'), 'utf8');
    const start = source.indexOf('class I18nManager');
    const end = source.indexOf('document.addEventListener("DOMContentLoaded"', start);
    assert.ok(start >= 0 && end > start, 'Real template translation manager must be present');
    const requests = [], errors = [];
    const context = {
        sessionStorage: {getItem: () => language},
        document: {querySelector: () => null, querySelectorAll: () => []},
        console: {error: (...args) => errors.push(args), warn() {}},
        fetch: async target => {
            const url = new URL(target, `https://example.test${pagePath}`);
            requests.push(url.pathname);
            const file = path.join(__dirname, '../../public', url.pathname);
            return {
                ok: fs.existsSync(file),
                json: async () => JSON.parse(fs.readFileSync(file, 'utf8')),
            };
        },
    };
    vm.runInNewContext(source.slice(start, end) + '\nglobalThis.Manager = I18nManager;', context);
    return {instance: new context.Manager(options), requests, errors};
}

for (const page of ['/auth/login', '/admin/login', '/owner/dashboard', '/client/studios/1']) {
    test(`translations load from asset root on ${page}`, async () => {
        const ui = manager(page);
        const translations = await ui.instance.loadTranslations();
        assert.deepEqual(ui.requests, ['/assets/data/translations/en.json']);
        assert.equal(translations['menu-title'], 'Navigation');
        assert.equal(ui.errors.length, 0);
    });
}

test('stored language and explicit translation path remain supported', async () => {
    const saved = manager('/client/studios/1', 'de');
    const translations = await saved.instance.loadTranslations();
    assert.deepEqual(saved.requests, ['/assets/data/translations/de.json']);
    assert.ok(Object.keys(translations).length > 0);
    const explicit = manager('/auth/login', null, {langPath: '/assets/data/translations/'});
    await explicit.instance.loadTranslations();
    assert.equal(explicit.errors.length, 0);
});

test('missing translation file retains existing empty fallback', async () => {
    const ui = manager('/auth/login', 'missing-language');
    const translations = await ui.instance.loadTranslations();
    assert.equal(Object.keys(translations).length, 0);
    assert.equal(ui.errors.length, 1);
});
