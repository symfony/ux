/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import { startStimulus } from './util/render_controller';

describe('SvelteController with intro', () => {

    it('connect with props and intro', async () => {
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

        startStimulus();
        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        expect(component.innerHTML).toContain('style="animation:');
        await waitFor(() => expect(component.innerHTML.trim()).toEqual('<div style=""><div>Hello Symfony with transition</div></div>'));

        clearDOM();
    });
});
