import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/offcanvas', () => {
    testState('opens on a click on its trigger', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Launch demo offcanvas' }).click();

            const panel = page.getByRole('dialog', { name: 'Offcanvas' });
            await expect(panel).toBeVisible();
            await expect(panel).toHaveAttribute('aria-modal', 'true');
            await expect(panel).toBeFocused();
        },
    });

    testState('opens a dark panel', {
        example: 'dark-offcanvas',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Toggle dark offcanvas' }).click();

            await expect(page.getByRole('dialog', { name: 'Dark offcanvas' })).toBeFocused();
        },
    });

    testState('opens from the end edge', {
        example: 'placement',
        state: 'end-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Toggle end' }).click();

            await expect(page.getByRole('dialog', { name: 'Offcanvas end' })).toBeFocused();
        },
    });

    test('keeps the closed panel out of reach', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/default');
        const trigger = page.getByRole('button', { name: 'Launch demo offcanvas' });

        await trigger.focus();
        await page.keyboard.press('Tab');

        await expect(page.getByRole('dialog')).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Close' })).toHaveCount(0);
        await expect(trigger).not.toBeFocused();
        expect(await page.evaluate(() => document.activeElement === document.body)).toBe(true);
    });

    test('opens from a link as well as from a button', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/live-demo');
        const panel = page.getByRole('dialog', { name: 'Offcanvas' });

        await page.getByRole('button', { name: 'Link with href' }).click();

        await expect(panel).toBeFocused();

        await page.keyboard.press('Escape');
        await expect(panel).toBeHidden();
        await page.getByRole('button', { name: 'Button with data-bs-target' }).click();

        await expect(panel).toBeFocused();
    });

    test('closes with its close button and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/default');
        const trigger = page.getByRole('button', { name: 'Launch demo offcanvas' });
        await trigger.click();
        const panel = page.getByRole('dialog', { name: 'Offcanvas' });
        await expect(panel).toBeFocused();

        await panel.getByRole('button', { name: 'Close' }).click();

        await expect(panel).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/default');
        const trigger = page.getByRole('button', { name: 'Launch demo offcanvas' });
        await trigger.click();
        const panel = page.getByRole('dialog', { name: 'Offcanvas' });
        await expect(panel).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(panel).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('closes on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/default');
        await page.getByRole('button', { name: 'Launch demo offcanvas' }).click();
        const panel = page.getByRole('dialog', { name: 'Offcanvas' });
        await expect(panel).toBeFocused();

        await page.mouse.click(700, 300);

        await expect(panel).toBeHidden();
    });

    test('keeps focus inside the open panel', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/default');
        await page.getByRole('button', { name: 'Launch demo offcanvas' }).click();
        const panel = page.getByRole('dialog', { name: 'Offcanvas' });
        await expect(panel).toBeFocused();
        await page.keyboard.press('Tab');
        await expect(panel.getByRole('button', { name: 'Close' })).toBeFocused();

        await page.keyboard.press('Shift+Tab');

        await expect(panel.getByRole('button', { name: 'Dropdown button' })).toBeFocused();
    });

    test('locks the page scroll while open', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/default');

        await page.getByRole('button', { name: 'Launch demo offcanvas' }).click();

        await expect(page.getByRole('dialog', { name: 'Offcanvas' })).toBeFocused();
        await expect(page.locator('body')).toHaveCSS('overflow', 'hidden');
    });

    test('keeps the page scrollable without a backdrop', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/body-scrolling');
        const trigger = page.getByRole('button', { name: 'Enable body scrolling' });

        await trigger.click();

        await expect(page.getByRole('dialog', { name: 'Offcanvas with body scrolling' })).toBeVisible();
        await expect(page.locator('body')).not.toHaveCSS('overflow', 'hidden');

        await trigger.click();

        await expect(page.getByRole('dialog')).toHaveCount(0);
    });

    test('keeps the page scrollable with a backdrop', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/body-scrolling-and-backdrop');
        await page.getByRole('button', { name: 'Enable both scrolling and backdrop' }).click();
        const panel = page.getByRole('dialog', { name: 'Backdrop with scrolling' });
        await expect(panel).toBeFocused();

        await expect(page.locator('body')).not.toHaveCSS('overflow', 'hidden');

        await page.mouse.click(700, 300);

        await expect(panel).toBeHidden();
    });

    test('stays open on a click on a static backdrop', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/static-backdrop');
        await page.getByRole('button', { name: 'Toggle static offcanvas' }).click();
        const panel = page.getByRole('dialog', { name: 'Offcanvas' });
        await expect(panel).toBeFocused();
        await panel.evaluate((element) =>
            element.addEventListener('hidePrevented.bs.offcanvas', () => (element.dataset.hidePrevented = 'true'))
        );

        await page.mouse.click(700, 300);

        await expect(panel).toHaveAttribute('data-hide-prevented', 'true');
        await expect(panel).toBeVisible();
        await expect(panel).toContainClass('show');

        await panel.getByRole('button', { name: 'Close' }).click();

        await expect(panel).toBeHidden();
    });

    test('becomes regular content above its breakpoint', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 1200, height: 600 });
        await gotoExample('bootstrap/offcanvas/responsive');

        await expect(page.getByText('This is content within an .offcanvas-lg.')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Toggle offcanvas' })).toBeHidden();
        await expect(page.getByRole('dialog')).toHaveCount(0);
    });

    test('opens as a panel below its breakpoint', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/offcanvas/responsive');
        await expect(page.getByText('This is content within an .offcanvas-lg.')).toBeHidden();

        await page.getByRole('button', { name: 'Toggle offcanvas' }).click();

        await expect(page.getByRole('dialog', { name: 'Responsive offcanvas' })).toBeFocused();
        await expect(page.getByText('This is content within an .offcanvas-lg.')).toBeVisible();
    });
});
