import type { Locator, Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const trigger = (page: Page) => page.locator('[data-slot="context-menu-trigger"]').first();
const menu = (page: Page) => page.getByRole('menu').first();

async function rightClick(locator: Locator, position = { x: 40, y: 30 }) {
    await locator.click({ button: 'right', position });
}

describeRecipe('shadcn/context-menu', () => {
    testState('opens on right click at the pointer', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await rightClick(trigger(page));

            await expect(menu(page)).toBeVisible();
            const box = await menu(page).boundingBox();
            const triggerBox = await trigger(page).boundingBox();
            expect(Math.abs(box!.x - (triggerBox!.x + 40))).toBeLessThan(2);
            expect(Math.abs(box!.y - (triggerBox!.y + 30))).toBeLessThan(2);
        },
    });

    test('moves to the new pointer position on a second right click', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');
        await rightClick(trigger(page), { x: 40, y: 30 });
        await expect(menu(page)).toBeVisible();

        await rightClick(trigger(page), { x: 230, y: 150 });

        await expect(menu(page)).toBeVisible();
        const box = await menu(page).boundingBox();
        const triggerBox = await trigger(page).boundingBox();
        expect(Math.abs(box!.x - (triggerBox!.x + 230))).toBeLessThan(2);
        expect(Math.abs(box!.y - (triggerBox!.y + 150))).toBeLessThan(2);
    });

    test('does not open on a left click', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');

        await trigger(page).click();

        await expect(menu(page)).toBeHidden();
    });

    test('opens from the keyboard with the context menu key', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');
        await trigger(page).evaluate((element) => element.setAttribute('tabindex', '0'));
        await trigger(page).focus();

        await page.keyboard.press('Shift+F10');

        await expect(menu(page)).toBeVisible();
        await expect(menu(page)).toBeFocused();
    });

    test('closes on Escape', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');
        await rightClick(trigger(page));
        await expect(menu(page)).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(menu(page)).toBeHidden();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');
        await rightClick(trigger(page));
        await expect(menu(page)).toBeVisible();

        await page.mouse.click(5, 5);

        await expect(menu(page)).toBeHidden();
    });

    test('closes on Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');
        await rightClick(trigger(page));
        await expect(menu(page)).toBeFocused();

        await page.keyboard.press('Tab');

        await expect(menu(page)).toBeHidden();
    });

    test('closes when an item is selected', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');
        await rightClick(trigger(page));

        await page.getByRole('menuitem', { name: 'Reload' }).click();

        await expect(menu(page)).toBeHidden();
    });

    test('does not show the native context menu on the menu itself', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');
        await rightClick(trigger(page));
        const prevented = await menu(page).evaluate(
            (element) => !element.dispatchEvent(new MouseEvent('contextmenu', { bubbles: true, cancelable: true }))
        );

        expect(prevented).toBe(true);
        await expect(menu(page)).toBeVisible();
    });

    test('moves between items with the arrow keys, Home and End, skipping the disabled one', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/context-menu/basic');
        await rightClick(trigger(page));
        await expect(menu(page)).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Back' })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Reload' })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Back' })).toBeFocused();

        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('menuitem', { name: 'Reload' })).toBeFocused();

        await page.keyboard.press('Home');

        await expect(page.getByRole('menuitem', { name: 'Back' })).toBeFocused();

        await page.keyboard.press('End');

        await expect(page.getByRole('menuitem', { name: 'Reload' })).toBeFocused();
    });

    test('marks the disabled item as disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic');

        await rightClick(trigger(page));

        await expect(page.getByRole('menuitem', { name: 'Forward' })).toHaveAttribute('aria-disabled', 'true');
    });

    test('keeps the menu inside the viewport near the bottom right corner', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 400, height: 240 });
        await gotoExample('shadcn/context-menu/basic');
        const triggerBox = (await trigger(page).boundingBox())!;

        await rightClick(trigger(page), { x: triggerBox.width - 6, y: triggerBox.height - 6 });

        await expect(menu(page)).toBeVisible();
        const box = (await menu(page).boundingBox())!;
        expect(box.x).toBeGreaterThanOrEqual(0);
        expect(box.y).toBeGreaterThanOrEqual(0);
        expect(box.x + box.width).toBeLessThanOrEqual(400);
        expect(box.y + box.height).toBeLessThanOrEqual(240);
        expect(box.x + box.width).toBeLessThanOrEqual(triggerBox.x + triggerBox.width);
    });

    test('toggles a checkbox item and keeps the menu open', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/checkboxes');
        await rightClick(trigger(page));
        const urls = page.getByRole('menuitemcheckbox', { name: 'Show Full URLs' });
        const bookmarks = page.getByRole('menuitemcheckbox', { name: 'Show Bookmarks Bar' });
        await expect(urls).not.toBeChecked();
        await expect(bookmarks).toBeChecked();

        await urls.click();
        await bookmarks.click();

        await expect(urls).toBeChecked();
        await expect(bookmarks).not.toBeChecked();
        await expect(menu(page)).toBeVisible();
    });

    testState('selects a radio item and keeps the menu open', {
        example: 'radio',
        state: 'colm-selected',
        act: async (page) => {
            await rightClick(trigger(page), { x: 20, y: 20 });
            const colm = page.getByRole('menuitemradio', { name: 'Colm Tuite' });
            await expect(colm).toBeVisible();

            await colm.click();
            await page.mouse.move(0, 0);

            await expect(colm).toBeChecked();
            await expect(page.getByRole('menuitemradio', { name: 'Pedro Duarte' })).not.toBeChecked();
            await expect(page.getByRole('menuitemradio', { name: 'Light' })).toBeChecked();
            await expect(menu(page)).toBeVisible();
        },
    });

    testState('opens a submenu on hover', {
        example: 'submenu',
        state: 'submenu-open',
        act: async (page) => {
            await rightClick(trigger(page), { x: 20, y: 20 });
            const tools = page.getByRole('menuitem', { name: 'More Tools' });
            await expect(tools).toBeVisible();

            await tools.hover();

            await expect(page.getByRole('menuitem', { name: 'Save Page...' })).toBeVisible();
            await expect(tools).toHaveAttribute('data-state', 'open');
        },
    });

    test('opens a submenu when its trigger gets keyboard focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/submenu');
        await rightClick(trigger(page));
        await expect(menu(page)).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'More Tools' })).toBeFocused();
        await expect(page.getByRole('menuitem', { name: 'Save Page...' })).toBeVisible();
    });

    test('marks the destructive item with its variant', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/destructive');

        await rightClick(trigger(page));

        await expect(page.getByRole('menuitem', { name: 'Delete' })).toHaveAttribute('data-variant', 'destructive');
        await expect(page.getByRole('menuitem', { name: 'Edit' })).toHaveAttribute('data-variant', 'default');
    });

    test('opens the menu on each side of the pointer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/sides');
        const triggers = page.locator('[data-slot="context-menu-trigger"]');

        for (const [index, side] of ['top', 'right', 'bottom', 'left'].entries()) {
            const target = triggers.nth(index);
            const targetBox = (await target.boundingBox())!;
            const point = {
                x: side === 'left' ? targetBox.width - 6 : 60,
                y: side === 'top' ? targetBox.height - 6 : 40,
            };
            await rightClick(target, point);

            const content = page.locator(`[data-slot="context-menu-content"][data-side="${side}"]`);
            await expect(content).toBeVisible();
            const box = (await content.boundingBox())!;
            const x = targetBox.x + point.x;
            const y = targetBox.y + point.y;
            if (side === 'top') expect(Math.abs(box.y + box.height - y)).toBeLessThan(2);
            if (side === 'bottom') expect(Math.abs(box.y - y)).toBeLessThan(2);
            if (side === 'left') expect(Math.abs(box.x + box.width - x)).toBeLessThan(2);
            if (side === 'right') expect(Math.abs(box.x - x)).toBeLessThan(2);

            await page.keyboard.press('Escape');
            await expect(content).toBeHidden();
        }
    });

    test('opens on a long press with a touch pointer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/basic', { timers: 'real' });
        const box = (await trigger(page).boundingBox())!;

        await trigger(page).evaluate(
            (element, point) => {
                element.dispatchEvent(
                    new PointerEvent('pointerdown', {
                        bubbles: true,
                        pointerType: 'touch',
                        clientX: point.x,
                        clientY: point.y,
                    })
                );
            },
            { x: box.x + 30, y: box.y + 30 }
        );

        await expect(menu(page)).toBeVisible();
    });

    test('does not open when the touch pointer is released before the long press delay', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/context-menu/basic', { timers: 'real' });

        await trigger(page).evaluate((element) => {
            element.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true, pointerType: 'touch' }));
            element.dispatchEvent(new PointerEvent('pointerup', { bubbles: true, pointerType: 'touch' }));
        });
        await page.waitForTimeout(900);

        await expect(menu(page)).toBeHidden();
    });

    test('opens a submenu on the left in RTL', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/context-menu/rtl');
        await rightClick(trigger(page), { x: 200, y: 20 });
        const moreTools = page.getByRole('menuitem', { name: 'المزيد من الأدوات' });

        await moreTools.hover();

        const save = page.getByRole('menuitem', { name: 'حفظ الصفحة...' });
        await expect(save).toBeVisible();
        const moreToolsBox = await moreTools.boundingBox();
        const saveBox = await save.boundingBox();
        expect(saveBox!.x + saveBox!.width).toBeLessThan(moreToolsBox!.x);
    });
});
