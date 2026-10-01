import type { Locator, Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const panel = (page: Page, text: string) => page.getByText(text, { exact: true }).locator('../..');

const size = async (locator: Locator, axis: 'width' | 'height' = 'width') => {
    const box = await locator.boundingBox();

    return box![axis];
};

const drag = async (page: Page, handle: Locator, dx: number, dy: number) => {
    const box = (await handle.boundingBox())!;
    const x = box.x + box.width / 2;
    const y = box.y + box.height / 2;

    await page.mouse.move(x, y);
    await page.mouse.down();
    await page.mouse.move(x + dx, y + dy, { steps: 5 });
    await page.mouse.up();
};

describeRecipe('shadcn/resizable', () => {
    testState('resizes the panels when the handle is dragged', {
        example: 'handle',
        state: 'dragged',
        act: async (page) => {
            const sidebar = panel(page, 'Sidebar');
            const before = await size(sidebar);

            await drag(page, page.getByRole('separator'), 100, 0);
            await page.mouse.move(0, 0);

            await expect.poll(() => size(sidebar)).toBeCloseTo(before + 100, 0);
        },
    });

    testState('resizes a vertical group with the keyboard', {
        example: 'vertical',
        state: 'resized',
        act: async (page) => {
            const header = panel(page, 'Header');
            const before = await size(header, 'height');
            await page.getByRole('separator').focus();

            await page.keyboard.press('Shift+ArrowDown');

            await expect.poll(() => size(header, 'height')).toBeCloseTo(before + 40, 0);
        },
    });

    testState('moves a handle by one step per key press after being moved in the DOM', {
        example: 'handle',
        state: 'resized-after-move',
        act: async (page) => {
            await page.locator('[data-controller="resizable"]').evaluate(async (element) => {
                const parent = element.parentNode!;
                const next = element.nextSibling;
                element.remove();
                await new Promise((resolve) => setTimeout(resolve, 50));
                parent.insertBefore(element, next);
            });
            const sidebar = panel(page, 'Sidebar');
            const before = await size(sidebar);
            await page.getByRole('separator').focus();

            await page.keyboard.press('ArrowRight');

            await expect.poll(() => size(sidebar)).toBeCloseTo(before + 8, 0);
        },
    });

    test('gives the dragged space to one panel and takes it from the other', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/default');
        const one = panel(page, 'One');
        const nested = panel(page, 'Two').locator('..');
        const total = (await size(one)) + (await size(nested));

        await drag(page, page.getByRole('separator').first(), -60, 0);

        await expect.poll(async () => (await size(one)) + (await size(nested))).toBeCloseTo(total, 0);
        await expect.poll(() => size(one)).toBeCloseTo(total / 2 - 60, 0);
    });

    test('resizes a nested vertical group by dragging its handle', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/default');
        const two = panel(page, 'Two');
        const three = panel(page, 'Three');
        const before = await size(two, 'height');

        await drag(page, page.getByRole('separator').nth(1), 0, 30);

        await expect.poll(() => size(two, 'height')).toBeCloseTo(before + 30, 0);
        await expect(panel(page, 'One')).toBeVisible();
        expect(await size(three, 'height')).toBeGreaterThan(0);
    });

    test('keeps each panel at least 20px wide when dragged past the edges', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/handle');
        const sidebar = panel(page, 'Sidebar');
        const content = panel(page, 'Content');
        const handle = page.getByRole('separator');
        const total = (await size(sidebar)) + (await size(content));

        await drag(page, handle, -500, 0);

        await expect.poll(() => size(sidebar)).toBeCloseTo(20, 0);

        await drag(page, handle, 1000, 0);

        await expect.poll(() => size(content)).toBeCloseTo(20, 0);
        await expect.poll(() => size(sidebar)).toBeCloseTo(total - 20, 0);
    });

    test('moves a horizontal handle with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/handle');
        const sidebar = panel(page, 'Sidebar');
        const before = await size(sidebar);
        const handle = page.getByRole('separator');
        await handle.focus();

        await page.keyboard.press('ArrowRight');

        await expect.poll(() => size(sidebar)).toBeCloseTo(before + 8, 0);

        await page.keyboard.press('Shift+ArrowRight');

        await expect.poll(() => size(sidebar)).toBeCloseTo(before + 48, 0);

        await page.keyboard.press('ArrowLeft');
        await page.keyboard.press('ArrowLeft');

        await expect.poll(() => size(sidebar)).toBeCloseTo(before + 32, 0);
        await expect(handle).toBeFocused();
    });

    test('ignores the arrow keys of the other orientation', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/vertical');
        const header = panel(page, 'Header');
        const before = await size(header, 'height');
        await page.getByRole('separator').focus();

        await page.keyboard.press('ArrowRight');
        await page.keyboard.press('ArrowLeft');
        await page.keyboard.press('ArrowRight');

        expect(await size(header, 'height')).toBeCloseTo(before, 0);

        await page.keyboard.press('ArrowUp');

        await expect.poll(() => size(header, 'height')).toBeCloseTo(before - 8, 0);
    });

    test('keeps each panel at least 20px tall with the keyboard', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/vertical');
        const header = panel(page, 'Header');
        await page.getByRole('separator').focus();

        for (let i = 0; i < 5; i++) {
            await page.keyboard.press('Shift+ArrowUp');
        }

        await expect.poll(() => size(header, 'height')).toBeCloseTo(20, 0);
    });

    test('mirrors the horizontal arrow keys and the drag direction in right-to-left', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/rtl');
        const first = panel(page, 'واحد');
        const before = await size(first);
        const handle = page.getByRole('separator').first();
        await handle.focus();

        await page.keyboard.press('ArrowLeft');

        await expect.poll(() => size(first)).toBeCloseTo(before + 8, 0);

        await drag(page, handle, -40, 0);

        await expect.poll(() => size(first)).toBeCloseTo(before + 48, 0);
    });

    test('exposes the orientation and the position of each handle', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/resizable/default');
        const [outer, inner] = await page.getByRole('separator').all();

        await expect(outer).toHaveAttribute('aria-orientation', 'vertical');
        await expect(inner).toHaveAttribute('aria-orientation', 'horizontal');
        await expect(outer).toHaveAttribute('aria-valuenow', '50');
        await expect(inner).toHaveAttribute('aria-valuenow', '25');

        await outer.focus();
        await page.keyboard.press('Shift+ArrowRight');

        await expect(outer).not.toHaveAttribute('aria-valuenow', '50');
    });
});
