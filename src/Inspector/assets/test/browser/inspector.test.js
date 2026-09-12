import assert from 'node:assert/strict';
import { test, expect } from '@playwright/test';

test.beforeAll(async ({ browser }, testInfo) => {
    console.log(`${testInfo.project.name}: ${browser.version()}`);
});

test('native focus, overlays, and Turbo navigation preserve the Inspector runtime', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/a');
    await expect(page.locator('ux-inspector')).toHaveAttribute('ready', '');
    await page.evaluate(() => {
        window.inspectorBefore = document.querySelector('ux-inspector');
    });
    await page.keyboard.type('ux');
    await page.getByRole('button', { name: 'Inspect page components', exact: true }).click();
    await page.locator('#probe').click({ position: { x: 150, y: 50 } });
    await page.waitForFunction(() =>
        document.querySelector('ux-inspector').shadowRoot.querySelector('.box[data-mode="selected"]')
    );
    await page.evaluate(() => (document.querySelector('#scroller').scrollTop = 30));
    await page.waitForFunction(
        () =>
            Math.abs(
                document
                    .querySelector('ux-inspector')
                    .shadowRoot.querySelector('.box[data-mode="selected"]')
                    .getBoundingClientRect().top -
                    (document.querySelector('#probe').getBoundingClientRect().top - 2)
            ) < 1
    );
    await page.evaluate(() => {
        document.querySelector('#probe').style.padding = '15px';
        document.querySelector('#probe').style.border = '5px solid black';
    });
    await page.waitForFunction(
        () =>
            Math.abs(
                document
                    .querySelector('ux-inspector')
                    .shadowRoot.querySelector('.box[data-mode="selected"]')
                    .getBoundingClientRect().width -
                    (document.querySelector('#probe').getBoundingClientRect().width + 4)
            ) < 1
    );
    await page.getByRole('button', { name: /^Show activity/ }).click();
    const disclosure = page.locator('ux-inspector button.disclosure').filter({ hasText: 'turbo:load' }).first();
    await disclosure.click();
    await disclosure.focus();
    await page.evaluate(() =>
        document.documentElement.dispatchEvent(new CustomEvent('turbo:load', { bubbles: true, detail: { sample: 1 } }))
    );
    await page.waitForFunction(
        () => Number(document.querySelector('ux-inspector').shadowRoot.querySelector('.event-count')?.textContent) >= 2
    );
    assert.equal(
        await page.evaluate(
            () =>
                document.querySelector('ux-inspector').shadowRoot.activeElement?.getAttribute('aria-expanded') ===
                'true'
        ),
        true,
        `grouped focus`
    );
    await page.evaluate(() => {
        window.activityFocus = document.querySelector('ux-inspector').shadowRoot.activeElement;
        document
            .querySelector('#frame')
            .dispatchEvent(new CustomEvent('turbo:frame-load', { bubbles: true, detail: { sample: 3 } }));
    });
    await page.waitForFunction(
        () => document.querySelector('ux-inspector').shadowRoot.querySelectorAll('.event').length >= 2
    );
    assert.equal(
        await page.evaluate(
            () => document.querySelector('ux-inspector').shadowRoot.activeElement === window.activityFocus
        ),
        true,
        `retained focus`
    );
    await page.getByRole('link', { name: 'Page B', exact: true }).click();
    await page.waitForURL('**/b');
    assert.equal(
        await page.evaluate(() => document.querySelector('ux-inspector') === window.inspectorBefore),
        true,
        `Turbo instance`
    );
    await page.goBack();
    await page.waitForURL('**/a');
    await page.goForward();
    await page.waitForURL('**/b');
    await page.getByRole('link', { name: 'Page A', exact: true }).click();
    await page.waitForURL('**/a');
    await page.getByRole('link', { name: 'Load frame', exact: true }).click();
    await page.waitForFunction(() => document.querySelector('turbo-frame [data-controller]'));
    await page.getByRole('link', { name: 'Canceled', exact: true }).click();
    assert.equal(new URL(page.url()).pathname, '/a');
    await page.evaluate(async () => {
        document.addEventListener('turbo:render', (e) => (window.renderMethod = e.detail.renderMethod), { once: true });
        (await import('/turbo.js')).visit(location.href, { action: 'replace' });
    });
    await page.waitForFunction(() => window.renderMethod === 'morph');
    const after = await page.evaluate(() => ({
        instance: document.querySelector('ux-inspector') === window.inspectorBefore,
        hosts: document.querySelectorAll('ux-inspector').length,
        scripts: document.querySelectorAll('script[src*="/_ux/inspector.js"]').length,
        importmaps: document.querySelectorAll('script[type="importmap"]').length,
    }));
    assert.deepEqual(after, { instance: true, hosts: 1, scripts: 1, importmaps: 0 });
    assert.deepEqual(errors, []);
});

