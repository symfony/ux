import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/accordion', () => {
    testState('opens another item and closes the open one', {
        example: 'examples',
        state: 'second-open',
        act: async (page) => {
            const first = page.getByRole('button', { name: 'Accordion Item #1' });
            const second = page.getByRole('button', { name: 'Accordion Item #2' });

            await second.click();

            await expect(page.getByText("This is the second item's accordion body.")).toBeVisible();
            await expect(page.getByText("This is the first item's accordion body.")).toBeHidden();
            await expect(second).toHaveAttribute('aria-expanded', 'true');
            await expect(second).not.toHaveClass(/\bcollapsed\b/);
            await expect(first).toHaveAttribute('aria-expanded', 'false');
            await expect(first).toHaveClass(/\bcollapsed\b/);
        },
    });

    testState('keeps several items open when always open', {
        example: 'always-open',
        state: 'several-open',
        act: async (page) => {
            const second = page.getByRole('button', { name: 'Accordion Item #2' });

            await second.click();

            await expect(page.getByText('Multiple items can stay open')).toBeVisible();
            await expect(second).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByText('This item starts open')).toBeVisible();
            await expect(page.getByRole('button', { name: 'Accordion Item #1' })).toHaveAttribute(
                'aria-expanded',
                'true'
            );
        },
    });

    test('opens a flush item on click', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/accordion/flush');
        const trigger = page.getByRole('button', { name: 'Accordion Item #1' });
        const panel = page.getByText('Placeholder content for this edge-to-edge accordion item.');
        await expect(panel).toBeHidden();

        await trigger.click();

        await expect(panel).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('collapses the open item on a click on its button', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/accordion/examples');
        const trigger = page.getByRole('button', { name: 'Accordion Item #1' });
        const panel = page.getByText("This is the first item's accordion body.");
        await expect(panel).toBeVisible();

        await trigger.click();

        await expect(panel).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toHaveClass(/\bcollapsed\b/);
    });

    test('toggles an item with Enter and Space', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/accordion/examples');
        const trigger = page.getByRole('button', { name: 'Accordion Item #3' });
        const panel = page.getByText("This is the third item's accordion body.");
        await trigger.focus();

        await page.keyboard.press('Enter');

        await expect(panel).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(trigger).toBeFocused();
        // Bootstrap ignores a toggle while the collapse transition runs.
        await expect(page.locator('#accordion-item-three-collapse')).toHaveClass(/\bshow\b/);

        await page.keyboard.press('Space');

        await expect(panel).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });
});
