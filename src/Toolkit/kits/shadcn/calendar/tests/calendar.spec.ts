import { type Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const cell = (page: Page, month: string, date: string) =>
    page.getByRole('grid', { name: month }).getByRole('gridcell', { name: date, exact: true });

const day = (page: Page, month: string, date: string) =>
    page.getByRole('grid', { name: month }).getByRole('button', { name: date, exact: true });

describeRecipe('shadcn/calendar', () => {
    testState('selects a day on click', {
        example: 'basic',
        state: 'selected',
        act: async (page) => {
            await day(page, 'March 2026', 'Tuesday, March 10, 2026').click();
            await page.mouse.move(0, 0);

            await expect(cell(page, 'March 2026', 'Tuesday, March 10, 2026')).toHaveAttribute('aria-selected', 'true');
        },
    });

    test('moves the single selection and deselects a day clicked twice', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/basic', { timers: 'fake' });
        await day(page, 'March 2026', 'Tuesday, March 10, 2026').click();

        await day(page, 'March 2026', 'Thursday, March 12, 2026').click();

        await expect(cell(page, 'March 2026', 'Tuesday, March 10, 2026')).toHaveAttribute('aria-selected', 'false');
        await expect(cell(page, 'March 2026', 'Thursday, March 12, 2026')).toHaveAttribute('aria-selected', 'true');

        await day(page, 'March 2026', 'Thursday, March 12, 2026').click();

        await expect(page.getByRole('gridcell', { selected: true })).toHaveCount(0);
    });

    testState('selects a range across two months', {
        example: 'range-calendar',
        state: 'range',
        act: async (page) => {
            await day(page, 'January 2026', 'Tuesday, January 20, 2026').click();
            await day(page, 'February 2026', 'Tuesday, February 3, 2026').click();
            await page.mouse.move(0, 0);

            await expect(cell(page, 'January 2026', 'Tuesday, January 20, 2026')).toHaveAttribute(
                'aria-selected',
                'true'
            );
            await expect(cell(page, 'January 2026', 'Saturday, January 31, 2026')).toHaveAttribute(
                'aria-selected',
                'true'
            );
            await expect(cell(page, 'February 2026', 'Tuesday, February 3, 2026')).toHaveAttribute(
                'aria-selected',
                'true'
            );
            await expect(cell(page, 'February 2026', 'Wednesday, February 4, 2026')).toHaveAttribute(
                'aria-selected',
                'false'
            );
            await expect(cell(page, 'January 2026', 'Monday, January 12, 2026')).toHaveAttribute(
                'aria-selected',
                'false'
            );
        },
    });

    test('previews the range up to the hovered day', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/range-calendar', { timers: 'fake' });
        await day(page, 'February 2026', 'Monday, February 2, 2026').click();
        await expect(cell(page, 'February 2026', 'Wednesday, February 4, 2026')).toHaveAttribute(
            'aria-selected',
            'false'
        );

        await day(page, 'February 2026', 'Friday, February 6, 2026').hover();

        await expect(cell(page, 'February 2026', 'Wednesday, February 4, 2026')).toHaveAttribute(
            'aria-selected',
            'true'
        );

        await page.mouse.move(0, 0);

        await expect(cell(page, 'February 2026', 'Wednesday, February 4, 2026')).toHaveAttribute(
            'aria-selected',
            'false'
        );
    });

    test('restarts the range when the second day is before the first one', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/range-calendar', { timers: 'fake' });
        await day(page, 'February 2026', 'Tuesday, February 10, 2026').click();

        await day(page, 'February 2026', 'Thursday, February 5, 2026').click();

        await expect(
            page.getByRole('grid', { name: 'February 2026' }).getByRole('gridcell', { selected: true })
        ).toHaveCount(1);
        await expect(cell(page, 'February 2026', 'Thursday, February 5, 2026')).toHaveAttribute(
            'aria-selected',
            'true'
        );
    });

    test('toggles days in multiple mode', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/multiple', { timers: 'fake' });

        await day(page, 'March 2026', 'Wednesday, March 25, 2026').click();
        await day(page, 'March 2026', 'Wednesday, March 4, 2026').click();

        await expect(cell(page, 'March 2026', 'Wednesday, March 25, 2026')).toHaveAttribute('aria-selected', 'true');
        await expect(cell(page, 'March 2026', 'Wednesday, March 4, 2026')).toHaveAttribute('aria-selected', 'false');
        await expect(page.getByRole('gridcell', { selected: true })).toHaveCount(3);
    });

    test('navigates between months with the previous and next buttons', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/basic', { timers: 'fake' });

        await page.getByRole('button', { name: 'Next month' }).click();

        await expect(page.getByRole('grid', { name: 'April 2026' })).toBeVisible();
        await expect(page.getByText('April 2026', { exact: true })).toBeVisible();
        await expect(day(page, 'April 2026', 'Thursday, April 30, 2026')).toBeVisible();

        await page.getByRole('button', { name: 'Previous month' }).click();
        await page.getByRole('button', { name: 'Previous month' }).click();

        await expect(page.getByRole('grid', { name: 'February 2026' })).toBeVisible();
        await expect(page.getByText('February 2026', { exact: true })).toBeVisible();
    });

    testState('jumps to the month and year picked in the dropdowns', {
        example: 'month-and-year-selector',
        state: 'navigated',
        act: async (page) => {
            await page.getByRole('combobox', { name: 'Month' }).selectOption('7');
            await page.getByRole('combobox', { name: 'Year' }).selectOption('2027');

            await expect(page.getByRole('grid', { name: 'July 2027' })).toBeVisible();
            await expect(page.getByRole('combobox', { name: 'Month' })).toHaveValue('7');
            await expect(page.getByRole('combobox', { name: 'Year' })).toHaveValue('2027');
        },
    });

    test('keeps the dropdowns in sync with the button navigation', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/month-and-year-selector', { timers: 'fake' });

        for (let i = 0; i < 10; i++) {
            await page.getByRole('button', { name: 'Next month' }).click();
        }

        await expect(page.getByRole('grid', { name: 'January 2027' })).toBeVisible();
        await expect(page.getByRole('combobox', { name: 'Month' })).toHaveValue('1');
        await expect(page.getByRole('combobox', { name: 'Year' })).toHaveValue('2027');
    });

    test('does not navigate past the end month', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/month-and-year-selector', { timers: 'fake' });
        const next = page.getByRole('button', { name: 'Next month' });
        await page.getByRole('combobox', { name: 'Month' }).selectOption('12');
        await page.getByRole('combobox', { name: 'Year' }).selectOption('2028');
        await expect(next).toHaveAttribute('aria-disabled', 'true');

        await next.click({ force: true });

        await expect(page.getByRole('grid', { name: 'December 2028' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Previous month' })).toHaveAttribute('aria-disabled', 'false');
    });

    test('moves the focus between days with the keyboard', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/basic', { timers: 'fake' });
        const today = day(page, 'March 2026', 'Sunday, March 15, 2026');
        await expect(today).toHaveAttribute('tabindex', '0');
        await today.focus();

        await page.keyboard.press('ArrowRight');

        await expect(day(page, 'March 2026', 'Monday, March 16, 2026')).toBeFocused();
        await expect(day(page, 'March 2026', 'Monday, March 16, 2026')).toHaveAttribute('tabindex', '0');
        await expect(today).toHaveAttribute('tabindex', '-1');

        await page.keyboard.press('ArrowDown');

        await expect(day(page, 'March 2026', 'Monday, March 23, 2026')).toBeFocused();

        await page.keyboard.press('End');

        await expect(day(page, 'March 2026', 'Saturday, March 28, 2026')).toBeFocused();

        await page.keyboard.press('Home');

        await expect(day(page, 'March 2026', 'Sunday, March 22, 2026')).toBeFocused();

        await page.keyboard.press('ArrowUp');
        await page.keyboard.press('ArrowLeft');

        await expect(day(page, 'March 2026', 'Saturday, March 14, 2026')).toBeFocused();
    });

    test('navigates to another month when the keyboard focus leaves the displayed one', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/calendar/basic', { timers: 'fake' });
        await day(page, 'March 2026', 'Sunday, March 15, 2026').focus();

        await page.keyboard.press('PageDown');

        await expect(day(page, 'April 2026', 'Wednesday, April 15, 2026')).toBeFocused();

        await page.keyboard.press('PageUp');
        await page.keyboard.press('PageUp');

        await expect(day(page, 'February 2026', 'Sunday, February 15, 2026')).toBeFocused();

        await page.keyboard.press('ArrowUp');
        await page.keyboard.press('ArrowUp');
        await page.keyboard.press('ArrowUp');

        await expect(day(page, 'January 2026', 'Sunday, January 25, 2026')).toBeFocused();
    });

    test('selects the focused day with Enter', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/basic', { timers: 'fake' });
        await day(page, 'March 2026', 'Sunday, March 15, 2026').focus();
        await page.keyboard.press('ArrowRight');

        await page.keyboard.press('Enter');

        await expect(cell(page, 'March 2026', 'Monday, March 16, 2026')).toHaveAttribute('aria-selected', 'true');
    });

    test('does not select a disabled day', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/booked-dates', { timers: 'fake' });
        const booked = day(page, 'February 2026', 'Friday, February 20, 2026');

        await expect(booked).toBeDisabled();
        await booked.click({ force: true });

        await expect(cell(page, 'February 2026', 'Friday, February 20, 2026')).toHaveAttribute(
            'aria-selected',
            'false'
        );
        await expect(cell(page, 'February 2026', 'Tuesday, February 3, 2026')).toHaveAttribute('aria-selected', 'true');
    });

    test('disables the days after the max date on the months reached by navigation', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/range-calendar', { timers: 'fake' });

        await page.getByRole('button', { name: 'Next month' }).click();

        await expect(day(page, 'March 2026', 'Sunday, March 15, 2026')).toBeEnabled();
        await expect(day(page, 'March 2026', 'Monday, March 16, 2026')).toBeDisabled();
    });

    test('selects the date of a preset and jumps to its month', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/presets', { timers: 'fake' });
        await expect(page.getByRole('grid', { name: 'February 2026' })).toBeVisible();

        await page.getByRole('button', { name: 'Tomorrow' }).click();

        await expect(cell(page, 'March 2026', 'Monday, March 16, 2026')).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('gridcell', { selected: true })).toHaveCount(1);
    });

    test('dispatches the selection to the page', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/calendar/api', { timers: 'fake' });
        const output = page.getByLabel('Selected dates');

        await day(page, 'March 2026', 'Friday, March 20, 2026').click();

        await expect(output).toHaveValue('2026-03-20');

        await day(page, 'March 2026', 'Wednesday, March 25, 2026').click();

        await expect(output).toHaveValue('2026-03-20, 2026-03-25');
    });
});
