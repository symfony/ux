import type { Page } from '@playwright/test';
import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

// `clip` rather than `hidden`, which the focus moving into the menu would scroll.
async function clipMenu(page: Page): Promise<void> {
    await page
        .locator('[data-slot="dropdown-menu"]')
        .first()
        .evaluate((menu) => {
            const box = menu.parentElement!;
            box.style.overflow = 'clip';
            box.style.minHeight = '0';
            box.style.height = `${menu.getBoundingClientRect().bottom - box.getBoundingClientRect().top + 4}px`;
        });
}

async function pinMenu(
    page: Page,
    corner: { bottom?: string; right?: string; left?: string; top?: string }
): Promise<void> {
    await page
        .locator('[data-slot="dropdown-menu"]')
        .first()
        .evaluate((menu, corner) => {
            Object.assign(menu.style, { position: 'fixed' }, corner);
        }, corner);
}

async function settle(page: Page, selector: string): Promise<void> {
    await page
        .locator(selector)
        .first()
        .evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
}

describeRecipe('shadcn/dropdown-menu', () => {
    testState('opens on click and focuses the first item', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open' });

            await trigger.click();

            await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();
        },
    });

    test('closes on a second click on the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
    });

    test('closes on Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
    });

    test('closes when an item is selected and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'Billing' }).click();

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('opens from the keyboard on the first or the last item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });

        await trigger.press('ArrowDown');

        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('Escape');
        await trigger.press('ArrowUp');

        await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeFocused();
    });

    test('moves between items with the arrow keys, Home and End, skipping the disabled one', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Billing' })).toBeFocused();

        await page.keyboard.press('ArrowUp');
        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeFocused();

        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('menuitem', { name: 'New Team' })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('End');

        await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeFocused();

        await page.keyboard.press('Home');

        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();
    });

    test('marks the disabled item as disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');

        await page.getByRole('button', { name: 'Open' }).click();

        await expect(page.getByRole('menuitem', { name: 'API' })).toHaveAttribute('aria-disabled', 'true');
    });

    test('toggles a checkbox item and keeps the menu open', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/checkboxes');
        await page.getByRole('button', { name: 'Open' }).click();
        const panel = page.getByRole('menuitemcheckbox', { name: 'Panel' });
        const statusBar = page.getByRole('menuitemcheckbox', { name: 'Status Bar' });
        await expect(panel).not.toBeChecked();
        await expect(statusBar).toBeChecked();

        await panel.click();
        await statusBar.click();

        await expect(panel).toBeChecked();
        await expect(statusBar).not.toBeChecked();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
        await expect(page.getByRole('menuitemcheckbox', { name: 'Full Screen' })).toHaveAttribute(
            'aria-disabled',
            'true'
        );
    });

    testState('selects a radio item and keeps the menu open', {
        example: 'radio-group',
        state: 'top-selected',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open' }).click();
            const top = page.getByRole('menuitemradio', { name: 'Top' });
            await expect(top).toBeVisible();

            await top.click();
            await page.mouse.move(0, 0);

            await expect(top).toBeChecked();
            await expect(page.getByRole('menuitemradio', { name: 'Bottom' })).not.toBeChecked();
            await expect(page.getByRole('menuitemradio', { name: 'Right' })).not.toBeChecked();
            await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
        },
    });

    testState('shows icons, shortcuts and a destructive item', {
        example: 'complex',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Complex Menu' }).click();

            await expect(page.getByRole('menuitem', { name: 'New File' })).toBeFocused();
            await expect(page.getByRole('menuitem', { name: 'Sign Out' })).toBeVisible();
            await expect(page.getByRole('menuitem', { name: 'Sign Out' })).toHaveAttribute(
                'data-variant',
                'destructive'
            );
        },
    });

    testState('opens a nested radio group in a submenu', {
        example: 'complex',
        state: 'theme-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Complex Menu' }).click();
            await expect(page.getByRole('menuitem', { name: 'New File' })).toBeFocused();
            const theme = page.getByRole('menuitem', { name: 'Theme' });
            await settle(page, '[data-slot="dropdown-menu-content"]');

            await theme.hover();

            const light = page.getByRole('menuitemradio', { name: 'Light' });
            await expect(theme).toHaveAttribute('data-state', 'open');
            await expect.poll(() => isUnderPointer(light)).toBe(true);
            await expect(light).toBeChecked();
        },
    });

    testState('opens the destructive example', {
        example: 'destructive',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Actions' }).click();

            await expect(page.getByRole('menuitem', { name: 'Delete' })).toBeVisible();
        },
    });

    testState('opens the menu aligned to the end of an avatar trigger', {
        example: 'avatar',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'shadcn' }).click();

            await expect(page.getByRole('menuitem', { name: 'Sign Out' })).toBeVisible();
        },
    });

    testState('opens a submenu on hover', {
        example: 'submenus',
        state: 'submenu-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open' }).click();
            const share = page.getByRole('menuitem', { name: 'Share' });
            await expect(share).toBeVisible();

            await share.hover();

            const copyLink = page.getByRole('menuitem', { name: 'Copy link' });
            await expect(copyLink).toBeVisible();
            await expect(share).toHaveAttribute('data-state', 'open');
            await expect.poll(() => isUnderPointer(copyLink)).toBe(true);
        },
    });

    test('opens a nested submenu on hover and closes it when the pointer leaves', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        await page.getByRole('menuitem', { name: 'Share' }).hover();

        await page.getByRole('menuitem', { name: 'More options' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Messages' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'New Tab' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Messages' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeHidden();
    });

    test('opens a submenu when its trigger gets keyboard focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Share' })).toBeFocused();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeVisible();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Print' })).toBeFocused();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeHidden();
    });

    test('opens a submenu on the left in RTL', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/rtl');
        await page.getByRole('button', { name: 'افتح القائمة' }).click();
        const share = page.getByRole('menuitem', { name: 'مشاركة' });

        await share.hover();

        const email = page.getByRole('menuitem', { name: 'بريد إلكتروني' });
        await expect(email).toBeVisible();
        const shareBox = await share.boundingBox();
        const emailBox = await email.boundingBox();
        expect(emailBox!.x + emailBox!.width).toBeLessThan(shareBox!.x);
    });

    test('closes the menu and opens the dialog from the same item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/with-dialog');
        await page.getByRole('button', { name: 'Open' }).click();

        await page.getByRole('menuitem', { name: 'Invite users' }).click();

        await expect(page.getByRole('dialog', { name: 'Invite team members' })).toBeVisible();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
    });

    test('is not clipped by an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await clipMenu(page);

        await page.getByRole('button', { name: 'Open' }).click();

        const menu = page.getByRole('menu', { name: 'Open' });
        await expect(menu).toBeVisible();
        expect(await isUnderPointer(menu)).toBe(true);
    });

    test('shows a submenu outside a menu that clips its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page
            .locator('[data-slot="dropdown-menu-content"]')
            .first()
            .evaluate((menu) => {
                menu.style.overflow = 'clip';
            });
        await page.getByRole('button', { name: 'Open' }).click();

        await page.getByRole('menuitem', { name: 'Share' }).hover();

        const copyLink = page.getByRole('menuitem', { name: 'Copy link' });
        await expect(copyLink).toBeVisible();
        await expect.poll(() => isUnderPointer(copyLink)).toBe(true);
    });

    test('opens below its trigger, aligned to its start', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });

        await trigger.click();

        const menu = page.getByRole('menu', { name: 'Open' });
        await expect(menu).toBeVisible();
        await settle(page, '[data-slot="dropdown-menu-content"]');
        const triggerBox = (await trigger.boundingBox())!;
        const menuBox = (await menu.boundingBox())!;
        expect(Math.abs(menuBox.y - (triggerBox.y + triggerBox.height + 8))).toBeLessThanOrEqual(1);
        expect(Math.abs(menuBox.x - triggerBox.x)).toBeLessThanOrEqual(1);
    });

    test('stays below the end of a root pinned to the viewport while the page scrolls', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/dropdown-menu/avatar');
        await pinMenu(page, { top: '16px', right: '16px' });
        await page.evaluate(() => {
            const spacer = document.createElement('div');
            spacer.style.height = '3000px';
            document.body.append(spacer);
        });
        const root = page.locator('[data-slot="dropdown-menu"]');
        await page.getByRole('button', { name: 'shadcn' }).click();
        const menu = page.getByRole('menu', { name: 'shadcn' });
        await expect(menu).toBeVisible();
        await settle(page, '[data-slot="dropdown-menu-content"]');

        const scrollY = await page.evaluate(() => {
            window.scrollBy(0, 200);

            return window.scrollY;
        });

        expect(scrollY).toBe(200);
        await expect(menu).toBeVisible();
        const rootBox = (await root.boundingBox())!;
        const menuBox = (await menu.boundingBox())!;
        expect(Math.abs(menuBox.y - (rootBox.y + rootBox.height + 8))).toBeLessThanOrEqual(1);
        expect(Math.abs(menuBox.x + menuBox.width - (rootBox.x + rootBox.width))).toBeLessThanOrEqual(1);
    });

    test('opens a submenu beside its trigger, aligned to its top', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        const share = page.getByRole('menuitem', { name: 'Share' });

        await share.hover();

        const submenu = page.locator('[data-slot="dropdown-menu-sub-content"]').first();
        await expect(submenu).toBeVisible();
        await settle(page, '[data-slot="dropdown-menu-sub-content"]');
        const shareBox = (await share.boundingBox())!;
        const submenuBox = (await submenu.boundingBox())!;
        // `ms-1` after the item, which is as wide as its `DropdownMenu:Sub`.
        expect(Math.abs(submenuBox.x - (shareBox.x + shareBox.width + 4))).toBeLessThanOrEqual(1);
        expect(Math.abs(submenuBox.y - shareBox.y)).toBeLessThanOrEqual(1);
    });

    test('flips above its trigger when there is no room below', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await pinMenu(page, { bottom: '16px', left: '16px' });
        const trigger = page.getByRole('button', { name: 'Open' });

        await trigger.click();

        const menu = page.getByRole('menu', { name: 'Open' });
        await expect(menu).toBeVisible();
        await settle(page, '[data-slot="dropdown-menu-content"]');
        const triggerBox = (await trigger.boundingBox())!;
        const menuBox = (await menu.boundingBox())!;
        expect(menuBox.y + menuBox.height).toBeLessThanOrEqual(triggerBox.y);
        expect(menuBox.y).toBeGreaterThanOrEqual(0);
    });

    test('opens a submenu on the other side when there is no room on its side', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await pinMenu(page, { top: '16px', right: '16px' });
        await page.getByRole('button', { name: 'Open' }).click();
        const share = page.getByRole('menuitem', { name: 'Share' });

        await share.hover();

        const copyLink = page.getByRole('menuitem', { name: 'Copy link' });
        await expect(copyLink).toBeVisible();
        await settle(page, '[data-slot="dropdown-menu-sub-content"]');
        const shareBox = (await share.boundingBox())!;
        const copyLinkBox = (await copyLink.boundingBox())!;
        expect(copyLinkBox.x + copyLinkBox.width).toBeLessThanOrEqual(shareBox.x);
        expect(copyLinkBox.x).toBeGreaterThanOrEqual(0);
    });

    test('closes its submenus with it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        await page.getByRole('menuitem', { name: 'Share' }).hover();
        await page.getByRole('menuitem', { name: 'More options' }).hover();
        await expect(page.getByRole('menuitem', { name: 'Messages' })).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Messages' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeHidden();
        // Found by its slot: the items of a hidden menu leave the accessibility tree.
        await expect(page.locator('[data-slot="dropdown-menu-sub-trigger"]').first()).toHaveAttribute(
            'data-state',
            'closed'
        );
        await expect(page.getByRole('button', { name: 'Open' })).toBeFocused();
    });

    test('keeps a submenu open on a click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        const share = page.getByRole('menuitem', { name: 'Share' });
        await share.hover();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeVisible();

        await share.click();

        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeVisible();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
    });

    test('takes no room once closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await trigger.click();

        const content = page.locator('[data-slot="dropdown-menu-content"]');
        await expect(content).toBeHidden();
        await settle(page, '[data-slot="dropdown-menu-content"]');
        await expect(content).toHaveCSS('display', 'none');
    });

    test('hides its closed menus as soon as it connects', async ({ page, gotoExample }) => {
        // Stretches the transitions, so a closed menu would stay on screen while turning into a popover.
        await page.addInitScript(() => {
            document.addEventListener('DOMContentLoaded', () => {
                const style = document.createElement('style');
                style.textContent = '[data-slot][data-slot] { transition-duration: 10s; }';
                document.head.append(style);
            });
        });
        await gotoExample('shadcn/dropdown-menu/submenus');

        const displayed = await page.evaluate(() =>
            [
                ...document.querySelectorAll(
                    '[data-slot="dropdown-menu-content"], [data-slot="dropdown-menu-sub-content"]'
                ),
            ].map((menu) => getComputedStyle(menu).display)
        );

        expect(displayed).toEqual(['none', 'none', 'none']);
    });

    test('opens against its root, as without anchor positioning', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const root = page.locator('[data-slot="dropdown-menu"]');
        await root.evaluate((element) => {
            element.style.paddingInlineStart = '40px';
        });

        await page.getByRole('button', { name: 'Open' }).click();

        const menu = page.getByRole('menu', { name: 'Open' });
        await expect(menu).toBeVisible();
        await settle(page, '[data-slot="dropdown-menu-content"]');
        const rootBox = (await root.boundingBox())!;
        const menuBox = (await menu.boundingBox())!;
        expect(Math.abs(menuBox.x - rootBox.x)).toBeLessThanOrEqual(1);
    });

    test('closes on a second click on a trigger that is not a button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.locator('[data-slot="dropdown-menu-trigger"]').evaluate((button) => {
            const link = document.createElement('a');
            for (const { name, value } of button.attributes) {
                link.setAttribute(name, value);
            }
            link.href = '#';
            link.textContent = button.textContent;
            button.replaceWith(link);
        });
        const trigger = page.locator('[data-slot="dropdown-menu-trigger"]');
        await trigger.click();
        await expect(page.getByRole('menu')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('menu')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open on a click inside its root, outside the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.locator('[data-slot="dropdown-menu"]').evaluate((root) => {
            const label = document.createElement('span');
            label.textContent = 'Inside the root';
            root.prepend(label);
        });
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await page.getByText('Inside the root').click();

        await expect(page.getByRole('button', { name: 'Open' })).toHaveAttribute('aria-expanded', 'true');
    });

    test('grows from the edge next to its trigger once flipped above it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await pinMenu(page, { bottom: '16px', left: '16px' });

        await page.getByRole('button', { name: 'Open' }).click();

        const menu = page.getByRole('menu', { name: 'Open' });
        await expect(menu).toBeVisible();
        const origin = await menu.evaluate((element) => ({
            y: parseFloat(getComputedStyle(element).transformOrigin.split(' ')[1]),
            height: element.offsetHeight,
        }));
        expect(Math.abs(origin.y - origin.height)).toBeLessThanOrEqual(1);
    });

    test('grows a submenu from the edge next to its trigger once flipped', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await pinMenu(page, { top: '16px', right: '16px' });
        await page.getByRole('button', { name: 'Open' }).click();

        await page.getByRole('menuitem', { name: 'Share' }).hover();

        const submenu = page.locator('[data-slot="dropdown-menu-sub-content"]').first();
        await expect(submenu).toBeVisible();
        const origin = await submenu.evaluate((element) => ({
            x: parseFloat(getComputedStyle(element).transformOrigin.split(' ')[0]),
            width: element.offsetWidth,
        }));
        expect(Math.abs(origin.x - origin.width)).toBeLessThanOrEqual(1);
    });

    test('keeps a submenu open while the pointer crosses the gap to it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        const share = page.getByRole('menuitem', { name: 'Share' });
        await share.hover();
        const copyLink = page.getByRole('menuitem', { name: 'Copy link' });
        await expect(copyLink).toBeVisible();
        await settle(page, '[data-slot="dropdown-menu-sub-content"]');
        const shareBox = (await share.boundingBox())!;
        const copyLinkBox = (await copyLink.boundingBox())!;

        await page.mouse.move(shareBox.x + shareBox.width + 2, shareBox.y + shareBox.height / 2);
        await page.waitForTimeout(300);
        await page.mouse.move(copyLinkBox.x + copyLinkBox.width / 2, copyLinkBox.y + copyLinkBox.height / 2);

        await expect(share).toHaveAttribute('data-state', 'open');
        await expect(copyLink).toBeVisible();
    });

    test('does not open a submenu once the menu is closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
        await page.keyboard.press('Escape');

        await page
            .locator('[data-slot="dropdown-menu-sub"]')
            .first()
            .evaluate(async (sub) => {
                sub.dispatchEvent(new MouseEvent('mouseenter'));
                await new Promise((resolve) => setTimeout(resolve, 50));
            });

        const subContent = page.locator('[data-slot="dropdown-menu-sub-content"]').first();
        await expect(subContent).toHaveAttribute('data-state', 'closed');
        await expect(subContent).toHaveCSS('display', 'none');
    });

    test('reflects a menu hidden by something else', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        const content = page.locator('[data-slot="dropdown-menu-content"]');
        await expect(content).toBeVisible();

        await content.evaluate((element) => element.hidePopover());

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.locator('[data-slot="dropdown-menu"]')).toHaveAttribute('data-state', 'closed');
    });

    test('opens again after a morph strips what the controller set', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const content = page.locator('[data-slot="dropdown-menu-content"]');
        await page.locator('[data-slot="dropdown-menu"]').evaluate((root) => {
            const content = root.querySelector<HTMLElement>('[data-slot="dropdown-menu-content"]')!;
            content.removeAttribute('popover');
            content.removeAttribute('style');
            root.removeAttribute('style');
        });

        await page.getByRole('button', { name: 'Open' }).click();

        await expect(content).toBeVisible();
        await expect(content).toHaveAttribute('popover', 'manual');
    });

    test('cleans up when its content is removed, and still toggles', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const root = page.locator('[data-slot="dropdown-menu"]');
        await root.evaluate((element) => {
            element.querySelector('[data-slot="dropdown-menu-content"]')!.remove();
            // Moving the root disconnects and reconnects the controller.
            element.parentElement!.append(element);
        });
        const trigger = page.getByRole('button', { name: 'Open' });

        await trigger.click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(root).not.toHaveAttribute('style', /anchor-name/);
    });

    test('closes once its controller disconnects', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        const content = page.locator('[data-slot="dropdown-menu-content"]');
        await expect(content).toBeVisible();

        const root = page.locator('[data-slot="dropdown-menu"]');
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
            await gotoExample('shadcn/dropdown-menu/default');
            const trigger = page.getByRole('button', { name: 'Open' });
            const menu = page.getByRole('menu', { name: 'Open' });

            await trigger.click();

            await expect(menu).toBeVisible();
            await expect(menu).not.toHaveAttribute('popover');
            await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

            await page.keyboard.press('Escape');

            await expect(menu).toBeHidden();
            await expect(trigger).toBeFocused();

            await trigger.click();
            await expect(menu).toBeVisible();

            await page.mouse.click(10, 10);

            await expect(menu).toBeHidden();
            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        });

        test('clips a submenu in a menu that clips its overflow', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/dropdown-menu/submenus');
            await page
                .locator('[data-slot="dropdown-menu-content"]')
                .first()
                .evaluate((menu) => {
                    menu.style.overflow = 'clip';
                });
            await page.getByRole('button', { name: 'Open' }).click();

            await page.getByRole('menuitem', { name: 'Share' }).hover();

            await expect(page.getByRole('menuitem', { name: 'Share' })).toHaveAttribute('data-state', 'open');
            expect(await isUnderPointer(page.getByRole('menuitem', { name: 'Copy link' }))).toBe(false);
        });
    });
});
