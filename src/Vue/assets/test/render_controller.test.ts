/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import VueController from '../src/render_controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('vue:connect', () => {
            this.element.classList.add('connected');
        });

        this.element.addEventListener('vue:mount', () => {
            this.element.classList.add('mounted');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('vue', VueController);
};

const Hello = {
    template: '<h1>Hello {{ name ?? \'world\' }}</h1>',
    props: ['name']
};

window.resolveVueComponent = () => {
    return Hello;
};

describe('VueController', () => {
    it('connect with props', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="check vue"
              data-vue-component-value="Hello"
              data-vue-props-value="{&quot;name&quot;: &quot;Thibault Richard&quot;}" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        startStimulus();
        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        await waitFor(() => expect(component.innerHTML).toEqual('<h1>Hello Thibault Richard</h1>'));

        clearDOM();
    });

    it('connect without props', async () => {
        const container = mountDOM(`
          <div data-testid="component" id="container-2"
              data-controller="check vue"
              data-vue-component-value="Hello" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        startStimulus();
        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        await waitFor(() => expect(component.innerHTML).toEqual('<h1>Hello world</h1>'));

        clearDOM();
    });
});
