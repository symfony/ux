import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

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
});
