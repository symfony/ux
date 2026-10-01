import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/alert-dialog', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Show Dialog' });

            await trigger.click();

            await expect(page.getByRole('dialog', { name: 'Are you absolutely sure?' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('button', { name: 'Cancel' })).toBeFocused();
        },
    });

    testState('opens the destructive dialog on click', {
        example: 'destructive',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Delete Chat' }).click();

            await expect(page.getByRole('dialog', { name: 'Delete chat?' })).toBeVisible();
        },
    });

    testState('stays modal after being moved in the DOM', {
        example: 'default',
        state: 'open-after-move',
        act: async (page) => {
            await page.getByRole('button', { name: 'Show Dialog' }).click();
            await page.locator('[data-controller="alert-dialog"]').evaluate(async (element) => {
                const parent = element.parentNode!;
                const next = element.nextSibling;
                element.remove();
                await new Promise((resolve) => setTimeout(resolve, 50));
                parent.insertBefore(element, next);
            });
            const dialog = page.getByRole('dialog');

            await expect(dialog).toBeVisible();
            expect(await dialog.evaluate((element) => element.matches(':modal'))).toBe(true);
        },
    });

    test('exposes its title and description', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });

        await trigger.click();

        await expect(trigger).toHaveAttribute('aria-haspopup', 'dialog');
        await expect(page.getByRole('dialog')).toHaveAccessibleName('Are you absolutely sure?');
        await expect(page.getByRole('dialog')).toHaveAccessibleDescription(/This action cannot be undone\./);
    });

    test('closes on Cancel and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.getByRole('button', { name: 'Cancel' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('stays open on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('keeps the page behind out of the keyboard focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        await page.getByRole('button', { name: 'Show Dialog' }).click();
        await expect(page.getByRole('button', { name: 'Cancel' })).toBeFocused();

        await page.keyboard.press('Shift+Tab');

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Show Dialog' })).not.toBeFocused();
    });

    test('opens again after being closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        await trigger.click();
        await page.getByRole('button', { name: 'Cancel' }).click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await trigger.click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });
});
