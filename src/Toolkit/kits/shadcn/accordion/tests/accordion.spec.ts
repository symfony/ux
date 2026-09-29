import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/accordion', () => {
    testState('opens another item and closes the open one', {
        example: 'default',
        state: 'returns-open',
        act: async (page) => {
            const shipping = page.getByRole('button', { name: 'What are your shipping options?' });
            const returns = page.getByRole('button', { name: 'What is your return policy?' });

            await returns.click();

            await expect(returns).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByText('Returns accepted within 30 days.')).toBeVisible();
            await expect(shipping).toHaveAttribute('aria-expanded', 'false');
            await expect(page.getByText('We offer standard (5-7 days)')).toBeHidden();
            await page.mouse.move(0, 0);
        },
    });

    test('closes the open item on a click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/accordion/default');
        const shipping = page.getByRole('button', { name: 'What are your shipping options?' });
        await expect(page.getByRole('region', { name: 'What are your shipping options?' })).toBeVisible();

        await shipping.click();

        await expect(shipping).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('region', { name: 'What are your shipping options?' })).toBeHidden();
    });

    testState('keeps several items open when multiple', {
        example: 'multiple',
        state: 'all-open',
        act: async (page) => {
            const notifications = page.getByRole('button', { name: 'Notification Settings' });
            const privacy = page.getByRole('button', { name: 'Privacy & Security' });
            const billing = page.getByRole('button', { name: 'Billing & Subscription' });

            await privacy.click();
            await billing.click();

            await expect(notifications).toHaveAttribute('aria-expanded', 'true');
            await expect(privacy).toHaveAttribute('aria-expanded', 'true');
            await expect(billing).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('region')).toHaveCount(3);
            await page.mouse.move(0, 0);
        },
    });

    test('toggles the focused item with Enter and Space', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/accordion/default');
        const returns = page.getByRole('button', { name: 'What is your return policy?' });
        await returns.focus();

        await page.keyboard.press('Enter');

        await expect(returns).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('region', { name: 'What is your return policy?' })).toBeVisible();

        await page.keyboard.press('Space');

        await expect(returns).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('region', { name: 'What is your return policy?' })).toBeHidden();
    });

    test('moves the focus between triggers with the arrow keys, Home and End', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/accordion/default');
        const shipping = page.getByRole('button', { name: 'What are your shipping options?' });
        const returns = page.getByRole('button', { name: 'What is your return policy?' });
        const support = page.getByRole('button', { name: 'How can I contact customer support?' });
        await shipping.focus();

        await page.keyboard.press('ArrowDown');
        await expect(returns).toBeFocused();

        await page.keyboard.press('End');
        await expect(support).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await expect(shipping).toBeFocused();

        await page.keyboard.press('ArrowUp');
        await expect(support).toBeFocused();

        await page.keyboard.press('Home');
        await expect(shipping).toBeFocused();
    });

    test('does not open a disabled item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/accordion/disabled');
        const premium = page.getByRole('button', { name: 'Premium feature information' });

        await expect(premium).toBeDisabled();
        await premium.click({ force: true });

        await expect(premium).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByText('This section contains information about premium features.')).toBeHidden();
    });

    test('skips a disabled item with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/accordion/disabled');
        const history = page.getByRole('button', { name: 'Can I access my account history?' });
        const email = page.getByRole('button', { name: 'How do I update my email address?' });
        await history.focus();

        await page.keyboard.press('ArrowDown');
        await expect(email).toBeFocused();

        await page.keyboard.press('ArrowUp');
        await expect(history).toBeFocused();
    });
});
