import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/toggle', () => {
    testState('turns on on click', {
        example: 'default',
        state: 'pressed',
        act: async (page) => {
            const toggle = page.getByRole('button', { name: 'Toggle bookmark' });

            await toggle.click();
            // Moves the pointer away, so the screenshot shows the pressed background and not the hover one.
            await page.mouse.move(0, 0);

            await expect(toggle).toHaveAttribute('aria-pressed', 'true');
            await expect(toggle).toHaveAttribute('data-state', 'on');
        },
    });

    testState('turns on on click in a right-to-left layout', {
        example: 'rtl',
        state: 'pressed',
        act: async (page) => {
            const toggle = page.getByRole('button', { name: 'Toggle bookmark' }).first();

            await toggle.click();
            await page.mouse.move(0, 0);

            await expect(toggle).toHaveAttribute('aria-pressed', 'true');
            await expect(toggle).toHaveAttribute('data-state', 'on');
        },
    });

    test('starts off', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle/default');

        const toggle = page.getByRole('button', { name: 'Toggle bookmark' });

        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await expect(toggle).toHaveAttribute('data-state', 'off');
    });

    test('turns off on a second click', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle/default');
        const toggle = page.getByRole('button', { name: 'Toggle bookmark' });
        await toggle.click();
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');

        await toggle.click();

        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await expect(toggle).toHaveAttribute('data-state', 'off');
    });

    test('toggles with Enter and Space', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle/with-text');
        const toggle = page.getByRole('button', { name: 'Toggle italic' });
        await toggle.focus();

        await page.keyboard.press('Enter');

        await expect(toggle).toHaveAttribute('aria-pressed', 'true');
        await expect(toggle).toHaveAttribute('data-state', 'on');

        await page.keyboard.press('Space');

        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await expect(toggle).toHaveAttribute('data-state', 'off');
    });

    test('keeps each toggle independent', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle/outline');
        const italic = page.getByRole('button', { name: 'Toggle italic' });
        const bold = page.getByRole('button', { name: 'Toggle bold' });

        await italic.click();

        await expect(italic).toHaveAttribute('aria-pressed', 'true');
        await expect(bold).toHaveAttribute('aria-pressed', 'false');
    });

    test('cannot be pressed when disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle/disabled');
        const toggle = page.getByRole('button', { name: 'Toggle disabled', exact: true });

        await expect(toggle).toBeDisabled();
        await toggle.click({ force: true });

        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await expect(toggle).toHaveAttribute('data-state', 'off');
    });
});
