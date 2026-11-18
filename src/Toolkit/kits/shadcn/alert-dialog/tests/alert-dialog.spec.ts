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

    test('centres itself without a transform', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');

        await page.getByRole('button', { name: 'Show Dialog' }).click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
        const { transform, translate, centerX, centerY } = await dialog.evaluate((element) => {
            const box = element.getBoundingClientRect();
            const style = getComputedStyle(element);

            return {
                transform: style.transform,
                translate: style.translate,
                centerX: box.x + box.width / 2,
                centerY: box.y + box.height / 2,
            };
        });
        const viewport = page.viewportSize()!;
        expect(transform).toBe('none');
        expect(translate).toBe('none');
        expect(Math.abs(centerX - viewport.width / 2)).toBeLessThanOrEqual(1);
        expect(Math.abs(centerY - viewport.height / 2)).toBeLessThanOrEqual(1);
    });

    test('opens from a command button outside of it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Delete from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'alert-dialog-delete_account');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Delete from elsewhere' });

        await invoker.click();

        await expect(page.getByRole('dialog', { name: 'Are you absolutely sure?' })).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('button', { name: 'Cancel' })).toBeFocused();

        await page.getByRole('button', { name: 'Cancel' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(invoker).toHaveAttribute('aria-expanded', 'false');
        await expect(invoker).toBeFocused();
    });

    test('syncs the trigger when a command button closes it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        await trigger.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => {
            const button = document.createElement('button');
            button.textContent = 'Done';
            button.setAttribute('command', 'close');
            button.setAttribute('commandfor', element.id);
            element.append(button);
        });

        await dialog.getByRole('button', { name: 'Done' }).click();

        await expect(dialog).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('syncs the trigger when Escape closes it while nothing inside holds the focus', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await page.evaluate(() => (document.activeElement as HTMLElement).blur());

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open when a file input inside it cancels its picker', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        await trigger.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.evaluate((element) => {
            const input = document.createElement('input');
            input.type = 'file';
            element.append(input);
            // What the browser fires when the file picker is dismissed.
            input.dispatchEvent(new Event('cancel', { bubbles: true }));
        });

        await expect(dialog).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('collapses the command button outside of it when it is removed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Delete from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'alert-dialog-delete_account');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Delete from elsewhere' });
        await invoker.click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');

        await page.locator('[data-controller="alert-dialog"]').evaluate((element) => element.remove());

        await expect(invoker).toHaveAttribute('aria-expanded', 'false');
    });

    test('marks the command button outside of it expanded again after a move', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Delete from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'alert-dialog-delete_account');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Delete from elsewhere' });
        await invoker.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.locator('[data-controller="alert-dialog"]').evaluate(async (element) => {
            const parent = element.parentNode!;
            const next = element.nextSibling;
            element.remove();
            await new Promise((resolve) => setTimeout(resolve, 50));
            parent.insertBefore(element, next);
        });

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');
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

    test('keeps aria-expanded in sync when opened a third time', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/alert-dialog/default');
        const trigger = page.getByRole('button', { name: 'Show Dialog' });
        const dialog = page.getByRole('dialog');
        const settle = () =>
            page
                .locator('[data-slot="alert-dialog-content"]')
                .evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
        for (let i = 0; i < 2; i++) {
            await trigger.click();
            await expect(dialog).toBeVisible();
            await settle();
            await page.getByRole('button', { name: 'Cancel' }).click();
            await expect(dialog).toBeHidden();
            await settle();
            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        }

        await trigger.click();
        await expect(dialog).toBeVisible();
        await settle();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
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

    test.describe('without JavaScript', () => {
        test.use({ javaScriptEnabled: false });

        test('opens from its trigger and closes from its cancel button', async ({ page }) => {
            await page.goto('/shadcn/alert-dialog/default');

            await page.getByRole('button', { name: 'Show Dialog' }).click();

            const dialog = page.getByRole('dialog', { name: 'Are you absolutely sure?' });
            await expect(dialog).toBeVisible();

            await dialog.getByRole('button', { name: 'Cancel' }).click();

            await expect(dialog).toBeHidden();
        });
    });
});
