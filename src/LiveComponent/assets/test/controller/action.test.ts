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
import { getByLabelText, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController Action Tests', () => {
    const template = (data) => `
        <div
            ${initLiveComponent('/_components/my_component', data)}
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

            <button data-action="live#action" data-action-name="sendNamedArgs(a=1, b=2, c=3)">Send named args</button>
        </div>
    `;

    afterEach(() => {
        clearDOM();
        if (!fetchMock.done()) {
            throw new Error('Mocked requests did not match');
        }
        fetchMock.reset();
    });

    it('Sends an action and cancels any re-renders', async () => {
        const data = { comments: 'hi' };
        const { element } = await startStimulus(template(data));

        // ONLY a post is sent, not a re-render GET
        const postMock = fetchMock.postOnce(
            'http://localhost/_components/my_component/save',
            template({ comments: 'hi weaver', isSaved: true })
        );

        await userEvent.type(getByLabelText(element, 'Comments:'), ' WEAVER');

        getByText(element, 'Save').click();

        await waitFor(() => expect(element).toHaveTextContent('Comment Saved!'));
        expect(getByLabelText(element, 'Comments:')).toHaveValue('hi weaver');

        expect(postMock.lastOptions().body.get('comments')).toEqual('hi WEAVER');
    });

    it('Sends action named args', async () => {
        const data = { comments: 'hi' };
        const { element } = await startStimulus(template(data));

        fetchMock.postOnce('http://localhost/_components/my_component/sendNamedArgs?args=a%3D1%26b%3D2%26c%3D3', {
            html: template({ comments: 'hi' }),
        });

        getByText(element, 'Send named args').click();
    });
});
