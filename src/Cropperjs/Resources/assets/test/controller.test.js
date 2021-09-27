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
import CropperjsController from '../dist/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('cropperjs:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('cropperjs', CropperjsController);
};

describe('CropperjsController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
            <div id="form_photo" class="cropperjs">
                <input type="hidden" id="form_photo_options" name="form[photo][options]" 
                    data-testid="input"
                    data-controller="check cropperjs"
                    data-public-url="https://symfony.com/logos/symfony_black_02.png" 
                    data-view-mode="1"
                    data-drag-mode="move"
                    data-aspect-ratio="1"
                    data-initial-aspect-ratio="2" 
                    data-responsive="data-responsive" 
                    data-restore="data-restore"
                    data-check-cross-origin="data-check-cross-origin" 
                    data-check-orientation="data-check-orientation"
                    data-modal="data-modal" 
                    data-guides="data-guides"
                    data-center="data-center"
                    data-highlight="data-highlight" 
                    data-background="data-background" 
                    data-auto-crop="data-auto-crop"
                    data-auto-crop-area="0.1" 
                    data-movable="data-movable"
                    data-rotatable="data-rotatable" 
                    data-scalable="data-scalable"
                    data-zoomable="data-zoomable" 
                    data-zoom-on-touch="data-zoom-on-touch" 
                    data-zoom-on-wheel="data-zoom-on-wheel" 
                    data-wheel-zoom-ratio="0.2" 
                    data-crop-box-movable="data-crop-box-movable"
                    data-crop-box-resizable="data-crop-box-resizable"
                    data-toggle-drag-mode-on-dblclick="data-toggle-drag-mode-on-dblclick"
                    data-min-container-width="1"
                    data-min-container-height="2" 
                    data-min-canvas-width="3" 
                    data-min-canvas-height="4"
                    data-min-crop-box-width="5" 
                    data-min-crop-box-height="6" />
            </div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        expect(getByTestId(container, 'input')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'input')).toHaveClass('connected'));
    });
});
