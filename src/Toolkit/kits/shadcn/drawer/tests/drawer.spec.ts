import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/drawer', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open Drawer' });

            await trigger.click();

            await expect(page.getByRole('dialog', { name: 'Move Goal' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('opens a drawer from the right edge', {
        example: 'directions',
        state: 'right-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'right', exact: true }).click();

            await expect(page.getByRole('dialog', { name: 'Move Goal' })).toBeVisible();
        },
    });

    testState('opens a right-to-left drawer', {
        example: 'rtl',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'فتح' }).click();

            await expect(page.getByRole('dialog', { name: 'تعديل الهدف' })).toBeVisible();
        },
    });

    test('exposes the title and the description', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');

        await page.getByRole('button', { name: 'Open Drawer' }).click();

        await expect(page.getByRole('dialog', { name: 'Move Goal' })).toHaveAccessibleDescription(
            'Set your daily activity goal.'
        );
    });

    for (const direction of ['top', 'right', 'bottom', 'left'] as const) {
        test(`slides in against the ${direction} edge`, async ({ page, gotoExample }) => {
            await gotoExample('shadcn/drawer/directions');
            const viewport = page.viewportSize()!;

            await page.getByRole('button', { name: direction, exact: true }).click();

            const dialog = page.getByRole('dialog', { name: 'Move Goal' });
            await expect(dialog).toBeVisible();
            await dialog.evaluate((element) =>
                Promise.all(element.getAnimations().map((animation) => animation.finished))
            );
            const box = (await dialog.boundingBox())!;
            const distances = {
                top: box.y,
                right: viewport.width - (box.x + box.width),
                bottom: viewport.height - (box.y + box.height),
                left: box.x,
            };
            expect(Math.abs(distances[direction])).toBeLessThan(1);
        });
    }

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes with a Drawer:Close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByRole('button', { name: 'Cancel' }).click();

        await expect(dialog).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open on a click inside the content', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        await page.getByRole('button', { name: 'Open Drawer' }).click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByText('Set your daily activity goal.').click();

        await expect(dialog).toBeVisible();
    });
});
