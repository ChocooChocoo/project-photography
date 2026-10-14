const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function tutorial(count = 4) {
    const element = () => ({
        hidden: false, disabled: false, textContent: '', events: {},
        addEventListener(name, handler) { this.events[name] = handler; },
    });
    const back = element(), next = element(), skip = element(), progress = element(), form = element();
    let focused = -1, submitted = 0;
    const steps = Array.from({length: count}, (_, index) => ({
        hidden: index !== 0,
        querySelector: () => ({focus() { focused = index; }}),
    }));
    form.requestSubmit = (submitter) => { assert.equal(submitter, skip); submitted++; form.events.submit(); };
    const nodes = {'[data-tutorial-back]': back, '[data-tutorial-next]': next,
        '[data-tutorial-skip]': skip, '[data-tutorial-form]': form, '#tutorial-progress': progress};
    const root = {querySelector: key => nodes[key], querySelectorAll: () => steps};
    const window = element();
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/assets/js/onboarding.js'), 'utf8'), {
        document: {querySelector: () => root}, window,
    });
    return {back, next, skip, progress, form, steps, window,
        focused: () => focused, submitted: () => submitted};
}

test('tutorial navigates role step counts, hides other sections and focuses headings', () => {
    for (const count of [4, 5]) {
        const ui = tutorial(count);
        assert.equal(ui.back.hidden, true);
        assert.equal(ui.next.hidden, false);
        ui.next.events.click();
        assert.equal(ui.focused(), 1);
        assert.equal(ui.progress.textContent, `Step 2 of ${count}`);
        assert.equal(ui.steps.filter(x => !x.hidden).length, 1);
        ui.back.events.click();
        assert.equal(ui.focused(), 0);
        for (let i = 1; i < count; i++) ui.next.events.click();
        assert.equal(ui.next.textContent, 'Finish');
        ui.next.events.click();
        assert.equal(ui.submitted(), 1);
        assert.equal(ui.skip.disabled, true);
    }
});

test('returning to cached tutorial restores controls after submission', () => {
    const ui = tutorial();
    ui.form.events.submit();
    assert.equal(ui.next.disabled, true);
    assert.equal(typeof ui.window.events.pageshow, 'function');
    ui.window.events.pageshow();
    assert.equal(ui.next.disabled, false);
    assert.equal(ui.skip.disabled, false);
    assert.equal(ui.progress.textContent, 'Step 1 of 4');
});
