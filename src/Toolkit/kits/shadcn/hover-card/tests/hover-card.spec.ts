import type { Page } from '@playwright/test';
import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

async function clipHoverCard(page: Page): Promise<void> {
    await page
        .locator('[data-slot="hover-card"]')
        .first()
        .evaluate((hoverCard) => {
            const box = hoverCard.parentElement!;
            box.style.overflow = 'clip';
            box.style.minHeight = '0';
            box.style.height = `${hoverCard.getBoundingClientRect().bottom - box.getBoundingClientRect().top + 4}px`;
        });
}

describeRecipe('shadcn/hover-card', () => {
    testState('opens on hover', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Hover Here' }).hover();

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toContainText('@symfony');
        },
    });

    testState('opens on the side of its trigger', {
        example: 'sides',
        state: 'top-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'top' }).hover();

            await expect(page.getByText('This hover card appears on the top side of the trigger.')).toBeVisible();
        },
    });

    test('waits for the open delay before opening', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/sides', { timers: 'fake' });
        await page.clock.pauseAt(Date.now() + 60_000);
        const content = page.getByText('This hover card appears on the left side of the trigger.');

        await page.getByRole('button', { name: 'left' }).hover();
        await page.clock.runFor(50);

        await expect(content).toBeHidden();

        await page.clock.runFor(100);

        await expect(content).toBeVisible();
    });

    test('waits for the close delay before closing when the pointer leaves', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/sides', { timers: 'fake' });
        await page.clock.pauseAt(Date.now() + 60_000);
        const content = page.getByText('This hover card appears on the left side of the trigger.');
        await page.getByRole('button', { name: 'left' }).hover();
        await page.clock.runFor(150);
        await expect(content).toBeVisible();

        await page.mouse.move(0, 0);
        await page.clock.runFor(50);

        await expect(content).toBeVisible();

        await page.clock.runFor(100);

        await expect(content).toBeHidden();
    });

    test('stays open when the pointer comes back before the close delay', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/sides', { timers: 'fake' });
        await page.clock.pauseAt(Date.now() + 60_000);
        const trigger = page.getByRole('button', { name: 'left' });
        const content = page.getByText('This hover card appears on the left side of the trigger.');
        await trigger.hover();
        await page.clock.runFor(150);
        await expect(content).toBeVisible();

        await page.mouse.move(0, 0);
        await page.clock.runFor(50);
        await trigger.hover();
        await page.clock.runFor(500);

        await expect(content).toBeVisible();
    });

    test('stays open while the pointer moves onto the content', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/sides', { timers: 'fake' });
        await page.clock.pauseAt(Date.now() + 60_000);
        const content = page.getByText('This hover card appears on the left side of the trigger.');
        await page.getByRole('button', { name: 'left' }).hover();
        await page.clock.runFor(150);
        await expect(content).toBeVisible();

        await content.hover();
        await page.clock.runFor(500);

        await expect(content).toBeVisible();
    });

    test('opens on focus and closes on blur', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/sides', { timers: 'fake' });
        await page.clock.pauseAt(Date.now() + 60_000);
        const left = page.getByText('This hover card appears on the left side of the trigger.');
        const top = page.getByText('This hover card appears on the top side of the trigger.');

        await page.keyboard.press('Tab');
        await page.clock.runFor(150);

        await expect(page.getByRole('button', { name: 'left' })).toBeFocused();
        await expect(left).toBeVisible();

        await page.keyboard.press('Tab');
        await page.clock.runFor(150);

        await expect(page.getByRole('button', { name: 'top' })).toBeFocused();
        await expect(left).toBeHidden();
        await expect(top).toBeVisible();
    });

    test('is not clipped by an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        await clipHoverCard(page);

        await page.getByRole('button', { name: 'Hover Here' }).hover();

        const content = page.getByRole('tooltip');
        await expect(content).toBeVisible();
        expect(await isUnderPointer(content)).toBe(true);
    });

    test('opens centred below its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        const trigger = page.getByRole('button', { name: 'Hover Here' });

        await trigger.hover();

        const content = page.getByRole('tooltip');
        await expect(content).toBeVisible();
        await content.evaluate((element) =>
            Promise.all(element.getAnimations().map((animation) => animation.finished))
        );
        const triggerBox = (await trigger.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(Math.abs(contentBox.y - (triggerBox.y + triggerBox.height + 8))).toBeLessThanOrEqual(1);
        expect(
            Math.abs(contentBox.x + contentBox.width / 2 - (triggerBox.x + triggerBox.width / 2))
        ).toBeLessThanOrEqual(1);
    });

    test('flips above its trigger when there is no room below', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        await page.locator('[data-slot="hover-card"]').evaluate((hoverCard) => {
            hoverCard.style.position = 'fixed';
            hoverCard.style.bottom = '16px';
            hoverCard.style.left = '50%';
        });
        const trigger = page.getByRole('button', { name: 'Hover Here' });

        await trigger.hover();

        const content = page.getByRole('tooltip');
        await expect(content).toBeVisible();
        await content.evaluate((element) =>
            Promise.all(element.getAnimations().map((animation) => animation.finished))
        );
        const triggerBox = (await trigger.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(contentBox.y + contentBox.height).toBeLessThanOrEqual(triggerBox.y);
        expect(contentBox.y).toBeGreaterThanOrEqual(0);
    });

    test('takes no room once closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/rtl');
        await page
            .locator('[data-slot="hover-card-content"]')
            .first()
            .evaluate((element) => {
                element.dataset.probe = '';
            });
        const content = page.locator('[data-slot="hover-card-content"][data-probe]');

        await page.locator('[data-slot="hover-card-trigger"]').first().hover();
        await expect(content).toBeVisible();
        await page.mouse.move(0, 0);

        await expect(content).toBeHidden();
        await expect(content).toHaveCSS('display', 'none');
    });

    test('opens against its root, as without anchor positioning', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        const hoverCard = page.locator('[data-slot="hover-card"]');
        await hoverCard.evaluate((element) => {
            element.style.paddingInlineStart = '40px';
        });

        await page.getByRole('button', { name: 'Hover Here' }).hover();

        const content = page.getByRole('tooltip');
        await expect(content).toBeVisible();
        await content.evaluate((element) =>
            Promise.all(element.getAnimations().map((animation) => animation.finished))
        );
        const hoverCardBox = (await hoverCard.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(
            Math.abs(contentBox.x + contentBox.width / 2 - (hoverCardBox.x + hoverCardBox.width / 2))
        ).toBeLessThanOrEqual(1);
    });

    test('closes on Escape and leaves the focus on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        const trigger = page.getByRole('button', { name: 'Hover Here' });
        await trigger.focus();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('tooltip')).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('reflects a popover hidden by something else', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        await page.getByRole('button', { name: 'Hover Here' }).hover();
        const content = page.locator('[data-slot="hover-card-content"]');
        await expect(content).toBeVisible();

        await content.evaluate((element) => element.hidePopover());

        await expect(page.locator('[data-slot="hover-card"]')).toHaveAttribute('data-state', 'closed');
    });

    test('opens again after a morph strips what the controller set', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        const content = page.locator('[data-slot="hover-card-content"]');
        await page.locator('[data-slot="hover-card"]').evaluate((hoverCard) => {
            const content = hoverCard.querySelector<HTMLElement>('[data-slot="hover-card-content"]')!;
            content.removeAttribute('popover');
            content.removeAttribute('style');
            hoverCard.removeAttribute('style');
        });

        await page.getByRole('button', { name: 'Hover Here' }).hover();

        await expect(content).toBeVisible();
        await expect(content).toHaveAttribute('popover', 'manual');
    });

    test('cleans up a removed content and sets up a new one', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        const hoverCard = page.locator('[data-slot="hover-card"]');
        const content = page.locator('[data-slot="hover-card-content"]');
        const replacement = await hoverCard.evaluateHandle((element) => {
            const content = element.querySelector('[data-slot="hover-card-content"]')!;
            const replacement = content.cloneNode(true) as HTMLElement;
            replacement.removeAttribute('popover');
            replacement.removeAttribute('style');
            content.remove();

            return replacement;
        });

        await expect(hoverCard).not.toHaveAttribute('style', /anchor-name/);

        await hoverCard.evaluate((element, replacement) => element.append(replacement), replacement);
        await page.getByRole('button', { name: 'Hover Here' }).hover();

        await expect(content).toBeVisible();
        await expect(content).toHaveAttribute('popover', 'manual');
    });

    test('closes once its controller disconnects', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/hover-card/default');
        await page.getByRole('button', { name: 'Hover Here' }).hover();
        const content = page.locator('[data-slot="hover-card-content"]');
        await expect(content).toBeVisible();

        await page.locator('[data-slot="hover-card"]').evaluate((hoverCard) => {
            hoverCard.removeAttribute('data-controller');
        });

        await expect(content).toBeHidden();
    });

    test.describe('without anchor positioning', () => {
        test.beforeEach(async ({ page }) => {
            await page.addInitScript(() => {
                const supports = CSS.supports.bind(CSS);
                CSS.supports = (...args: [string, string?]) =>
                    args[0].startsWith('position-area') ? false : supports(...(args as [string]));
            });
        });

        test('opens in place on hover and closes when the pointer leaves', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/hover-card/default');
            const content = page.getByRole('tooltip');

            await page.getByRole('button', { name: 'Hover Here' }).hover();

            await expect(content).toBeVisible();
            await expect(content).not.toHaveAttribute('popover');

            await page.mouse.move(0, 0);

            await expect(content).toBeHidden();
        });

        test('opens below its trigger, clipped by an ancestor that hides its overflow', async ({
            page,
            gotoExample,
        }) => {
            await gotoExample('shadcn/hover-card/default');
            await clipHoverCard(page);

            await page.getByRole('button', { name: 'Hover Here' }).hover();

            const content = page.getByRole('tooltip');
            await expect(content).toBeVisible();
            expect(await isUnderPointer(content)).toBe(false);
        });
    });
});
