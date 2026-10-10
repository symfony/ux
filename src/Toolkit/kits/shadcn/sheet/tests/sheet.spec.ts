import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sheet', () => {
    testState('opens on click and focuses the first field', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open', exact: true });

            await trigger.click();

            await expect(page.getByRole('dialog', { name: 'Edit profile' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByLabel('Name', { exact: true })).toBeFocused();
        },
    });

    testState('opens from the left edge', {
        example: 'sides',
        state: 'left-open',
        act: async (page) => {
            await page.getByRole('button', { name: /^left$/i }).click();

            const dialog = page.getByRole('dialog', { name: 'Edit profile' });
            await expect(dialog).toBeVisible();
            await expect(dialog).toHaveAttribute('data-side', 'left');
        },
    });

    testState('opens in right-to-left', {
        example: 'rtl',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'فتح' }).click();

            await expect(page.getByRole('dialog', { name: 'تعديل الملف الشخصي' })).toBeVisible();
        },
    });

    test('exposes the title and the description', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/default');

        await page.getByRole('button', { name: 'Open', exact: true }).click();

        const dialog = page.getByRole('dialog', { name: 'Edit profile' });
        await expect(dialog).toHaveAccessibleDescription(
            "Make changes to your profile here. Click save when you're done."
        );
    });

    test('slides in against the edge given by the side prop', async ({ page, gotoExample }) => {
        // Wider than the content, so a top or bottom sheet that does not stretch shows.
        await page.setViewportSize({ width: 1600, height: 900 });
        await gotoExample('shadcn/sheet/sides');
        const viewport = page.viewportSize()!;

        for (const side of ['top', 'right', 'bottom', 'left']) {
            await page.getByRole('button', { name: new RegExp(`^${side}$`, 'i') }).click();
            const dialog = page.getByRole('dialog');
            await expect(dialog).toBeVisible();
            await dialog.evaluate((element) =>
                Promise.all(element.getAnimations().map((animation) => animation.finished))
            );

            const box = (await dialog.boundingBox())!;
            const edges = {
                top: box.y,
                right: viewport.width - (box.x + box.width),
                bottom: viewport.height - (box.y + box.height),
                left: box.x,
            };
            expect(Math.abs(edges[side as keyof typeof edges])).toBeLessThan(1);
            if (side === 'top' || side === 'bottom') {
                expect(box.width).toBe(viewport.width);
            }

            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
        }
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/default');
        const trigger = page.getByRole('button', { name: 'Open', exact: true });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes with the built-in close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/rtl');
        const trigger = page.getByRole('button', { name: 'فتح' });
        await trigger.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByRole('button', { name: 'Close', exact: true }).click();

        await expect(dialog).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes with a Sheet:Close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/sides');
        await page.getByRole('button', { name: /^right$/i }).click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByRole('button', { name: 'Cancel' }).click();

        await expect(dialog).toBeHidden();
    });

    test('closes on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/default');
        const trigger = page.getByRole('button', { name: 'Open', exact: true });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open on a click inside the content', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/default');
        await page.getByRole('button', { name: 'Open', exact: true }).click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByText('Edit profile').click();

        await expect(dialog).toBeVisible();
    });

    test('keeps aria-expanded in sync when opened a second time', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/sides');
        const trigger = page.getByRole('button', { name: /^right$/i });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await trigger.click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await page
            .getByRole('dialog')
            .evaluate((dialog) => Promise.all(dialog.getAnimations().map((animation) => animation.finished)));
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('closes without a built-in close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/no-close-button');
        await page.getByRole('button', { name: 'Open Sheet' }).click();
        const dialog = page.getByRole('dialog', { name: 'No Close Button' });
        await expect(dialog).toBeVisible();
        await expect(dialog.getByRole('button')).toHaveCount(0);

        await page.mouse.click(10, 10);

        await expect(dialog).toBeHidden();
    });

    test('slides out before closing', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/sides');
        await page.getByRole('button', { name: /^right$/i }).click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => Promise.all(element.getAnimations().map((animation) => animation.finished)));

        await dialog.getByRole('button', { name: 'Cancel' }).click();

        // Still open while the exit transition plays, in every browser.
        await expect(dialog).toHaveAttribute('data-closing', '');
        expect(await dialog.evaluate((element) => (element as HTMLDialogElement).open)).toBe(true);
        await expect(dialog).toBeHidden();
        // A closed <dialog> leaves the accessibility tree, so find it by its side.
        await expect(page.locator('dialog[data-side="right"]')).not.toHaveAttribute('data-closing');
    });

    test('leaves the top layer as soon as it closes', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sheet/default');
        await page.getByRole('button', { name: 'Open', exact: true }).click();
        const dialog = page.locator('[data-slot="sheet-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => {
            element.addEventListener(
                'close',
                () => {
                    element.dataset.displayOnClose = getComputedStyle(element).display;
                },
                { once: true }
            );
        });

        await page.keyboard.press('Escape');

        await expect(dialog).toHaveAttribute('data-display-on-close', 'none');
    });
});
