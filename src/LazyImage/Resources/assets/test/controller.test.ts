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
        this.element.addEventListener('lazy-image:connect', () => {
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
              data-hd-src="https://symfony.com/logos/symfony_black_03.png" 
              data-hd-srcset="https://symfony.com/logos/symfony_black_03.png 1x, https://symfony.com/logos/symfony_black_03.png 2x"
              data-controller="check lazy-image" />
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        expect(getByTestId(container, 'img')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'img')).toHaveClass('connected'));
    });
});
