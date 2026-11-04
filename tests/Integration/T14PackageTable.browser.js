async (page) => {
    const context = await page.context().browser().newContext({viewport: {width: 1440, height: 1000}});
    const owner = await context.newPage();
    await owner.goto('http://127.0.0.1:8013/auth/login');
    await owner.getByPlaceholder('Enter Email Address').fill('yolanda.inocencio@gmail.com');
    await owner.getByPlaceholder('Enter Password', {exact: true}).fill('Password_123');
    await owner.getByRole('button', {name: 'Sign In', exact: true}).click();
    await owner.waitForURL('**/owner/dashboard', {waitUntil: 'domcontentloaded'});
    await owner.goto('http://127.0.0.1:8013/owner/view/packages', {waitUntil: 'domcontentloaded'});
    const row = owner.getByRole('row').filter({hasText: 'E2E Flexible Evening Session'});
    const text = await row.innerText();
    if (!text.includes('20% off') || !text.includes('₱4,000.00')) {
        throw new Error('Package table must show discount and client price: ' + text);
    }
    await owner.screenshot({path: '.playwright-mcp/fixed-package-table.png', fullPage: true});
    await context.close();
    return {passed: true, row: text};
}
