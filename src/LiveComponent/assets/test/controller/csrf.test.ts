/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { createTest, initComponent, shutdownTest } from '../tools';
import { getByText, waitFor } from '@testing-library/dom';

describe('LiveController CSRF Tests', () => {
    afterEach(() => {
        shutdownTest();
    })

    it('Sends the CSRF token on an action', async () => {
        const test = await createTest({ isSaved: 0 }, (data: any) => `
            <div ${initComponent(data, { csrf: '123TOKEN' })}>
                ${data.isSaved ? 'Saved' : ''}
                <button
                    data-action="live#action"
                    data-action-name="save"
                >Save</button>
            </div>
        `);

        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .expectHeader('X-CSRF-TOKEN', '123TOKEN')
            .serverWillChangeData((data: any) => {
                data.isSaved = true;
            })
            .init();

        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Saved'));
    });
});
