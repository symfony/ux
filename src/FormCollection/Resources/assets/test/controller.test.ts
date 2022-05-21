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
import FormCollectionController from '../src/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('form-collection:pre-connect', () => {
            this.element.classList.add('pre-connected');
        });
        this.element.addEventListener('form-collection:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('formCollection', FormCollectionController);

    return application;
};

describe('FormCollectionController', () => {
    let application;

    afterEach(() => {
        clearDOM();
        application.stop();
    });

    it('events', async () => {
        const container = mountDOM(`
            <div
              data-testid="container"
              data-controller="check formCollection"
              class="container">
            </div>
        `);

        expect(getByTestId(container, 'container')).not.toHaveClass('pre-connected');
        expect(getByTestId(container, 'container')).not.toHaveClass('connected');

        application = startStimulus();

        await waitFor(() => {
            expect(getByTestId(container, 'container')).toHaveClass('pre-connected')
            expect(getByTestId(container, 'container')).toHaveClass('connected')
        });
    });
});
