/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from 'stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import FormCollectionController from '../dist/controller';

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
};

describe('FormCollectionController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
            <div class="container" data-controller="check formCollection" data-testid="container"> 
                
            </div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('events', async () => {
        expect(getByTestId(container, 'container')).not.toHaveClass('connected');
        expect(getByTestId(container, 'container')).not.toHaveClass('pre-connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('pre-connected'));
    });

});
