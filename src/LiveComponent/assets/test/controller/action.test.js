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
import { startStimulus } from '../tools';
import { getByLabelText, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController Action Tests', () => {
    const template = (data) => `
        <div
            data-controller="live"
            data-live-url-value="http://localhost/_components/my_component"
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

    it('Sends an action and cancels any re-renders', async () => {
        const data = { comments: 'hi' };
        const { element } = await startStimulus(
            template(data),
            data
        );

        // ONLY a post is sent, not a re-render GET
        const postMock = fetchMock.postOnce('http://localhost/_components/my_component/save', {
            html: template({ comments: 'hi weaver', isSaved: true }),
            data: { comments: 'hi weaver', isSaved: true }
        });

        await userEvent.type(getByLabelText(element, 'Comments:'), ' WEAVER');

        getByText(element, 'Save').click();

        await waitFor(() => expect(element).toHaveTextContent('Comment Saved!'));
        expect(getByLabelText(element, 'Comments:')).toHaveValue('hi weaver');

        fetchMock.done();

        expect(postMock.lastOptions().body.get('comments')).toEqual('hi WEAVER');
    });
});
