import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/trace', () => {
    testState('expands on click', {
        example: 'collapsible',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Ran node --version' });

            await trigger.click();
            await page.mouse.move(0, 0);

            await expect(page.getByText('v24.18.0')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('starts open when the row is open', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/trace/open-state');

        await expect(page.getByRole('button', { name: 'Ran node --version' })).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByText('v24.18.0')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Ran pnpm --version' })).toHaveAttribute(
            'aria-expanded',
            'false'
        );
        await expect(page.getByText('10.33.4')).toBeHidden();
    });

    test('opens a row from a button outside its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/trace/open-state');
        const toggle = page.getByRole('button', { name: 'Show the pnpm output' });

        await toggle.click();

        await expect(page.getByText('10.33.4')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Ran pnpm --version' })).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('button', { name: 'Hide the pnpm output' })).toHaveAttribute(
            'aria-expanded',
            'true'
        );
    });

    test('toggles a nested row without closing its parent', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/trace/whole-turn');
        const parent = page.getByRole('button', { name: 'Worked for 2m 4s, 1 command failed' });
        const nested = page.getByRole('button', { name: 'Failed to run pnpm --version' });

        await nested.click();

        await expect(page.getByText('command not found: pnpm')).toBeVisible();
        await expect(parent).toHaveAttribute('aria-expanded', 'true');
    });

    test('opens a dialog from a line', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/trace/dialog');

        await page.getByRole('button', { name: 'Searched the web for cache keys' }).click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByRole('link', { name: /Caching build outputs/ })).toBeVisible();
    });
});
