import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('daisyui-5/dropdown', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Click to open' });
            await expect(page.getByRole('button', { name: 'Item 1' })).toBeHidden();

            await trigger.click();

            await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    for (const [example, name] of [
        ['align-start', 'Click ⬇️'],
        ['align-end', 'Click ⬇️'],
        ['align-center', 'Click ⬇️'],
        ['top', 'Click ⬆️'],
        ['top-center', 'Click ⬆️'],
        ['top-end', 'Click ⬆️'],
        ['left', 'Click ⬅️'],
        ['left-center', 'Click ⬅️'],
        ['left-end', 'Click ⬅️'],
        ['right', 'Click ➡️'],
        ['right-end', 'Click ➡️'],
        ['right-center', 'Click ➡️'],
        ['card-as-dropdown', 'Click'],
        ['dropdown-in-navbar', 'Dropdown'],
        ['helper-dropdown', 'More info'],
    ]) {
        testState(`opens the ${example} example on click`, {
            example,
            state: 'open',
            act: async (page) => {
                const trigger = page.getByRole('button', { name, exact: true });

                await trigger.click();

                await expect(page.locator('.dropdown-content')).toBeVisible();
                await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            },
        });
    }

    test('closes on a second click on the trigger', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Click to open' });
        await trigger.click();
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('button', { name: 'Item 1' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Click to open' });
        await trigger.click();
        await page.keyboard.press('Tab');
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(trigger).toBeFocused();
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Click to open' });
        await trigger.click();
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();

        await page.mouse.click(5, 5);

        await expect(page.getByRole('button', { name: 'Item 1' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open on a click inside the content', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/card-as-dropdown');
        await page.getByRole('button', { name: 'Click' }).click();
        const card = page.getByText('This is a card.');
        await expect(card).toBeVisible();

        await card.click();

        await expect(card).toBeVisible();
    });

    test('closes when the focus leaves it', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/dropdown-in-navbar');
        const trigger = page.getByRole('button', { name: 'Dropdown' });
        await trigger.click();
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();

        await page.keyboard.press('Shift+Tab');

        await expect(page.getByRole('button', { name: 'Button', exact: true })).toBeFocused();
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    testState('opens on hover', {
        example: 'hover',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Hover' });

            await trigger.hover();

            await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('closes when the pointer leaves a dropdown opened on hover', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/hover');
        const trigger = page.getByRole('button', { name: 'Hover' });
        await trigger.hover();
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();

        await page.mouse.move(5, 5);

        await expect(page.getByRole('button', { name: 'Item 1' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open after a click on a trigger opened on hover', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/hover');
        const trigger = page.getByRole('button', { name: 'Hover' });
        await trigger.hover();

        await trigger.click();
        await page.mouse.move(5, 5);

        await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('renders open and closes on Escape', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/dropdown/open');
        const trigger = page.getByRole('button', { name: 'Button' });
        await expect(page.getByRole('button', { name: 'Item 1' })).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await trigger.focus();
        await page.keyboard.press('Escape');

        await expect(page.getByRole('button', { name: 'Item 1' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });
});
