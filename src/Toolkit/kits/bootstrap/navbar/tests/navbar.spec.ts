import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/navbar', () => {
    testState('expands the collapsed navigation with the toggler', {
        example: 'default',
        state: 'expanded',
        act: async (page) => {
            const toggler = page.getByRole('button', { name: 'Toggle navigation' });

            await toggler.click();

            await expect(toggler).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('link', { name: 'Home' })).toBeVisible();
            await expect(page.locator('#navbarDemo')).toHaveClass(/\bshow\b/);
        },
    });

    test('collapses the navigation again with the toggler', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navbar/default');
        const toggler = page.getByRole('button', { name: 'Toggle navigation' });
        await expect(page.getByRole('link', { name: 'Home' })).toBeHidden();
        await toggler.click();
        await expect(page.locator('#navbarDemo')).toHaveClass(/\bshow\b/);

        await toggler.click();

        await expect(toggler).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('link', { name: 'Home' })).toBeHidden();
    });

    testState('opens a nav dropdown', {
        example: 'default',
        state: 'dropdown-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Toggle navigation' }).click();
            await expect(page.locator('#navbarDemo')).toHaveClass(/\bshow\b/);
            const dropdown = page.getByRole('button', { name: 'Dropdown' });

            await dropdown.click();

            await expect(dropdown).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('link', { name: 'Another action' })).toBeVisible();
        },
    });

    test('opens a nav dropdown with ArrowDown and closes it on Escape', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navbar/default');
        await page.getByRole('button', { name: 'Toggle navigation' }).click();
        await expect(page.locator('#navbarDemo')).toHaveClass(/\bshow\b/);
        const dropdown = page.getByRole('button', { name: 'Dropdown' });
        await dropdown.focus();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeHidden();
        await expect(dropdown).toHaveAttribute('aria-expanded', 'false');
        await expect(dropdown).toBeFocused();
    });

    test('toggles external content from the navbar', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navbar/external-content');
        const toggler = page.getByRole('button', { name: 'Toggle navigation' });
        await expect(page.getByRole('heading', { name: 'Collapsed content' })).toBeHidden();

        await toggler.click();

        await expect(page.getByRole('heading', { name: 'Collapsed content' })).toBeVisible();
        await expect(toggler).toHaveAttribute('aria-expanded', 'true');
    });

    testState('opens the offcanvas navigation', {
        example: 'offcanvas',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Toggle navigation' }).click();

            const offcanvas = page.getByRole('dialog', { name: 'Offcanvas' });
            await expect(offcanvas).toBeVisible();
            await expect(offcanvas).toBeFocused();
        },
    });

    test('closes the offcanvas navigation with its close button', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navbar/offcanvas');
        await page.getByRole('button', { name: 'Toggle navigation' }).click();
        const offcanvas = page.getByRole('dialog', { name: 'Offcanvas' });
        await expect(offcanvas).toBeFocused();

        await offcanvas.getByRole('button', { name: 'Close' }).click();

        await expect(offcanvas).toBeHidden();
    });

    test('closes the offcanvas navigation on Escape and gives focus back to the toggler', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('bootstrap/navbar/offcanvas');
        const toggler = page.getByRole('button', { name: 'Toggle navigation' });
        await toggler.click();
        const offcanvas = page.getByRole('dialog', { name: 'Offcanvas' });
        await expect(offcanvas).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(offcanvas).toBeHidden();
        await expect(toggler).toBeFocused();
    });
});
