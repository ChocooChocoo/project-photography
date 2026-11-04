async (page) => {
    const context = await page.context().browser().newContext({viewport: {width: 375, height: 812}});
    const landing = await context.newPage();
    await landing.goto('http://127.0.0.1:8013/');
    const menu = landing.getByRole('button', {name: 'Open menu', exact: true});
    await menu.click();
    await landing.getByRole('link', {name: 'Studios', exact: true}).click();
    if (!landing.url().endsWith('/#studios')) throw new Error('Studio anchor did not navigate');
    await landing.locator('#mainNav.show').waitFor({state: 'hidden', timeout: 5000});
    await landing.locator('button[aria-controls=mainNav][aria-expanded=false]').waitFor({timeout: 5000});
    const overflow = await landing.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    if (overflow) throw new Error('Mobile landing overflows horizontally');
    await landing.screenshot({path: '.playwright-mcp/fixed-landing-menu.png', fullPage: true});
    await context.close();
    return {passed: true, viewport: 375, navigation: '#studios', menuCollapsed: true, horizontalOverflow: false};
}
