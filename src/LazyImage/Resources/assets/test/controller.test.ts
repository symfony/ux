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
import LazyImageController from '../src/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('lazy-image:connect', (event) => {
            // the Image won't natively have its "load" method in this test
            // so we trigger it manually, to "fake" the Image loading.
            event.detail.hd.dispatchEvent(new Event('load'));
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('lazy-image', LazyImageController);
};

describe('LazyImageController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
          <img 
              src="https://symfony.com/logos/symfony_black_02.png"
              srcset="https://symfony.com/logos/symfony_black_02.png 1x, https://symfony.com/logos/symfony_black_02.png 2x"
              data-testid="img"
              data-lazy-image-src-value="https://symfony.com/logos/symfony_black_03.png"
              data-lazy-image-srcset-value="{&quot;1x&quot;: &quot;https://symfony.com/logos/symfony_black_03.png&quot;, &quot;2x&quot;: &quot;https://symfony.com/logos/symfony_black_03_2x.png&quot;}"
              data-controller="check lazy-image" />
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        const img = getByTestId(container, 'img');
        expect(img).not.toHaveClass('connected');
        expect(img).toHaveAttribute('src', 'https://symfony.com/logos/symfony_black_02.png');

        startStimulus();
        await waitFor(() => expect(img).toHaveClass('connected'));
        expect(img).toHaveAttribute('src', 'https://symfony.com/logos/symfony_black_03.png');
        expect(img).toHaveAttribute('srcset', 'https://symfony.com/logos/symfony_black_03.png 1x, https://symfony.com/logos/symfony_black_03_2x.png 2x');
    });
});
