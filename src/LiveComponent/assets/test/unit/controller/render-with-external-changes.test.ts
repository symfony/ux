/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { getByTestId } from '@testing-library/dom';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { htmlToElement } from '../../../src/dom_utils';
import { createTest, getComponent, initComponent, shutdownTests } from '../../tools';

describe('LiveController rendering with external changes tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('will respect attribute changes to a tracked element', async () => {
        const test = await createTest(
            { id: 'element-id', isDisabled: false, bonusClass: '', margin: '10px' },
            (data: any) => `
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
        `
        );

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

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            // change the data on the server so the template renders differently
            data.isDisabled = true;
            data.bonusClass = 'class-added-by-server';
            data.margin = '20px';
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
        const test = await createTest(
            { withBonusElement: false },
            (data: any) => `
            <div ${initComponent(data)}>
                <div data-testid="inner-div">
                    Text inside the div
                    ${data.withBonusElement ? '<div class="bonus-element">Bonus element</div>' : ''}
                </div>
            </div>
        `
        );

        // add a new element directly inside the root element
        test.element.appendChild(htmlToElement('<div class="added-outside-element">Added outside element</div>'));
        const innerDiv = getByTestId(test.element, 'inner-div');
        // append a new element inside the inner div
        innerDiv.appendChild(
            htmlToElement('<div class="added-inside-element-append">Added inside element append</div>')
        );
        // prepend a new element inside the inner div
        innerDiv.prepend(htmlToElement('<div class="added-inside-element-prepend">Added inside element prepend</div>'));

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
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
        const test = await createTest(
            { isDisabled: false, bonusClass: '', withBonusElement: false },
            (data: any) => `
           <div ${initComponent(data)}>
               <button
                   data-testid='the-button'
                   ${data.isDisabled ? 'disabled' : ''}
                   class='originalclass1 originalclass2 ${data.bonusClass}'
               >I'm a button!</button>
               ${data.withBonusElement ? '<div class="bonus-element">Bonus element</div>' : ''}
           </div>
       `
        );

        // mess with the button
        const button = getByTestId(test.element, 'the-button') as HTMLButtonElement;
        button.setAttribute('data-foo', 'bar');
        const addedOutsideElement = htmlToElement('<div class="added-outside-element">Added outside element</div>');
        test.element.appendChild(addedOutsideElement);

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
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
        const secondAddedOutsideElement = htmlToElement(
            '<div class="added-outside-element-2">Added outside element 2</div>'
        );
        test.element.appendChild(secondAddedOutsideElement);
        addedOutsideElement.classList.add('class-added-later');

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
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

    it.each([
        [null, 'client-id'],
        ['server-id', 'client-id'],
        ['server-id', null],
        ['server-id', ''],
    ])('preserves a client ID change from %s to %s across renders', async (serverId, clientId) => {
        const test = await createTest(
            { count: 0 },
            (data) => `
            <div ${initComponent(data)}>
                <input ${serverId === null ? '' : `id="${serverId}"`} data-testid="field" value="${data.count}">
            </div>
        `
        );
        const field = getByTestId(test.element, 'field');
        if (clientId === null) {
            field.removeAttribute('id');
        } else {
            field.id = clientId;
        }
        field.classList.add('client-class');

        for (const count of [1, 2]) {
            test.expectsAjaxCall().serverWillChangeProps((data) => {
                data.count = count;
            });
            await test.component.render();
            expect(getByTestId(test.element, 'field')).toBe(field);
            expect(field.getAttribute('id')).toBe(clientId);
            expect(field).toHaveClass('client-class');
            expect(field).toHaveValue(String(count));
        }
    });

    it('replaces an externally modified element when its server ID changes', async () => {
        const test = await createTest(
            { id: 'original', label: 'Original' },
            (data) => `
            <div ${initComponent(data)}><button id="${data.id}" data-testid="button">${data.label}</button></div>
        `
        );
        const original = getByTestId(test.element, 'button');
        original.id = 'client-id';
        original.classList.add('client-class');
        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.id = 'replacement';
            data.label = 'Replacement';
        });
        await test.component.render();
        const replacement = getByTestId(test.element, 'button');
        expect(replacement).not.toBe(original);
        expect(original.isConnected).toBe(false);
        expect(original.id).toBe('client-id');
        expect(replacement.id).toBe('replacement');
        expect(replacement).not.toHaveClass('client-class');
        expect(replacement).toHaveTextContent('Replacement');
    });

    it('uses server IDs to reorder elements whose IDs changed externally', async () => {
        const test = await createTest(
            { ids: ['a', 'b', 'c'] },
            (data) => `
            <div ${initComponent(data)}>${data.ids.map((id: string) => `<input id="${id}" data-testid="${id}" value="${id}">`).join('')}</div>
        `
        );
        const fields = ['a', 'b', 'c'].map((id) => getByTestId(test.element, id));
        fields.forEach((field, index) => {
            field.id = `client-${index}`;
        });
        for (const ids of [
            ['c', 'b', 'a'],
            ['b', 'a', 'c'],
        ]) {
            test.expectsAjaxCall().serverWillChangeProps((data) => {
                data.ids = ids;
            });
            await test.component.render();
            expect([...test.element.children]).toEqual(ids.map((id) => fields[['a', 'b', 'c'].indexOf(id)]));
            fields.forEach((field, index) => {
                expect(field.id).toBe(`client-${index}`);
            });
        }
    });

    it('renders all incoming siblings while preserving consecutive external elements', async () => {
        const test = await createTest(
            { ids: [] as number[] },
            (data) => `
            <div ${initComponent(data)}>${data.ids.map((id: number) => `<div id="item-${id}">${id}</div>`).join('')}</div>
        `
        );
        const widgets = [htmlToElement('<div>First widget</div>'), htmlToElement('<div>Second widget</div>')];
        const onClick = vi.fn();
        widgets[0].addEventListener('click', onClick);
        test.element.append(...widgets);
        for (const ids of [
            [1, 2, 3],
            [3, 4],
        ]) {
            test.expectsAjaxCall().serverWillChangeProps((data) => {
                data.ids = ids;
            });
            await test.component.render();
            expect([...test.element.children].slice(0, 2)).toEqual(widgets);
            expect([...test.element.children].slice(2).map((element) => element.id)).toEqual(
                ids.map((id) => `item-${id}`)
            );
            expect([...test.element.childNodes].some((node) => node.nodeType === Node.COMMENT_NODE)).toBe(false);
        }
        widgets[0].click();
        expect(onClick).toHaveBeenCalledTimes(1);
    });

    it('moves a keyed field with a client ID past an external element without cloning it', async () => {
        const fieldHtml = '<input id="field" value="hello">';
        const test = await createTest(
            { moved: false },
            (data) => `
            <div ${initComponent(data)}>
                <div id="destination">${data.moved ? `<div class="server-wrapper">${fieldHtml}</div>` : ''}</div>
                <section id="source">${data.moved ? '' : fieldHtml}</section>
            </div>
        `
        );
        const destination = test.element.querySelector('#destination') as HTMLElement;
        const widget = htmlToElement('<div>External widget</div>');
        destination.append(widget);
        const field = test.element.querySelector('#field') as HTMLInputElement;
        field.id = 'client-field';
        field.focus();
        field.setSelectionRange(2, 2);
        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.moved = true;
        });
        await test.component.render();
        expect(destination.firstElementChild).toBe(widget);
        expect(destination.querySelector('.server-wrapper > input')).toBe(field);
        expect(field.id).toBe('client-field');
        expect(document.activeElement).toBe(field);
        expect(field.selectionStart).toBe(2);
    });

    it('moves a preserved child into a new wrapper after an external element', async () => {
        const test = await createTest(
            { moved: false },
            (data) => `
            <div ${initComponent(data)}>
                <div id="destination">${data.moved ? '<div class="server-wrapper"><div id="child" data-live-preserve></div></div>' : ''}</div>
                <section id="source">${data.moved ? '' : `<div ${initComponent({}, { id: 'child' })}>Child content</div>`}</section>
            </div>
        `
        );
        const destination = test.element.querySelector('#destination') as HTMLElement;
        const widget = htmlToElement('<div>External widget</div>');
        destination.append(widget);
        const child = test.element.querySelector('#child') as HTMLElement;
        const childComponent = getComponent(child);
        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.moved = true;
        });
        await test.component.render();
        expect(destination.firstElementChild).toBe(widget);
        expect(destination.querySelector('.server-wrapper > #child')).toBe(child);
        expect(getComponent(child)).toBe(childComponent);
        expect(child).toHaveTextContent('Child content');
    });

    it.each(['div', 'form'])('preserves an external %s when the server starts rendering the same ID', async (tag) => {
        const test = await createTest(
            { show: false },
            (data) => `
            <div ${initComponent(data)}>${data.show ? `<${tag} id="shared"><input name="id" value="server">Server representation</${tag}><span>After</span>` : ''}</div>
        `
        );
        const widget = htmlToElement(`<${tag} id="shared"><input name="id" value="client">External widget</${tag}>`);
        test.element.append(widget);
        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.show = true;
        });
        await test.component.render();
        expect(test.element.querySelectorAll('#shared')).toHaveLength(1);
        expect(test.element.querySelector('#shared')).toBe(widget);
        expect(widget).toHaveTextContent('External widget');
        expect(test.element.lastElementChild).toHaveTextContent('After');
    });
});
