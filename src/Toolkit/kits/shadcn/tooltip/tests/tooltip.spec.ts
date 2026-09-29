import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/tooltip', () => {
    testState('opens on hover', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Hover' }).hover();

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toHaveText('Add to library');
        },
    });

    testState('opens on the side it is given', {
        example: 'side',
        state: 'open-bottom',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Bottom' });

            await trigger.hover();

            const tooltip = page.getByRole('tooltip');
            await expect(tooltip).toBeVisible();
            const triggerBox = await trigger.boundingBox();
            const tooltipBox = await tooltip.boundingBox();
            expect(tooltipBox!.y).toBeGreaterThan(triggerBox!.y + triggerBox!.height);
        },
    });

    testState('opens on hover of a disabled button', {
        example: 'disabled-button',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Disabled' }).locator('..').hover();

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toHaveText('This feature is currently unavailable');
        },
    });

    test('describes its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');

        await expect(page.getByRole('button', { name: 'Hover' })).toHaveAccessibleDescription('Add to library');
    });

    test('closes when the mouse leaves the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await page.getByRole('button', { name: 'Hover' }).hover();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.mouse.move(0, 0);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('opens on focus and closes on blur', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        const trigger = page.getByRole('button', { name: 'Hover' });

        await page.keyboard.press('Tab');

        await expect(trigger).toBeFocused();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.keyboard.press('Tab');

        await expect(trigger).not.toBeFocused();
        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('closes when the window is resized', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await page.getByRole('button', { name: 'Hover' }).hover();
        await expect(page.getByRole('tooltip')).toBeVisible();

        const viewport = page.viewportSize()!;
        await page.setViewportSize({ width: viewport.width - 100, height: viewport.height });

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('waits for the delay before opening', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default', { timers: 'fake' });
        await page.locator('#tooltip-demo').evaluate((element) => {
            element.setAttribute('data-tooltip-delay-duration-value', '1000');
        });

        await page.getByRole('button', { name: 'Hover' }).hover();
        await page.clock.runFor(400);

        await expect(page.getByRole('tooltip')).toBeHidden();

        await page.clock.runFor(1000);

        await expect(page.getByRole('tooltip')).toBeVisible();
    });

    test('does not open when the mouse leaves before the delay', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default', { timers: 'fake' });
        await page.locator('#tooltip-demo').evaluate((element) => {
            element.setAttribute('data-tooltip-delay-duration-value', '1000');
        });

        await page.getByRole('button', { name: 'Hover' }).hover();
        await page.clock.runFor(400);
        await page.mouse.move(0, 0);
        await page.clock.runFor(2000);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });
});
