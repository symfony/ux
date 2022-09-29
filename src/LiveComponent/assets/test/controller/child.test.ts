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
            <div ${initComponent(data, {id: 'the-child-id'})} data-testid="child"></div>
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
                <div ${initComponent({}, {id: 'the-child-id1', fingerprint: 'child-fingerprint1'})}>Child1</div>
                <div ${initComponent({}, {id: 'the-child-id2', fingerprint: 'child-fingerprint2'})}>Child2</div>
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
});
