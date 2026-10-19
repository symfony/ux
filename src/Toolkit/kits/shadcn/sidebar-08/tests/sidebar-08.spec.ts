import { describeRecipe, expect, isUnderPointer, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-08', () => {
    testState('expands a submenu on a click on its toggle', {
        example: 'default',
        state: 'submenu-open',
        fullPage: false,
        act: async (page) => {
            const toggle = page
                .getByRole('listitem')
                .filter({ has: page.getByRole('link', { name: 'Models' }) })
                .getByRole('button', { name: 'Toggle' });

            await toggle.click();

            await expect(toggle).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('link', { name: 'Genesis' })).toBeVisible();
        },
    });

    testState('opens the menu of a project', {
        example: 'default',
        state: 'project-menu-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page
                .getByRole('listitem')
                .filter({ has: page.getByRole('link', { name: 'Design Engineering' }) })
                .getByRole('button', { name: 'More' });

            await trigger.click();

            const viewProject = page.getByRole('menuitem', { name: 'View Project' });
            await expect(viewProject).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect.poll(() => isUnderPointer(viewProject)).toBe(true);
        },
    });

    testState('opens the user menu', {
        example: 'default',
        state: 'user-menu-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: /m@example\.com/ });

            await trigger.click();

            await expect(page.getByRole('menu')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeInViewport();
        },
    });
});
