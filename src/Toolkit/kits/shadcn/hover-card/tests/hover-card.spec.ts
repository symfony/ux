import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

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
});
