async (page) => {
    const context = await page.context().browser().newContext({viewport: {width: 1440, height: 1000}});
    const client = await context.newPage();
    await client.goto('http://127.0.0.1:8013/auth/login');
    await client.getByPlaceholder('Enter Email Address').fill('beatriz.inocencio@gmail.com');
    await client.getByPlaceholder('Enter Password', {exact: true}).fill('Password_123');
    await client.getByRole('button', {name: 'Sign In', exact: true}).click();
    await client.waitForURL('**/client/dashboard', {waitUntil: 'domcontentloaded'});
    const search = client.getByRole('searchbox', {name: 'Search studios, freelancers, or groups'});
    await search.waitFor({timeout: 5000});
    await search.fill('Golden Hour Collective');
    const response = client.waitForResponse(r => r.url().includes('/dashboard/filter') && r.request().method() === 'POST');
    await search.press('Enter');
    await response;
    await client.locator('#loading-spinner').waitFor({state: 'hidden'});
    const cards = client.locator('.photographer-card');
    if (await cards.count() !== 2) throw new Error('Group search must return exactly both members');
    for (const card of await cards.all()) {
        if (!(await card.innerText()).toLowerCase().includes('golden hour collective')) {
            throw new Error('Filtered card lost its group name');
        }
    }
    await search.fill('No such group regression fixture');
    const emptyResponse = client.waitForResponse(r => r.url().includes('/dashboard/filter') && r.request().method() === 'POST');
    await search.press('Enter');
    await emptyResponse;
    await client.locator('#no-results').waitFor({state: 'visible'});
    await client.getByRole('button', {name: 'Clear All', exact: true}).click();
    await client.locator('#loading-spinner').waitFor({state: 'hidden'});
    if (await search.inputValue() !== '' || await cards.count() < 3) throw new Error('Clear All must restore discovery');
    await client.screenshot({path: '.playwright-mcp/fixed-group-discovery.png', fullPage: true});
    await context.close();
    return {passed: true, searchedMembers: 2, emptyState: true, clearAll: true};
}
