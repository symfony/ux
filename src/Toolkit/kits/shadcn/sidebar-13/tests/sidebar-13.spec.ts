import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-13', () => {
    testState('opens the settings dialog', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open Dialog' });

            await trigger.click();

            const dialog = page.getByRole('dialog', { name: 'Settings' });
            await expect(dialog).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(
                dialog.getByRole('complementary').getByRole('link', { name: 'Messages & media' })
            ).toHaveAttribute('aria-current', 'page');
        },
    });
});
