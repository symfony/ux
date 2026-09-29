import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/button', () => {
    testState('presses toggle buttons and links on click', {
        example: 'toggle-states',
        state: 'pressed',
        act: async (page) => {
            const toggles = [
                ...(await page.getByRole('button', { name: 'Toggle button', exact: true }).all()),
                ...(await page.getByRole('button', { name: 'Toggle link', exact: true }).all()),
            ];
            expect(toggles).toHaveLength(4);

            for (const toggle of toggles) {
                await toggle.click();
            }
            await page.mouse.move(0, 0);

            for (const toggle of toggles) {
                await expect(toggle).toHaveClass(/\bactive\b/);
                await expect(toggle).toHaveAttribute('aria-pressed', 'true');
            }
        },
    });

    test('releases an active toggle button on click', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button/toggle-states');
        const toggle = page.getByRole('button', { name: 'Active toggle button', exact: true }).first();
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');

        await toggle.click();

        await expect(toggle).not.toHaveClass(/\bactive\b/);
        await expect(toggle).toHaveAttribute('aria-pressed', 'false');

        await toggle.click();

        await expect(toggle).toHaveClass(/\bactive\b/);
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');
    });

    test('toggles a button with Space and Enter', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button/toggle-states');
        const toggle = page.getByRole('button', { name: 'Toggle button', exact: true }).first();

        await toggle.press('Space');

        await expect(toggle).toHaveAttribute('aria-pressed', 'true');

        await toggle.press('Enter');

        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
    });

    test('toggles a link without following it', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button/toggle-states');
        const url = page.url();
        const toggle = page.getByRole('button', { name: 'Toggle link', exact: true }).first();

        await toggle.press('Enter');

        await expect(toggle).toHaveAttribute('aria-pressed', 'true');
        expect(page.url()).toBe(url);
    });

    test('keeps a disabled toggle button unpressed', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button/toggle-states');
        const toggle = page.getByRole('button', { name: 'Disabled toggle button', exact: true }).first();
        await expect(toggle).toBeDisabled();

        await toggle.click({ force: true });

        await expect(toggle).not.toHaveClass(/\bactive\b/);
        await expect(toggle).not.toHaveAttribute('aria-pressed');
    });

    test('skips a disabled toggle link in the keyboard navigation', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button/toggle-states');
        await page.getByRole('button', { name: 'Active toggle link', exact: true }).first().focus();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('button', { name: 'Toggle link', exact: true }).nth(1)).toBeFocused();
        await expect(page.getByRole('button', { name: 'Disabled toggle link', exact: true }).first()).toHaveAttribute(
            'aria-disabled',
            'true'
        );
    });
});
