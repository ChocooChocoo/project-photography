async (page) => {
    const context = await page.context().browser().newContext({
        viewport: {width: 1440, height: 1000},
        geolocation: {latitude: 14.4295, longitude: 120.939, accuracy: 10},
        permissions: ['geolocation'],
    });
    const client = await context.newPage();
    await client.goto('http://127.0.0.1:8013/auth/login');
    await client.getByPlaceholder('Enter Email Address').fill('beatriz.inocencio@gmail.com');
    await client.getByPlaceholder('Enter Password', {exact: true}).fill('Password_123');
    await client.getByRole('button', {name: 'Sign In', exact: true}).click();
    await client.waitForURL('**/client/dashboard', {waitUntil: 'domcontentloaded'});
    await client.locator('a[href$="/client/booking-details/freelancer/52"]').click();
    await client.getByRole('link', {name: /Book Now/}).click();
    await client.locator('label[for=package1]').click();
    await client.locator('#eventDate').fill('2026-11-21');
    await client.locator('#startTime').fill('09:00');
    await client.locator('#venuePinMap').waitFor({state: 'visible', timeout: 5000});
    await client.locator('#venueName').fill('Playwright regression venue');
    await client.locator('#city').selectOption('Imus');
    await client.locator('#barangay option').nth(1).waitFor({state: 'attached'});
    await client.locator('#barangay').selectOption(await client.locator('#barangay option').nth(1).getAttribute('value'));
    await client.locator('#street').fill('Local test fixture');
    await client.locator('#termsCheck').check();
    await client.getByText('Checking availability...', {exact: false}).waitFor({state: 'hidden'});
    await client.locator('#submitBookingBtn').click();
    await client.getByRole('dialog').getByText('Pin the Venue', {exact: true}).waitFor();
    await client.getByRole('button', {name: 'OK', exact: true}).click();
    await client.getByRole('button', {name: 'Use my current location'}).click();
    await client.getByText('Venue pinned. Drag the pin to adjust.', {exact: true}).waitFor();
    await client.locator('#submitBookingBtn').click();
    await client.getByRole('heading', {name: 'Booking Summary', exact: true}).waitFor();
    const summary = await client.getByRole('dialog').innerText();
    if (summary.includes('In-Studio Package') || !summary.includes('On-Location Package')) {
        throw new Error('Summary must show the selected on-location venue, not an in-studio package');
    }
    await client.locator('#bookingSummaryModal .modal-dialog').screenshot();
    await client.getByRole('dialog').getByRole('button', {name: 'Close', exact: true}).click();
    await client.getByRole('dialog').waitFor({state: 'hidden'});
    await client.locator('#submitBookingBtn').click();
    await client.getByRole('heading', {name: 'Booking Summary', exact: true}).waitFor();
    for (const label of ['Package Location:', 'Package Type:', 'Online Gallery:', 'Duration:']) {
        if (await client.getByRole('dialog').getByText(label, {exact: true}).count() !== 1) {
            throw new Error('Duplicate summary label: ' + label);
        }
    }
    if (!summary.includes('9:00 AM - 1:00 PM') || !summary.includes('₱4,200.00') || !summary.includes('₱1,260.00')) {
        throw new Error('Freelancer summary time, total, or 30% deposit is incorrect: ' + summary);
    }
    await client.screenshot({path: '.playwright-mcp/fixed-freelancer-summary.png', fullPage: true});
    await client.locator('#bookingSummaryModal .modal-dialog').screenshot();
    await client.getByRole('dialog').getByRole('button', {name: 'Close', exact: true}).click();
    await client.getByRole('dialog').waitFor({state: 'hidden'});
    await client.locator('#startTime').fill('22:00');
    await client.locator('#startTime').press('Tab');
    const timingError = await client.locator('#dateStatusText').innerText();
    if (!timingError.includes('midnight') || timingError.includes('End time must')) {
        throw new Error('Fixed-duration midnight error is misleading: ' + timingError);
    }
    if (await client.locator('#submitBookingBtn').isEnabled()) throw new Error('Midnight overflow must block continuation');
    const result = {passed: true, url: client.url(), summary};
    await context.close();
    return result;
}
