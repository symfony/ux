/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { createTestForExistingComponent, createTest, initComponent, shutdownTests, getComponent } from '../tools';
import { getByTestId, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

describe('Component parent -> child initialization and rendering tests', () => {
    afterEach(() => {
        shutdownTests();
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
        const childComponent = getComponent(getByTestId(test.element, 'child'));
        // setting a marker to help verify THIS exact Component instance continues to be used
        childComponent.fingerprint = 'FOO-FINGERPRINT';

        // check that the relationships all loaded correctly
        expect(parentComponent.getChildren().size).toEqual(1);
        // check fingerprint instead of checking object equality with childComponent
        // because childComponent is actually the proxied Component
        expect(parentComponent.getChildren().get('the-child-id')?.fingerprint).toEqual('FOO-FINGERPRINT');
        expect(childComponent.getParent()).toBe(parentComponent);

        // remove the child
        childComponent.element.remove();
        // wait because the event is slightly async
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(0));
        expect(childComponent.getParent()).toBeNull();

        // now put it back!
        test.element.appendChild(childComponent.element);
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(1));
        expect(parentComponent.getChildren().get('the-child-id')?.fingerprint).toEqual('FOO-FINGERPRINT');
        expect(childComponent.getParent()).toEqual(parentComponent);

        // now remove the whole darn thing!
        test.element.remove();
        // this will, while disconnected, break the parent-child bond
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(0));
        expect(childComponent.getParent()).toBeNull();

        // put it *all* back
        document.body.appendChild(test.element);
        await waitFor(() => expect(parentComponent.getChildren().size).toEqual(1));
        expect(parentComponent.getChildren().get('the-child-id')?.fingerprint).toEqual('FOO-FINGERPRINT');
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

    it('removes missing child component on re-render', async () => {
        const test = await createTest({renderChild: true}, (data: any) => `
            <div ${initComponent(data)}>
                ${data.renderChild
                    ? `<div ${initComponent({}, {id: 'the-child-id'})} data-testid="child">Child Component</div>`
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
            ? `<div ${initComponent({}, {id: 'the-child-id'})} data-testid="child">Child Component</div>`
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

    it('existing child component that has no props is ignored', async () => {
        const originalChild = `
            <div ${initComponent({}, {id: 'the-child-id'})}>
                Original Child Component
            </div>
        `;
        const updatedChild = `
            <div ${initComponent({}, {id: 'the-child-id'})}>
                Updated Child Component
            </div>
        `;

        const test = await createTest({useOriginalChild: true}, (data: any) => `
           <div ${initComponent(data)}>
               ${data.useOriginalChild ? originalChild : updatedChild}
           </div>
       `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.useOriginalChild = false;
            })
            .init();

        expect(test.element).toHaveTextContent('Original Child Component')
        test.component.render();
        // wait for Ajax call
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // child component is STILL here: the new rendering was ignored
        expect(test.element).toHaveTextContent('Original Child Component')
    });

    it('existing child component gets props & triggers re-render', async () => {
        const childTemplate = (data: any) => `
            <div ${initComponent(
                { toUppercase: data.toUppercase, fullName: data.fullName },
                { id: 'the-child-id', fingerprint: 'original fingerprint'}
            )} data-testid="child-component">
                <input data-model="norender|fullName">
                Full Name: ${data.toUppercase ? data.fullName.toUpperCase() : data.fullName}
            </div>
        `;

        // a simpler version of the child is returned from the prent component's re-render
        const childReturnedFromParentCall = `
            <div ${initComponent(
                { toUppercase: true }, // new prop value (firstName is not sent as it is writable)
                { id: 'the-child-id', fingerprint: 'updated fingerprint'}
            )}><!-- no body needed --></div>
        `;

        const test = await createTest({useOriginalChild: true}, (data: any) => `
           <div ${initComponent(data)}>
               Using Original child: ${data.useOriginalChild ? 'yes' : 'no'}
               ${data.useOriginalChild
                    ? childTemplate({ fullName: 'Ryan', toUppercase: false })
                    : childReturnedFromParentCall
                }
           </div>
       `);

        const childComponent = getComponent(getByTestId(test.element, 'child-component'));
        // just used to mock the Ajax call
        const childTest = createTestForExistingComponent(childComponent);

        /*
         * The flow of this test:
         *      A) Original parent & child are rendered
         *      B) We type into a child "model" input, but it has "norender".
         *          So, no Ajax call is made, but the "data" on the child has been updated.
         *      C) We Re-render the parent component
         *      D) On re-render, the child element is empty, but its element has
         *          updated "props" and also a new "fingerprint". This mimics
         *          the condition on the server when the old child fingerprint
         *          that was just sent does not match the new fingerprint, indicating
         *          that data passed "into" the component to create it has changed.
         *          And so, the server returns the new set of props and the new
         *          fingerprint representing that "input" data.
         *      E) Seeing that its props have changed, the child component makes
         *          an Ajax call to re-render itself. But it keeps its modified data.
         */

        // B) Type into the child
        // |norender is used on this field
        userEvent.type(childTest.queryByDataModel('fullName'), ' Weaver');

        // C) Re-render the parent
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.useOriginalChild = false;
            })
            .init();
        test.component.render();
        // wait for parent Ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));

        // E) Expect the child to re-render
        // after the parent Ajax call has finished, but shortly before it's
        // done processing, the child component should start its own Aja call
        childTest.expectsAjaxCall('get')
            // expect the modified firstName data
            // expect the new prop
            .expectSentData({ toUppercase: true, fullName: 'Ryan Weaver' })
            .willReturn(childTemplate)
            .init();

        // wait for parent Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // sanity check
        expect(test.element).toHaveTextContent('Using Original child: no')

        // after the parent re-renders, the child should already have received its new fingerprint
        expect(childComponent.fingerprint).toEqual('updated fingerprint');

        // wait for child to start and stop loading
        await waitFor(() => expect(childComponent.element).toHaveAttribute('busy'));
        await waitFor(() => expect(childComponent.element).not.toHaveAttribute('busy'));

        // child component re-rendered and there are a few important things here
        // 1) the toUppercase prop was changed after by the parent and that change remains
        // 2) The " Weaver" change to the "firstName" data was kept, not "run over"
        expect(childComponent.element).toHaveTextContent('Full Name: RYAN WEAVER')
    });

    it('existing child gets new props even though the element type differs', async () => {
        const realChildTemplate = (data: any) => `
            <span ${initComponent({ prop1: data.prop1 }, { id: 'child-id' })} data-testid="child-component">
                Child prop1: ${data.prop1}
            </span>
        `;
        // the empty-ish child element used on re-render
        const parentReRenderedChildTemplate = (data: any) => `
            <div ${initComponent({ prop1: data.prop1 }, { id: 'child-id' })} data-testid="child-component"></div>
        `;

        const test = await createTest({prop1: 'original_prop', useRealChild: true}, (data: any) => `
           <div ${initComponent(data)}>
               Parent Component
               ${data.useRealChild ? realChildTemplate({data}) : parentReRenderedChildTemplate(data)}
           </div>
       `);

        const childComponent = getComponent(getByTestId(test.element, 'child-component'));
        // just used to mock the Ajax call
        const childTest = createTestForExistingComponent(childComponent);

        // Re-render the parent
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                // change the child prop
                data.prop1 = 'updated_prop';
                // re-render the "fake" component with a different tag
                data.useRealChild = false;
            })
            .init();
        test.component.render();
        // wait for parent Ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));

        // Expect the child to re-render
        childTest.expectsAjaxCall('get')
            // expect the new prop
            .expectSentData({ prop1: 'updated_prop' })
            .willReturn(realChildTemplate)
            .init();

        // wait for parent Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // wait for child to start and stop loading
        await waitFor(() => expect(childComponent.element).toHaveAttribute('busy'));
        await waitFor(() => expect(childComponent.element).not.toHaveAttribute('busy'));

        // child component re-rendered successfully
        // re-grabbing child-component fresh to prove it's not stale
        const childElement2 = getByTestId(test.element, 'child-component');
        expect(childElement2).toHaveTextContent('Child prop1: updated_prop')
        // it should still be a span, even though the initial re-render was a div
        expect(childElement2.tagName).toEqual('SPAN');
    });

    it('replaces old child with new child if the "id" changes', async () => {
        const originalChildTemplate = `
            <span ${initComponent({}, { id: 'original-child-id' })} data-testid="child-component">
                Original Child
            </span>
        `;
        const reRenderedChildTemplate = `
            <span ${initComponent({}, { id: 'new-child-id' })} data-testid="child-component">
                New Child
            </span>
        `;

        const test = await createTest({useOriginalChild: true}, (data: any) => `
           <div ${initComponent(data)}>
               Parent Component
               ${data.useOriginalChild ? originalChildTemplate : reRenderedChildTemplate}
           </div>
       `);

        // Re-render the parent
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                // trigger the re-rendered child to be used
                data.useOriginalChild = false;
            })
            .init();
        test.component.render();
        // wait for parent Ajax call to start/finish
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));

        // no child Ajax call made: we simply use the new child's content
        expect(test.element).toHaveTextContent('New Child')
        expect(test.element).not.toHaveTextContent('Original Child')

        expect(test.component.getChildren().size).toEqual(1);
    });

    it('tracks various children correctly, even if position changes', async () => {
        const childTemplate = (data: any) => `
            <span ${initComponent({ number: data.number, value: data.value }, { id: `child-id-${data.number}` })} data-testid="child-component-${data.number}">
                Child number: ${data.number} value "${data.value}"
            </span>
        `;
        // the empty-ish child element used on re-render
        const childRenderedFromParentTemplate = (data: any) => `
            <span ${initComponent({ number: data.number, value: data.value }, { id: `child-id-${data.number}` })} data-testid="child-component-${data.number}"></span>
        `;

        const test = await createTest({}, (data: any) => `
           <div ${initComponent(data)}>
               ${childTemplate({ number: 1, value: 'Original value for child 1' })}
               <div>Parent Component</div>
               ${childTemplate({ number: 2, value: 'Original value for child 2' })}
           </div>
       `);

        // Re-render the parent
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            // return the template in a different order
            // and render children with an updated value prop
            .willReturn((data: any) => `
                <div ${initComponent(data)}>
                    <div id="foo">
                        ${childRenderedFromParentTemplate({ number: 2, value: 'New value for child 2' })}
                    </div>
                    <div>Parent Component Updated</div>
                    <ul>
                        <li>
                            ${childRenderedFromParentTemplate({ number: 1, value: 'New value for child 1' })}
                        </li>
                    </ul>
                </div>
            `)
            .init();
        test.component.render();
        // wait for parent Ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));

        const childComponent1 = getComponent(getByTestId(test.element, 'child-component-1'));
        const childTest1 = createTestForExistingComponent(childComponent1);
        const childComponent2 = getComponent(getByTestId(test.element, 'child-component-2'));
        const childTest2 = createTestForExistingComponent(childComponent2);

        // Expect both children to re-render
        childTest1.expectsAjaxCall('get')
            // expect the new prop
            .expectSentData({ number: 1, value: 'New value for child 1' })
            .willReturn(childTemplate)
            .init();
        childTest2.expectsAjaxCall('get')
            // expect the new prop
            .expectSentData({ number: 2, value: 'New value for child 2' })
            .willReturn(childTemplate)
            .init();

        // wait for parent Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // wait for child to start and stop loading
        await waitFor(() => expect(childTest1.element).toHaveAttribute('busy'));
        await waitFor(() => expect(childTest1.element).not.toHaveAttribute('busy'));
        await waitFor(() => expect(childTest2.element).not.toHaveAttribute('busy'));

        expect(test.element).toHaveTextContent('Child number: 1 value "New value for child 1"');
        expect(test.element).toHaveTextContent('Child number: 2 value "New value for child 2"');
        expect(test.element).not.toHaveTextContent('Child number: 1 value "Original value for child 1"');
        expect(test.element).not.toHaveTextContent('Child number: 2 value "Original value for child 2"');
        // make sure child 2 is in the correct spot
        expect(test.element.querySelector('#foo')).toHaveTextContent('Child number: 2 value "New value for child 2"');
        expect(test.component.getChildren().size).toEqual(2);
    });
});
