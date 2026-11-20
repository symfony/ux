import type { Page } from '@playwright/test';
import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

async function clipMenu(page: Page): Promise<void> {
    await page
        .locator('[data-slot="navigation-menu"]')
        .first()
        .evaluate((element) => {
            const box = element.parentElement!;
            box.style.overflow = 'clip';
            box.style.minHeight = '0';
            box.style.height = `${element.getBoundingClientRect().bottom - box.getBoundingClientRect().top + 4}px`;
        });
}

function testFirstOpening(): void {
    test('opens its panel under its trigger at once the first time', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const positioner = page.locator('[data-navigation-menu-target="viewportPositioner"]');
        // Slow enough to catch a panel that would travel to its place.
        await positioner.evaluate((element) => element.style.setProperty('transition-duration', '10s'));
        const trigger = page.getByRole('button', { name: 'Getting started' });

        await trigger.click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        const triggerBox = (await trigger.boundingBox())!;
        const positionerBox = (await positioner.boundingBox())!;
        expect(positionerBox.x).toBeCloseTo(triggerBox.x, 0);
    });
}

describeRecipe('shadcn/navigation-menu', () => {
    testState('opens on hover', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Getting started' });

            await trigger.hover();

            await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('keeps the panel of the last item inside the viewport', {
        example: 'default',
        state: 'open-last',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Resources' });

            await trigger.hover();

            await expect(page.getByRole('link', { name: /Roadmap/ })).toBeVisible();
            await expect(page.getByRole('link', { name: /Roadmap/ })).toBeInViewport({ ratio: 1 });
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('opens on hover in a right-to-left menu', {
        example: 'rtl',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'ابدأ' });

            await trigger.hover();

            await expect(page.getByRole('link', { name: /مقدمة/ })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('still opens after being moved in the DOM', {
        example: 'default',
        state: 'open-after-move',
        act: async (page) => {
            await page.locator('[data-controller="navigation-menu"]').evaluate(async (element) => {
                const parent = element.parentNode!;
                const next = element.nextSibling;
                element.remove();
                await new Promise((resolve) => setTimeout(resolve, 50));
                parent.insertBefore(element, next);
            });
            const trigger = page.getByRole('button', { name: 'Getting started' });

            await trigger.hover();

            await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('waits for the open delay before opening on hover', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default', { timers: 'fake' });
        await page.clock.pauseAt(Date.now() + 60_000);
        const trigger = page.getByRole('button', { name: 'Getting started' });

        await trigger.hover();
        await page.clock.runFor(100);

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeHidden();

        await page.clock.runFor(200);

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();
    });

    test('opens on click without waiting for the open delay', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Getting started' });

        await trigger.click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();
    });

    for (const { example, trigger, edge } of [
        { example: 'default', trigger: 'Getting started', edge: 'left' },
        { example: 'rtl', trigger: 'ابدأ', edge: 'right' },
    ] as const) {
        test(`aligns the ${edge} edge of the panel on a whole pixel in the ${example} example`, async ({
            page,
            gotoExample,
        }) => {
            await gotoExample(`shadcn/navigation-menu/${example}`);
            const viewport = page.locator('[data-slot="navigation-menu-viewport"][data-state="open"]');

            await page.getByRole('button', { name: trigger }).click();
            await expect(viewport).toBeVisible();
            await viewport
                .locator('..')
                .evaluate((positioner) =>
                    Promise.all(positioner.getAnimations({ subtree: true }).map((animation) => animation.finished))
                );

            const box = (await viewport.boundingBox())!;
            const position = 'left' === edge ? box.x : box.x + box.width;
            expect(position).toBe(Math.round(position));
        });
    }

    test('switches to another item at once while one is open', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default', { timers: 'fake' });
        const gettingStarted = page.getByRole('button', { name: 'Getting started' });
        const components = page.getByRole('button', { name: 'Components' });
        await gettingStarted.hover();
        await page.clock.runFor(300);
        await expect(gettingStarted).toHaveAttribute('aria-expanded', 'true');

        await components.hover();
        await page.clock.runFor(50);

        await expect(components).toHaveAttribute('aria-expanded', 'true');
        await expect(gettingStarted).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('link', { name: /Alert Dialog/ })).toBeVisible();
    });

    test('closes after the close delay once the pointer leaves the menu', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default', { timers: 'fake' });
        await page.clock.pauseAt(Date.now() + 60_000);
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.hover();
        await page.clock.runFor(300);
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.mouse.move(0, 0);
        await page.clock.runFor(100);

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.clock.runFor(400);

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeHidden();
    });

    test('stays open while the pointer moves into the panel', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.hover();
        await page.clock.runFor(300);
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.getByRole('link', { name: /Installation/ }).hover();
        await page.clock.runFor(1000);

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: /Installation/ })).toBeVisible();
    });

    test('closes after the close delay when the pointer moves to a plain link', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.hover();
        await page.clock.runFor(300);
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.getByRole('link', { name: 'Docs', exact: true }).hover();
        await page.clock.runFor(500);

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeHidden();
    });

    test('opens when a trigger gets focus and closes when focus leaves the menu', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const trigger = page.getByRole('button', { name: 'Getting started' });

        await page.keyboard.press('Tab');

        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();

        await page.keyboard.press('Shift+Tab');

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeHidden();
    });

    test('moves focus from a trigger into its panel with Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.focus();
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('link', { name: /Introduction/ })).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('shows the panel outside an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        await clipMenu(page);

        await page.getByRole('button', { name: 'Getting started' }).click();

        const link = page.getByRole('link', { name: /Introduction/ });
        await expect(link).toBeVisible();
        await expect(page.locator('[data-slot="navigation-menu-viewport"]')).toHaveCSS('opacity', '1');
        expect(await isUnderPointer(link)).toBe(true);
    });

    test('shows the panel above a positioned element stacked higher than the menu', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        await page.evaluate(() => {
            const cover = document.createElement('div');
            Object.assign(cover.style, { position: 'fixed', inset: '100px 0 0 0', zIndex: '100', background: 'white' });
            document.body.append(cover);
        });

        await page.getByRole('button', { name: 'Getting started' }).click();

        const link = page.getByRole('link', { name: /Introduction/ });
        await expect(link).toBeVisible();
        await expect(page.locator('[data-slot="navigation-menu-viewport"]')).toHaveCSS('opacity', '1');
        expect(await isUnderPointer(link)).toBe(true);
    });

    testFirstOpening();

    test('takes no room while closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');

        await expect(page.locator('[data-navigation-menu-target="viewportPositioner"]')).toHaveCSS('display', 'none');
    });

    test('closes on Escape and gives the focus back to the trigger without reopening', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.click();
        await page.keyboard.press('Tab');
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.locator('[data-slot="navigation-menu-viewport"]')).toHaveAttribute('data-state', 'closed');
    });

    test('closes a panel opened on hover on Escape', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.hover();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open on a click on a spot of the panel that cannot take the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.click();
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();
        await page.locator('[data-slot="navigation-menu-content"][data-active]').evaluate((content) => {
            const label = document.createElement('span');
            label.textContent = 'Inside the panel';
            content.prepend(label);
        });

        await page.getByText('Inside the panel').click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('stays open while another popover opens', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.click();
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();

        await page.evaluate(() => {
            const other = document.createElement('div');
            other.popover = 'auto';
            other.textContent = 'Another popover';
            document.body.append(other);
            other.showPopover();
        });

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('stays open while the pointer crosses the gap to the panel', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        await page.getByRole('button', { name: 'Getting started' }).hover();
        const link = page.getByRole('link', { name: /Introduction/ });
        await expect(link).toBeVisible();
        const viewportBox = (await page.locator('[data-slot="navigation-menu-viewport"]').boundingBox())!;
        const linkBox = (await link.boundingBox())!;

        await page.mouse.move(viewportBox.x + 20, viewportBox.y - 3);
        await page.waitForTimeout(500);
        await page.mouse.move(linkBox.x + linkBox.width / 2, linkBox.y + linkBox.height / 2);

        await expect(page.getByRole('button', { name: 'Getting started' })).toHaveAttribute('aria-expanded', 'true');
        await expect(link).toBeVisible();
    });

    test('reflects a panel hidden by something else', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const trigger = page.getByRole('button', { name: 'Getting started' });
        await trigger.click();
        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();

        await page
            .locator('[data-navigation-menu-target="viewportPositioner"]')
            .evaluate((positioner) => positioner.hidePopover());

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.locator('[data-slot="navigation-menu-viewport"]')).toHaveAttribute('data-state', 'closed');
    });

    test('opens again after a morph strips what the controller set', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const positioner = page.locator('[data-navigation-menu-target="viewportPositioner"]');
        await page.locator('[data-slot="navigation-menu"]').evaluate((nav) => {
            const positioner = nav.querySelector<HTMLElement>('[data-navigation-menu-target="viewportPositioner"]')!;
            positioner.removeAttribute('popover');
            positioner.removeAttribute('style');
            nav.removeAttribute('style');
        });

        await page.getByRole('button', { name: 'Getting started' }).click();

        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();
        await expect(positioner).toHaveAttribute('popover', 'manual');
    });

    test('opens again once restored from the Turbo cache', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        await page.locator('[data-slot="navigation-menu"]').evaluate((nav) => {
            // Turbo caches a clone of the page taken while the controller is connected.
            nav.replaceWith(nav.cloneNode(true));
        });

        await page.getByRole('button', { name: 'Getting started' }).click();

        await expect(page.getByRole('link', { name: /Introduction/ })).toBeVisible();
    });

    test('cleans up when its viewport is removed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        const nav = page.locator('[data-slot="navigation-menu"]');
        await nav.evaluate((element) => {
            element.querySelector('[data-navigation-menu-target="viewportPositioner"]')!.remove();
        });

        await page.getByRole('button', { name: 'Getting started' }).click();

        await expect(nav).not.toHaveAttribute('style', /anchor-name/);
    });

    test('closes its open panel once its controller disconnects', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/navigation-menu/default');
        await page.getByRole('button', { name: 'Getting started' }).click();
        const link = page.getByRole('link', { name: /Introduction/ });
        await expect(link).toBeVisible();

        await page.locator('[data-slot="navigation-menu"]').evaluate((nav) => {
            nav.removeAttribute('data-controller');
        });

        await expect(link).toBeHidden();
    });

    test.describe('without anchor positioning', () => {
        test.beforeEach(async ({ page }) => {
            await page.addInitScript(() => {
                const supports = CSS.supports.bind(CSS);
                CSS.supports = (...args: [string, string?]) =>
                    args[0].startsWith('position-area') ? false : supports(...(args as [string]));
            });
        });

        test('closes a panel opened on hover on Escape', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/navigation-menu/default');
            const trigger = page.getByRole('button', { name: 'Getting started' });
            await trigger.hover();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');

            await page.keyboard.press('Escape');

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        });

        test('opens in place and clips the panel in an ancestor that hides its overflow', async ({
            page,
            gotoExample,
        }) => {
            await gotoExample('shadcn/navigation-menu/default');
            await clipMenu(page);

            await page.getByRole('button', { name: 'Getting started' }).click();

            await expect(page.locator('[data-slot="navigation-menu-viewport"]')).toHaveAttribute('data-state', 'open');
            await expect(page.locator('[data-navigation-menu-target="viewportPositioner"]')).not.toHaveAttribute(
                'popover'
            );
            await expect(page.locator('[data-slot="navigation-menu-viewport"]')).toHaveCSS('opacity', '1');
            expect(await isUnderPointer(page.getByRole('link', { name: /Introduction/ }))).toBe(false);
        });

        testFirstOpening();
    });
});
