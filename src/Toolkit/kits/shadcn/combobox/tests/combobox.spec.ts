import type { Locator, Page } from '@playwright/test';
import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

async function clipCombobox(combobox: Locator): Promise<void> {
    await combobox.evaluate((element) => {
        const box = element.parentElement!;
        box.style.overflow = 'clip';
        box.style.minHeight = '0';
        box.style.height = `${element.getBoundingClientRect().bottom - box.getBoundingClientRect().top + 4}px`;
    });
}

async function pinToViewportBottom(combobox: Locator): Promise<void> {
    await combobox.evaluate((element) => {
        Object.assign(element.style, { position: 'fixed', bottom: '16px', left: '16px', width: '240px' });
    });
}

async function settle(page: Page): Promise<void> {
    await page
        .locator('[data-slot="combobox-content"][data-state="open"]')
        .evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
}

const GAP = 6;
const placements = [
    { side: 'top', align: 'start' },
    { side: 'top', align: 'center' },
    { side: 'top', align: 'end' },
    { side: 'left', align: 'center' },
    { side: 'right', align: 'center' },
    { side: 'bottom', align: 'start' },
    { side: 'bottom', align: 'center' },
    { side: 'bottom', align: 'end' },
];

function testPlacements(): void {
    for (const { side, align } of placements) {
        test(`opens on the ${side} of its input, aligned to its ${align}, with the width it is given`, async ({
            page,
            gotoExample,
        }) => {
            await gotoExample('shadcn/combobox/side-and-alignment');
            const combobox = page.locator(`[data-slot="combobox"]:has(#combobox-framework-${side}-${align}-content)`);
            // Away from every edge of the viewport, so the list opens on the side it is given.
            await combobox.evaluate((element) => {
                Object.assign(element.style, {
                    position: 'fixed',
                    top: '280px',
                    left: '320px',
                    width: '120px',
                    zIndex: '100',
                });
            });

            await combobox.getByRole('combobox').click();

            await expect(combobox.getByRole('listbox')).toBeVisible();
            await settle(page);
            const anchor = (await combobox.boundingBox())!;
            const content = (await combobox.locator('[data-slot="combobox-content"]').boundingBox())!;
            expect(content.width).toBeCloseTo(160, 0);
            if ('top' === side || 'bottom' === side) {
                const y = 'top' === side ? anchor.y - GAP - content.height : anchor.y + anchor.height + GAP;
                const x = {
                    start: anchor.x,
                    center: anchor.x + (anchor.width - content.width) / 2,
                    end: anchor.x + anchor.width - content.width,
                }[align]!;
                expect(content.y).toBeCloseTo(y, 0);
                expect(content.x).toBeCloseTo(x, 0);
            } else {
                const x = 'left' === side ? anchor.x - GAP - content.width : anchor.x + anchor.width + GAP;
                expect(content.x).toBeCloseTo(x, 0);
                expect(content.y + content.height / 2).toBeCloseTo(anchor.y + anchor.height / 2, 0);
            }
        });
    }
}

