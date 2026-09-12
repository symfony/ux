import { test, expect } from '@playwright/test';

async function openInspector(page) {
    await page.goto('/a');
    const inspector = page.locator('ux-inspector');
    await expect(inspector).toHaveAttribute('ready', '');
    await page.keyboard.type('ux');
    await inspector.evaluate((host) => host.clearLog());
    await page.evaluate(() => {
        for (const id of ['probe', 'frame'])
            document.getElementById(id).dispatchEvent(new CustomEvent('turbo:frame-load', { bubbles: true }));
    });
    return inspector;
}

test('restores the component Activity drawer after the journal and nested navigation', async ({ page }) => {
    const inspector = await openInspector(page);
    await inspector.locator('.component').filter({ hasText: 'section#probe' }).getByRole('button').click();
    const detail = await inspector.locator('.detail').elementHandle();
    const drawer = inspector.locator('.drawer');
    await expect(drawer).toHaveAttribute('aria-label', 'Activity for probe');
    await expect(drawer.locator('.event:not([hidden])')).toHaveCount(1);

    const handle = drawer.getByRole('separator', { name: 'Resize Activity' });
    await handle.press('ArrowUp');
    const height = await drawer.evaluate((node) => node.getBoundingClientRect().height);

    await inspector.getByRole('button', { name: /^Show activity/ }).click();
    await expect(inspector.locator('#panel-activity')).toBeVisible();
    await inspector.getByRole('button', { name: /^Show components/ }).click();
    await expect(drawer).toBeVisible();
    expect(await drawer.evaluate((node) => node.getBoundingClientRect().height)).toBe(height);
    await expect(drawer.locator('.event:not([hidden])')).toHaveCount(1);
    expect(await detail.evaluate((node) => node.isConnected && !node.closest('[hidden]'))).toBe(true);

    await inspector.evaluate((host) => host.inspectElement(document.getElementById('frame')));
    await expect(drawer).toHaveAttribute('aria-label', 'Activity for Frame: frame');
    await inspector.getByRole('button', { name: 'Back to probe', exact: true }).click();
    await expect(drawer).toHaveAttribute('aria-label', 'Activity for probe');
    await expect(drawer.locator('.event:not([hidden])')).toHaveCount(1);
    expect(await detail.evaluate((node) => node.isConnected && !node.closest('[hidden]'))).toBe(true);

    await inspector.getByRole('button', { name: /^Show activity/ }).click();
    await inspector.evaluate((host) => host.inspectElement(document.getElementById('probe')));
    await expect(drawer).toBeVisible();
    await expect(drawer).toHaveCount(1);
});

test('resets the Activity query together with its search field when reopening the journal', async ({ page }) => {
    const inspector = await openInspector(page);
    await inspector.getByRole('button', { name: /^Show activity/ }).click();
    const search = inspector.getByRole('searchbox', { name: 'Filter activity', exact: true });
    const rows = inspector.locator('#panel-activity .event:not([hidden])');
    await expect(rows).toHaveCount(2);
    await search.fill('impossible');
    await expect(rows).toHaveCount(0);
    await inspector.getByRole('button', { name: /^Show components/ }).click();
    await inspector.getByRole('button', { name: /^Show activity/ }).click();
    await expect(search).toHaveValue('');
    await expect(rows).toHaveCount(2);
});
