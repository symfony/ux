import { test, expect } from '@playwright/test';

async function capture(page, target, count = 1) {
    await page.locator(target).evaluate((element, count) => {
        for (let i = 0; i < count; i++) {
            element.dispatchEvent(new CustomEvent('turbo:frame-load', { bubbles: true }));
        }
    }, count);
}

test('component activity grows with visible events, caps, and preserves manual resizing', async ({ page }) => {
    await page.goto('/a');
    await expect(page.locator('ux-inspector')).toHaveAttribute('ready', '');
    await page.keyboard.type('ux');
    await page.locator('ux-inspector').evaluate((inspector) => inspector.clearLog());
    // Captured events for another component must not consume drawer space.
    await capture(page, '#probe', 12);
    await page.locator('ux-inspector .component').filter({ hasText: 'turbo-frame#frame' }).getByRole('button').click();
    const drawer = page.locator('ux-inspector .drawer');
    const height = () => drawer.evaluate((element) => element.getBoundingClientRect().height);
    const rows = drawer.locator('.event:not([hidden])');
    await expect(rows).toHaveCount(0);
    await expect(drawer.getByText('No matches.', { exact: true })).toBeVisible();
    const emptyHeight = await height();
    expect(emptyHeight).toBeLessThanOrEqual(48);

    await capture(page, '#frame');
    await expect(rows).toHaveCount(1);
    const firstHeight = await height();
    await capture(page, '#frame');
    await expect(rows).toHaveCount(2);
    await expect.poll(height).toBeGreaterThan(firstHeight);
    await capture(page, '#frame', 10);
    await expect(rows).toHaveCount(12);
    const cappedHeight = await height();
    expect(cappedHeight).toBeGreaterThan(emptyHeight);
    const layout = await drawer.evaluate((element) => {
        const timeline = element.querySelector('.timeline');
        return {
            height: element.getBoundingClientRect().height,
            available: element.parentElement.clientHeight,
            scrolls: timeline.scrollHeight > timeline.clientHeight,
            rootFont: Number.parseFloat(getComputedStyle(document.documentElement).fontSize),
        };
    });
    expect(layout.height).toBeLessThanOrEqual(Math.min(10 * layout.rootFont, layout.available * 0.4) + 1);
    expect(layout.scrolls).toBe(true);

    const handle = drawer.getByRole('separator', { name: 'Resize Activity' });
    await handle.focus();
    await handle.press('ArrowUp');
    await expect.poll(height).toBeGreaterThan(cappedHeight);
    await expect(handle).toBeFocused();
    const manualHeight = await height();
    await expect(handle).toHaveAttribute('aria-valuenow', String(Math.round(manualHeight)));
    await capture(page, '#frame');
    await expect(rows).toHaveCount(13);
    expect(await height()).toBe(manualHeight);
    await page.locator('ux-inspector').evaluate((inspector) => inspector.clearLog());
    await expect(rows).toHaveCount(0);
    expect(await height()).toBe(manualHeight);

    // Keyboard resizing leaves enough of the component detail visible above.
    for (let i = 0; i < 60; i++) await handle.press('ArrowUp');
    expect(await height()).toBeLessThanOrEqual(layout.available - 6 * layout.rootFont + 1);
    await expect(handle).toBeFocused();
});
