/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import {createTest, initComponent, shutdownTests, setCurrentSearch, expectCurrentSearch} from '../tools';
import { getByText, waitFor } from '@testing-library/dom';

describe('LiveController query string binding', () => {
    afterEach(() => {
        shutdownTests();
        setCurrentSearch('');
    });

    it('doesn\'t initialize URL if props are not defined', async () => {
        await createTest({ prop: ''}, (data: any) => `
            <div ${initComponent(data, { queryMapping: {prop: {name: 'prop'}}})}></div>
        `)

        expectCurrentSearch().toEqual('');
    })

    it('doesn\'t initialize URL with defined props values', async () => {
        await createTest({ prop: 'foo'}, (data: any) => `
            <div ${initComponent(data, { queryMapping: {prop: {name: 'prop'}}})}></div>
        `)

        expectCurrentSearch().toEqual('');
    });

    it('updates basic props in the URL', async () => {
        const test = await createTest({ prop1: '', prop2: null}, (data: any) => `
            <div ${initComponent(data, { queryMapping: {prop1: {name: 'prop1'}, prop2: {name: 'prop2'}}})}></div>
        `)

        // String

        // Set value
        test.expectsAjaxCall()
            .expectUpdatedData({prop1: 'foo'});

        await test.component.set('prop1', 'foo', true);

        expectCurrentSearch().toEqual('?prop1=foo&prop2=');

        // Remove value
        test.expectsAjaxCall()
            .expectUpdatedData({prop1: ''});

        await test.component.set('prop1', '', true);

        expectCurrentSearch().toEqual('?prop1=&prop2=');

        // Number

        // Set value
        test.expectsAjaxCall()
            .expectUpdatedData({prop2: 42});

        await test.component.set('prop2', 42, true);

        expectCurrentSearch().toEqual('?prop1=&prop2=42');

        // Remove value
        test.expectsAjaxCall()
            .expectUpdatedData({prop2: null});

        await test.component.set('prop2', null, true);

        expectCurrentSearch().toEqual('?prop1=&prop2=');
    });

    it('updates array props in the URL', async () => {
        const test = await createTest({ prop: []}, (data: any) => `
            <div ${initComponent(data, { queryMapping: {prop: {name: 'prop'}}})}></div>
        `)

        // Set value
        test.expectsAjaxCall()
            .expectUpdatedData({prop: ['foo', 'bar']});

        await test.component.set('prop', ['foo', 'bar'], true);

        expectCurrentSearch().toEqual('?prop[0]=foo&prop[1]=bar');

        // Remove one value
        test.expectsAjaxCall()
            .expectUpdatedData({prop: ['foo']});

        await test.component.set('prop', ['foo'], true);

        expectCurrentSearch().toEqual('?prop[0]=foo');

        // Remove all remaining values
        test.expectsAjaxCall()
            .expectUpdatedData({prop: []});

        await test.component.set('prop', [], true);

        expectCurrentSearch().toEqual('?prop=');
    });

    it('updates objects in the URL', async () => {
        const test = await createTest({ prop: { foo: null, bar: null, baz: null}}, (data: any) => `
            <div ${initComponent(data, { queryMapping: {prop: {name: 'prop'}}})}></div>
        `)

        // Set single nested prop
        test.expectsAjaxCall()
            .expectUpdatedData({'prop.foo': 'dummy' });

        await test.component.set('prop.foo', 'dummy', true);

        expectCurrentSearch().toEqual('?prop[foo]=dummy');

        // Set multiple values
        test.expectsAjaxCall()
            .expectUpdatedData({prop: { foo: 'other', bar: 42 } });

        await test.component.set('prop', { foo: 'other', bar: 42 }, true);

        expectCurrentSearch().toEqual('?prop[foo]=other&prop[bar]=42');

        // Remove one value
        test.expectsAjaxCall()
            .expectUpdatedData({prop: { foo: 'other', bar: null } });

        await test.component.set('prop', { foo: 'other', bar: null }, true);

        expectCurrentSearch().toEqual('?prop[foo]=other');

        // Remove all values
        test.expectsAjaxCall()
            .expectUpdatedData({prop: { foo: null, bar: null } });

        await test.component.set('prop', { foo: null, bar: null }, true);

        expectCurrentSearch().toEqual('?prop=');
    });

    it('updates the URL with props changed by the server', async () => {
        const test = await createTest({ prop: ''}, (data: any) => `
            <div ${initComponent(data, {queryMapping: {prop: {name: 'prop'}}})}>
                Prop: ${data.prop}
                <button data-action="live#action" data-live-action-param="changeProp">Change prop</button>
            </div>
        `);

        test.expectsAjaxCall()
            .expectActionCalled('changeProp')
            .serverWillChangeProps((data: any) => {
                data.prop = 'foo';
            });

        getByText(test.element, 'Change prop').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Prop: foo'));

        expectCurrentSearch().toEqual('?prop=foo');
    });

    it('uses custom name instead of prop name in the URL', async () => {
        const test = await createTest({ prop1: ''}, (data: any) => `
            <div ${initComponent(data, { queryMapping: {prop1: {name: 'alias1'} }})}></div>
        `)

        // Set value
        test.expectsAjaxCall()
            .expectUpdatedData({prop1: 'foo'});

        await test.component.set('prop1', 'foo', true);

        expectCurrentSearch().toEqual('?alias1=foo');

        // Remove value
        test.expectsAjaxCall()
            .expectUpdatedData({prop1: ''});

        await test.component.set('prop1', '', true);

        expectCurrentSearch().toEqual('?alias1=');
    });
})
