/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import {
    createTestForExistingComponent,
    createTest,
    initComponent,
    shutdownTests,
    getComponent,
    dataToJsonAttribute,
    getStimulusApplication,
} from '../tools';
import { getByTestId, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { findChildren } from '../../src/ComponentRegistry';
import { Controller } from '@hotwired/stimulus';

describe('Component parent -> child initialization and rendering tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('sends a map of child fingerprints on re-render', async () => {
        const test = await createTest(
            {},
            (data: any) => `
            <div ${initComponent(data)}>
                <div ${initComponent({}, { id: 'the-child-id1', fingerprint: 'child-fingerprint1' })}>Child1</div>
                <div ${initComponent({}, { id: 'the-child-id2', fingerprint: 'child-fingerprint2' })}>Child2</div>
            </div>
        `
        );

        test.expectsAjaxCall().expectChildFingerprints({
            'the-child-id1': { fingerprint: 'child-fingerprint1', tag: 'div' },
            'the-child-id2': { fingerprint: 'child-fingerprint2', tag: 'div' },
        });

        test.component.render();
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
    });

    it('removes missing child component on re-render', async () => {
        const test = await createTest(
            { renderChild: true },
            (data: any) => `
            <div ${initComponent(data)}>
                ${
                    data.renderChild
                        ? `<div ${initComponent({}, { id: 'the-child-id' })} data-testid="child">Child Component</div>`
                        : ''
                }
            </div>
        `
        );

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderChild = false;
        });

        expect(test.element).toHaveTextContent('Child Component');
        expect(findChildren(test.component).length).toEqual(1);
        test.component.render();
        // wait for child to disappear
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        expect(test.element).not.toHaveTextContent('Child Component');
        expect(findChildren(test.component).length).toEqual(0);
    });

    it('adds new child component on re-render', async () => {
        const test = await createTest(
            { renderChild: false },
            (data: any) => `
           <div ${initComponent(data)}>
               ${
                   data.renderChild
                       ? `<div ${initComponent({}, { id: 'the-child-id' })} data-testid="child">Child Component</div>`
                       : ''
               }
           </div>
       `
        );

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderChild = true;
        });

        expect(test.element).not.toHaveTextContent('Child Component');
        expect(findChildren(test.component).length).toEqual(0);
        test.component.render();
        // wait for child to disappear
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        expect(test.element).toHaveTextContent('Child Component');
        expect(findChildren(test.component).length).toEqual(1);
    });

    it('new child marked as data-live-preserve is ignored except for new attributes', async () => {
        const originalChild = `
            <div ${initComponent({}, { id: 'the-child-id' })}>
                Original Child Component
            </div>
        `;
        const updatedChild = `
            <div id="the-child-id" data-live-preserve data-new="bar">
                Updated Child Component
            </div>
        `;

        const test = await createTest(
            { useOriginalChild: true },
            (data: any) => `
           <div ${initComponent(data)}>
               ${data.useOriginalChild ? originalChild : updatedChild}
           </div>
       `
        );

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.useOriginalChild = false;
        });

        expect(test.element).toHaveTextContent('Original Child Component');
        test.component.render();
        // wait for Ajax call
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // child component is STILL here: the new rendering was ignored
        expect(test.element).toHaveTextContent('Original Child Component');
        expect(test.element).toContainHTML('data-new="bar"');
        expect(test.element).not.toContainHTML('data-live-preserve');
    });

    it('data-live-preserve child in same location is not removed/re-added to the DOM', async () => {
        const originalChild = `
            <div ${initComponent({}, { id: 'the-child-id' })}>
                <div data-controller="track-connect"></div>
                Original Child Component
            </div>
        `;
        const updatedChild = `
            <div id="the-child-id" data-live-preserve></div>
        `;

        const test = await createTest(
            { useOriginalChild: true },
            (data: any) => `
           <div ${initComponent(data)}>
               ${data.useOriginalChild ? originalChild : updatedChild}
           </div>
       `
        );

        getStimulusApplication().register(
            'track-connect',
            class extends Controller {
                disconnect() {
                    this.element.setAttribute('disconnected', '');
                }
            }
        );

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.useOriginalChild = false;
        });

        await test.component.render();
        // sanity check that the child is there
        expect(test.element).toHaveTextContent('Original Child Component');
        // check that the element was never disconnected/removed from the DOM
        expect(test.element).not.toContainHTML('disconnected');
    });

    it('data-live-preserve element moved correctly when position changes and old element morphed into different element', async () => {
        const originalChild = `
            <div ${initComponent({}, { id: 'the-child-id' })} data-testid="child-component">
                <div data-controller="track-connect"></div>
                Original Child Component
            </div>
        `;
        const updatedChild = `
            <div id="the-child-id" data-live-preserve></div>
        `;

        // when morphing original -> updated, the outer div (which was the child)
        // will be morphed into a normal div
        const test = await createTest(
            { useOriginalChild: true },
            (data: any) => `
           <div ${initComponent(data)}>
               ${data.useOriginalChild ? originalChild : ''}
               ${data.useOriginalChild ? '' : `<div class="wrapper">${updatedChild}</div>`}
           </div>
       `
        );

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.useOriginalChild = false;
        });

        const childElement = getByTestId(test.element, 'child-component');
        await test.component.render();
        // sanity check that the child is there
        expect(test.element).toHaveTextContent('Original Child Component');
        expect(test.element).toContainHTML('class="wrapper"');
        expect(childElement.parentElement).toHaveClass('wrapper');
    });

    it('existing child component gets props & triggers re-render', async () => {
        const childTemplate = (data: any) => `
            <div ${initComponent(
                { toUppercase: data.toUppercase, fullName: data.fullName },
                { id: 'the-child-id', fingerprint: 'original fingerprint' }
            )} data-testid="child-component">
                <input data-model="norender|fullName">
                Full Name: ${data.toUppercase ? data.fullName.toUpperCase() : data.fullName}
            </div>
        `;

        // a simpler version of the child is returned from the parent component's re-render
        const childReturnedFromParentCall = `
            <div
                id="the-child-id"
                data-live-fingerprint-value="updated fingerprint"
                data-live-preserve
                data-live-props-updated-from-parent-value="${dataToJsonAttribute({ toUppercase: true })}"
            ><!-- no body needed --></div>
        `;

        const test = await createTest(
            { useOriginalChild: true },
            (data: any) => `
           <div ${initComponent(data)}>
               Using Original child: ${data.useOriginalChild ? 'yes' : 'no'}
               ${
                   data.useOriginalChild
                       ? childTemplate({ fullName: 'Ryan', toUppercase: false })
                       : childReturnedFromParentCall
               }
           </div>
       `
        );

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
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.useOriginalChild = false;
        });
        test.component.render();
        // wait for parent Ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));

        // E) Expect the child to re-render
        // after the parent Ajax call has finished, but shortly before it's
        // done processing, the child component should start its own Ajax call
        childTest
            .expectsAjaxCall()
            // expect the modified firstName data
            // expect the new prop
            .expectUpdatedData({ fullName: 'Ryan Weaver' })
            .expectUpdatedPropsFromParent({ toUppercase: true })
            .willReturn(childTemplate);

        // wait for parent Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // sanity check
        expect(test.element).toHaveTextContent('Using Original child: no');

        // after the parent re-renders, the child should already have received its new fingerprint
        expect(childComponent.fingerprint).toEqual('updated fingerprint');

        // wait for child to start and stop loading
        await waitFor(() => expect(childComponent.element).toHaveAttribute('busy'));
        await waitFor(() => expect(childComponent.element).not.toHaveAttribute('busy'));

        // child component re-rendered and there are a few important things here
        // 1) the toUppercase prop was changed by the parent and that change remains
        // 2) The " Weaver" change to the "firstName" data was kept, not "run over"
        expect(childComponent.element).toHaveTextContent('Full Name: RYAN WEAVER');
    });

    it('child controller changes its component if child id changes', async () => {
        // both are a span in the same position: so the same Stimulus controller
        // will be used for both.
        const originalChildTemplate = (data: any) => `
            <span ${initComponent(data, { id: 'original-child-id' })} data-testid="child-component">
                Original Child
            </span>
        `;

        const reRenderedChildTemplate = (data: any) => `
            <span ${initComponent(data, { id: 'new-child-id' })} data-testid="child-component">
                New Child
            </span>
        `;

        const test = await createTest(
            { useOriginalChild: true },
            (data: any) => `
           <div ${initComponent(data)}>
               Parent Component
               ${data.useOriginalChild ? originalChildTemplate({ name: 'original' }) : reRenderedChildTemplate({ name: 'new' })}
           </div>
       `
        );

        const originalChildElement = getByTestId(test.element, 'child-component');

        // Re-render the parent
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            // trigger the re-rendered child to be used
            data.useOriginalChild = false;
        });
        test.component.render();
        // wait for parent Ajax call to start/finish
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));

        // no child Ajax call made: we simply use the new child's content
        expect(test.element).toHaveTextContent('New Child');
        expect(test.element).not.toHaveTextContent('Original Child');

        expect(findChildren(test.component).length).toEqual(1);
        const newChildElement = getByTestId(test.element, 'child-component');
        expect(newChildElement).toEqual(originalChildElement);
        const childComponent = getComponent(newChildElement);
        expect(childComponent.id).toEqual('new-child-id');
    });

    it('tracks various children correctly, even if position changes', async () => {
        const childTemplate = (data: any) => `
            <span ${initComponent({ number: data.number, value: data.value }, { id: `child-id-${data.number}` })} data-testid="child-component-${data.number}">
                Child number: ${data.number} value "${data.value}"
            </span>
        `;
        // the empty-ish child element used on re-render
        const emptyChildTemplate = (data: any) => `
            <span
                id="child-id-${data.number}"
                data-testid="child-component-${data.number}"
                data-live-preserve
                data-live-props-updated-from-parent-value="${dataToJsonAttribute({ number: data.number, value: data.value })}"
            ></span>
        `;

        const test = await createTest(
            {},
            (data: any) => `
           <div ${initComponent(data)}>
               ${childTemplate({ number: 1, value: 'Original value for child 1' })}
               <div>Parent Component</div>
               ${childTemplate({ number: 2, value: 'Original value for child 2' })}
           </div>
       `
        );

        // Re-render the parent
        test.expectsAjaxCall()
            // return the template in a different order
            // and render children with an updated value prop
            .willReturn(
                (data: any) => `
                <div ${initComponent(data)}>
                    <div id="foo">
                        ${emptyChildTemplate({ number: 2, value: 'New value for child 2' })}
                    </div>
                    <div>Parent Component Updated</div>
                    <ul>
                        <li>
                            ${emptyChildTemplate({ number: 1, value: 'New value for child 1' })}
                        </li>
                    </ul>
                </div>
            `
            );

        const childComponent1 = getComponent(getByTestId(test.element, 'child-component-1'));
        const childTest1 = createTestForExistingComponent(childComponent1);
        const childComponent2 = getComponent(getByTestId(test.element, 'child-component-2'));
        const childTest2 = createTestForExistingComponent(childComponent2);

        // Expect both children to re-render
        childTest1
            .expectsAjaxCall()
            // new props are sent, but that doesn't count as updated data
            // we verify the new props are used below by checking the HTML
            .expectUpdatedData({})
            .expectUpdatedPropsFromParent({ number: 1, value: 'New value for child 1' })
            .willReturn(childTemplate);
        childTest2
            .expectsAjaxCall()
            .expectUpdatedData({})
            .expectUpdatedPropsFromParent({ number: 2, value: 'New value for child 2' })
            .willReturn(childTemplate);

        // trigger the parent render, which will trigger the children to re-render
        await test.component.render();

        // wait for child to start and stop loading
        await waitFor(() => expect(getByTestId(test.element, 'child-component-1')).not.toHaveAttribute('busy'));
        await waitFor(() => expect(getByTestId(test.element, 'child-component-2')).not.toHaveAttribute('busy'));

        expect(test.element).toHaveTextContent('Child number: 1 value "New value for child 1"');
        expect(test.element).toHaveTextContent('Child number: 2 value "New value for child 2"');
        expect(test.element).not.toHaveTextContent('Child number: 1 value "Original value for child 1"');
        expect(test.element).not.toHaveTextContent('Child number: 2 value "Original value for child 2"');
        // make sure child 2 is in the correct spot
        expect(test.element.querySelector('#foo')).toHaveTextContent('Child number: 2 value "New value for child 2"');
        expect(findChildren(test.component).length).toEqual(2);
    });
});
