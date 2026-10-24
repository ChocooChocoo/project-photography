const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

// The freelancer package forms build their FormData field by field, so the
// shared extras helper must add the photo editing prices itself.
test('package extras helper sends the photo editing prices', () => {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/package-extras.blade.php'), 'utf8');
    const script = source.slice(source.indexOf('<script>') + 8, source.indexOf('</script>'));

    const input = (name, value) => ({ name, value });
    const prices = [
        input('editing_prices[color_grading]', '400'),
        input('editing_prices[brightness_contrast]', ''),
        input('editing_prices[retouching]', '1200'),
    ];
    const form = {
        querySelector: () => ({ files: [] }),
        querySelectorAll: (selector) => (selector.includes('editing_prices') ? prices : []),
        addEventListener: () => {},
    };
    const container = { querySelectorAll: () => [], closest: () => form };
    const window = {};
    vm.runInNewContext(script, {
        window,
        document: {
            getElementById: () => ({ ...container, addEventListener: () => {} }),
            addEventListener: () => {},
            querySelectorAll: () => [],
        },
    });

    const sent = new Map();
    const formData = {
        set: (key, value) => sent.set(key, value),
        append: (key, value) => sent.set(key, value),
        delete: (key) => sent.delete(key),
    };
    window.appendPackageExtras(formData, form);

    assert.equal(sent.get('editing_prices[color_grading]'), '400');
    assert.equal(sent.get('editing_prices[brightness_contrast]'), '');
    assert.equal(sent.get('editing_prices[retouching]'), '1200');

    // Videos are hidden (photo-only studios). Sending no video fields keeps stored videos.
    assert.ok(!source.includes('name="cover_videos[]"'));
    assert.ok(![...sent.keys()].some((key) => key.includes('cover_videos')));
});
