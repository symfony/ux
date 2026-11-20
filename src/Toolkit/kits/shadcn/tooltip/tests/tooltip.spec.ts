import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

async function makeScrollable(page: Page): Promise<void> {
    await page.evaluate(() => {
        const spacer = document.createElement('div');
        spacer.style.height = '3000px';
        document.body.append(spacer);
    });
}

describeRecipe('shadcn/tooltip', () => {
    testState('opens on hover', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Hover' }).hover();

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toHaveText('Add to library');
        },
    });

    testState('opens on the side it is given', {
        example: 'side',
        state: 'open-bottom',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Bottom' });

            await trigger.hover();

            const tooltip = page.getByRole('tooltip');
            await expect(tooltip).toBeVisible();
            const triggerBox = await trigger.boundingBox();
            const tooltipBox = await tooltip.boundingBox();
            expect(tooltipBox!.y).toBeGreaterThan(triggerBox!.y + triggerBox!.height);
        },
    });

    testState('opens on hover of a disabled button', {
        example: 'disabled-button',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Disabled' }).locator('..').hover();

            await expect(page.getByRole('tooltip')).toBeVisible();
            await expect(page.getByRole('tooltip')).toHaveText('This feature is currently unavailable');
        },
    });

    testState('shrinks to the longest line of a wrapped text', {
        example: 'long-content',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Hover' }).hover();

            const tooltip = page.getByRole('tooltip');
            await expect(tooltip).toBeVisible();
            const { lines, gap } = await tooltip.evaluate((element) => {
                const range = document.createRange();
                range.selectNodeContents(element.querySelector('p')!);
                const rects = [...range.getClientRects()];
                const right = Math.max(...rects.map((rect) => rect.right));
                const paddingRight = parseFloat(getComputedStyle(element).paddingRight);

                return { lines: rects.length, gap: element.getBoundingClientRect().right - paddingRight - right };
            });
            expect(lines).toBeGreaterThan(1);
            expect(gap).toBeLessThan(3);
        },
    });

    test('stays inside the viewport', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 360, height: 640 });
        await gotoExample('shadcn/tooltip/long-content');
        await page.getByRole('button', { name: 'Hover' }).hover();

        const tooltip = page.getByRole('tooltip');
        await expect(tooltip).toBeVisible();
        const box = (await tooltip.boundingBox())!;
        expect(box.x).toBeGreaterThanOrEqual(0);
        expect(box.x + box.width).toBeLessThanOrEqual(360);
    });

    test('describes its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');

        await expect(page.getByRole('button', { name: 'Hover' })).toHaveAccessibleDescription('Add to library');
    });

    test('closes when the mouse leaves the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await page.getByRole('button', { name: 'Hover' }).hover();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.mouse.move(0, 0);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('opens on focus and closes on blur', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        const trigger = page.getByRole('button', { name: 'Hover' });

        await page.keyboard.press('Tab');

        await expect(trigger).toBeFocused();
        await expect(page.getByRole('tooltip')).toBeVisible();

        await page.keyboard.press('Tab');

        await expect(trigger).not.toBeFocused();
        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('still opens after being moved in the DOM', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await page.locator('#tooltip-demo').evaluate(async (element) => {
            const parent = element.parentNode!;
            const next = element.nextSibling;
            element.remove();
            await new Promise((resolve) => setTimeout(resolve, 50));
            parent.insertBefore(element, next);
        });

        await page.getByRole('button', { name: 'Hover' }).hover();

        await expect(page.getByRole('tooltip')).toBeVisible();
        await expect(page.getByRole('tooltip')).toHaveText('Add to library');
    });

    test('opens above a modal dialog that contains its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await page.locator('#tooltip-demo').evaluate((element) => {
            const dialog = document.createElement('dialog');
            document.body.append(dialog);
            dialog.append(element);
            dialog.showModal();
        });

        await page.getByRole('button', { name: 'Hover' }).hover();

        const wrapper = page.locator('[data-slot="tooltip-wrapper"]');
        await expect(wrapper.locator('[data-slot="tooltip-content"]')).toBeVisible();
        // A modal dialog is in the top layer, so only another element of the top layer is displayed above it.
        expect(await wrapper.evaluate((element) => element.matches(':popover-open'))).toBe(true);
    });

    test('waits for the delay before opening', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default', { timers: 'fake' });
        await page.locator('#tooltip-demo').evaluate((element) => {
            element.setAttribute('data-tooltip-delay-duration-value', '1000');
        });

        await page.getByRole('button', { name: 'Hover' }).hover();
        await page.clock.runFor(400);

        await expect(page.getByRole('tooltip')).toBeHidden();

        await page.clock.runFor(1000);

        await expect(page.getByRole('tooltip')).toBeVisible();
    });

    test('does not open when the mouse leaves before the delay', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default', { timers: 'fake' });
        await page.locator('#tooltip-demo').evaluate((element) => {
            element.setAttribute('data-tooltip-delay-duration-value', '1000');
        });

        await page.getByRole('button', { name: 'Hover' }).hover();
        await page.clock.runFor(400);
        await page.mouse.move(0, 0);
        await page.clock.runFor(2000);

        await expect(page.getByRole('tooltip')).toBeHidden();
    });

    test('points its arrow at the trigger when it is shifted into the viewport', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 360, height: 640 });
        await gotoExample('shadcn/tooltip/long-content');
        const trigger = page.getByRole('button', { name: 'Hover' });

        await trigger.hover();

        await expect(page.getByRole('tooltip')).toBeVisible();
        await expect(page.getByRole('tooltip')).toHaveCSS('opacity', '1');
        const triggerBox = (await trigger.boundingBox())!;
        const arrowBox = (await page.locator('#tooltip-long-content_arrow').boundingBox())!;
        expect(Math.abs(arrowBox.x + arrowBox.width / 2 - (triggerBox.x + triggerBox.width / 2))).toBeLessThanOrEqual(
            3
        );
    });

    test('keeps the width of its text when its trigger is at the edge of the viewport', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/tooltip/long-content');
        await page.locator('#tooltip-long-content').evaluate((element) => {
            element.style.cssText = 'position: fixed; top: 200px; right: 8px;';
        });

        await page.getByRole('button', { name: 'Hover' }).hover();

        const tooltip = page.getByRole('tooltip');
        await expect(tooltip).toHaveCSS('opacity', '1');
        const tooltipBox = (await tooltip.boundingBox())!;
        expect(tooltipBox.width).toBeGreaterThan(250);
    });

    test('stays against its trigger while the page scrolls', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await makeScrollable(page);
        const trigger = page.getByRole('button', { name: 'Hover' });
        const tooltip = page.getByRole('tooltip');
        await page.keyboard.press('Tab');
        await expect(tooltip).toHaveCSS('opacity', '1');

        await page.evaluate(() => window.scrollBy(0, 40));

        await expect(tooltip).toBeVisible();
        await expect
            .poll(async () => {
                const triggerBox = (await trigger.boundingBox())!;
                const tooltipBox = (await tooltip.boundingBox())!;

                return Math.round(triggerBox.y - (tooltipBox.y + tooltipBox.height));
            })
            .toBeGreaterThan(0);
        const triggerBox = (await trigger.boundingBox())!;
        const tooltipBox = (await tooltip.boundingBox())!;
        expect(triggerBox.y - (tooltipBox.y + tooltipBox.height)).toBeLessThan(12);
        expect(
            Math.abs(tooltipBox.x + tooltipBox.width / 2 - (triggerBox.x + triggerBox.width / 2))
        ).toBeLessThanOrEqual(1);
    });

    test('stays open when the window is resized', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        // Focused rather than hovered: the resize moves the trigger away from the mouse.
        await page.keyboard.press('Tab');
        await expect(page.getByRole('tooltip')).toBeVisible();

        const viewport = page.viewportSize()!;
        await page.setViewportSize({ width: viewport.width - 100, height: viewport.height });
        // Past the closing transition, during which a closing tooltip still counts as visible.
        await page.waitForTimeout(400);

        await expect(page.getByRole('tooltip')).toBeVisible();
        await expect(page.getByRole('tooltip')).toHaveCSS('opacity', '1');
    });

    test('keeps its arrow on the trigger once the window is resized', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 360, height: 640 });
        await gotoExample('shadcn/tooltip/long-content');
        const trigger = page.getByRole('button', { name: 'Hover' });
        // Focused rather than hovered: the resize moves the trigger away from the mouse.
        await page.keyboard.press('Tab');
        await expect(page.getByRole('tooltip')).toHaveCSS('opacity', '1');

        await page.setViewportSize({ width: 500, height: 640 });

        await expect
            .poll(async () => {
                const triggerBox = (await trigger.boundingBox())!;
                const arrowBox = (await page.locator('#tooltip-long-content_arrow').boundingBox())!;

                return Math.abs(arrowBox.x + arrowBox.width / 2 - (triggerBox.x + triggerBox.width / 2));
            })
            .toBeLessThanOrEqual(3);
    });

    test('centres the tooltip over its trigger in right-to-left', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await page.locator('#tooltip-demo').evaluate((element) => {
            element.parentElement!.dir = 'rtl';
        });
        const trigger = page.getByRole('button', { name: 'Hover' });

        await trigger.hover();

        const tooltip = page.getByRole('tooltip');
        await expect(tooltip).toHaveCSS('opacity', '1');
        const triggerBox = (await trigger.boundingBox())!;
        const tooltipBox = (await tooltip.boundingBox())!;
        expect(
            Math.abs(tooltipBox.x + tooltipBox.width / 2 - (triggerBox.x + triggerBox.width / 2))
        ).toBeLessThanOrEqual(1);
    });

    test('opens against its trigger again after a morph strips what the controller set', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/tooltip/default');
        const wrapper = page.locator('[data-slot="tooltip-wrapper"]');
        await page.locator('#tooltip-demo').evaluate((element) => {
            const wrapper = element.querySelector<HTMLElement>('[data-slot="tooltip-wrapper"]')!;
            delete wrapper.dataset.anchored;
            wrapper.removeAttribute('style');
            element.querySelector('[data-slot="tooltip-trigger"]')!.removeAttribute('style');
        });
        const trigger = page.getByRole('button', { name: 'Hover' });

        await trigger.hover();

        const tooltip = page.getByRole('tooltip');
        await expect(tooltip).toHaveCSS('opacity', '1');
        await expect(wrapper).toHaveAttribute('data-anchored');
        const triggerBox = (await trigger.boundingBox())!;
        const tooltipBox = (await tooltip.boundingBox())!;
        expect(
            Math.abs(tooltipBox.x + tooltipBox.width / 2 - (triggerBox.x + triggerBox.width / 2))
        ).toBeLessThanOrEqual(1);
    });

    test('removes the anchor of its trigger when its content is removed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        const trigger = page.getByRole('button', { name: 'Hover' });
        await page.locator('[data-slot="tooltip-wrapper"]').evaluate((wrapper) => wrapper.remove());

        await trigger.hover();
        await page.waitForTimeout(300);

        await expect(trigger).not.toHaveAttribute('style', /anchor-name/);
    });

    test('reflects a tooltip hidden by something else', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        await page.getByRole('button', { name: 'Hover' }).hover();
        await expect(page.getByRole('tooltip')).toHaveCSS('opacity', '1');

        await page.locator('[data-slot="tooltip-wrapper"]').evaluate((wrapper) => wrapper.hidePopover());

        await expect(page.locator('[data-slot="tooltip-content"]')).not.toHaveAttribute('open');
    });

    test('closes once its controller disconnects', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tooltip/default');
        const trigger = page.getByRole('button', { name: 'Hover' });
        await trigger.hover();
        await expect(page.getByRole('tooltip')).toHaveCSS('opacity', '1');

        await page.locator('#tooltip-demo').evaluate((element) => element.removeAttribute('data-controller'));

        await expect(page.locator('[data-slot="tooltip-content"]')).not.toHaveAttribute('open');
        await expect(trigger).not.toHaveAttribute('style', /anchor-name/);
    });

    test.describe('without anchor positioning', () => {
        test.beforeEach(async ({ page }) => {
            await page.addInitScript(() => {
                const supports = CSS.supports.bind(CSS);
                CSS.supports = (...args: [string, string?]) =>
                    args[0].startsWith('position-area') ? false : supports(...(args as [string]));
            });
        });

        test('opens above a modal dialog that contains its trigger', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/tooltip/default');
            await page.locator('#tooltip-demo').evaluate((element) => {
                const dialog = document.createElement('dialog');
                document.body.append(dialog);
                dialog.append(element);
                dialog.showModal();
            });

            await page.getByRole('button', { name: 'Hover' }).hover();

            const wrapper = page.locator('[data-slot="tooltip-wrapper"]');
            await expect(wrapper.locator('[data-slot="tooltip-content"]')).toBeVisible();
            await expect(wrapper).not.toHaveAttribute('data-anchored');
            expect(await wrapper.evaluate((element) => element.matches(':popover-open'))).toBe(true);
        });

        test('moves to the body and closes on scroll', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/tooltip/default');
            await makeScrollable(page);
            const tooltip = page.getByRole('tooltip');
            await page.keyboard.press('Tab');
            await expect(tooltip).toBeVisible();
            await expect(page.locator('body > [data-slot="tooltip-wrapper"]')).toHaveCount(1);

            await page.evaluate(() => window.scrollBy(0, 40));

            await expect(tooltip).toBeHidden();
        });

        test('closes when the window is resized', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/tooltip/default');
            await page.getByRole('button', { name: 'Hover' }).hover();
            await expect(page.getByRole('tooltip')).toBeVisible();

            const viewport = page.viewportSize()!;
            await page.setViewportSize({ width: viewport.width - 100, height: viewport.height });

            await expect(page.getByRole('tooltip')).toBeHidden();
        });
    });
});
