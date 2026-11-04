async (page) => {
    const context = await page.context().browser().newContext({viewport: {width: 1440, height: 1000}});
    const owner = await context.newPage();
    await owner.goto('http://127.0.0.1:8013/auth/login');
    await owner.getByPlaceholder('Enter Email Address').fill('yolanda.inocencio@gmail.com');
    await owner.getByPlaceholder('Enter Password', {exact: true}).fill('Password_123');
    await owner.getByRole('button', {name: 'Sign In', exact: true}).click();
    await owner.waitForURL('**/owner/dashboard', {waitUntil: 'domcontentloaded'});
    await owner.getByRole('link', {name: /Online Gallery/}).click();
    await owner.getByRole('row').filter({hasText: 'BK-6AE9C50EEA1EA'}).getByRole('button').click();
    await owner.getByRole('dialog').getByText('Total Images: 2', {exact: true}).waitFor();
    if (await owner.locator('.add-original:not([disabled])').count() !== 0) {
        throw new Error('Published gallery offers an enabled original upload');
    }
    await owner.screenshot({path: '.playwright-mcp/fixed-published-gallery.png', fullPage: true});
    await context.close();
    return {passed: true, publishedOriginalUploadsDisabled: true};
}
