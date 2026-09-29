import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/date-picker', () => {
    testState('opens the calendar on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Pick a date' });

            await trigger.click();

            await expect(page.getByRole('dialog')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('grid', { name: 'March 2026' })).toBeVisible();
        },
    });

    testState('keeps the range picker open after the start date', {
        example: 'range-picker',
        state: 'range-start',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Date Picker Range' });
            await trigger.click();
            await expect(page.getByRole('dialog')).toBeVisible();

            await page.getByRole('button', { name: 'Tuesday, February 3, 2026' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('dialog')).toBeVisible();
            await expect(trigger).toHaveText('Feb 3, 2026');
        },
    });

    test('opens the calendar with focus on a day', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/default', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Pick a date' });

        await trigger.click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('button', { name: 'Sunday, March 15, 2026' })).toBeFocused();
    });

    test('picks a date, shows it on the trigger and closes', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/basic', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Date', exact: true });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.getByRole('button', { name: 'Friday, March 20, 2026' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveText('March 20, 2026');
        await expect(trigger).toHaveAttribute('data-empty', 'false');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('shows the placeholder again when the picked date is unselected', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/basic', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Date', exact: true });
        await trigger.click();
        await page.getByRole('button', { name: 'Friday, March 20, 2026' }).click();
        await expect(trigger).toHaveText('March 20, 2026');
        await trigger.click();

        await page.getByRole('button', { name: 'Friday, March 20, 2026' }).click();

        await expect(trigger).toHaveText('Pick a date');
        await expect(trigger).toHaveAttribute('data-empty', 'true');
        await expect(page.getByRole('dialog')).toBeVisible();
    });

    test('closes the range picker once the end date is picked', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/range-picker', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Date Picker Range' });
        await expect(trigger).toHaveText('Jan 20, 2026 - Feb 9, 2026');
        await trigger.click();

        await page.getByRole('button', { name: 'Tuesday, February 3, 2026' }).click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(trigger).toHaveText('Feb 3, 2026');

        await page.getByRole('button', { name: 'Thursday, February 12, 2026' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveText('Feb 3, 2026 - Feb 12, 2026');
    });

    test('writes the picked date into the input', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/input', { timers: 'fake' });
        const input = page.getByLabel('Subscription Date');
        await expect(input).toHaveValue('June 1, 2026');
        await page.getByRole('button', { name: 'Select date' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.getByRole('button', { name: 'Wednesday, June 10, 2026' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(input).toHaveValue('June 10, 2026');
    });

    test('moves the calendar to the date typed in the input', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/input', { timers: 'fake' });
        const input = page.getByLabel('Subscription Date');

        await input.fill('2026-08-05');
        await input.press('ArrowDown');

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByRole('grid', { name: 'August 2026' })).toBeVisible();
        await expect(page.getByRole('gridcell', { name: 'Wednesday, August 5, 2026' })).toHaveAttribute(
            'aria-selected',
            'true'
        );
        await expect(input).toHaveValue('2026-08-05');
    });

    test('formats the picked date with the locale', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/rtl', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: '15 במרץ 2026' });
        await trigger.click();

        await page.getByRole('button', { name: 'יום שישי, 20 במרץ 2026' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(page.getByRole('button', { name: '20 במרץ 2026' })).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeHidden();
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/default', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Pick a date' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/date-picker/default', { timers: 'fake' });
        const trigger = page.getByRole('button', { name: 'Pick a date' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });
});
