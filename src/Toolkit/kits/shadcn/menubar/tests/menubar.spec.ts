import type { Page } from '@playwright/test';
import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

async function clipMenubar(page: Page): Promise<void> {
    await page
        .locator('[data-slot="menubar"]')
        .first()
        .evaluate((element) => {
            const box = element.parentElement!;
            box.style.overflow = 'clip';
            box.style.minHeight = '0';
            box.style.height = `${element.getBoundingClientRect().bottom - box.getBoundingClientRect().top + 4}px`;
        });
}

async function pinMenubar(page: Page, position: Record<string, string>): Promise<void> {
    await page
        .locator('[data-slot="menubar"]')
        .first()
        .evaluate((element, style) => Object.assign(element.style, { position: 'fixed' }, style), position);
}

describeRecipe('shadcn/menubar', () => {
    testState('opens a menu on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('menuitem', { name: 'File', exact: true });

            await trigger.click();

            await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await page.mouse.move(0, 0);
        },
    });

    test('closes the menu on a second click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const trigger = page.getByRole('menuitem', { name: 'File', exact: true });
        await trigger.click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens a menu with the keyboard and closes it on Escape', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const trigger = page.getByRole('menuitem', { name: 'Edit' });
        await trigger.focus();

        await page.keyboard.press('Enter');

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes the menu on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'File', exact: true })).toHaveAttribute(
            'aria-expanded',
            'false'
        );
    });

    test('does not open a menu on hover while every menu is closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');

        await page.getByRole('menuitem', { name: 'Edit' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Edit' })).toHaveAttribute('aria-expanded', 'false');
    });

    test('switches to the hovered menu once a menu is open', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const file = page.getByRole('menuitem', { name: 'File', exact: true });
        const edit = page.getByRole('menuitem', { name: 'Edit' });
        await file.click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await edit.hover();

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        await expect(edit).toHaveAttribute('aria-expanded', 'true');
        await expect(file).toHaveAttribute('aria-expanded', 'false');
    });

    testState('opens a submenu on hover', {
        example: 'submenus',
        state: 'submenu-open',
        act: async (page) => {
            await page.getByRole('menuitem', { name: 'Edit' }).click();

            await page.getByRole('menuitem', { name: 'Find', exact: true }).hover();

            const findPrevious = page.getByRole('menuitem', { name: 'Find Previous' });
            await expect(findPrevious).toBeVisible();
            await expect.poll(() => isUnderPointer(findPrevious)).toBe(true);
        },
    });

    test('closes a submenu when the pointer leaves it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await page.getByRole('menuitem', { name: 'Share' }).hover();
        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'Print...' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Print...' })).toBeVisible();
    });

    test('opens a submenu when its trigger gets the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await page.mouse.move(0, 0);

        await page.getByRole('menuitem', { name: 'Share' }).focus();

        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'Share' }).blur();

        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeHidden();
    });

    test('toggles a checkbox item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/checkbox-items');
        await page.getByRole('menuitem', { name: 'View' }).click();
        const bookmarks = page.getByRole('checkbox', { name: 'Always Show Bookmarks Bar' });
        await expect(bookmarks).not.toBeChecked();

        await page.getByText('Always Show Bookmarks Bar').click();

        await expect(bookmarks).toBeChecked();
        await expect(page.getByRole('checkbox', { name: 'Always Show Full URLs' })).toBeChecked();

        await page.getByText('Always Show Bookmarks Bar').click();

        await expect(bookmarks).not.toBeChecked();
    });

    testState('selects a radio item', {
        example: 'radio-group',
        state: 'selected',
        act: async (page) => {
            await page.getByRole('menuitem', { name: 'Profiles' }).click();
            await expect(page.getByRole('radio', { name: 'Benoit' })).toBeChecked();

            await page.getByText('Andy').click();

            await expect(page.getByRole('radio', { name: 'Andy' })).toBeChecked();
            await expect(page.getByRole('radio', { name: 'Benoit' })).not.toBeChecked();
            await expect(page.getByRole('menuitem', { name: 'Add Profile...' })).toBeVisible();
            await page.mouse.move(0, 0);
        },
    });

    testState('shows icons and a destructive item', {
        example: 'with-icons',
        state: 'more-open',
        act: async (page) => {
            await page.getByRole('menuitem', { name: 'More' }).click();

            await expect(page.getByRole('menuitem', { name: 'Delete' })).toHaveAttribute('data-variant', 'destructive');
        },
    });

    for (const { example, trigger, role, name } of [
        { example: 'checkbox-items', trigger: 'View', role: 'checkbox', name: 'Always Show Bookmarks Bar' },
        { example: 'radio-group', trigger: 'Profiles', role: 'radio', name: 'Benoit' },
    ] as const) {
        test(`highlights a ${role} item focused with the keyboard`, async ({ page, gotoExample }) => {
            await gotoExample(`shadcn/menubar/${example}`);
            await page.getByRole('menuitem', { name: trigger }).click();
            const control = page.getByRole(role, { name });

            await page.keyboard.press('Tab');

            await expect(control).toBeFocused();
            await expect(page.locator('label', { has: control })).not.toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
        });
    }

    test('shows the menu outside an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        await clipMenubar(page);

        await page.getByRole('menuitem', { name: 'File', exact: true }).click();

        await expect(page.getByRole('menuitem', { name: 'Print...' })).toBeVisible();
        expect(await isUnderPointer(page.getByRole('menuitem', { name: 'Print...' }))).toBe(true);
    });

    test('flips the menu above its trigger when there is no room below', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        await pinMenubar(page, { bottom: '16px', left: '16px' });
        const trigger = page.getByRole('menuitem', { name: 'File', exact: true });

        await trigger.click();

        const content = page.locator('[data-slot="menubar-content"]').first();
        await expect(content).toBeVisible();
        const triggerBox = (await trigger.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(contentBox.y + contentBox.height).toBeLessThanOrEqual(triggerBox.y);
        expect(contentBox.y).toBeGreaterThanOrEqual(0);
    });

    test('flips a submenu to the other side when there is no room', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        // The File menu then ends close to the right edge of the viewport.
        await pinMenubar(page, { top: '16px', left: 'calc(100vw - 220px)' });
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        const share = page.getByRole('menuitem', { name: 'Share' });

        await share.hover();

        const subContent = page.locator('[data-slot="menubar-sub-content"]').first();
        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeVisible();
        const shareBox = (await share.boundingBox())!;
        const subBox = (await subContent.boundingBox())!;
        expect(subBox.x + subBox.width).toBeLessThanOrEqual(shareBox.x + 1);
        expect(subBox.x).toBeGreaterThanOrEqual(0);
    });

    test('closes the open submenu along with its menu', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await page.mouse.move(0, 0);
        await page.getByRole('menuitem', { name: 'Share' }).focus();
        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeHidden();
    });

    test('shows the full label of each item on a single line', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');

        await page.getByRole('menuitem', { name: 'File', exact: true }).click();

        const item = page.getByRole('menuitem', { name: 'New Incognito Window' });
        await expect(item).toBeVisible();
        const singleLine = (await page.getByRole('menuitem', { name: 'New Tab' }).boundingBox())!.height;
        expect((await item.boundingBox())!.height).toBeLessThanOrEqual(singleLine + 1);
    });

    test('opens a menu against its `Menubar:Menu`, as without anchor positioning', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const menu = page.locator('[data-slot="menubar-menu"]').first();
        await menu.evaluate((element) => {
            element.style.paddingInlineStart = '40px';
        });

        await page.getByRole('menuitem', { name: 'File', exact: true }).click();

        const content = page.locator('[data-slot="menubar-content"]').first();
        await expect(content).toBeVisible();
        const menuBox = (await menu.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(Math.abs(contentBox.x - menuBox.x)).toBeLessThanOrEqual(1);
    });

    test('opens from a submit trigger inside a form without submitting it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        await page.locator('[data-slot="menubar"]').evaluate((menubar) => {
            const form = document.createElement('form');
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                form.dataset.submitted = 'true';
            });
            menubar.before(form);
            form.append(menubar);
            menubar.querySelector('[data-slot="menubar-trigger"]')!.removeAttribute('type');
        });

        await page.getByRole('menuitem', { name: 'File', exact: true }).click();

        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();
        await expect(page.locator('form')).not.toHaveAttribute('data-submitted');
    });

    test('keeps a menu open on a click inside its `Menubar:Menu`', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        await page
            .locator('[data-slot="menubar-menu"]')
            .first()
            .evaluate((menu) => {
                const label = document.createElement('span');
                label.textContent = 'Inside the menu';
                menu.prepend(label);
            });
        const file = page.getByRole('menuitem', { name: 'File', exact: true });
        await file.click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await page.getByText('Inside the menu').click();

        await expect(file).toHaveAttribute('aria-expanded', 'true');
    });

    test('keeps a menu open while another popover opens', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const file = page.getByRole('menuitem', { name: 'File', exact: true });
        await file.click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await page.evaluate(() => {
            const other = document.createElement('div');
            other.popover = 'auto';
            other.textContent = 'Another popover';
            document.body.append(other);
            other.showPopover();
        });

        await expect(file).toHaveAttribute('aria-expanded', 'true');
    });

    test('reflects a menu hidden by something else, and opens again on a click', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const file = page.getByRole('menuitem', { name: 'File', exact: true });
        await file.click();
        const content = page.locator('[data-slot="menubar-content"]').first();
        await expect(content).toBeVisible();

        await content.evaluate((element) => element.hidePopover());

        await expect(file).toHaveAttribute('aria-expanded', 'false');
        await expect(page.locator('[data-slot="menubar-menu"]').first()).toHaveAttribute('data-state', 'closed');

        await file.click();

        await expect(content).toBeVisible();
    });

    test('opens again after a morph strips what the controller set', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const content = page.locator('[data-slot="menubar-content"]').first();
        await page
            .locator('[data-slot="menubar-menu"]')
            .first()
            .evaluate((menu) => {
                const content = menu.querySelector<HTMLElement>('[data-slot="menubar-content"]')!;
                content.removeAttribute('popover');
                content.removeAttribute('style');
                menu.removeAttribute('style');
            });

        await page.getByRole('menuitem', { name: 'File', exact: true }).click();

        await expect(content).toBeVisible();
        await expect(content).toHaveAttribute('popover', 'manual');
    });

    test('cleans up when a menu content is removed, and still toggles', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const menu = page.locator('[data-slot="menubar-menu"]').first();
        await menu.evaluate((element) => {
            element.querySelector('[data-slot="menubar-content"]')!.remove();
        });
        const file = page.getByRole('menuitem', { name: 'File', exact: true });

        await expect(menu).not.toHaveAttribute('style', /anchor-name/);

        await file.click();

        await expect(file).toHaveAttribute('aria-expanded', 'true');
    });

    test('closes its open menu once its controller disconnects', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        const content = page.locator('[data-slot="menubar-content"]').first();
        await expect(content).toBeVisible();

        await page.locator('[data-slot="menubar"]').evaluate((menubar) => {
            menubar.removeAttribute('data-controller');
        });

        await expect(content).toBeHidden();
        await expect(page.locator('[data-slot="menubar-menu"]').first()).toHaveAttribute('data-state', 'closed');
    });

    test('does not open a submenu once its menu is closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await expect(page.locator('[data-slot="menubar-content"]').first()).toBeVisible();
        await page.keyboard.press('Escape');

        await page
            .locator('[data-slot="menubar-sub"]')
            .first()
            .evaluate((sub) => sub.dispatchEvent(new MouseEvent('mouseenter')));

        await expect(page.locator('[data-slot="menubar-sub"]').first()).toHaveAttribute('data-state', 'closed');
        await expect(page.locator('[data-slot="menubar-sub-content"]').first()).toHaveCSS('display', 'none');
    });

    test('keeps a submenu open while the pointer crosses the gap to it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        const share = page.getByRole('menuitem', { name: 'Share' });
        await share.hover();
        const emailLink = page.getByRole('menuitem', { name: 'Email link' });
        await expect(emailLink).toBeVisible();
        const shareBox = (await share.boundingBox())!;
        const emailLinkBox = (await emailLink.boundingBox())!;

        await page.mouse.move(shareBox.x + shareBox.width + 2, shareBox.y + shareBox.height / 2);
        await page.waitForTimeout(300);
        await page.mouse.move(emailLinkBox.x + emailLinkBox.width / 2, emailLinkBox.y + emailLinkBox.height / 2);

        await expect(page.locator('[data-slot="menubar-sub"]').first()).toHaveAttribute('data-state', 'open');
        await expect(emailLink).toBeVisible();
    });

    test.describe('without anchor positioning', () => {
        test.beforeEach(async ({ page }) => {
            await page.addInitScript(() => {
                const supports = CSS.supports.bind(CSS);
                CSS.supports = (...args: [string, string?]) =>
                    args[0].startsWith('position-area') ? false : supports(...(args as [string]));
            });
        });

        test('opens in place and closes on a click outside', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/menubar/submenus');
            await page.getByRole('menuitem', { name: 'File', exact: true }).click();
            await page.getByRole('menuitem', { name: 'Share' }).hover();

            await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeVisible();
            await expect(page.locator('[data-slot="menubar-content"]').first()).not.toHaveAttribute('popover');
            await expect(page.locator('[data-slot="menubar-sub-content"]').first()).not.toHaveAttribute('popover');

            await page.mouse.click(10, 10);

            await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        });

        test('shows the full label of each item on a single line', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/menubar/default');

            await page.getByRole('menuitem', { name: 'File', exact: true }).click();

            const item = page.getByRole('menuitem', { name: 'New Incognito Window' });
            await expect(item).toBeVisible();
            const singleLine = (await page.getByRole('menuitem', { name: 'New Tab' }).boundingBox())!.height;
            expect((await item.boundingBox())!.height).toBeLessThanOrEqual(singleLine + 1);
        });

        test('clips the menu in an ancestor that hides its overflow', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/menubar/default');
            await clipMenubar(page);

            await page.getByRole('menuitem', { name: 'File', exact: true }).click();

            await expect(page.locator('[data-slot="menubar-menu"]').first()).toHaveAttribute('data-state', 'open');
            expect(await isUnderPointer(page.getByRole('menuitem', { name: 'Print...' }))).toBe(false);
        });
    });
});
