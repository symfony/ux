import { test, expect } from '@playwright/test';

async function setup(page) {
    await page.goto('/a');
    const host = page.locator('ux-inspector');
    await expect(host).toHaveAttribute('ready', '');
    return {
        host,
        tab: host.locator('.pull-tab'),
        button: host.getByRole('button', { name: 'Open Inspector', exact: true }),
    };
}

test('pull tab previews the dock without blocking nearby page controls', async ({ page }) => {
    const { host, tab, button } = await setup(page);
    const viewport = page.viewportSize();
    await page.mouse.move(100, 100);
    await expect(button).toBeVisible();
    const rest = await button.boundingBox();
    expect(viewport.width - rest.x).toBeCloseTo(8, 0);
    await page.evaluate(() => {
        const button = document.createElement('button');
        button.id = 'near-tab';
        button.textContent = 'Page action';
        button.style.cssText = 'position:fixed;right:16px;top:calc(50% - 48px);width:24px;height:24px';
        button.onclick = () => (button.dataset.clicked = 'true');
        document.body.append(button);
    });
    await page.locator('#near-tab').hover();
    await expect(tab).toHaveAttribute('data-near', '');
    await expect.poll(() => tab.evaluate((node) => getComputedStyle(node, '::before').opacity)).toBe('1');
    expect(await tab.evaluate((node) => getComputedStyle(node, '::before').width)).toBe('3px');
    await page.locator('#near-tab').click();
    await expect(page.locator('#near-tab')).toHaveAttribute('data-clicked', 'true');
    await expect(host).not.toHaveAttribute('open', '');
    await button.click();
    await expect(host).toHaveAttribute('open', '');
    await expect(tab).toBeHidden();
    await expect(host.getByRole('button', { name: 'Hide inspector', exact: true })).toBeFocused();
    await host.getByRole('button', { name: 'Hide inspector', exact: true }).click();
    await expect(button).toBeVisible();
    await page.mouse.move(100, 100);
    await button.evaluate((node) => node.blur());
    await expect(tab).not.toHaveAttribute('data-near', '');
});

test('keyboard focus reveals UX and returns after closing with reduced motion', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const { host, tab, button } = await setup(page);
    await button.focus();
    await expect(button).toBeFocused();
    expect((await button.boundingBox()).x).toBe(page.viewportSize().width - 40);
    expect(await button.evaluate((node) => getComputedStyle(node).transitionDuration)).toBe('0s');
    await button.press('Enter');
    await expect(tab).toBeHidden();
    const close = host.getByRole('button', { name: 'Hide inspector', exact: true });
    await expect(close).toBeFocused();
    await close.press('Enter');
    await expect(button).toBeFocused();
    await button.press('Space');
    await expect(host).toHaveAttribute('open', '');
});

test('pull_tab false preserves shortcut opening across a full reconnect', async ({ page }) => {
    await page.route('**/a', async (route) => {
        const response = await route.fetch();
        await route.fulfill({
            response,
            body: (await response.text()).replace(
                '<ux-inspector ',
                '<ux-inspector data-config=\'{"pull_tab":false}\' '
            ),
        });
    });
    const { host, tab } = await setup(page);
    await expect(tab).toHaveCount(0);
    await page.keyboard.type('ux');
    await expect(host).toHaveAttribute('open', '');
    await host.evaluate((node) => {
        node.close();
        node.remove();
        window.detachedInspector = node;
    });
    await page.waitForTimeout(1100);
    await page.evaluate(() => document.body.append(window.detachedInspector));
    await expect(host).toHaveAttribute('ready', '');
    await expect(tab).toHaveCount(0);
    await page.keyboard.type('ux');
    await expect(host).toHaveAttribute('open', '');
});

test.describe('touch', () => {
    test.use({ hasTouch: true });
    test('shows UX without requiring hover on a narrow viewport', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const { host, button } = await setup(page);
        expect((await button.boundingBox()).x).toBe(350);
        await button.tap();
        await expect(host).toHaveAttribute('open', '');
    });
});
