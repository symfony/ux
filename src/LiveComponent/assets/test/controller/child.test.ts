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
import {getByTestId, waitFor} from '@testing-library/dom';
import Component from '../../src/Component';

describe('LiveController parent -> child component tests', () => {
    afterEach(() => {
        shutdownTest();
    })

    it('adds & removes the child correctly', async () => {
        const childTemplate = (data: any) => `
            <div ${initComponent(data, {}, {id: 'the-child-id'})} data-testid="child"></div>
        `;

        const test = await createTest({}, (data: any) => `
            <div ${initComponent(data)}>
                ${childTemplate({})}
            </div>
        `);

        const parentComponent = test.component;
        const childElement = getByTestId(test.element, 'child');
        // @ts-ignore
        const childComponent = childElement.__component;
        if (!(childComponent instanceof Component)) {
            throw new Error('Child component did not load correctly')
        }

        // check that the relationships all loaded correctly
        expect(parentComponent.getChildren().size).toEqual(1);
        expect(parentComponent.getChildren().get('the-child-id')).toEqual(childComponent);
        expect(childComponent.getParent()).toEqual(parentComponent);

        // remove the child
        childElement.remove();
        // wait because the event is slightly async
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(0));
        expect(childComponent.getParent()).toBeNull();

        // now put it back!
        test.element.appendChild(childElement);
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(1));
        expect(parentComponent.getChildren().get('the-child-id')).toEqual(childComponent);
        expect(childComponent.getParent()).toEqual(parentComponent);

        // now remove the whole darn thing!
        test.element.remove();
        // this will, while disconnected, break the parent-child bond
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(0));
        expect(childComponent.getParent()).toBeNull();

        // put it *all* back
        document.body.appendChild(test.element);
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(1));
        expect(parentComponent.getChildren().get('the-child-id')).toEqual(childComponent);
        expect(childComponent.getParent()).toEqual(parentComponent);
    });

    it('sends a map of child fingerprints on re-render', async () => {
        const test = await createTest({}, (data: any) => `
            <div ${initComponent(data)}>
                <div ${initComponent({}, {}, {id: 'the-child-id1', fingerprint: 'child-fingerprint1'})}>Child1</div>
                <div ${initComponent({}, {}, {id: 'the-child-id2', fingerprint: 'child-fingerprint2'})}>Child2</div>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .expectChildFingerprints({
                'the-child-id1': 'child-fingerprint1',
                'the-child-id2': 'child-fingerprint2'
            })
            .init();

        test.component.render();
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
    });

    it('removes missing child component on re-render', async () => {
        const test = await createTest({renderChild: true}, (data: any) => `
            <div ${initComponent(data)}>
                ${data.renderChild
                    ? `<div ${initComponent({}, {}, {id: 'the-child-id'})} data-testid="child">Child Component</div>`
                    : ''
                }
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.renderChild = false;
            })
            .init();

        expect(test.element).toHaveTextContent('Child Component')
        expect(test.component.getChildren().size).toEqual(1);
        test.component.render();
        // wait for child to disappear
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        expect(test.element).not.toHaveTextContent('Child Component')
        expect(test.component.getChildren().size).toEqual(0);
    });

    it('adds new child component on re-render', async () => {
        const test = await createTest({renderChild: false}, (data: any) => `
           <div ${initComponent(data)}>
               ${data.renderChild
            ? `<div ${initComponent({}, {}, {id: 'the-child-id'})} data-testid="child">Child Component</div>`
            : ''
        }
           </div>
       `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.renderChild = true;
            })
            .init();

        expect(test.element).not.toHaveTextContent('Child Component')
        expect(test.component.getChildren().size).toEqual(0);
        test.component.render();
        // wait for child to disappear
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        expect(test.element).toHaveTextContent('Child Component')
        expect(test.component.getChildren().size).toEqual(1);
    });

    // it('existing child component that has no props is ignored', async () => {
    //     const originalChild = `
    //         <div ${initComponent({}, {}, {id: 'the-child-id'})} data-testid="child">
    //             Child Component
    //         </div>
    //     `;
    //     const updatedChild = `
    //         <div ${initComponent({}, {}, {id: 'the-child-id'})} data-testid="child">
    //             Child Component
    //         </div>
    //     `;
    //
    //     const test = await createTest({useOriginalChild: true}, (data: any) => `
    //        <div ${initComponent(data)}>
    //            ${data.useUpdatedChild ? originalChild : updatedChild}
    //        </div>
    //    `);
    //
    //     test.expectsAjaxCall('get')
    //         .expectSentData(test.initialData)
    //         .serverWillChangeData((data: any) => {
    //             data.renderChild = true;
    //         })
    //         .init();
    //
    //     expect(test.element).not.toHaveTextContent('Child Component')
    //     expect(test.component.getChildren().size).toEqual(0);
    //     test.component.render();
    //     // wait for child to disappear
    //     await waitFor(() => expect(test.element).toHaveAttribute('busy'));
    //     await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
    //     expect(test.element).toHaveTextContent('Child Component')
    //     expect(test.component.getChildren().size).toEqual(1);
    // });


    // TODO: response comes back with existing child element but no props, child is untouched
    // TODO: response comes back with empty child element BUT with new fingerprint & props, those are pushed down onto the child, which will trigger a re-render
        // TODO: check above situation mixing element types - e.g. span vs div
        // TODO: check that data-live-id could be used to remove old component and use new component entirely, with fresh data
        // TODO: multiple children, even if they change position, are correctly handled
});
