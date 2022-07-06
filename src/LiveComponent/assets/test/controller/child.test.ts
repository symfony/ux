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
import userEvent from '@testing-library/user-event';
import { htmlToElement } from '../../src/dom_utils';

describe('LiveController parent -> child component tests', () => {
    afterEach(() => {
        shutdownTest();
    })

    it('renders parent component without affecting child component', async () => {
        const childTemplate = (data: any) => `
            <div ${initComponent(data)} id="child-component">
                Child component original text
                Favorite food: ${data.food}
                
                <button data-action="live#$render">Render Child</button>
            </div>
        `;

        const test = await createTest({ count: 0 }, (data: any) => `
            <div ${initComponent(data)}>
                Parent component count: ${data.count}
                
                ${childTemplate({food: 'pizza'})}
            </div>
        `);

        // for the child component render
        test.expectsAjaxCall('get')
            .expectSentData({food: 'pizza'})
            .serverWillChangeData((data: any) => {
                data.food = 'popcorn';
            })
            .willReturn(childTemplate)
            .init();

        // re-render *just* the child
        userEvent.click(getByText(test.element, 'Render Child'));
        await waitFor(() => expect(test.element).toHaveTextContent('Favorite food: popcorn'));

        // now let's re-render the parent
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.count = 1;
            })
            .init();

        test.controller.$render();
        await waitFor(() => expect(test.element).toHaveTextContent('Parent component count: 1'));
        // child component retains its re-rendered, custom text
        // this is because the parent's data changed, but the changed data is not passed to the child
        expect(test.element).toHaveTextContent('Favorite food: popcorn');
    });

    it('renders parent component AND replaces child component when changed parent data affects child data', async () => {
        const childTemplate = (data: any) => `
            <div ${initComponent(data)} id="child-component">
                Child component count: ${data.childCount}
            </div>
        `;

        const test = await createTest({ count: 0 }, (data: any) => `
            <div ${initComponent(data)}>
                Parent component count: ${data.count}
                
                ${childTemplate({childCount: data.count})}
            </div>
        `);

        // change some content on the child
        (document.getElementById('child-component') as HTMLElement).innerHTML = 'changed child content';

        // re-render the parent
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.count = 1;
            })
            .init();

        test.controller.$render();
        await waitFor(() => expect(test.element).toHaveTextContent('Parent component count: 1'));
        // child component reverts to its original text
        // this is because the parent's data changed AND that change affected child's data
        expect(test.element).toHaveTextContent('Child component count: 1');
    });

    it('updates the parent model when the child model updates', async () => {
        const childTemplate = (data: any) => `
            <div ${initComponent(data)}>
                <textarea
                    data-model="content"
                    name="post[content]"
                >${data.content}</textarea>
                
                Child Content: ${data.content}
            </div>
        `;

        const test = await createTest({ post: { content: 'i love'} }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    Parent Post Content: ${data.post.content}
                
                    ${childTemplate({content: data.post.content})}
                </form>
            </div>
        `);

        // request for the child render
        test.expectsAjaxCall('get')
            .expectSentData({content: 'i love turtles'})
            .willReturn(childTemplate)
            .init();

        await userEvent.type(test.queryByDataModel('content'), ' turtles');
        // wait for the render to complete
        await waitFor(() => expect(test.element).toHaveTextContent('Child Content: i love turtles'));

        // the parent should not re-render
        // TODO: this behavior was originally added, but it's questionabl
        expect(test.element).not.toHaveTextContent('Parent Post Content: i love turtles');
        // but the value DID update on the parent component
        // this is because the name="post[content]" in the child matches the parent model
        expect(test.controller.dataValue.post.content).toEqual('i love turtles');
    });

    it('uses data-model-map to map child models to parent models', async () => {
        const childTemplate = (data: any) => `
            <div ${initComponent(data)}>
                <textarea data-model="value">${data.value}</textarea>
                
                Child Content: ${data.value}
            </div>
        `;

        const test = await createTest({ post: { content: 'i love'} }, (data: any) => `
            <div ${initComponent(data)}>
                <div data-model-map="from(value)|post.content">
                    ${childTemplate({value: data.post.content})}
                </div>
            </div>
        `);

        // request for the child render
        test.expectsAjaxCall('get')
            .expectSentData({ value: 'i love dragons' })
            .willReturn(childTemplate)
            .init();

        await userEvent.type(test.queryByDataModel('value'), ' dragons');
        // wait for the render to complete
        await waitFor(() => expect(test.element).toHaveTextContent('Child Content: i love dragons'));
        expect(test.controller.dataValue.post.content).toEqual('i love dragons');
    });

    it('updates child data-original-data on parent re-render', async () => {
        const initialData = { children: [{ name: 'child1' }, { name: 'child2' }, { name: 'child3' }] };
        const test = await createTest(initialData, (data: any) => `
            <div ${initComponent(data)}>
                Parent count: ${data.count}
                
                <ul>
                    ${data.children.map((child: any) => {
                        return `
                            <li ${initComponent(child)}>
                               ${child.name}
                            </li>
                        `
                    })}
                </ul>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                // "remove" child2
                data.children = [{ name: 'child1' }, { name: 'child3' }];
            })
            .init();

        test.controller.$render();

        await waitFor(() => expect(test.element).not.toHaveTextContent('child2'));
        const secondLi = test.element.querySelectorAll('li').item(1);
        expect(secondLi).not.toBeNull();
        // the 2nd li was just "updated" by the parent component, which
        // would have eliminated its data-original-data attribute. Check
        // that it was re-established to the 3rd child's data.
        // see MutationObserver in live_controller for more details.
        expect(secondLi.dataset.originalData).toEqual(JSON.stringify({name: 'child3'}));
    });

    it('notices as children are connected and disconnected', async () => {
        const test = await createTest({}, (data: any) => `
            <div ${initComponent(data)}>
                Parent component
            </div>
        `);

        expect(test.controller.childComponentControllers).toHaveLength(0);

        const createChildElement = () => {
            const childElement = htmlToElement(`<div ${initComponent({})}>child</div>`);
            childElement.addEventListener('live:connect', (event: any) => {
                event.detail.controller.element.setAttribute('connected', '1');
            });
            childElement.addEventListener('live:disconnect', (event: any) => {
                event.detail.controller.element.setAttribute('connected', '0');
            });

            return childElement;
        };

        const childElement1 = createChildElement();
        const childElement2 = createChildElement();
        const childElement3 = createChildElement();

        test.element.appendChild(childElement1);
        await waitFor(() => expect(childElement1).toHaveAttribute('connected', '1'));
        expect(test.controller.childComponentControllers).toHaveLength(1);

        test.element.appendChild(childElement2);
        test.element.appendChild(childElement3);
        await waitFor(() => expect(childElement2).toHaveAttribute('connected', '1'));
        await waitFor(() => expect(childElement3).toHaveAttribute('connected', '1'));
        expect(test.controller.childComponentControllers).toHaveLength(3);

        test.element.removeChild(childElement2);
        await waitFor(() => expect(childElement2).toHaveAttribute('connected', '0'));
        expect(test.controller.childComponentControllers).toHaveLength(2);
        test.element.removeChild(childElement1);
        test.element.removeChild(childElement3);
        await waitFor(() => expect(childElement1).toHaveAttribute('connected', '0'));
        await waitFor(() => expect(childElement3).toHaveAttribute('connected', '0'));
        expect(test.controller.childComponentControllers).toHaveLength(0);
    });
});
