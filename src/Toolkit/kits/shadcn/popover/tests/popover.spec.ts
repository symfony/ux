import type { Locator, Page } from '@playwright/test';
import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

// `clip` rather than `hidden`, which the focus moving into the content would scroll.
async function clipPopover(page: Page): Promise<void> {
    await page
        .locator('[data-slot="popover"]')
        .first()
        .evaluate((popover) => {
            const box = popover.parentElement!;
            box.style.overflow = 'clip';
            box.style.minHeight = '0';
            box.style.height = '80px';
        });
}

async function finishAnimations(locator: Locator): Promise<void> {
    await locator.evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));
}

async function pinToBottom(page: Page): Promise<void> {
    await page.locator('[data-slot="popover"]').evaluate((popover) => {
        popover.style.position = 'fixed';
        popover.style.bottom = '16px';
        popover.style.left = '50%';
    });
}

describeRecipe('shadcn/popover', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open Popover' });

            await trigger.click();

            await expect(page.getByRole('dialog')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByLabel('Width', { exact: true })).toBeFocused();
        },
    });

    test('focuses the content even when it becomes visible late', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        // Stands in for a busy browser, where the visibility transition lags behind the frames.
        await page.addStyleTag({ content: '[data-popover-target="content"] { transition-delay: 100ms; }' });

        await page.getByRole('button', { name: 'Open Popover' }).click();

        await expect(page.getByLabel('Width', { exact: true })).toBeFocused();
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        const trigger = page.getByRole('button', { name: 'Open Popover' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await page.getByRole('button', { name: 'Open Popover' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
    });

    test('closes on a second click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        const trigger = page.getByRole('button', { name: 'Open Popover' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes the other popover of its group', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/alignments');
        await page.getByRole('button', { name: 'Start' }).click();
        await expect(page.getByText('Aligned to start')).toBeVisible();

        await page.getByRole('button', { name: 'End' }).click();

        await expect(page.getByText('Aligned to end')).toBeVisible();
        await expect(page.getByText('Aligned to start')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Start' })).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens from a submit trigger inside a form without submitting it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await page.locator('[data-slot="popover"]').evaluate((popover) => {
            const form = document.createElement('form');
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                form.dataset.submitted = 'true';
            });
            popover.before(form);
            form.append(popover);
            popover.querySelector('[data-slot="popover-trigger"]')!.removeAttribute('type');
        });

        await page.getByRole('button', { name: 'Open Popover' }).click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.locator('form')).not.toHaveAttribute('data-submitted');
    });

    test('stays open on a click inside its root, outside the content', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await page.locator('[data-slot="popover"]').evaluate((popover) => {
            const input = document.createElement('input');
            input.setAttribute('aria-label', 'Inside the root');
            popover.prepend(input);
        });
        await page.getByRole('button', { name: 'Open Popover' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.getByLabel('Inside the root').click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByLabel('Inside the root')).toBeFocused();
    });

    test('gives focus back to the trigger on Escape when opened from code', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await page.locator('[data-slot="popover"]').evaluate((popover) => {
            popover.dataset.popoverOpenValue = 'true';
        });
        await expect(page.getByLabel('Width', { exact: true })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Open Popover' })).toBeFocused();
    });

    test('opens against its root, as without anchor positioning', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/alignments');
        const popover = page.locator('[data-slot="popover"]').first();
        await popover.evaluate((element) => {
            element.style.paddingInlineStart = '40px';
        });

        await page.getByRole('button', { name: 'Start' }).click();

        const content = page.getByText('Aligned to start');
        await expect(content).toBeVisible();
        await finishAnimations(content);
        const popoverBox = (await popover.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(Math.abs(contentBox.x - popoverBox.x)).toBeLessThanOrEqual(1);
    });

    test('is not clipped by an ancestor that hides its overflow', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await clipPopover(page);

        await page.getByRole('button', { name: 'Open Popover' }).click();

        const content = page.getByRole('dialog');
        await expect(content).toBeVisible();
        await expect(page.getByLabel('Width', { exact: true })).toBeFocused();
        expect(await isUnderPointer(content)).toBe(true);
    });

    test('opens against its trigger, below it and aligned to its start', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/alignments');
        const trigger = page.getByRole('button', { name: 'Start' });

        await trigger.click();

        const content = page.getByText('Aligned to start');
        await expect(content).toBeVisible();
        await finishAnimations(content);
        const triggerBox = (await trigger.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(Math.abs(contentBox.y - (triggerBox.y + triggerBox.height + 8))).toBeLessThanOrEqual(1);
        expect(Math.abs(contentBox.x - triggerBox.x)).toBeLessThanOrEqual(1);
    });

    test('aligns the content to the start of its trigger in right-to-left', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/alignments');
        await page
            .locator('[data-slot="popover"]')
            .first()
            .evaluate((popover) => {
                popover.parentElement!.dir = 'rtl';
            });
        const trigger = page.getByRole('button', { name: 'Start' });

        await trigger.click();

        const content = page.getByText('Aligned to start');
        await expect(content).toBeVisible();
        await finishAnimations(content);
        const triggerBox = (await trigger.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(Math.abs(contentBox.x + contentBox.width - (triggerBox.x + triggerBox.width))).toBeLessThanOrEqual(1);
    });

    test('centres the content under its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/alignments');
        const trigger = page.getByRole('button', { name: 'Center' });

        await trigger.click();

        const content = page.getByText('Aligned to center');
        await expect(content).toBeVisible();
        await finishAnimations(content);
        const triggerBox = (await trigger.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(
            Math.abs(contentBox.x + contentBox.width / 2 - (triggerBox.x + triggerBox.width / 2))
        ).toBeLessThanOrEqual(1);
    });

    test('flips above its trigger when there is no room below', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        const trigger = page.getByRole('button', { name: 'Open Popover' });
        await pinToBottom(page);

        await trigger.click();

        const content = page.getByRole('dialog');
        await expect(content).toBeVisible();
        await finishAnimations(content);
        const triggerBox = (await trigger.boundingBox())!;
        const contentBox = (await content.boundingBox())!;
        expect(contentBox.y + contentBox.height).toBeLessThanOrEqual(triggerBox.y);
        expect(contentBox.y).toBeGreaterThanOrEqual(0);
    });

    test('grows from the edge next to its trigger once flipped above it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await pinToBottom(page);

        await page.getByRole('button', { name: 'Open Popover' }).click();

        const content = page.getByRole('dialog');
        await expect(content).toBeVisible();
        const origin = await content.evaluate((element) => ({
            y: parseFloat(getComputedStyle(element).transformOrigin.split(' ')[1]),
            height: element.offsetHeight,
        }));
        expect(Math.abs(origin.y - origin.height)).toBeLessThanOrEqual(1);
    });

    test('opens again after a morph strips what the controller set', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        const content = page.locator('[data-slot="popover-content"]');
        await page.locator('[data-slot="popover"]').evaluate((popover) => {
            const content = popover.querySelector<HTMLElement>('[data-slot="popover-content"]')!;
            content.removeAttribute('popover');
            content.removeAttribute('style');
            popover.removeAttribute('style');
        });

        await page.getByRole('button', { name: 'Open Popover' }).click();

        await expect(content).toBeVisible();
        await expect(content).toHaveAttribute('popover', 'manual');
    });

    test('cleans up when its content is removed, and still toggles', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        const popover = page.locator('[data-slot="popover"]');
        await popover.evaluate((element) => {
            element.querySelector('[data-slot="popover-content"]')!.remove();
            // Moving the root disconnects and reconnects the controller.
            element.parentElement!.append(element);
        });
        const trigger = page.getByRole('button', { name: 'Open Popover' });

        await trigger.click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(popover).not.toHaveAttribute('style', /anchor-name/);
    });

    test('plays its exit transition when it closes', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await page.getByRole('button', { name: 'Open Popover' }).click();
        // Found by its slot: a hidden dialog leaves the accessibility tree.
        const content = page.locator('[data-slot="popover-content"]');
        await expect(content).toBeVisible();
        await finishAnimations(content);

        const whileClosing = await content.evaluate((element) => {
            element.hidePopover();

            return {
                state: element.dataset.state,
                display: getComputedStyle(element).display,
                animated: element.getAnimations().length > 0,
            };
        });

        expect(whileClosing).toEqual({ state: 'closed', display: 'flex', animated: true });
        await expect(content).toBeHidden();
        await finishAnimations(content);
        // A closed popover takes no room, so content placed against a trigger at the edge widens nothing.
        await expect(content).toHaveCSS('display', 'none');
    });

    test.describe('without anchor positioning', () => {
        test.beforeEach(async ({ page }) => {
            await page.addInitScript(() => {
                const supports = CSS.supports.bind(CSS);
                CSS.supports = (...args: [string, string?]) =>
                    'position-area' === args[0] || args[0].startsWith('position-area')
                        ? false
                        : supports(...(args as [string]));
            });
        });

        test('opens in place and closes on Escape or on a click outside', async ({ page, gotoExample }) => {
            await gotoExample('shadcn/popover/default');
            const trigger = page.getByRole('button', { name: 'Open Popover' });
            const content = page.getByRole('dialog');

            await trigger.click();

            await expect(content).toBeVisible();
            await expect(content).not.toHaveAttribute('popover');
            await expect(page.getByLabel('Width', { exact: true })).toBeFocused();

            await page.keyboard.press('Escape');

            await expect(content).toBeHidden();
            await expect(trigger).toBeFocused();

            await trigger.click();
            await expect(content).toBeVisible();

            await page.mouse.click(10, 10);

            await expect(content).toBeHidden();
            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        });

        test('opens below its trigger, clipped by an ancestor that hides its overflow', async ({
            page,
            gotoExample,
        }) => {
            await gotoExample('shadcn/popover/default');
            await clipPopover(page);

            await page.getByRole('button', { name: 'Open Popover' }).click();

            const content = page.getByRole('dialog');
            await expect(content).toBeVisible();
            expect(await isUnderPointer(content)).toBe(false);
        });
    });
});
