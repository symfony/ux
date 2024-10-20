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
import SvelteController from '../src/render_controller';
import MyComponent from './fixtures/MyComponent.svelte';
import { VERSION as SVELTE_VERSION } from 'svelte/compiler';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('svelte:connect', () => {
            this.element.classList.add('connected');
        });

        this.element.addEventListener('svelte:mount', () => {
            this.element.classList.add('mounted');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('svelte', SvelteController);

    return application;
};

(window as any).resolveSvelteComponent = () => {
    return MyComponent;
};

describe('SvelteController', () => {
    let application: Application;

    afterEach(() => {
        clearDOM();
        application.stop();
    });

    it('connect with props', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="check svelte"
              data-svelte-component-value="SvelteComponent"
              data-svelte-props-value="{&quot;name&quot;: &quot;Symfony&quot;}" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        application = startStimulus();

        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        await waitFor(() => expect(component.innerHTML).toContain('<div><div>Hello Symfony</div></div>'));
    });

    it('connect without props', async () => {
        const container = mountDOM(`
          <div data-testid="component" id="container"
              data-controller="check svelte"
              data-svelte-component-value="SvelteComponent" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        application = startStimulus();

        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        await waitFor(() => expect(component.innerHTML).toContain('<div><div>Hello without props</div></div>'));
    });

    // Disabled for Svelte 5 : https://github.com/sveltejs/svelte/issues/11280
    it.skipIf(SVELTE_VERSION >= '5')('connect with props and intro', async () => {
        const container = mountDOM(`
          <div data-testid="component" id="container"
              data-controller="check svelte"
              data-svelte-component-value="SvelteComponent"
              data-svelte-props-value="{&quot;name&quot;: &quot;Symfony with transition&quot;}"
              data-svelte-intro-value="true" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        application = startStimulus();

        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        expect(component.innerHTML).toContain('style="animation:');
        await waitFor(() => expect(component.innerHTML.trim()).toContain('<div>Hello Symfony with transition</div>'));
    });
});
