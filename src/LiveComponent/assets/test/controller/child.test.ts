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
import { initLiveComponent, mockRerender, startStimulus } from '../tools';
import { getByLabelText, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController parent -> child component tests', () => {
    const parentTemplate = (data) => {
        const errors = data.errors || { post: {} };

        return `
            <div
                ${initLiveComponent('/_components/parent', data)}
            >
                <span>Title: ${data.post.title}</span>
                <span>Description in Parent: ${data.post.content}</span>

                <label>
                    Title:
                    <input
                        type="text"
                        name="post[title]"
                        value="${data.post.title}"
                        data-action="live#update"
                    >
                </label>

                ${childTemplate({ value: data.post.content, error: errors.post.content })}

                <button
                    data-action="live#$render"
                >Parent Re-render</button>
            </div>
        `
    }

    const childTemplate = (data) => `
        <div
            ${initLiveComponent('/_components/child', data)}
        >
            <label>
                Content:
                <textarea
                    data-model="value"
                    name="post[content]"
                    data-action="live#update"
                    rows="${data.rows ? data.rows : '3'}"
                >${data.value}</textarea>
            </label>

            <div>Value in child: ${data.value}</div>
            <div>Error in child: ${data.error ? data.error : 'none'}</div>
            {# Rows represents a writable prop that's private to the child component #}
            <div>Rows in child: ${data.rows ? data.rows : 'not set'}</div>

            <button
                data-action="live#$render"
            >Child Re-render</button>
        </div>
    `;

    afterEach(() => {
        clearDOM();
        fetchMock.reset();
    });

    it('renders parent component without affecting child component', async () => {
        const data = { post: { title: 'Parent component', content: 'i love' } };
        const { element } = await startStimulus(parentTemplate(data));

        // on child re-render, expect the new value, change rows on the server
        mockRerender({ value: 'i love popcorn' }, childTemplate, (data) => {
            // change the "rows" data on the "server"
            data.rows = 5;
        });
        await userEvent.type(getByLabelText(element, 'Content:'), ' popcorn');

        await waitFor(() => expect(element).toHaveTextContent('Value in child: i love popcorn'));
        expect(element).toHaveTextContent('Rows in child: 5');

        // when the parent re-renders, expect the changed title AND content (from child)
        // but, importantly, the only "changed" data that will be passed into
        // the child component will be "content", which will match what the
        // child already has. This will NOT trigger a re-render.
        mockRerender(
            { post: { title: 'Parent component changed', content: 'i love popcorn' } },
            parentTemplate
        )
        await userEvent.type(getByLabelText(element, 'Title:'), ' changed');
        await waitFor(() => expect(element).toHaveTextContent('Title: Parent component changed'));

        // the child component should *not* have updated
        expect(element).toHaveTextContent('Rows in child: 5');
    });

    it('updates child model and parent model in a deferred way', async () => {
        const data = { post: { title: 'Parent component', content: 'i love' } };
        const { element, controller } = await startStimulus(parentTemplate(data));

        // verify the child request contains the correct description & re-render
        mockRerender({ value: 'i love turtles' }, childTemplate);

        // change the description in the child
        const inputElement = getByLabelText(element, 'Content:');
        await userEvent.type(inputElement, ' turtles');

        // wait for the render to complete
        await waitFor(() => expect(element).toHaveTextContent('Value in child: i love turtles'));

        // the parent should not re-render
        expect(element).not.toHaveTextContent('Content in parent: i love turtles');
        // but the value DID update on the parent component
        // this is because the name="post[content]" in the child matches the parent model
        expect(controller.dataValue.post.content).toEqual('i love turtles');
    });

    it('updates re-renders a child component if data has changed from initial', async () => {
        const data = { post: { title: 'Parent component', content: 'initial content' } };
        const { element } = await startStimulus(parentTemplate(data));

        // allow the child to re-render, but change the "rows" value
        const inputElement = getByLabelText(element, 'Content:');
        await userEvent.clear(inputElement);
        await userEvent.type(inputElement, 'changed content');
        // change the rows on the server
        mockRerender({'value': 'changed content'}, childTemplate, (data) => {
            data.rows = 5;
        });

        // reload, which will give us rows=5
        getByText(element, 'Child Re-render').click();
        await waitFor(() => expect(element).toHaveTextContent('Rows in child: 5'));

        // simulate an action in the parent component where "errors" changes
        mockRerender({'post': { title: 'Parent component', content: 'changed content' }}, parentTemplate, (data) => {
            data.post.title = 'Changed title';
            data.errors = { post: { content: 'the content is not interesting enough' }};
        });

        getByText(element, 'Parent Re-render').click();
        await waitFor(() => expect(element).toHaveTextContent('Title: Changed title'));
        // the child, of course, still has the "changed content" value
        expect(element).toHaveTextContent('Value in child: changed content');
        // but because some child data *changed* from its original value, the child DOES re-render
        expect(element).toHaveTextContent('Error in child: the content is not interesting enough');
        // however, this means that the updated "rows" data on the child is lost
        expect(element).toHaveTextContent('Rows in child: not set');
    });

    it('uses data-model-map to map child models to parent models', async () => {
        const parentTemplateDifferentModel = (data) => `
            <div
                ${initLiveComponent('/_components/parent', data)}
            >
                <span>Parent textarea content: ${data.textareaContent}</span>

                <div
                    data-model-map="from(value)|textareaContent"
                >
                    ${childTemplate({ value: data.textareaContent, error: null })}
                </div>

                <button
                    data-action="live#$render"
                >Parent Re-render</button>
            </div>
        `;

        const data = { textareaContent: 'Original content' };
        const { element, controller } = await startStimulus(parentTemplateDifferentModel(data));

        // update & re-render the child component
        const inputElement = getByLabelText(element, 'Content:');
        await userEvent.clear(inputElement);
        await userEvent.type(inputElement, 'changed content');
        mockRerender({value: 'changed content'}, childTemplate);

        await waitFor(() => expect(element).toHaveTextContent('Value in child: changed content'));

        expect(controller.dataValue).toEqual({ textareaContent: 'changed content' });
   });
});
