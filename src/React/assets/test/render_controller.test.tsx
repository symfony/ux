/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import React from 'react';
import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import ReactController from '../src/render_controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('react:connect', () => {
            this.element.classList.add('connected');
        });

        this.element.addEventListener('react:mount', () => {
            this.element.classList.add('mounted');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('react', ReactController);
};

function ReactComponent(props: { fullName?: string }) {
    return <div>Hello {props.fullName ? props.fullName : 'without props'}</div>;
}

(window as any).resolveReactComponent = () => {
    return ReactComponent;
};

describe('ReactController', () => {
    it('connect with props', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="check react"
              data-react-component-value="ReactComponent"
              data-react-props-value="{&quot;fullName&quot;: &quot;Titouan Galopin&quot;}" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        startStimulus();
        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        await waitFor(() => expect(component.innerHTML).toEqual('<div>Hello Titouan Galopin</div>'));

        clearDOM();
    });

    it('connect without props', async () => {
        const container = mountDOM(`
          <div data-testid="component" id="container-2"
              data-controller="check react"
              data-react-component-value="ReactComponent" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        startStimulus();
        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        await waitFor(() => expect(component.innerHTML).toEqual('<div>Hello without props</div>'));

        clearDOM();
    });
});
