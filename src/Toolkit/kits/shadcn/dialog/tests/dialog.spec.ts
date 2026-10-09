import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/dialog', () => {
    testState('opens on click and focuses the first field', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open Dialog' });

            await trigger.click();

            await expect(page.getByRole('dialog', { name: 'Edit profile' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByLabel('Name', { exact: true })).toBeFocused();
        },
    });

    testState('opens a dialog whose content scrolls', {
        example: 'sticky-footer',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Sticky Footer' }).click();

            await expect(page.getByRole('dialog', { name: 'Sticky Footer' })).toBeVisible();
            await expect(
                page.getByRole('dialog').getByRole('button', { name: 'Close', exact: true }).first()
            ).toBeInViewport();
        },
    });

    testState('stays modal after being moved in the DOM', {
        example: 'default',
        state: 'open-after-move',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open Dialog' }).click();
            await page.locator('[data-controller="dialog"]').evaluate(async (element) => {
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

    test('exposes the title and the description', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');

        await page.getByRole('button', { name: 'Open Dialog' }).click();

        const dialog = page.getByRole('dialog', { name: 'Edit profile' });
        await expect(dialog).toHaveAccessibleDescription(
            "Make changes to your profile here. Click save when you're done."
        );
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes with the built-in close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await trigger.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByRole('button', { name: 'Close', exact: true }).click();

        await expect(dialog).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('closes with a Dialog:Close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByRole('button', { name: 'Cancel' }).click();

        await expect(dialog).toBeHidden();
    });

    test('closes on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open on a click inside the content', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByText('Edit profile').click();

        await expect(dialog).toBeVisible();
    });

    test('keeps aria-expanded in sync when opened a second time', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await trigger.click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await page
            .getByRole('dialog')
            .evaluate((dialog) => Promise.all(dialog.getAnimations().map((animation) => animation.finished)));
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('closes on Escape without a built-in close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/no-close-button');
        await page.getByRole('button', { name: 'No Close Button' }).click();
        const dialog = page.getByRole('dialog', { name: 'No Close Button' });
        await expect(dialog).toBeVisible();
        await expect(dialog.getByRole('button')).toHaveCount(0);

        await page.keyboard.press('Escape');

        await expect(dialog).toBeHidden();
    });

    test('closes while its content runs an endless animation', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => {
            const spinner = document.createElement('span');
            spinner.className = 'size-4 animate-spin';
            element.append(spinner);
        });

        await page.keyboard.press('Escape');

        await expect(dialog).not.toHaveAttribute('open');
    });

    test('leaves the top layer as soon as it closes', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => {
            element.addEventListener(
                'close',
                () => {
                    element.dataset.displayOnClose = getComputedStyle(element).display;
                },
                { once: true }
            );
        });

        await page.keyboard.press('Escape');

        await expect(dialog).toHaveAttribute('data-display-on-close', 'none');
    });

    test('opens again when a native close cut its exit short', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();

        await dialog.evaluate((element: HTMLDialogElement) => {
            element.querySelector<HTMLElement>('[data-slot="dialog-close"]')!.click();
            element.close();
            document.querySelector<HTMLElement>('[data-dialog-target="trigger"]')!.click();
        });

        await expect(dialog).toHaveAttribute('open');
        await expect(dialog).not.toHaveAttribute('data-closing');
    });

    test('plays its whole exit when closed again after reopening during the first one', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));

        const openAfterTheFirstExit = await dialog.evaluate(async (element: HTMLDialogElement) => {
            const wait = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));
            const closeButton = element.querySelector<HTMLElement>('[data-slot="dialog-close"]')!;
            const trigger = document.querySelector<HTMLElement>('[data-dialog-target="trigger"]')!;
            // Slow enough for the two exits to overlap.
            element.style.transitionDuration = '1000ms';

            closeButton.click();
            await wait(100);
            trigger.click();
            await wait(500);
            closeButton.click();
            await wait(700);

            return element.open;
        });

        expect(openAfterTheFirstExit).toBe(true);
        await expect(dialog).not.toHaveAttribute('open');
    });
});
