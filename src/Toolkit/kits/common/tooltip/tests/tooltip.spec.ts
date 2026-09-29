import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('common/tooltip', () => {
    testState('shows on hover', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Hover me' }).hover();

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toHaveText('Thanks for hovering!');
        },
    });

    test('hides when the pointer leaves the trigger', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/default');
        await page.getByRole('button', { name: 'Hover me' }).hover();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.mouse.move(0, 0);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('shows on keyboard focus and hides on blur', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/default');
        const trigger = page.getByRole('button', { name: 'Hover me' });

        await page.keyboard.press('Tab');

        await expect(trigger).toBeFocused();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('replaces the native title and describes the trigger', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/default');

        const trigger = page.getByRole('button', { name: 'Hover me' });

        await expect(trigger).not.toHaveAttribute('title');
        await expect(trigger).toHaveAccessibleDescription('Thanks for hovering!');
        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('shows on the preferred side of the trigger', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/placement');

        for (const side of ['Top', 'Bottom', 'Left', 'Right']) {
            const trigger = page.getByRole('button', { name: side, exact: true });
            await trigger.hover();
            const tooltip = page.getByRole('tooltip').filter({ hasText: side });
            await expect(tooltip).toBeVisible();

            const triggerBox = (await trigger.boundingBox())!;
            const tooltipBox = (await tooltip.boundingBox())!;

            expect(
                {
                    Top: tooltipBox.y + tooltipBox.height <= triggerBox.y,
                    Bottom: tooltipBox.y >= triggerBox.y + triggerBox.height,
                    Left: tooltipBox.x + tooltipBox.width <= triggerBox.x,
                    Right: tooltipBox.x >= triggerBox.x + triggerBox.width,
                }[side],
                side
            ).toBe(true);
        }
    });

    testState('opens on click', {
        example: 'click-to-toggle',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Click me' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toHaveText('Click again to close');
        },
    });

    test('closes on a second click', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/click-to-toggle');
        const trigger = page.getByRole('button', { name: 'Click me' });
        await trigger.click();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('does not open on hover when triggered by click', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/click-to-toggle');

        await page.getByRole('button', { name: 'Click me' }).hover();

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('hides itself after the auto-hide delay', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/auto-hide', { timers: 'fake' });

        await page.getByRole('button', { name: 'Show for 2s' }).click();

        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.clock.runFor(1500);

        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.clock.runFor(1000);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    testState('shows from a manual action', {
        example: 'manual-control',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Show' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toHaveText('Toggled by hand');
        },
    });

    test('hides from a manual action and ignores hover', async ({ page, gotoExample }) => {
        await gotoExample('common/tooltip/manual-control');
        const show = page.getByRole('button', { name: 'Show' });
        await show.hover();
        await expect(page.getByRole('tooltip')).toBeHidden();
        await show.click();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.getByRole('button', { name: 'Hide' }).click();

        await expect(page.getByRole('tooltip')).toBeHidden();
    });
});
