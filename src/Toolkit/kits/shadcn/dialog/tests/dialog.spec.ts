import type { Locator } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

async function recordExit(dialog: Locator): Promise<void> {
    await dialog.evaluate((element) => {
        new MutationObserver(() => {
            if (element.hasAttribute('data-closing')) {
                element.dataset.exitPlayed = '';
            }
        }).observe(element, { attributeFilter: ['data-closing'] });
    });
}

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

    test('centres itself without a transform', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');

        await page.getByRole('button', { name: 'Open Dialog' }).click();

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
        await gotoExample('shadcn/dialog/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Edit from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'dialog-edit_profile');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Edit from elsewhere' });

        await invoker.click();

        await expect(page.getByRole('dialog', { name: 'Edit profile' })).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByLabel('Name', { exact: true })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(invoker).toHaveAttribute('aria-expanded', 'false');
        await expect(invoker).toBeFocused();
    });

    test('plays its exit when a command button closes it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await recordExit(dialog);
        await dialog.evaluate((element) => {
            const button = document.createElement('button');
            button.textContent = 'Done';
            button.setAttribute('command', 'close');
            button.setAttribute('commandfor', element.id);
            element.append(button);
        });

        await dialog.getByRole('button', { name: 'Done' }).click();

        await expect(dialog).toBeHidden();
        await expect(dialog).toHaveAttribute('data-exit-played');
        await expect(page.getByRole('button', { name: 'Open Dialog' })).toHaveAttribute('aria-expanded', 'false');
    });

    test('plays its exit when Escape closes it while nothing inside holds the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await trigger.click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await recordExit(dialog);
        await page.evaluate(() => (document.activeElement as HTMLElement).blur());

        await page.keyboard.press('Escape');

        await expect(dialog).toBeHidden();
        await expect(dialog).toHaveAttribute('data-exit-played');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open when a file input inside it cancels its picker', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();

        await dialog.evaluate((element) => {
            const input = document.createElement('input');
            input.type = 'file';
            element.append(input);
            // What the browser fires when the file picker is dismissed.
            input.dispatchEvent(new Event('cancel', { bubbles: true }));
        });

        await expect(dialog).toBeVisible();
        await expect(dialog).not.toHaveAttribute('data-closing');
        await expect(page.getByRole('button', { name: 'Open Dialog' })).toHaveAttribute('aria-expanded', 'true');
    });

    test('closes with the value of the command button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => {
            const button = document.createElement('button');
            button.textContent = 'Confirm';
            button.value = 'confirm';
            button.setAttribute('command', 'close');
            button.setAttribute('commandfor', element.id);
            element.append(button);
        });

        await dialog.getByRole('button', { name: 'Confirm' }).click();

        await expect(dialog).toBeHidden();
        expect(await dialog.evaluate((element: HTMLDialogElement) => element.returnValue)).toBe('confirm');
    });

    test('lets a cancel listener keep it open on a request-close command', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await trigger.click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await recordExit(dialog);
        await dialog.evaluate((element) => {
            const button = document.createElement('button');
            button.textContent = 'Leave';
            button.value = 'leave';
            button.setAttribute('command', 'request-close');
            button.setAttribute('commandfor', element.id);
            element.append(button);
            document.addEventListener('cancel', (event) => event.preventDefault(), { capture: true, once: true });
        });
        const leave = dialog.getByRole('button', { name: 'Leave' });

        await leave.click();

        await expect(dialog).toBeVisible();
        await expect(dialog).not.toHaveAttribute('data-exit-played');
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await leave.click();

        await expect(dialog).toBeHidden();
        await expect(dialog).toHaveAttribute('data-exit-played');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        expect(await dialog.evaluate((element: HTMLDialogElement) => element.returnValue)).toBe('leave');
    });

    test('resets aria-expanded when a form inside closes it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Edit from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'dialog-edit_profile');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Edit from elsewhere' });
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await invoker.click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');

        await dialog.evaluate((element) => {
            const form = document.createElement('form');
            form.method = 'dialog';
            element.append(form);
            form.requestSubmit();
        });

        await expect(dialog).toBeHidden();
        await expect(invoker).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('collapses the command button outside of it when it is removed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Edit from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'dialog-edit_profile');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Edit from elsewhere' });
        await invoker.click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');

        await page.locator('[data-controller="dialog"]').evaluate((element) => element.remove());

        await expect(invoker).toHaveAttribute('aria-expanded', 'false');
    });

    test('marks the command button outside of it expanded again after a move', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Edit from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'dialog-edit_profile');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Edit from elsewhere' });
        await invoker.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.locator('[data-controller="dialog"]').evaluate(async (element) => {
            const parent = element.parentNode!;
            const next = element.nextSibling;
            element.remove();
            await new Promise((resolve) => setTimeout(resolve, 50));
            parent.insertBefore(element, next);
        });

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');
    });

    test('opens once per click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const trigger = page.getByRole('button', { name: 'Open Dialog' });
        await trigger.evaluate((element) => {
            element.dataset.expandedWrites = '0';
            new MutationObserver((records) => {
                element.dataset.expandedWrites = String(Number(element.dataset.expandedWrites) + records.length);
            }).observe(element, { attributeFilter: ['aria-expanded'] });
        });

        await trigger.click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(trigger).toHaveAttribute('data-expanded-writes', '1');
    });

    test('closes with the value of its Dialog:Close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        const cancel = dialog.getByRole('button', { name: 'Cancel' });
        await cancel.evaluate((button: HTMLButtonElement) => {
            button.value = 'cancel';
        });

        await cancel.click();

        await expect(dialog).toBeHidden();
        expect(await dialog.evaluate((element: HTMLDialogElement) => element.returnValue)).toBe('cancel');
    });

    test('closes from a close button inside a form', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => {
            const form = document.createElement('form');
            form.addEventListener('submit', (event) => event.preventDefault());
            const button = document.createElement('button');
            button.textContent = 'Close from form';
            button.setAttribute('command', 'close');
            button.setAttribute('commandfor', element.id);
            button.dataset.action = 'click->dialog#close';
            form.append(button);
            element.append(form);
        });

        await dialog.getByRole('button', { name: 'Close from form' }).click();

        await expect(dialog).toBeHidden();
    });

    test('opens as modal when it was already open but not modal', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        const dialog = page.locator('[data-slot="dialog-content"]');
        await dialog.evaluate((element: HTMLDialogElement) => element.show());

        await page.getByRole('button', { name: 'Open Dialog' }).click();

        await expect(dialog).toBeVisible();
        expect(await dialog.evaluate((element) => element.matches(':modal'))).toBe(true);
        await expect(page.getByRole('button', { name: 'Open Dialog' })).toHaveAttribute('aria-expanded', 'true');
    });

    test('stays open on a click in its padding', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
        await recordExit(dialog);
        const box = (await dialog.boundingBox())!;

        await page.mouse.click(box.x + 4, box.y + 4);

        await expect(dialog).toBeVisible();
        await expect(dialog).not.toHaveAttribute('data-exit-played');
    });

    test('stays open when a press inside it is released on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dialog/default');
        await page.getByRole('button', { name: 'Open Dialog' }).click();
        const dialog = page.locator('[data-slot="dialog-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
        await recordExit(dialog);
        const box = (await dialog.boundingBox())!;

        await page.mouse.move(box.x + 4, box.y + 4);
        await page.mouse.down();
        await page.mouse.move(10, 10, { steps: 5 });
        await page.mouse.up();

        await expect(dialog).toBeVisible();
        await expect(dialog).not.toHaveAttribute('data-exit-played');
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

    test.describe('without JavaScript', () => {
        test.use({ javaScriptEnabled: false });

        test('opens from its trigger and closes from its close button', async ({ page }) => {
            await page.goto('/shadcn/dialog/default');

            await page.getByRole('button', { name: 'Open Dialog' }).click();

            const dialog = page.getByRole('dialog', { name: 'Edit profile' });
            await expect(dialog).toBeVisible();

            await dialog.getByRole('button', { name: 'Cancel' }).click();

            await expect(dialog).toBeHidden();
        });
    });
});
