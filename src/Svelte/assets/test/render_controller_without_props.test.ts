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

describe('SvelteController without props', () => {

    it('connect without props', async () => {

        const container = mountDOM(`
          <div data-testid="component" id="container"
              data-controller="check svelte"
              data-svelte-component-value="SvelteComponent" />
        `);

        const component = getByTestId(container, 'component');
        expect(component).not.toHaveClass('connected');
        expect(component).not.toHaveClass('mounted');

        await new Promise(resolve => setTimeout(resolve, 1000));
        startStimulus();
        await waitFor(() => expect(component).toHaveClass('connected'));
        await waitFor(() => expect(component).toHaveClass('mounted'));
        await waitFor(() => expect(component.innerHTML).toEqual('<div><div>Hello without props</div></div>'));

        clearDOM();
    });
});
