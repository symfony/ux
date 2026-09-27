import { test, expect } from '@playwright/test';

test('layered styles preserve search controls and nested diagnostic layouts', async ({ page }) => {
    await page.goto('/a');
    await expect(page.locator('ux-inspector')).toHaveAttribute('ready', '');
    await page.keyboard.type('ux');
    const inspector = page.locator('ux-inspector');
    await expect(inspector.getByRole('searchbox')).toHaveCSS('border-top-width', '0px');
    await inspector.evaluate((element) => element.clearLog());
    await page.locator('#frame').evaluate((frame) => {
        frame.dispatchEvent(
            new CustomEvent('turbo:frame-load', {
                bubbles: true,
                detail: { sample: { label: 'Nested diagnostic', items: ['first', 'second'] } },
            })
        );
    });
    await inspector.getByRole('button', { name: /^Show activity/ }).click();
    const event = inspector.locator('.event').filter({ hasText: 'frame-load' });
    await event.locator('button.disclosure').click();
    const tree = event.locator('.tree');
    await expect(tree.locator('.row').first()).toHaveCSS('display', 'grid');
    await tree.locator('summary').filter({ hasText: 'items' }).click();
    const array = tree.locator('.array');
    await expect(array).toBeVisible();
    await expect(array).toHaveCSS('display', 'flex');
    await expect(array.locator('.row').first()).toHaveCSS('display', 'grid');

    await page.locator('#frame').evaluate((frame) => {
        const child = document.createElement('turbo-frame');
        child.id = 'nested';
        frame.append(child);
    });
    await inspector.getByRole('button', { name: /^Show components/ }).click();
    await inspector.locator('.component').filter({ hasText: 'turbo-frame#frame' }).getByRole('button').click();
    await expect(inspector.locator('.relations').first()).toHaveCSS('display', 'contents');

    await page.emulateMedia({ reducedMotion: 'reduce' });
    await expect(inspector.locator('.drawer button.disclosure').first()).toHaveCSS('transition-duration', '0s');
    await page.setViewportSize({ width: 390, height: 844 });
    await expect(inspector.locator('.detail-body')).toBeVisible();
    const panel = inspector.locator('.inspector');
    expect(await panel.evaluate((element) => element.scrollWidth <= element.clientWidth)).toBe(true);
});
