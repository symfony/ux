/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { clearDOM } from '@symfony/stimulus-testing';
import { initLiveComponent, startStimulus } from '../tools';
import { getByText, waitFor } from '@testing-library/dom';
import fetchMock from 'fetch-mock-jest';

describe('LiveController CSRF Tests', () => {
    const template = (data) => `
        <div
            ${initLiveComponent('/_components/my_component', data)}
            data-live-csrf-value="123TOKEN"
        >
            <label>
                Comments:
                <input
                    data-model="comments"
                    data-action="live#update"
                    value="${data.comments}"
                >
            </label>
            
            ${data.isSaved ? 'Comment Saved!' : ''}

            <button
                data-action="live#action"
                data-action-name="save"
            >Save</button>
        </div>
    `;

    afterEach(() => {
        clearDOM();
        fetchMock.reset();
    });

    it('Sends the CSRF token on an action', async () => {
        const data = { comments: 'hi' };
        const { element } = await startStimulus(template(data));

        const postMock = fetchMock.postOnce(
            'http://localhost/_components/my_component/save',
            template({ comments: 'hi', isSaved: true })
        );
        getByText(element, 'Save').click();

        await waitFor(() => expect(element).toHaveTextContent('Comment Saved!'));

        expect(postMock.lastOptions().headers['X-CSRF-TOKEN']).toEqual('123TOKEN');

        fetchMock.done();
    });
});