test('reconnects after complete teardown without duplicate Activity listeners', async ({ page }) => {
    await page.goto('/a');
    await expect(page.locator('ux-inspector')).toHaveAttribute('ready', '');
    const host = await page.locator('ux-inspector').elementHandle();
    await host.evaluate((node) => node.remove());
    // Allow the short disconnect grace period used by Turbo to expire.
    await page.waitForTimeout(1100);
    await host.evaluate((node) => document.body.append(node));
    await page.keyboard.type('ux');
    await page.getByRole('button', { name: /^Show activity/ }).click();
    await page.evaluate(() =>
        document.documentElement.dispatchEvent(
            new CustomEvent('turbo:load', { bubbles: true, detail: { reconnect: true } })
        )
    );
    await expect(page.locator('ux-inspector .event')).toHaveCount(1);
    await expect(page.locator('ux-inspector .event-count')).toHaveCount(0);
});

test('keeps a focused page rule while new Activity arrives', async ({ page }) => {
    await page.goto('/a');
    await expect(page.locator('ux-inspector')).toHaveAttribute('ready', '');
    await page.evaluate(() => {
        const element = document.createElement('div');
        element.dataset.turbo = 'false';
        document.body.append(element);
    });
    await page.keyboard.type('ux');
    const rule = page.locator('ux-inspector .page-rule').filter({ hasText: 'Turbo disabled' });
    await expect(rule).toBeVisible();
    await rule.focus();
    const original = await rule.elementHandle();
    await page.evaluate(() => {
        for (let i = 0; i < 20; i++) {
            document.documentElement.dispatchEvent(
                new CustomEvent('turbo:load', { bubbles: true, detail: { sample: i } })
            );
        }
    });
    await expect
        .poll(async () => Number(await page.locator('ux-inspector [data-action="activity"] b').textContent()))
        .toBeGreaterThan(0);
    // Wait for the batched UI update even if grouping leaves the badge count unchanged.
    await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    expect(await original.evaluate((node) => node.isConnected && node.getRootNode().activeElement === node)).toBe(true);
});

test('refreshes an open outlet detail after sibling attributes and text change', async ({ page }) => {
    await page.goto('/a');
    await expect(page.locator('ux-inspector')).toHaveAttribute('ready', '');
    await page.evaluate(() => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML =
            '<div id="outlet-owner" data-controller="probe" data-probe-probe-outlet="[aria-expanded=true] + .probe-target"></div><div id="outlet-gate" aria-expanded="false"></div><div id="outlet-target" class="probe-target" data-controller="probe">Occupied</div>';
        document.body.append(wrapper);
    });
    await page.keyboard.type('ux');
    await page.locator('ux-inspector .component').filter({ hasText: '#outlet-owner' }).getByRole('button').click();
    const missing = page.locator('ux-inspector [data-field-key="probe outlet"]');
    await expect(missing).toHaveCount(1);
    await page.locator('#outlet-gate').evaluate((node) => node.setAttribute('aria-expanded', 'true'));
    await expect(missing).toHaveCount(0);
    await page.locator('#outlet-gate').evaluate((node) => node.setAttribute('aria-expanded', 'false'));
    await expect(missing).toHaveCount(1);
    await page
        .locator('#outlet-owner')
        .evaluate((node) => node.setAttribute('data-probe-probe-outlet', '.probe-target:empty'));
    await page.locator('#outlet-target').evaluate((node) => {
        node.firstChild.data = '';
    });
    await expect(missing).toHaveCount(0);
    await page.locator('#outlet-target').evaluate((node) => {
        node.className = '';
    });
    await expect(missing).toHaveCount(1);
});

test('delegated pointer and keyboard preview reuse the overlay and preserve fields when changes expire', async ({
    page,
}) => {
    await page.goto('/a');
    const inspector = page.locator('ux-inspector');
    await expect(inspector).toHaveAttribute('ready', '');
    await page.keyboard.type('ux');
    const card = inspector.locator('.component').filter({ hasText: 'section#probe' }).getByRole('button');
    await card.hover();
    const hover = inspector.locator('.box[data-mode="hover"]');
    await expect(hover).toHaveCount(1);
    const original = await hover.elementHandle();
    await card.focus();
    expect(await hover.evaluate((node, previous) => node === previous, original)).toBe(true);
    await card.press('Enter');
    await expect(inspector.locator('.detail')).toBeVisible();
    await page.locator('#probe').evaluate((node) => node.setAttribute('data-probe-count-value', '8'));
    const changed = inspector.locator('[data-changed]').first();
    await expect(changed).toBeVisible();
    const field = await changed.elementHandle();
    await expect(inspector.locator('[data-changed]')).toHaveCount(0, { timeout: 4000 });
    expect(await field.evaluate((node) => node.isConnected)).toBe(true);
});