describeRecipe('shadcn/combobox', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const input = page.getByRole('combobox');

            await input.click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(input).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('option')).toHaveCount(5);
        },
    });

    testState('filters the items as you type', {
        example: 'default',
        state: 'filtered',
        act: async (page) => {
            const input = page.getByRole('combobox');
            await input.click();

            await input.fill('xt');

            await expect(page.getByRole('option')).toHaveText(['Next.js', 'Nuxt.js']);
            await expect(input).not.toHaveAttribute('aria-activedescendant');
        },
    });

    testState('shows the empty message when nothing matches', {
        example: 'default',
        state: 'empty',
        act: async (page) => {
            const input = page.getByRole('combobox');
            await input.click();

            await input.fill('angular');

            await expect(page.getByText('No items found.')).toBeVisible();
            await expect(page.getByRole('option')).toHaveCount(0);
        },
    });

    test('does not open on focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');

        await input.focus();

        await expect(input).toBeFocused();
        await expect(input).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('selects an item with the mouse', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();

        await page.getByRole('option', { name: 'Remix' }).click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('Remix');
        await expect(input).toHaveAttribute('aria-expanded', 'false');
        await expect(input).toBeFocused();
    });

    test('moves through the items with the keyboard and selects with Enter', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        const itemId = (index: number) => `combobox-framework-demo-list-item-${index}`;
        await input.focus();

        await input.press('ArrowDown');

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(0));

        await input.press('ArrowDown');
        await input.press('ArrowDown');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(2));

        await input.press('ArrowUp');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(1));

        await input.press('End');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(4));

        await input.press('ArrowDown');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(4));

        await input.press('Home');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(0));

        await input.press('ArrowDown');
        await input.press('Enter');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('SvelteKit');
        await expect(input).toBeFocused();
        await expect(input).not.toHaveAttribute('aria-activedescendant');
    });

    test('opens on the last item with ArrowUp', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.focus();

        await input.press('ArrowUp');

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(input).toHaveAttribute('aria-activedescendant', 'combobox-framework-demo-list-item-4');
    });

    test('highlights the first match while filtering with autoHighlight', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/auto-highlight');
        const input = page.getByRole('combobox');
        await input.click();

        await input.fill('r');

        await expect(page.getByRole('option')).toHaveText(['Remix', 'Astro']);
        await expect(input).toHaveAttribute('aria-activedescendant', 'combobox-framework-auto-highlight-list-item-3');

        await input.press('Enter');

        await expect(input).toHaveValue('Remix');
    });

    test('clears the typed text on Escape', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();
        await input.fill('zzz');

        await input.press('Escape');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('');
        await expect(input).toBeFocused();
    });

    test('restores the selection when it closes without a new choice', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/clear-button');
        const input = page.getByRole('combobox');
        await input.click();
        await input.fill('zzz');

        await page.mouse.click(700, 500);

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('Next.js');
    });

    test('closes on a click on the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('button', { name: 'Toggle the list' });
        await trigger.click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.mouse.click(700, 500);

        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('resets the filter when it opens again', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();
        await input.fill('remix');
        await expect(page.getByRole('option')).toHaveCount(1);
        await input.press('Escape');

        await input.click();

        await expect(page.getByRole('option')).toHaveCount(5);
    });

    test('hides a group when none of its items match', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/groups');
        const input = page.getByRole('combobox');
        await input.click();
        await expect(page.getByRole('group', { name: /Americas|Europe|Asia/ })).toHaveCount(3);

        await input.fill('paris');

        await expect(page.getByRole('group', { name: /Americas|Europe|Asia/ })).toHaveCount(1);
        await expect(page.getByRole('group', { name: 'Europe' })).toBeVisible();
        await expect(page.getByRole('option')).toHaveText(['(GMT+1) Paris']);
    });

    testState('marks the selected item', {
        example: 'clear-button',
        state: 'open',
        act: async (page) => {
            await page.getByRole('combobox').click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(page.getByRole('option', { selected: true })).toHaveText('Next.js');
        },
    });

    test('clears the selection with the clear button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/clear-button');
        const input = page.getByRole('combobox');
        const clear = page.getByRole('button', { name: 'Clear selection' });
        const trigger = page.getByRole('button', { name: 'Toggle the list' });
        const hiddenInput = page.locator('input[type="hidden"][name="framework"]');
        await expect(input).toHaveValue('Next.js');
        await expect(hiddenInput).toHaveValue('Next.js');
        await expect(clear).toBeVisible();
        await expect(trigger).toBeHidden();

        await clear.click();

        await expect(input).toHaveValue('');
        await expect(hiddenInput).toHaveValue('');
        await expect(clear).toBeHidden();
        await expect(trigger).toBeVisible();
        await expect(input).toBeFocused();
    });

    test('does not open when disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/disabled');
        const input = page.getByRole('combobox');

        await input.click({ force: true });

        await expect(input).toBeDisabled();
        await expect(input).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('lines the list up with its input', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const content = page.locator('[data-slot="combobox-content"]');
        const groupBox = await page.locator('[data-slot="input-group"]').boundingBox();

        await page.getByRole('combobox').click();

        // The content grows from 95% while it opens.
        await expect.poll(async () => (await content.boundingBox())?.width).toBeCloseTo(groupBox?.width ?? 0, 0);
        expect((await content.boundingBox())?.x).toBeCloseTo(groupBox?.x ?? 0, 0);
    });

    testState('adds the selected items as chips', {
        example: 'multiple',
        state: 'selected',
        act: async (page) => {
            await page.getByRole('combobox').click();

            await page.getByRole('option', { name: 'Remix' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(page.locator('[data-slot="combobox-chips"] [data-slot="combobox-chip"]')).toHaveText([
                'Next.js',
                'Remix',
            ]);
            await expect(page.getByRole('option', { selected: true })).toHaveText(['Next.js', 'Remix']);
            await expect(page.locator('input[type="hidden"][name="frameworks[]"]')).toHaveCount(2);
        },
    });

    test('removes a chip with its button and with Backspace', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/multiple');
        const input = page.getByRole('combobox');
        const chips = page.locator('[data-slot="combobox-chips"] [data-slot="combobox-chip"]');
        await input.click();
        await page.getByRole('option', { name: 'Astro' }).click();
        await expect(chips).toHaveText(['Next.js', 'Astro']);

        await page.getByRole('button', { name: 'Remove' }).first().click();

        await expect(chips).toHaveText(['Astro']);
        await expect(input).toBeFocused();

        await input.press('Backspace');

        await expect(chips).toHaveCount(0);
        await expect(page.locator('input[type="hidden"][name="frameworks[]"]')).toHaveCount(0);
    });

    testState('opens from a button', {
        example: 'popup',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Select country' });

            await trigger.click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('combobox')).toBeFocused();
        },
    });

    test('gives the focus back to the button of a popup', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/popup');
        const search = page.getByRole('combobox');
        await page.getByRole('button', { name: 'Select country' }).click();
        await expect(search).toBeFocused();

        await search.fill('fr');
        await search.press('ArrowDown');
        await search.press('Enter');

        const trigger = page.getByRole('button', { name: 'France' });
        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toBeFocused();

        await trigger.click();
        await expect(search).toBeFocused();
        await expect(search).toHaveValue('');

        await search.press('Escape');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('stays open on a click in its input', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await input.click();

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(input).toHaveAttribute('aria-expanded', 'true');
        await expect(input).toBeFocused();
    });

    test('shows the list outside an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        await clipCombobox(page.locator('[data-slot="combobox"]'));

        await page.getByRole('combobox').click();

        await expect(page.getByRole('listbox')).toBeVisible();
        await settle(page);
        expect(await isUnderPointer(page.getByRole('option', { name: 'Astro' }))).toBe(true);

        await page.getByRole('option', { name: 'Astro' }).click();

        await expect(page.getByRole('combobox')).toHaveValue('Astro');
    });

    test('lines the list up with its input in right-to-left', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/rtl');
        const chips = page.locator('[data-slot="combobox-chips"]').first();

        await page.getByRole('combobox').first().click();

        await expect(page.getByRole('listbox').first()).toBeVisible();
        await settle(page);
        const chipsBox = (await chips.boundingBox())!;
        const contentBox = (await page.locator('[data-slot="combobox-content"]').first().boundingBox())!;
        expect(Math.abs(contentBox.x + contentBox.width - (chipsBox.x + chipsBox.width))).toBeLessThanOrEqual(1);
        expect(Math.abs(contentBox.width - chipsBox.width)).toBeLessThanOrEqual(1);
    });

    test('flips the list above its input when there is no room below', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        await pinToViewportBottom(page.locator('[data-slot="combobox"]'));
        const input = page.getByRole('combobox');

        await input.click();

        await expect(page.getByRole('listbox')).toBeVisible();
        await settle(page);
        const inputBox = (await page.locator('[data-slot="input-group"]').boundingBox())!;
        const contentBox = (await page.locator('[data-slot="combobox-content"]').boundingBox())!;
        expect(contentBox.y + contentBox.height).toBeLessThanOrEqual(inputBox.y);
        expect(contentBox.y).toBeGreaterThanOrEqual(0);
    });

    test('hides its closed list as soon as it connects', async ({ page, gotoExample }) => {
        // Stretches the transitions, so a closed list would stay on screen while turning into a popover.
        await page.addInitScript(() => {
            document.addEventListener('DOMContentLoaded', () => {
                const style = document.createElement('style');
                style.textContent = '[data-slot][data-slot] { transition-duration: 10s; }';
                document.head.append(style);
            });
        });
        await gotoExample('shadcn/combobox/default');

        await expect(page.locator('[data-slot="combobox-content"]')).toHaveCSS('display', 'none');
    });

    test('moves the focus past the combobox on Tab from the input of its popup', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/popup');
        await page.locator('[data-slot="combobox"]').evaluate((combobox) => {
            const next = document.createElement('button');
            next.textContent = 'After the combobox';
            combobox.after(next);
        });
        const trigger = page.getByRole('button', { name: 'Select country' });
        await trigger.click();
        await expect(page.getByRole('combobox')).toBeFocused();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('button', { name: 'After the combobox' })).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on Escape while the trigger of its popup has the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/popup');
        const trigger = page.getByRole('button', { name: 'Select country' });
        await trigger.click();
        await expect(page.getByRole('listbox')).toBeVisible();
        await trigger.focus();

        await page.keyboard.press('Escape');

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('stays open on a click inside its root, outside the input', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        await page.locator('[data-slot="combobox"]').evaluate((combobox) => {
            const label = document.createElement('span');
            label.textContent = 'Inside the root';
            combobox.prepend(label);
        });
        const input = page.getByRole('combobox');
        await input.click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.getByText('Inside the root').click();

        await expect(input).toHaveAttribute('aria-expanded', 'true');
    });

    test('stays open while another popover opens', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.evaluate(() => {
            const other = document.createElement('div');
            other.popover = 'auto';
            other.textContent = 'Another popover';
            document.body.append(other);
            other.showPopover();
        });

        await expect(input).toHaveAttribute('aria-expanded', 'true');
    });

    test('grows the list from the edge next to its input once flipped above it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        await pinToViewportBottom(page.locator('[data-slot="combobox"]'));

        await page.getByRole('combobox').click();

        const content = page.locator('[data-slot="combobox-content"]');
        await expect(content).toBeVisible();
        const origin = await content.evaluate((element) => ({
            y: parseFloat(getComputedStyle(element).transformOrigin.split(' ')[1]),
            height: element.offsetHeight,
        }));
        expect(Math.abs(origin.y - origin.height)).toBeLessThanOrEqual(1);
    });

    test('reflects a list hidden by something else', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();
        const content = page.locator('[data-slot="combobox-content"]');
        await expect(content).toBeVisible();

        // Read before the next task, so the state has changed in time for the exit transition.
        const stateRightAfter = await content.evaluate(async (element) => {
            element.hidePopover();
            await Promise.resolve();

            return element.closest<HTMLElement>('[data-slot="combobox"]')!.dataset.state;
        });

        expect(stateRightAfter).toBe('closed');
        await expect(input).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens again after a morph strips what the controller set', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const content = page.locator('[data-slot="combobox-content"]');
        await page.locator('[data-slot="combobox"]').evaluate((combobox) => {
            const content = combobox.querySelector<HTMLElement>('[data-slot="combobox-content"]')!;
            content.removeAttribute('popover');
            content.removeAttribute('style');
            combobox.removeAttribute('style');
        });

        await page.getByRole('combobox').click();

        await expect(content).toBeVisible();
        await expect(content).toHaveAttribute('popover', 'manual');
    });

    test('cleans up when its content is removed, and still toggles', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const root = page.locator('[data-slot="combobox"]');
        await root.evaluate((element) => {
            element.querySelector('[data-slot="combobox-content"]')!.remove();
            // Moving the root disconnects and reconnects the controller.
            element.parentElement!.append(element);
        });
        const input = page.getByRole('combobox');

        await input.click();

        await expect(input).toHaveAttribute('aria-expanded', 'true');
        await expect(root).not.toHaveAttribute('style', /anchor-name/);
    });

    test('closes once its controller disconnects', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        await page.getByRole('combobox').click();
        const content = page.locator('[data-slot="combobox-content"]');
        await expect(content).toBeVisible();

        const root = page.locator('[data-slot="combobox"]');
        await root.evaluate((element) => {
            element.removeAttribute('data-controller');
        });

        await expect(content).toBeHidden();
        await expect(root).toHaveAttribute('data-state', 'closed');
    });

    testPlacements();

    test.describe('without anchor positioning', () => {
        test.beforeEach(async ({ page }) => {
            await page.addInitScript(() => {
                const supports = CSS.supports.bind(CSS);
                CSS.supports = (...args: [string, string?]) =>
                    args[0].startsWith('position-area') ? false : supports(...(args as [string]));
            });
        });

        test('opens in place and closes on Escape or on a click outside', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/combobox/default');
            const input = page.getByRole('combobox');
            const listbox = page.getByRole('listbox');

            await input.click();

            await expect(listbox).toBeVisible();
            await expect(page.locator('[data-slot="combobox-content"]')).not.toHaveAttribute('popover');

            await input.press('Escape');

            await expect(listbox).toBeHidden();
            await expect(input).toBeFocused();

            await input.click();
            await expect(listbox).toBeVisible();

            await page.mouse.click(700, 500);

            await expect(listbox).toBeHidden();
            await expect(input).toHaveAttribute('aria-expanded', 'false');
        });

        test('clips the list in an ancestor that hides its overflow', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/combobox/default');
            await clipCombobox(page.locator('[data-slot="combobox"]'));

            await page.getByRole('combobox').click();

            await expect(page.locator('[data-slot="combobox-content"]')).toHaveAttribute('data-state', 'open');
            await settle(page);
            expect(await isUnderPointer(page.getByRole('option', { name: 'Astro' }))).toBe(false);
        });

        testPlacements();
    });
});
