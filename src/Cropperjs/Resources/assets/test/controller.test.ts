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

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('cropperjs:connect', (event: any) => {
            this.element.classList.add('connected');

            expect(event.detail.cropper.options).toStrictEqual({
                viewMode: 1,
                dragMode: 'move',
                initialAspectRatio: 2,
                aspectRatio: 1,
                data: null,
                preview: '',
                responsive: true,
                restore: true,
                checkCrossOrigin: true,
                checkOrientation: true,
                modal: true,
                guides: true,
                center: true,
                highlight: true,
                background: true,
                autoCrop: true,
                autoCropArea: 0.1,
                movable: true,
                rotatable: true,
                scalable: true,
                zoomable: true,
                zoomOnTouch: true,
                zoomOnWheel: true,
                wheelZoomRatio: 0.2,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: true,
                minCanvasWidth: 3,
                minCanvasHeight: 4,
                minCropBoxWidth: 5,
                minCropBoxHeight: 6,
                minContainerWidth: 1,
                minContainerHeight: 2,
                ready: null,
                cropstart: null,
                cropmove: null,
                cropend: null,
                crop: null,
                zoom: null,
                publicUrl: 'https://symfony.com/logos/symfony_black_02.png'
            });
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('cropperjs', CropperjsController);
};

describe('CropperjsController', () => {
    let container: any;

    beforeEach(() => {
        container = mountDOM(`
            <div id="form_photo" class="cropperjs">
                <input type="hidden" id="form_photo_options" name="form[photo][options]" 
                    data-testid="input"
                    data-controller="check cropperjs"
                    data-cropperjs-public-url-value="https://symfony.com/logos/symfony_black_02.png" 
                    data-cropperjs-view-mode-value="1"
                    data-cropperjs-drag-mode-value="move"
                    data-cropperjs-aspect-ratio-value="1"
                    data-cropperjs-initial-aspect-ratio-value="2" 
                    data-cropperjs-responsive-value="data-responsive" 
                    data-cropperjs-restore-value="data-restore"
                    data-cropperjs-check-cross-origin-value="data-check-cross-origin" 
                    data-cropperjs-check-orientation-value="data-check-orientation"
                    data-cropperjs-modal-value="data-modal" 
                    data-cropperjs-guides-value="data-guides"
                    data-cropperjs-center-value="data-center"
                    data-cropperjs-highlight-value="data-highlight" 
                    data-cropperjs-background-value="data-background" 
                    data-cropperjs-auto-crop-value="data-auto-crop"
                    data-cropperjs-auto-crop-area-value="0.1" 
                    data-cropperjs-movable-value="data-movable"
                    data-cropperjs-rotatable-value="data-rotatable" 
                    data-cropperjs-scalable-value="data-scalable"
                    data-cropperjs-zoomable-value="data-zoomable" 
                    data-cropperjs-zoom-on-touch-value="data-zoom-on-touch" 
                    data-cropperjs-zoom-on-wheel-value="data-zoom-on-wheel" 
                    data-cropperjs-wheel-zoom-ratio-value="0.2" 
                    data-cropperjs-crop-box-movable-value="data-crop-box-movable"
                    data-cropperjs-crop-box-resizable-value="data-crop-box-resizable"
                    data-cropperjs-toggle-drag-mode-on-dblclick-value="data-toggle-drag-mode-on-dblclick"
                    data-cropperjs-min-container-width-value="1"
                    data-cropperjs-min-container-height-value="2" 
                    data-cropperjs-min-canvas-width-value="3" 
                    data-cropperjs-min-canvas-height-value="4"
                    data-cropperjs-min-crop-box-width-value="5" 
                    data-cropperjs-min-crop-box-height-value="6" />
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
