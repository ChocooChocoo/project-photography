async (page) => {
    await page.goto('http://127.0.0.1:8013/client/booking-form/studio/4', {waitUntil: 'domcontentloaded'});
    const contrast = await page.locator('#bookingForm h4.text-primary').first().evaluate(element => {
        const foreground = getComputedStyle(element).color.match(/[\d.]+/g).slice(0, 3).map(Number);
        let parent = element;
        let background;
        while (parent) {
            const color = getComputedStyle(parent).backgroundColor.match(/[\d.]+/g).map(Number);
            if (color.length === 3 || color[3] === 1) { background = color.slice(0, 3); break; }
            parent = parent.parentElement;
        }
        const luminance = rgb => rgb.map(n => n / 255).map(c => c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4).reduce((sum, c, i) => sum + c * [0.2126, 0.7152, 0.0722][i], 0);
        const a = luminance(foreground), b = luminance(background);
        return {ratio: (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05), foreground, background};
    });
    if (contrast.ratio < 4.5) throw new Error(`Heading contrast failed: ${contrast.ratio.toFixed(2)}:1`);
    for (const name of ['Full Name', 'Contact Number', 'Email Address', 'Service Category', 'Event Date', 'Start Time']) {
        if (await page.getByLabel(name, {exact: true}).count() !== 1) throw new Error(`Missing associated label: ${name}`);
    }
    await page.locator('.package-radio').first().waitFor({state: 'attached'});
    await page.getByLabel('Full Name', {exact: true}).focus();
    let reachedPackage = false;
    for (let i = 0; i < 12; i++) {
        await page.keyboard.press('Tab');
        if (await page.evaluate(() => document.activeElement.classList.contains('package-radio'))) {
            reachedPackage = true;
            break;
        }
    }
    if (!reachedPackage) throw new Error('Package selection cannot be reached by Tab');
    await page.keyboard.press('Space');
    if (!await page.locator('.package-radio:checked').count()) throw new Error('Space did not select package');
    const previous = page.viewportSize();
    try {
        await page.setViewportSize({width: 390, height: 844});
        await page.waitForFunction(() => document.documentElement.getAttribute('data-sidenav-size') === 'offcanvas');
        await page.waitForFunction(() => document.documentElement.scrollWidth <= innerWidth);
        const small = await page.locator('#bookingForm .form-control:visible, #bookingForm .form-select:visible, #bookingForm .btn:visible').evaluateAll(elements => elements.filter(e => e.getBoundingClientRect().height < 44).map(e => e.id || e.textContent.trim()));
        if (small.length) throw new Error(`Controls below 44px: ${small.join(', ')}`);
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Horizontal overflow');
        return {contrast, keyboardPackageSelection: true, mobileControlsAtLeast44px: true, horizontalOverflow: false, contexts: page.context().browser().contexts().length, tabs: page.context().pages().length};
    } finally {
        if (previous) await page.setViewportSize(previous);
    }
}
