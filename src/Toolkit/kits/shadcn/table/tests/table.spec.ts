import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/table', () => {
    testState('opens the actions of a row', {
        example: 'actions',
        state: 'open',
        act: async (page) => {
            const row = page.getByRole('row', { name: 'Mechanical Keyboard' });
            const trigger = row.getByRole('button', { name: 'Open menu' });

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('menuitem', { name: 'Duplicate' })).toBeVisible();
            await expect(page.getByRole('menu')).toHaveCount(1);
            await page.mouse.move(0, 0);
        },
    });
});
