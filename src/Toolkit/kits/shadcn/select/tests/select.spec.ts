import type { Locator, Page } from '@playwright/test';
import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

// `clip` rather than `hidden`, which the focus moving into the list would scroll.
async function clipSelect(select: Locator): Promise<void> {
    await select.evaluate((element) => {
        const box = element.parentElement!;
        box.style.overflow = 'clip';
        box.style.minHeight = '0';
        box.style.height = `${element.getBoundingClientRect().bottom - box.getBoundingClientRect().top + 4}px`;
    });
}

async function pinToBottom(select: Locator): Promise<void> {
    await select.evaluate((element) => {
        Object.assign(element.style, { position: 'fixed', bottom: '16px', left: '16px', width: '200px' });
    });
}

async function settle(page: Page): Promise<void> {
    await page
        .locator('[data-slot="select-content"][data-state="open"]')
        .evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
}

describeRecipe('shadcn/select', () => {
    testState('opens on click and focuses the first item', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('combobox');

            await trigger.click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();
        },
    });

    testState('opens the item-aligned popup with the selected item over the trigger', {
        example: 'align-item-with-trigger',
        state: 'item-aligned',
        act: async (page) => {
            const trigger = page.getByRole('combobox', { name: 'Item aligned' });

            await trigger.click();

            const banana = page.getByRole('option', { name: 'Banana', exact: true });
            await expect(banana).toBeFocused();
            const triggerBox = await trigger.boundingBox();
            const bananaBox = await banana.boundingBox();
            expect(Math.abs(bananaBox!.y - triggerBox!.y)).toBeLessThanOrEqual(4);
        },
    });

    testState('opens the popper popup below the trigger', {
        example: 'align-item-with-trigger',
        state: 'popper',
        act: async (page) => {
            const trigger = page.getByRole('combobox', { name: 'Popper' });

            await trigger.click();

            const listbox = page.getByRole('listbox');
            await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
            const triggerBox = await trigger.boundingBox();
            const listboxBox = await listbox.boundingBox();
            expect(listboxBox!.y).toBeGreaterThanOrEqual(triggerBox!.y + triggerBox!.height);
        },
    });

    testState('shows a scroll button when the list overflows', {
        example: 'scrollable',
        state: 'open',
        act: async (page) => {
            await page.getByRole('combobox').click();

            await expect(page.getByRole('option', { name: 'Eastern Standard Time', exact: true })).toBeFocused();
            await expect(page.locator('[data-slot="select-scroll-down-button"]')).toBeVisible();
            await expect(page.locator('[data-slot="select-scroll-up-button"]')).toBeHidden();
        },
    });

    testState('opens in right-to-left documents', {
        example: 'rtl',
        state: 'open',
        act: async (page) => {
            await page.getByRole('combobox').first().click();

            await expect(page.getByRole('option', { name: 'تفاح', exact: true })).toBeFocused();
        },
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.mouse.click(10, 500);

        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('closes on Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('selects an item with the mouse and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        const changes = page.evaluate(
            () =>
                new Promise((resolve) => {
                    document.addEventListener('select:change', (event) => resolve(event.detail), { once: true });
                })
        );
        await trigger.click();

        await page.getByRole('option', { name: 'Blueberry', exact: true }).click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveText('Blueberry');
        await expect(trigger).toHaveAttribute('data-has-value', 'true');
        expect(await changes).toEqual({ value: 'blueberry', label: 'Blueberry' });

        await trigger.click();

        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toBeFocused();
        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toHaveAttribute(
            'aria-selected',
            'true'
        );
    });

    test('opens from the keyboard on the selected item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        const trigger = page.getByRole('combobox', { name: 'Popper' });

        for (const key of ['Enter', ' ', 'ArrowDown', 'ArrowUp']) {
            await trigger.focus();
            await page.keyboard.press(key);

            await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();

            await page.keyboard.press('Escape');
            await expect(page.getByRole('listbox')).toBeHidden();
        }
    });

    test('selects the focused item with Enter or Space', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('Enter');

        await expect(trigger).toHaveText('Banana');
        await expect(trigger).toBeFocused();

        await page.keyboard.press('Enter');
        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press(' ');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveText('Blueberry');
    });

    test('moves between items with the arrow keys, Home and End, without wrapping', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/groups');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('option', { name: 'Carrot', exact: true })).toBeFocused();

        await page.keyboard.press('End');

        await expect(page.getByRole('option', { name: 'Spinach', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('option', { name: 'Spinach', exact: true })).toBeFocused();

        await page.keyboard.press('Home');

        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();
    });

    test('moves the focus to the item under the pointer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.getByRole('option', { name: 'Blueberry', exact: true }).hover();

        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('option', { name: 'Grapes', exact: true })).toBeFocused();
    });

    test('focuses the first item matching the typed letters, cycling on a repeated letter', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/select/groups');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('b');

        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();

        await page.keyboard.press('b');

        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toBeFocused();

        await page.keyboard.press('b');

        await expect(page.getByRole('option', { name: 'Broccoli', exact: true })).toBeFocused();
    });

    test('selects the item matching the letters typed on the closed trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await trigger.focus();

        await page.keyboard.press('g');

        await expect(trigger).toHaveText('Grapes');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('names the trigger after its field label', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/invalid');

        const trigger = page.getByRole('combobox', { name: 'Fruit' });

        await expect(trigger).toBeVisible();
        await expect(trigger).toHaveAccessibleDescription('Please select a fruit.');
    });

    test('points the trigger at the listbox it controls', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');

        await trigger.click();

        const listboxId = await page.getByRole('listbox').getAttribute('id');
        expect(listboxId).not.toBeNull();
        await expect(trigger).toHaveAttribute('aria-controls', listboxId!);
    });

    test('requires a value when required', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/invalid');
        const input = page.locator('[name="fruit"]');
        expect(await input.evaluate((element: HTMLInputElement) => element.checkValidity())).toBe(false);

        await page.getByRole('combobox', { name: 'Fruit' }).click();
        await page.getByRole('option', { name: 'Banana', exact: true }).click();

        await expect(input).toHaveValue('banana');
        expect(await input.evaluate((element: HTMLInputElement) => element.checkValidity())).toBe(true);
    });

    test('places the selected item of a scrolled list over the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/scrollable');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await page.getByRole('option', { name: 'Fiji Time', exact: true }).click();
        await expect(trigger).toHaveText('Fiji Time');

        await trigger.click();

        const fiji = page.getByRole('option', { name: 'Fiji Time', exact: true });
        await expect(fiji).toBeFocused();
        const triggerBox = await trigger.boundingBox();
        const fijiBox = await fiji.boundingBox();
        expect(Math.abs(fijiBox!.y - triggerBox!.y)).toBeLessThanOrEqual(4);
    });

    test('widens the popup to fit items longer than the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/scrollable');
        await page.locator('[data-slot="select"]').evaluate((select: HTMLElement) => (select.style.width = '8rem'));

        await page.getByRole('combobox').click();

        await expect(page.getByRole('option', { name: 'Eastern Standard Time', exact: true })).toBeFocused();
        const heights = await page.evaluate(() =>
            ['est', 'gmt'].map(
                (value) =>
                    document.querySelector<HTMLElement>(`[data-slot="select-item"][data-value="${value}"]`)!
                        .offsetHeight
            )
        );
        expect(heights[0]).toBe(heights[1]);
    });

    test('disables the trigger and the disabled item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/disabled');

        await expect(page.getByRole('combobox')).toBeDisabled();
        await expect(page.locator('[data-slot="select-item"][data-value="grapes"]')).toHaveAttribute(
            'aria-disabled',
            'true'
        );
    });

    test('closes the popper popup on a second click on the trigger', async ({ page, gotoExample }) => {
        // The popper popup leaves the trigger uncovered, unlike the item-aligned one.
        await gotoExample('shadcn/select/align-item-with-trigger');
        const trigger = page.getByRole('combobox', { name: 'Popper' });
        await trigger.click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('shows the item-aligned popup outside an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await clipSelect(page.locator('[data-slot="select"]'));

        await page.getByRole('combobox').click();

        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();
        expect(await isUnderPointer(page.getByRole('option', { name: 'Pineapple', exact: true }))).toBe(true);
    });

    test('shows the popper popup outside an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        await clipSelect(page.locator('[data-slot="select"]').last());

        await page.getByRole('combobox', { name: 'Popper' }).click();

        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
        expect(await isUnderPointer(page.getByRole('option', { name: 'Pineapple', exact: true }))).toBe(true);
    });

    test('lines the item-aligned popup up with the start of its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        const trigger = page.getByRole('combobox', { name: 'Item aligned' });

        await trigger.click();

        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
        await settle(page);
        const triggerBox = (await trigger.boundingBox())!;
        const listboxBox = (await page.getByRole('listbox').boundingBox())!;
        expect(Math.abs(listboxBox.x - triggerBox.x)).toBeLessThanOrEqual(1);
    });

    test('lines the item-aligned popup up with the start of its trigger in right-to-left', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/select/rtl');
        const trigger = page.getByRole('combobox').first();

        await trigger.click();

        await expect(page.getByRole('option', { name: 'تفاح', exact: true })).toBeFocused();
        await settle(page);
        const triggerBox = (await trigger.boundingBox())!;
        const listboxBox = (await page.getByRole('listbox').boundingBox())!;
        expect(Math.abs(listboxBox.x + listboxBox.width - (triggerBox.x + triggerBox.width))).toBeLessThanOrEqual(1);
    });

    test('flips the popper popup above its trigger when there is no room below', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        await pinToBottom(page.locator('[data-slot="select"]').last());
        const trigger = page.getByRole('combobox', { name: 'Popper' });

        await trigger.click();

        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
        await settle(page);
        const triggerBox = (await trigger.boundingBox())!;
        const listboxBox = (await page.getByRole('listbox').boundingBox())!;
        expect(listboxBox.y + listboxBox.height).toBeLessThanOrEqual(triggerBox.y);
        expect(listboxBox.y).toBeGreaterThanOrEqual(0);
    });

    test('hides its closed popup as soon as it connects', async ({ page, gotoExample }) => {
        // Stretches the transitions, so a closed popup would stay on screen while turning into a popover.
        await page.addInitScript(() => {
            document.addEventListener('DOMContentLoaded', () => {
                const style = document.createElement('style');
                style.textContent = '[data-slot][data-slot] { transition-duration: 10s; }';
                document.head.append(style);
            });
        });
        await gotoExample('shadcn/select/align-item-with-trigger');

        const displayed = await page.evaluate(() =>
            [...document.querySelectorAll('[data-slot="select-content"]')].map(
                (content) => getComputedStyle(content).display
            )
        );

        expect(displayed).toEqual(['none', 'none']);
    });

    test('opens the popper popup against its root, as without anchor positioning', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        const root = page.locator('[data-slot="select"]').last();
        await root.evaluate((element) => {
            element.style.paddingInlineStart = '40px';
        });

        await page.getByRole('combobox', { name: 'Popper' }).click();

        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
        await settle(page);
        const rootBox = (await root.boundingBox())!;
        const listboxBox = (await page.getByRole('listbox').boundingBox())!;
        expect(Math.abs(listboxBox.x - rootBox.x)).toBeLessThanOrEqual(1);
    });

    test('moves the focus past the select on Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.locator('[data-slot="select"]').evaluate((select) => {
            const next = document.createElement('button');
            next.textContent = 'After the select';
            select.after(next);
        });
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('button', { name: 'After the select' })).toBeFocused();
        await expect(page.getByRole('combobox')).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open while another popover opens', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.evaluate(() => {
            const other = document.createElement('div');
            other.popover = 'auto';
            other.textContent = 'Another popover';
            document.body.append(other);
            other.showPopover();
        });

        await expect(page.getByRole('combobox')).toHaveAttribute('aria-expanded', 'true');
    });

    test('closes on Escape while its trigger has the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await page.locator('[data-slot="select"]').evaluate((select) => {
            select.dataset.selectOpenValue = 'true';
            // Moving the root reconnects the controller, which opens it without moving the focus.
            select.parentElement!.append(select);
        });
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await trigger.focus();

        await page.keyboard.press('Escape');

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('stays open on a click inside its root, outside the trigger and the popup', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        const root = page.locator('[data-slot="select"]').last();
        await root.evaluate((element) => {
            const label = document.createElement('span');
            label.textContent = 'Inside the root';
            element.prepend(label);
        });
        const trigger = page.getByRole('combobox', { name: 'Popper' });
        await trigger.click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.getByText('Inside the root').click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('grows the popper popup from the edge next to its trigger once flipped above it', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        await pinToBottom(page.locator('[data-slot="select"]').last());

        await page.getByRole('combobox', { name: 'Popper' }).click();

        const listbox = page.locator('[data-slot="select-content"][data-state="open"]');
        await expect(listbox).toBeVisible();
        const origin = await listbox.evaluate((element) => ({
            y: parseFloat(getComputedStyle(element).transformOrigin.split(' ')[1]),
            height: element.offsetHeight,
        }));
        expect(Math.abs(origin.y - origin.height)).toBeLessThanOrEqual(1);
    });

    test('reflects a popup hidden by something else', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        const content = page.locator('[data-slot="select-content"]');
        await expect(content).toBeVisible();

        await content.evaluate((element) => element.hidePopover());

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.locator('[data-slot="select"]')).toHaveAttribute('data-state', 'closed');
    });

    test('opens again after a morph strips what the controller set', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const content = page.locator('[data-slot="select-content"]');
        await page.locator('[data-slot="select"]').evaluate((select) => {
            const content = select.querySelector<HTMLElement>('[data-slot="select-content"]')!;
            content.removeAttribute('popover');
            content.removeAttribute('style');
            select.removeAttribute('style');
        });

        await page.getByRole('combobox').click();

        await expect(content).toBeVisible();
        await expect(content).toHaveAttribute('popover', 'manual');
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();
    });

    test('cleans up when its content is removed, and still toggles', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const root = page.locator('[data-slot="select"]');
        await root.evaluate((element) => {
            element.querySelector('[data-slot="select-content"]')!.remove();
            // Moving the root disconnects and reconnects the controller.
            element.parentElement!.append(element);
        });
        const trigger = page.getByRole('combobox');

        await trigger.click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(root).not.toHaveAttribute('style', /anchor-name/);
    });

    test('closes once its controller disconnects', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        const content = page.locator('[data-slot="select-content"]');
        await expect(content).toBeVisible();

        const root = page.locator('[data-slot="select"]');
        await root.evaluate((element) => {
            element.removeAttribute('data-controller');
        });

        await expect(content).toBeHidden();
        await expect(root).toHaveAttribute('data-state', 'closed');
    });

    test.describe('without anchor positioning', () => {
        test.beforeEach(async ({ page }) => {
            await page.addInitScript(() => {
                const supports = CSS.supports.bind(CSS);
                CSS.supports = (...args: [string, string?]) =>
                    args[0].startsWith('position-area') ? false : supports(...(args as [string]));
            });
        });

        test('opens in place and closes on Escape or on a click outside', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/select/default');
            const trigger = page.getByRole('combobox');
            const listbox = page.getByRole('listbox');

            await trigger.click();

            await expect(listbox).toBeVisible();
            await expect(listbox).not.toHaveAttribute('popover');
            await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

            await page.keyboard.press('Escape');

            await expect(listbox).toBeHidden();
            await expect(trigger).toBeFocused();

            await trigger.click();
            await expect(listbox).toBeVisible();

            await page.mouse.click(10, 10);

            await expect(listbox).toBeHidden();
            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        });

        test('closes on Escape while its trigger has the focus', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/select/default');
            const trigger = page.getByRole('combobox');
            await page.locator('[data-slot="select"]').evaluate((select) => {
                select.dataset.selectOpenValue = 'true';
                select.parentElement!.append(select);
            });
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await trigger.focus();

            await page.keyboard.press('Escape');

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        });

        test('clips the popup in an ancestor that hides its overflow', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/select/default');
            await clipSelect(page.locator('[data-slot="select"]'));

            await page.getByRole('combobox').click();

            await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();
            expect(await isUnderPointer(page.getByRole('option', { name: 'Pineapple', exact: true }))).toBe(false);
        });
    });
});
