import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/questionnaire', () => {
    testState('shows an error when a required question has no answer', {
        example: 'default',
        state: 'error',
        act: async (page) => {
            await page.getByRole('button', { name: 'Next' }).click();

            await expect(page.getByRole('alert')).toHaveText('Choose an answer to continue.');
            await expect(page.getByRole('group', { name: 'What should the agent build next?' })).toHaveAttribute(
                'aria-invalid',
                'true'
            );
            await expect(page.getByRole('radio', { name: 'Tool call timeline' })).toBeFocused();
        },
    });

    testState('moves to the next question once answered', {
        example: 'default',
        state: 'second-question',
        act: async (page) => {
            await page.getByText('Tool call timeline', { exact: true }).click();

            await page.getByRole('button', { name: 'Next' }).click();

            await expect(page.getByRole('group', { name: 'What should every progress update include?' })).toBeVisible();
            await expect(page.getByRole('group', { name: 'What should the agent build next?' })).toBeHidden();
            await expect(page.getByRole('progressbar')).toHaveText('Question 2 of 3');
            await expect(page.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '2');
            await expect(page.getByRole('button', { name: 'Previous' })).toBeVisible();
            await expect(page.getByRole('button', { name: 'Skip' })).toBeVisible();
            await page.mouse.move(0, 0);
        },
    });

    testState('selects a choice with its shortcut key', {
        example: 'shortcuts',
        state: 'selected',
        act: async (page) => {
            await page.getByRole('radio', { name: 'Inspect the implementation' }).focus();
            await page.keyboard.press('b');
            await page.getByRole('radio', { name: 'A single file' }).focus();
            await page.keyboard.press('3');

            await expect(page.getByRole('radio', { name: 'Run the relevant tests' })).toBeChecked();
            await expect(page.getByRole('radio', { name: 'The whole workspace' })).toBeChecked();
        },
    });

    test('hides the error once the question is answered', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/default');
        await page.getByRole('button', { name: 'Next' }).click();
        await expect(page.getByRole('alert')).toBeVisible();

        await page.getByText('Approval checkpoints', { exact: true }).click();

        await expect(page.getByRole('alert')).toBeHidden();
        await expect(page.getByRole('group', { name: 'What should the agent build next?' })).not.toHaveAttribute(
            'aria-invalid'
        );
    });

    test('shows only the relevant navigation buttons', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/default');

        await expect(page.getByRole('button', { name: 'Previous' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Skip' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Next' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Save plan' })).toBeHidden();

        await page.getByText('Tool call timeline', { exact: true }).click();
        await page.getByRole('button', { name: 'Next' }).click();
        await page.getByRole('button', { name: 'Skip' }).click();

        await expect(page.getByRole('group', { name: 'When should work begin?' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Previous' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Skip' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Next' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Save plan' })).toBeVisible();
    });

    test('goes back to the previous question and keeps its answer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/default');
        await page.getByText('Sub-agent handoffs', { exact: true }).click();
        await page.getByRole('button', { name: 'Next' }).click();
        await expect(page.getByRole('progressbar')).toHaveText('Question 2 of 3');

        await page.getByRole('button', { name: 'Previous' }).click();

        await expect(page.getByRole('group', { name: 'What should the agent build next?' })).toBeVisible();
        await expect(page.getByRole('progressbar')).toHaveText('Question 1 of 3');
        await expect(page.getByRole('radio', { name: 'Sub-agent handoffs' })).toBeChecked();
    });

    test('moves forward with Enter and submits on the last question', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/default');
        await page.getByRole('radio', { name: 'Tool call timeline' }).focus();

        await page.keyboard.press('a');
        await page.keyboard.press('Enter');

        await expect(page.getByRole('group', { name: 'What should every progress update include?' })).toBeVisible();

        await page.getByRole('checkbox', { name: 'Progress' }).focus();
        await page.keyboard.press('a');
        await page.keyboard.press('c');
        await page.keyboard.press('Enter');
        await page.getByRole('radio', { name: 'Start now' }).focus();
        await page.keyboard.press('Enter');

        await expect(page.getByRole('alert')).toHaveText('Choose an answer to continue.');

        await page.keyboard.press('b');
        await page.keyboard.press('Enter');

        await expect(page).toHaveURL(/direction=tool-calls/);
        expect(new URL(page.url()).searchParams.getAll('signals[]')).toEqual(['progress', 'risks']);
        expect(new URL(page.url()).searchParams.get('timing')).toBe('next-cycle');
    });

    test('toggles a multiple choice with its shortcut key', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/multiple-selection');
        await page.getByRole('checkbox', { name: 'Relevant source files' }).focus();

        await page.keyboard.press('b');
        await page.keyboard.press('d');

        await expect(page.getByRole('checkbox', { name: 'Existing tests' })).toBeChecked();
        await expect(page.getByRole('checkbox', { name: 'Recent commit history' })).toBeChecked();

        await page.keyboard.press('b');

        await expect(page.getByRole('checkbox', { name: 'Existing tests' })).not.toBeChecked();
        await expect(page.getByRole('checkbox', { name: 'Recent commit history' })).toBeChecked();
    });

    test('clears the fixed choice when a freeform answer is typed, and the other way round', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/questionnaire/freeform-answer');
        const choice = page.getByRole('radio', { name: 'Refactor one module at a time' });
        const input = page.getByRole('textbox', { name: 'Another refactoring approach' });
        await page.getByText('Refactor one module at a time', { exact: true }).click();

        await input.fill('Start with the tests');

        await expect(choice).not.toBeChecked();
        await expect(input).toHaveAttribute('name', 'approach');

        await page.getByText('Refactor one module at a time', { exact: true }).click();

        await expect(input).toHaveValue('');
        await expect(input).not.toHaveAttribute('name');
    });

    test('ignores the shortcut keys while typing a freeform answer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/freeform-answer');
        const input = page.getByRole('textbox', { name: 'Another refactoring approach' });

        await input.pressSequentially('abc');

        await expect(input).toHaveValue('abc');
        await expect(page.getByRole('radio', { name: 'Make the smallest safe change' })).not.toBeChecked();

        await page.getByRole('radio', { name: 'Make the smallest safe change' }).focus();
        await page.keyboard.press('b');

        await expect(page.getByRole('radio', { name: 'Refactor one module at a time' })).toBeChecked();
        await expect(input).toHaveValue('');
    });

    test('skips an optional question and clears its answer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/navigation-state');
        await page.getByText('Project files', { exact: true }).click();
        await page.getByRole('button', { name: 'Next' }).click();
        const checks = page.getByRole('group', { name: 'Which checks should the agent run?' });
        await page.getByText('Tests', { exact: true }).click();

        await page.getByRole('button', { name: 'Skip' }).click();

        await expect(checks).toHaveAttribute('data-status', 'skipped');
        await expect(page.getByRole('radio', { name: 'Tests', exact: true })).not.toBeChecked();
        await expect(
            page.getByRole('group', { name: 'What may the agent modify?', includeHidden: true })
        ).toHaveAttribute('data-status', 'answered');
    });

    test('opens on the question given by defaultItem', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/controlled');

        await expect(page.getByRole('group', { name: 'Which checks should run before handoff?' })).toBeVisible();
        await expect(page.getByRole('group', { name: 'What may the agent change?' })).toBeHidden();
        await expect(page.getByRole('progressbar')).toHaveText('Question 2 of 2');
    });

    test('steps over a disabled question', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/conditional-items');
        await expect(page.getByRole('progressbar')).toHaveText('Question 1 of 2');
        await page.getByText('On this machine', { exact: true }).click();

        await page.getByRole('button', { name: 'Next' }).click();

        await expect(page.getByRole('group', { name: 'When should the agent ask for approval?' })).toBeVisible();
        await expect(page.getByRole('progressbar')).toHaveText('Question 2 of 2');
    });

    test('marks the progress steps up to the current question', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/custom-progress');
        const progress = page.getByRole('progressbar');
        await expect(progress).toHaveText('Checkpoint 1 of 3');
        await page.getByText('Small patch', { exact: true }).click();

        await page.getByRole('button', { name: 'Next' }).click();

        await expect(progress).toHaveText('Checkpoint 2 of 3');
        await expect(progress.locator('[data-questionnaire-target="progressStep"][data-active="true"]')).toHaveCount(2);
    });

    test('animates the next question in', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/questionnaire/animated-items');
        await page.getByText('Implement the change', { exact: true }).click();
        // Keeps the 300ms animation running long enough to be observed.
        await page.addStyleTag({ content: '[data-slot="questionnaire-item"] { animation-duration: 60s !important; }' });

        await page.getByRole('button', { name: 'Next' }).click();

        const review = page.getByRole('group', { name: 'How much should be reviewed?' });
        await expect(review).toBeVisible();
        expect(await review.evaluate((element) => element.getAnimations().length)).toBeGreaterThan(0);
    });
});
