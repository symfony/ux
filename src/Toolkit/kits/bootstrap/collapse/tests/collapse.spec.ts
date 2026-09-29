import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

// Bootstrap sets `show` once the transition ends, and ignores a toggle until then.
const SHOWN = /(^|\s)show(\s|$)/;

describeRecipe('bootstrap/collapse', () => {
    testState('expands on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Show project details' });

            await trigger.click();
            await page.mouse.move(0, 0);

            await expect(page.getByText('This panel is controlled by')).toBeVisible();
            await expect(page.locator('#collapse-demo')).toHaveClass(SHOWN);
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('starts collapsed', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/collapse/default');

        await expect(page.getByText('This panel is controlled by')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Show project details' })).toHaveAttribute(
            'aria-expanded',
            'false'
        );
    });

    test('collapses on a second click', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/collapse/default');
        const trigger = page.getByRole('button', { name: 'Show project details' });
        await trigger.click();
        await expect(page.locator('#collapse-demo')).toHaveClass(SHOWN);

        await trigger.click();

        await expect(page.getByText('This panel is controlled by')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('toggles the same panel from a link and from a button', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/collapse/examples');
        const link = page.getByRole('button', { name: 'Link with href' });
        const button = page.getByRole('button', { name: 'Button with data-bs-target' });
        const content = page.getByText('Some placeholder content');

        await link.click();

        await expect(content).toBeVisible();
        await expect(page.locator('#collapse-example')).toHaveClass(SHOWN);
        await expect(link).toHaveAttribute('aria-expanded', 'true');
        await expect(button).toHaveAttribute('aria-expanded', 'true');

        await button.click();

        await expect(content).toBeHidden();
        await expect(link).toHaveAttribute('aria-expanded', 'false');
        await expect(button).toHaveAttribute('aria-expanded', 'false');
        await expect(page).toHaveURL(/\/bootstrap\/collapse\/examples\?theme=light$/);
    });

    testState('expands horizontally on click', {
        example: 'horizontal',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Toggle width collapse' });

            await trigger.click();
            await page.mouse.move(0, 0);

            await expect(page.getByText('This content collapses horizontally')).toBeVisible();
            await expect(page.locator('#collapse-width-example')).toHaveClass(SHOWN);
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('toggles a single panel from its own trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/collapse/multiple-toggles-and-targets');

        await page.getByRole('button', { name: 'Toggle first element' }).click();

        await expect(page.getByText('The first panel can be toggled')).toBeVisible();
        await expect(page.locator('#multi-collapse-one')).toHaveClass(SHOWN);
        await expect(page.getByText('The second panel responds')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Toggle first element' })).toHaveAttribute(
            'aria-expanded',
            'true'
        );
        await expect(page.getByRole('button', { name: 'Toggle second element' })).toHaveAttribute(
            'aria-expanded',
            'false'
        );
    });

    testState('expands every target of a class selector', {
        example: 'multiple-toggles-and-targets',
        state: 'both-open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Toggle both elements' });

            await trigger.click();
            await page.mouse.move(0, 0);

            await expect(page.getByText('The first panel can be toggled')).toBeVisible();
            await expect(page.getByText('The second panel responds')).toBeVisible();
            await expect(page.locator('#multi-collapse-one')).toHaveClass(SHOWN);
            await expect(page.locator('#multi-collapse-two')).toHaveClass(SHOWN);
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });
});
