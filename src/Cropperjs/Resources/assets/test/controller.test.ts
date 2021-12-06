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
import CropperjsController from '../src/controller';

let cropper: Cropper|null = null;

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('cropperjs:connect', (event: any) => {
            this.element.classList.add('connected');
            cropper = event.detail.cropper;
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('cropperjs', CropperjsController);
};

const dataToJsonAttribute = (data: any) => {
    const container = document.createElement('div');
    container.dataset.foo = JSON.stringify(data);

    // returns the now-escaped string, ready to be used in an HTML attribute
    return container.outerHTML.match(/data-foo="(.+)"/)[1]
}

describe('CropperjsController', () => {
    let container: any;

    beforeEach(() => {
        container = mountDOM(`
            <div id="form_photo" class="cropperjs">
                <input type="hidden" id="form_photo_options" name="form[photo][options]" 
                    data-testid="input"
                    data-controller="check cropperjs"
                    data-cropperjs-public-url-value="https://symfony.com/logos/symfony_black_02.png"
                    data-cropperjs-options-value="${dataToJsonAttribute({
                        viewMode: 1,
                        dragMode: "move"
                    })}"
                >
            </div>
        `);
    });

    afterEach(() => {
        clearDOM();
        cropper = null;
    });

    it('connect', async () => {
        expect(getByTestId(container, 'input')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'input')).toHaveClass('connected'));
        expect(cropper.options.viewMode).toBe(1);
        expect(cropper.options.dragMode).toBe('move');
    });
});
