import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/collapsible', () => {
    testState('expands on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Toggle details' });

            await trigger.click();
            await page.mouse.move(0, 0);

            await expect(page.getByText('Shipping address')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('expands nested folders', {
        example: 'file-tree',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'components' }).click();
            await page.getByRole('button', { name: 'ui', exact: true }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('button', { name: 'button.tsx' })).toBeVisible();
            await expect(page.getByRole('button', { name: 'login-form.tsx' })).toBeVisible();
            await expect(page.getByRole('button', { name: 'utils.ts' })).toBeHidden();
        },
    });

    test('starts collapsed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/collapsible/basic');

        await expect(page.getByRole('button', { name: 'Product details' })).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('button', { name: 'Learn More' })).toBeHidden();
    });

    test('collapses on a second click', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/collapsible/basic');
        const trigger = page.getByRole('button', { name: 'Product details' });
        await trigger.click();
        await expect(page.getByRole('button', { name: 'Learn More' })).toBeVisible();
        await expect(trigger).toHaveAttribute('data-state', 'open');

        await trigger.click();

        await expect(page.getByRole('button', { name: 'Learn More' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toHaveAttribute('data-state', 'closed');
    });

    test('toggles with Enter and Space', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/collapsible/basic');
        const trigger = page.getByRole('button', { name: 'Product details' });
        await trigger.focus();

        await page.keyboard.press('Enter');

        await expect(page.getByRole('button', { name: 'Learn More' })).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Space');

        await expect(page.getByRole('button', { name: 'Learn More' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('hides the nested content when a parent folder collapses', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/collapsible/file-tree');
        const components = page.getByRole('button', { name: 'components' });
        await components.click();
        await page.getByRole('button', { name: 'ui', exact: true }).click();
        await expect(page.getByRole('button', { name: 'button.tsx' })).toBeVisible();

        await components.click();

        await expect(page.getByRole('button', { name: 'button.tsx' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'ui', exact: true })).toBeHidden();
        await expect(components).toHaveAttribute('aria-expanded', 'false');
    });
});
