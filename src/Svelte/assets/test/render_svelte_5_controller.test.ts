/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import SvelteController from '../src/render_controller';
import MyComponentSvelte5 from './fixtures/MyComponentSvelte5.svelte';
import { VERSION as SVELTE_VERSION } from 'svelte/compiler';

const startStimulus = () => {
    const application = Application.start();
    application.register('svelte', SvelteController);

    return application;
};

(window as any).resolveSvelteComponent = () => {
    return MyComponentSvelte5;
};

describe.skipIf(SVELTE_VERSION < '5')('Svelte5Controller', () => {
    let application: Application;

    afterEach(() => {
        clearDOM();
        application.stop();
    });

    it('connect with props', async () => {
        const container = mountDOM(`
          <div data-testid="component"
              data-controller="check svelte 5"
              data-svelte-component-value="Svelte5Component"
              data-svelte-props-value="{&quot;name&quot;: &quot;Svelte 5 !&quot;}" />
        `);

        const component = getByTestId(container, 'component');

        application = startStimulus();

        await waitFor(() => expect(component.innerHTML).toContain('<div><div>Hello Svelte 5 !</div></div>'));
    });

    it('connect without props', async () => {
        const container = mountDOM(`
          <div data-testid="component" id="container"
              data-controller="check svelte 5"
              data-svelte-component-value="Svelte5Component" />
        `);

        const component = getByTestId(container, 'component');

        application = startStimulus();

        await waitFor(() => expect(component.innerHTML).toContain('<div><div>Hello without props</div></div>'));
    });
});
