import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('flowbite-4/modal', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open Modal' });

            await trigger.click();

            await expect(page.getByRole('dialog', { name: 'Edit profile' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('opens the pop-up modal on click', {
        example: 'pop-up-modal',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Delete' }).click();

            await expect(page.getByRole('dialog')).toBeVisible();
            await page.mouse.move(0, 0);
        },
    });

    test('stays closed and out of reach until opened', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/default');

        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');

        await expect(page.getByRole('dialog')).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Decline' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Open Modal' })).not.toHaveAttribute('aria-expanded', 'true');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/default');
        const trigger = page.getByRole('button', { name: 'Open Modal' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes with the close button and the Decline button', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/default');
        const trigger = page.getByRole('button', { name: 'Open Modal' });
        await trigger.click();

        await page.getByRole('button', { name: 'Close' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.getByRole('button', { name: 'Decline' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/default');
        const trigger = page.getByRole('button', { name: 'Open Modal' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.mouse.click(5, 5);

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('keeps its state in sync over several openings', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/default');
        const trigger = page.getByRole('button', { name: 'Open Modal' });

        for (let i = 0; i < 3; i++) {
            await trigger.click();
            await expect(page.getByRole('dialog')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');

            await page.keyboard.press('Escape');
            await expect(page.getByRole('dialog')).toBeHidden();
            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        }
    });

    test('does not close on a click on its content', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/default');
        await page.getByRole('button', { name: 'Open Modal' }).click();

        await page.getByText('With less than a month to go').click();

        await expect(page.getByRole('dialog')).toBeVisible();
    });

    test('does not close on a click on the backdrop with a static backdrop', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/static-modal');
        await page.getByRole('button', { name: 'Open Modal' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.mouse.click(5, 5);

        await expect(page.getByRole('dialog')).toBeVisible();
    });

    test('is open on page load when asked to', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/modal/opened-by-default');

        await expect(page.getByRole('dialog', { name: 'Edit profile' })).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Open Modal' })).toHaveAttribute('aria-expanded', 'false');
    });
});
