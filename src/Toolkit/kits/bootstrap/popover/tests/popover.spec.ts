import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/popover', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Toggle popover' });

            await trigger.click();

            const popover = page.getByRole('tooltip');
            await expect(popover).toBeVisible();
            await expect(popover).toContainText('Bootstrap popover');
            await expect(popover).toContainText('This content appears beside the trigger.');
            await expect(trigger).toHaveAccessibleDescription(/This content appears beside the trigger\./);
        },
    });

    test('closes on a second click on the trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/popover/default');
        const trigger = page.getByRole('button', { name: 'Toggle popover' });
        await trigger.click();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('opens on the side given by the placement', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/popover/four-directions');

        for (const placement of ['left', 'top', 'bottom', 'right']) {
            await page.getByRole('button', { name: placement, exact: true }).click();

            const popover = page.getByRole('tooltip');
            await expect(popover).toContainText(`On the ${placement} side.`);
            await expect(popover).toHaveAttribute('data-popper-placement', placement);

            await page.getByRole('button', { name: placement, exact: true }).click();
            await expect(popover).toBeHidden();
        }
    });

    test('renders the popover inside the custom container', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/popover/custom-container');

        await page.getByRole('button', { name: 'Use custom container' }).click();

        await expect(page.locator('#popover-container').getByRole('tooltip')).toContainText('Contained popover');
    });

    test('adds the custom class to the popover', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/popover/custom-popovers');

        await page.getByRole('button', { name: 'Custom popover' }).click();

        await expect(page.getByRole('tooltip')).toHaveClass(/\bcustom-popover\b/);
    });

    test('dismisses on the next click with the focus trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/popover/dismiss-on-next-click');
        await page.getByRole('button', { name: 'Dismissible popover' }).click();
        await expect(page.getByRole('tooltip')).toContainText('Dismissible popover');

        await page.mouse.click(10, 10);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    testState('opens on hover over the wrapper of a disabled element', {
        example: 'disabled-elements',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Disabled button' }).hover({ force: true });

            await expect(page.getByRole('tooltip')).toContainText('the wrapper owns the popover');
        },
    });

    test('closes when the pointer leaves the wrapper of a disabled element', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/popover/disabled-elements');
        await page.getByRole('button', { name: 'Disabled button' }).hover({ force: true });
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.mouse.move(0, 0);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('opens on keyboard focus of the wrapper of a disabled element', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/popover/disabled-elements');

        await page.keyboard.press('Tab');

        await expect(page.getByRole('tooltip')).toContainText('the wrapper owns the popover');

        await page.keyboard.press('Tab');

        await expect(page.getByRole('tooltip')).toBeHidden();
    });
});
