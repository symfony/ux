import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/tooltip', () => {
    testState('shows on hover', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Top' });

            await trigger.hover();

            await expect(page.getByRole('tooltip')).toHaveText('Tooltip on top');
            await expect(trigger).toHaveAccessibleDescription('Tooltip on top');
        },
    });

    test('hides when the pointer leaves the trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/tooltip/default');
        const trigger = page.getByRole('button', { name: 'Top' });
        await trigger.hover();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.mouse.move(0, 0);

        await expect(page.getByRole('tooltip')).toBeHidden();
        await expect(trigger).not.toHaveAttribute('aria-describedby');
    });

    test('shows on focus and moves with the keyboard focus', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/tooltip/tooltips-on-links');

        await page.keyboard.press('Tab');

        await expect(page.getByRole('link', { name: 'inline links' })).toBeFocused();
        await expect(page.getByRole('tooltip')).toHaveText('Default tooltip');

        await page.keyboard.press('Tab');

        await expect(page.getByRole('link', { name: 'real text' })).toBeFocused();
        await expect(page.getByRole('tooltip', { name: 'Another tooltip' })).toBeVisible();
        await expect(page.getByRole('tooltip', { name: 'Default tooltip' })).toBeHidden();
        await expect(page.getByRole('link', { name: 'inline links' })).not.toHaveAttribute('aria-describedby');
    });

    test('hides when the trigger loses focus', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/tooltip/enable-tooltips');
        const trigger = page.getByRole('button', { name: 'Hover or focus me' });
        await trigger.focus();
        await expect(page.getByRole('tooltip')).toHaveText('This tooltip is initialized explicitly');

        await trigger.blur();

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    for (const side of ['top', 'right', 'bottom', 'left']) {
        test(`places the tooltip on the ${side}`, async ({ page, gotoExample }) => {
            await gotoExample('bootstrap/tooltip/directions');
            const trigger = page.getByRole('button', { name: `Tooltip on ${side}` });

            await trigger.focus();

            const tooltip = page.getByRole('tooltip');
            await expect(tooltip).toHaveText(`Tooltip on ${side}`);
            await expect(tooltip).toHaveAttribute('data-popper-placement', side);
            const triggerBox = (await trigger.boundingBox())!;
            const tooltipBox = (await tooltip.boundingBox())!;
            const isOnSide = {
                top: tooltipBox.y + tooltipBox.height <= triggerBox.y,
                right: tooltipBox.x >= triggerBox.x + triggerBox.width,
                bottom: tooltipBox.y >= triggerBox.y + triggerBox.height,
                left: tooltipBox.x + tooltipBox.width <= triggerBox.x,
            }[side];
            expect(isOnSide).toBe(true);
        });
    }

    testState('renders HTML content', {
        example: 'directions',
        state: 'html',
        act: async (page) => {
            await page.getByRole('button', { name: 'Tooltip with HTML' }).focus();

            const tooltip = page.getByRole('tooltip');
            await expect(tooltip).toHaveText('Tooltip with HTML');
            await expect(tooltip.locator('em')).toHaveText('Tooltip');
        },
    });

    testState('applies the custom class', {
        example: 'custom-tooltips',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Custom tooltip' }).focus();

            const tooltip = page.getByRole('tooltip');
            await expect(tooltip).toHaveText('This top tooltip is themed via CSS variables.');
            await expect(tooltip).toContainClass('bootstrap-custom-tooltip');
        },
    });

    test('shows the tooltip of a disabled button on hover of its wrapper', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/tooltip/disabled-elements');

        await page.getByRole('button', { name: 'Disabled button' }).hover({ force: true });

        await expect(page.getByRole('tooltip')).toHaveText('Disabled tooltip');
    });

    test('shows the tooltip of a disabled button when its wrapper gets the keyboard focus', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('bootstrap/tooltip/disabled-elements');

        await page.keyboard.press('Tab');

        await expect(page.getByRole('tooltip')).toHaveText('Disabled tooltip');
    });
});
