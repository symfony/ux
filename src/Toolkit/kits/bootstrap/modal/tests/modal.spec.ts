import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/modal', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Review changes' }).click();

            const modal = page.getByRole('dialog', { name: 'Review changes' });
            await expect(modal).toBeVisible();
            await expect(modal).toHaveClass(/\bshow\b/);
            await expect(modal).toBeFocused();
        },
    });

    test('exposes itself as a modal dialog only while open', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/default');
        const modal = page.locator('#demo-modal');
        await expect(modal).toHaveAttribute('aria-hidden', 'true');

        await page.getByRole('button', { name: 'Review changes' }).click();

        await expect(modal).toHaveAttribute('role', 'dialog');
        await expect(modal).toHaveAttribute('aria-modal', 'true');
        await expect(modal).not.toHaveAttribute('aria-hidden');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/default');
        const trigger = page.getByRole('button', { name: 'Review changes' });
        await trigger.click();
        await expect(page.getByRole('dialog', { name: 'Review changes' })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('closes with the header close button and the footer dismiss button', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/default');
        const trigger = page.getByRole('button', { name: 'Review changes' });
        const modal = page.getByRole('dialog', { name: 'Review changes' });
        await trigger.click();
        await expect(modal).toBeFocused();

        await modal.getByRole('button', { name: 'Close' }).click();

        await expect(modal).toBeHidden();

        await trigger.click();
        await expect(modal).toBeFocused();

        await modal.getByRole('button', { name: 'Keep editing' }).click();

        await expect(modal).toBeHidden();
    });

    test('closes on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/default');
        await page.getByRole('button', { name: 'Review changes' }).click();
        await expect(page.getByRole('dialog', { name: 'Review changes' })).toBeFocused();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
    });

    test('keeps focus inside the modal', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/default');
        await page.getByRole('button', { name: 'Review changes' }).click();
        const modal = page.getByRole('dialog', { name: 'Review changes' });
        await expect(modal).toBeFocused();
        await modal.getByRole('button', { name: 'Close' }).focus();

        await page.keyboard.press('Shift+Tab');

        await expect(modal.getByRole('button', { name: 'Save changes' })).toBeFocused();
    });

    test('does not close on a click outside with a static backdrop', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/static-backdrop');
        await page.getByRole('button', { name: 'Launch static backdrop modal' }).click();
        const modal = page.getByRole('dialog', { name: 'Modal title' });
        await expect(modal).toBeFocused();

        await page.mouse.click(10, 10);

        await expect(modal).toHaveClass(/\bmodal-static\b/);
        await expect(modal).not.toHaveClass(/\bmodal-static\b/);
        await expect(modal).toBeVisible();

        await modal.getByRole('button', { name: 'Close' }).first().click();

        await expect(modal).toBeHidden();
    });

    test('does not close on Escape with a static backdrop', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/static-backdrop');
        await page.getByRole('button', { name: 'Launch static backdrop modal' }).click();
        const modal = page.getByRole('dialog', { name: 'Modal title' });
        await expect(modal).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(modal).toHaveClass(/\bmodal-static\b/);
        await expect(modal).not.toHaveClass(/\bmodal-static\b/);
        await expect(modal).toBeVisible();
    });

    for (const { size, label, width } of [
        { size: 'sm', label: 'Small', width: 300 },
        { size: 'lg', label: 'Large', width: 800 },
        { size: 'xl', label: 'Extra large', width: 1140 },
        { size: 'xxl', label: 'Extra extra large', width: 1320 },
    ]) {
        test(`opens the ${size} modal at its own width`, async ({ page, gotoExample }) => {
            await page.setViewportSize({ width: 1440, height: 900 });
            await gotoExample('bootstrap/modal/optional-sizes');
            const modal = page.getByRole('dialog', { name: `${label} modal`, exact: true });

            await page.getByRole('button', { name: `${label} modal`, exact: true }).click();

            await expect(modal).toBeFocused();
            await expect(modal.locator('.modal-dialog')).toHaveCSS('width', `${width}px`);
        });
    }

    testState('fills the modal from the trigger that opened it', {
        example: 'varying-modal-content',
        state: 'open-for-fat',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open modal for @fat' }).click();

            const modal = page.getByRole('dialog', { name: 'New message to @fat' });
            await expect(modal).toBeFocused();
            await expect(modal.getByLabel('Recipient:')).toHaveValue('@fat');
        },
    });

    testState('switches to the second modal', {
        example: 'toggle-between-modals',
        state: 'second-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open first modal' }).click();
            await expect(page.getByRole('dialog', { name: 'Modal 1' })).toBeFocused();

            await page.getByRole('button', { name: 'Open second modal' }).click();

            await expect(page.getByRole('dialog', { name: 'Modal 2' })).toBeFocused();
            await expect(page.getByRole('dialog', { name: 'Modal 1' })).toBeHidden();
        },
    });

    test('switches back to the first modal', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/toggle-between-modals');
        await page.getByRole('button', { name: 'Open first modal' }).click();
        await page.getByRole('button', { name: 'Open second modal' }).click();
        await expect(page.getByRole('dialog', { name: 'Modal 2' })).toBeFocused();

        await page.getByRole('button', { name: 'Back to first' }).click();

        await expect(page.getByRole('dialog', { name: 'Modal 1' })).toBeFocused();
        await expect(page.getByRole('dialog', { name: 'Modal 2' })).toBeHidden();
    });

    test('hides the popovers it contains when it closes', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/modal/tooltips-and-popovers');
        await page.getByRole('button', { name: 'Launch demo modal' }).click();
        const modal = page.getByRole('dialog', { name: 'Modal title' });
        await expect(modal).toBeFocused();
        await modal.getByRole('button', { name: 'button', exact: true }).click();
        await expect(page.getByText('Popover body content is set in this attribute.')).toBeVisible();

        await modal.getByRole('button', { name: 'Close' }).first().click();

        await expect(modal).toBeHidden();
        await expect(page.getByText('Popover body content is set in this attribute.')).toBeHidden();
    });
});
