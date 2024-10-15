/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import VueController from '../src/render_controller';
import SimpleForm from './fixtures/SimpleForm.vue';

const startStimulus = () => {
    const application = Application.start();
    application.register('vue', VueController);
};

window.resolveVueComponent = () => {
    return SimpleForm;
};

describe('VueController', () => {
    it('reacts on field value changed', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="vue"
              data-vue-component-value="SimpleForm"
              data-vue-props-value="{&quot;value1&quot;:&quot;Derron Macgregor&quot;,&quot;value2&quot;:&quot;Tedrick Speers&quot;,&quot;value3&quot;:&quot;Janell Highfill&quot;}" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).toHaveAttribute(
            'data-vue-props-value',
            '{"value1":"Derron Macgregor","value2":"Tedrick Speers","value3":"Janell Highfill"}'
        );

        startStimulus();

        await waitFor(() => expect(component).toHaveAttribute('data-v-app'));

        expect(component).toHaveAttribute(
            'data-vue-props-value',
            '{"value1":"Derron Macgregor","value2":"Tedrick Speers","value3":"Janell Highfill"}'
        );

        const field1 = getByTestId(container, 'field-1') as HTMLInputElement;
        const field2 = getByTestId(container, 'field-2') as HTMLInputElement;
        const field3 = getByTestId(container, 'field-3') as HTMLInputElement;

        field1.value = 'Devi Sund';
        field1.dispatchEvent(new Event('input'));

        field2.value = 'Shanai Nance';
        field2.dispatchEvent(new Event('input'));

        field3.value = 'Georgios Baylor';
        field3.dispatchEvent(new Event('input'));

        await waitFor(() =>
            expect(component).toHaveAttribute(
                'data-vue-props-value',
                '{"value1":"Devi Sund","value2":"Shanai Nance","value3":"Georgios Baylor"}'
            )
        );

        clearDOM();
    });

    it('reacts on props changed', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="vue"
              data-vue-component-value="SimpleForm"
              data-vue-props-value="{&quot;value1&quot;:&quot;Marshawn Caley&quot;,&quot;value2&quot;:&quot;Ontario Hopper&quot;,&quot;value3&quot;:&quot;Latria Gibb&quot;}" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).toHaveAttribute(
            'data-vue-props-value',
            '{"value1":"Marshawn Caley","value2":"Ontario Hopper","value3":"Latria Gibb"}'
        );

        startStimulus();

        await waitFor(() => expect(component).toHaveAttribute('data-v-app'));

        expect(component).toHaveAttribute(
            'data-vue-props-value',
            '{"value1":"Marshawn Caley","value2":"Ontario Hopper","value3":"Latria Gibb"}'
        );

        const field1 = getByTestId(container, 'field-1') as HTMLInputElement;
        const field2 = getByTestId(container, 'field-2') as HTMLInputElement;
        const field3 = getByTestId(container, 'field-3') as HTMLInputElement;

        expect(field1).toHaveValue('Marshawn Caley');
        expect(field2).toHaveValue('Ontario Hopper');
        expect(field3).toHaveValue('Latria Gibb');

        component.dataset.vuePropsValue = '{"value1":"Shon Pahl","value2":"Simi Kester","value3":"Shenelle Corso"}';

        await waitFor(() => expect(field1).toHaveValue('Shon Pahl'));
        await waitFor(() => expect(field2).toHaveValue('Simi Kester'));
        await waitFor(() => expect(field3).toHaveValue('Shenelle Corso'));

        clearDOM();
    });

    it('reacts on props adding', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="vue"
              data-vue-component-value="SimpleForm"
              data-vue-props-value="{&quot;value1&quot;:&quot;Marshawn Caley&quot;}" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).toHaveAttribute('data-vue-props-value', '{"value1":"Marshawn Caley"}');

        startStimulus();

        await waitFor(() => expect(component).toHaveAttribute('data-v-app'));

        expect(component).toHaveAttribute('data-vue-props-value', '{"value1":"Marshawn Caley"}');

        const field1 = getByTestId(container, 'field-1') as HTMLInputElement;
        const field2 = getByTestId(container, 'field-2') as HTMLInputElement;
        const field3 = getByTestId(container, 'field-3') as HTMLInputElement;

        expect(field1).toHaveValue('Marshawn Caley');
        expect(field2).toHaveValue('');
        expect(field3).toHaveValue('');

        component.dataset.vuePropsValue = '{"value1":"Marshawn Caley","value2":"Abelino Dollard"}';

        await waitFor(() => expect(field1).toHaveValue('Marshawn Caley'));
        await waitFor(() => expect(field2).toHaveValue('Abelino Dollard'));
        await waitFor(() => expect(field3).toHaveValue(''));

        component.dataset.vuePropsValue =
            '{"value1":"Marshawn Caley","value2":"Abelino Dollard","value3":"Ravan Farr"}';

        await waitFor(() => expect(field1).toHaveValue('Marshawn Caley'));
        await waitFor(() => expect(field2).toHaveValue('Abelino Dollard'));
        await waitFor(() => expect(field3).toHaveValue('Ravan Farr'));
    });

    it('reacts on props removing', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="vue"
              data-vue-component-value="SimpleForm"
              data-vue-props-value="{&quot;value1&quot;:&quot;Trista Elbert&quot;,&quot;value2&quot;:&quot;Mistina Truax&quot;,&quot;value3&quot;:&quot;Chala Paddock&quot;}" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).toHaveAttribute(
            'data-vue-props-value',
            '{"value1":"Trista Elbert","value2":"Mistina Truax","value3":"Chala Paddock"}'
        );

        startStimulus();

        await waitFor(() => expect(component).toHaveAttribute('data-v-app'));

        expect(component).toHaveAttribute(
            'data-vue-props-value',
            '{"value1":"Trista Elbert","value2":"Mistina Truax","value3":"Chala Paddock"}'
        );

        const field1 = getByTestId(container, 'field-1') as HTMLInputElement;
        const field2 = getByTestId(container, 'field-2') as HTMLInputElement;
        const field3 = getByTestId(container, 'field-3') as HTMLInputElement;

        expect(field1).toHaveValue('Trista Elbert');
        expect(field2).toHaveValue('Mistina Truax');
        expect(field3).toHaveValue('Chala Paddock');

        component.dataset.vuePropsValue = '{"value1":"Trista Elbert","value3":"Chala Paddock"}';

        await waitFor(() => expect(field1).toHaveValue('Trista Elbert'));
        await waitFor(() => expect(field2).toHaveValue(''));
        await waitFor(() => expect(field3).toHaveValue('Chala Paddock'));

        component.dataset.vuePropsValue = '{"value3":"Chala Paddock"}';

        await waitFor(() => expect(field1).toHaveValue(''));
        await waitFor(() => expect(field2).toHaveValue(''));
        await waitFor(() => expect(field3).toHaveValue('Chala Paddock'));
    });
});
