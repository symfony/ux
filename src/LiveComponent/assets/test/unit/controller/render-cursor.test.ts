/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { afterEach, describe, expect, it } from 'vitest';
import { createTest, initComponent, shutdownTests } from '../../tools';

// No user-event in this file: once it has focused a field, it moves the cursor to the end on every value a script sets, which browsers do not do.
describe('LiveController rendering cursor tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('conserves cursor position of active model element', async () => {
        const test = await createTest(
            { name: '' },
            (data) => `
            <div ${initComponent(data)}>
                <input data-model="name" class="anything">
            </div>
        `
        );

        test.expectsAjaxCall().expectUpdatedData({ name: 'Hello' });

        const input = test.queryByDataModel('name') as HTMLInputElement;
        input.focus();
        input.value = 'Hello';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.setSelectionRange(3, 3);

        await test.component.render();

        expect(input.value).toBe('Hello');
        expect(input.selectionStart).toBe(3);
    });
});
