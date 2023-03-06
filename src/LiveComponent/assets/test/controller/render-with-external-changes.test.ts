/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { shutdownTests, createTest, initComponent } from '../tools';
import { getByTestId } from '@testing-library/dom';
import { htmlToElement } from '../../src/dom_utils';

describe('LiveController rendering with external changes tests', () => {
    afterEach(() => {
        shutdownTests();
    })

    it('will respect attribute changes to a tracked element', async () => {
        const test = await createTest({ id: 'element-id', isDisabled: false, bonusClass: '', margin: '10px' }, (data: any) => `
            <div ${initComponent(data)}>
                <button
                    data-testid="the-button"
                    ${data.isDisabled ? 'disabled' : ''}
                    class="originalclass1 originalclass2 ${data.bonusClass}"
                    style="margin: ${data.margin}; background-color: red; border-radius: 5px"
                    title="original title"
                    id="${data.id}"
                >I'm a button!</button>
            </div>
        `);

        // mess with the elements
        const button = getByTestId(test.element, 'the-button') as HTMLButtonElement;
        // add a new attribute
        button.setAttribute('data-foo', 'bar');
        // remove an attribute
        button.removeAttribute('title');
        // change an attribute that will also be changed on the server
        button.setAttribute('id', 'changed-externally');
        // add a new class
        button.classList.add('externally-added-class');
        // remove a class
        button.classList.remove('originalclass2');
        // change a style
        button.style.backgroundColor = 'blue';
        // add a new style
        button.style.setProperty('padding', '10px');
        // remove a style
        button.style.removeProperty('border-radius');

        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                // change the data on the server so the template renders differently
                data.isDisabled = true;
                data.bonusClass = 'class-added-by-server';
                data.margin = '20px';
                data.id = 'will-be-ignored'
            });

        await test.component.render();

        const expectedButton = htmlToElement(`
            <button
                data-testid="the-button"
                class="originalclass1 class-added-by-server externally-added-class"
                style="margin: 20px; background-color: blue; padding: 10px;"
                id="changed-externally"
                data-foo="bar"
                disabled=""
            >I'm a button!</button>
        `);
        expect(test.element.innerHTML.trim()).toBe(expectedButton.outerHTML);
    });

    it('will not remove an added element', async () => {
        const test = await createTest({ withBonusElement: false }, (data: any) => `
            <div ${initComponent(data)}>
                <div data-testid="inner-div">
                    Text inside the div
                    ${data.withBonusElement ? '<div class="bonus-element">Bonus element</div>' : ''}
                </div>
            </div>
        `);

        // add a new element directly inside the root element
        test.element.appendChild(htmlToElement('<div class="added-outside-element">Added outside element</div>'));
        const innerDiv = getByTestId(test.element, 'inner-div');
        // append a new element inside the inner div
        innerDiv.appendChild(htmlToElement('<div class="added-inside-element-append">Added inside element append</div>'));
        // prepend a new element inside the inner div
        innerDiv.prepend(htmlToElement('<div class="added-inside-element-prepend">Added inside element prepend</div>'));

        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.withBonusElement = true;
            });

        await test.component.render();

        // original child + new appended element
        expect(test.element.children.length).toBe(2);
        // first child still the innerDiv
        expect(test.element.children[0]).toBe(innerDiv);
        // second child is the new added-outside-element
        expect(test.element.children[1].classList.contains('added-outside-element')).toBe(true);

        // inner div has 5 children: 3 elements + 2 text nodes
        expect(innerDiv.childNodes.length).toBe(5);
        expect(innerDiv.childElementCount).toBe(3);
        // (1) added-inside-element-prepend
        expect(innerDiv.childNodes[0]).toBe(innerDiv.children[0]);
        expect(innerDiv.children[0].classList.contains('added-inside-element-prepend')).toBe(true);
        // (2) Original text inside the div - check that it's a text node
        expect(innerDiv.childNodes[4].nodeType).toBe(3);
        expect((innerDiv.childNodes[1].textContent as string).trim()).toBe('Text inside the div');
        // (3) added-inside-element-append
        expect(innerDiv.childNodes[2]).toBe(innerDiv.children[1]);
        expect(innerDiv.children[1].classList.contains('added-inside-element-append')).toBe(true);
        // (4) bonus-element
        expect(innerDiv.childNodes[3]).toBe(innerDiv.children[2]);
        expect(innerDiv.children[2].classList.contains('bonus-element')).toBe(true);
        // (5) ending whitespace - check that it's a text node
        expect(innerDiv.childNodes[4].nodeType).toBe(3);
        expect((innerDiv.childNodes[4].textContent as string).trim()).toBe('');
    });

    it('keeps external changes across multiple renders', async () => {
        const test = await createTest({ isDisabled: false, bonusClass: '', withBonusElement: false }, (data: any) => `
           <div ${initComponent(data)}>
               <button
                   data-testid='the-button'
                   ${data.isDisabled ? 'disabled' : ''}
                   class='originalclass1 originalclass2 ${data.bonusClass}'
               >I'm a button!</button>
               ${data.withBonusElement ? '<div class="bonus-element">Bonus element</div>' : ''}
           </div>
       `);

        // mess with the button
        const button = getByTestId(test.element, 'the-button') as HTMLButtonElement;
        button.setAttribute('data-foo', 'bar');
        const addedOutsideElement = htmlToElement('<div class="added-outside-element">Added outside element</div>');
        test.element.appendChild(addedOutsideElement);

        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.isDisabled = true;
                data.withBonusElement = true;
            });

        await test.component.render();

        // make sure the changes are still there
        expect(button.getAttribute('data-foo')).toBe('bar');
        expect(test.element).toContainElement(addedOutsideElement);
        expect(test.element.innerHTML).toContain('Bonus element');

        // make some more changes
        button.classList.add('externally-added-class');
        button.classList.remove('originalclass2');
        const secondAddedOutsideElement = htmlToElement('<div class="added-outside-element-2">Added outside element 2</div>');
        test.element.appendChild(secondAddedOutsideElement);
        addedOutsideElement.classList.add('class-added-later');

        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.bonusClass = 'class-added-by-server';
                data.withBonusElement = false;
            });

        await test.component.render();

        // make sure the changes are still there
        expect(button.getAttribute('data-foo')).toBe('bar');
        expect(button.classList.contains('externally-added-class')).toBe(true);
        expect(button.classList.contains('originalclass2')).toBe(false);
        expect(button.classList.contains('class-added-by-server')).toBe(true);
        // make sure the new elements are still there
        expect(test.element).toContainElement(addedOutsideElement);
        expect(addedOutsideElement.classList.contains('class-added-later')).toBe(true);
        expect(test.element).toContainElement(secondAddedOutsideElement);

        // bonus element change from server is gone
        // this verifies that server changes are not being tracked as "external"
        expect(test.element.innerHTML).not.toContain('Bonus element');
    });
});

