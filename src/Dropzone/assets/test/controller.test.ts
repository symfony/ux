/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import user from '@testing-library/user-event';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import DropzoneController from '../src/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('dropzone:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('dropzone', DropzoneController);
};

describe('DropzoneController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
            <div class="dropzone-container" data-controller="check dropzone" data-testid="container"> 
                <input type="file"
                       style="display: none"
                       data-dropzone-target="input"
                       data-testid="input" />
        
                <div class="dropzone-placeholder" 
                     data-dropzone-target="placeholder" 
                     data-testid="placeholder">
                    Placeholder
                </div>
        
                <div class="dropzone-preview"
                     data-dropzone-target="preview"
                     data-testid="preview"
                     style="display: none">
                     
                    <button type="button"
                            class="dropzone-preview-button"
                            data-dropzone-target="previewClearButton"
                            data-testid="button"></button>
        
                    <div class="dropzone-preview-image"
                         data-dropzone-target="previewImage"
                         data-testid="preview-image"
                         style="display: none"></div>
        
                    <div class="dropzone-preview-filename"
                         data-dropzone-target="previewFilename" 
                         data-testid="preview-filename"></div>
                </div>
            </div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        expect(getByTestId(container, 'container')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
    });

    it('clear', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'input')).toHaveStyle({ display: 'block' }));

        // Attach a listener to ensure the event is dispatched
        let dispatched = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            dispatched = true;
        });

        // Manually show preview
        getByTestId(container, 'input').style.display = 'none';
        getByTestId(container, 'placeholder').style.display = 'none';
        getByTestId(container, 'preview').style.display = 'block';

        // Click the clear button
        getByTestId(container, 'button').click();

        await waitFor(() => expect(getByTestId(container, 'input')).toHaveStyle({ display: 'block' }));
        await waitFor(() => expect(getByTestId(container, 'placeholder')).toHaveStyle({ display: 'block' }));
        await waitFor(() => expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' }));

        // The event should have been dispatched
        expect(dispatched).toBe(true);
    });

    it('file chosen', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'input')).toHaveStyle({ display: 'block' }));

        // Attach a listener to ensure the event is dispatched
        let dispatched = null;
        getByTestId(container, 'container').addEventListener('dropzone:change', (event) => {
            dispatched = event;
        });

        // Select the file
        const input = getByTestId(container, 'input');
        const file = new File(['hello'], 'hello.png', { type: 'image/png' });

        user.upload(input, file);
        expect(input.files[0]).toStrictEqual(file);

        // The dropzone should be in preview mode
        await waitFor(() => expect(getByTestId(container, 'input')).toHaveStyle({ display: 'none' }));
        await waitFor(() => expect(getByTestId(container, 'placeholder')).toHaveStyle({ display: 'none' }));

        // The event should have been dispatched
        expect(dispatched).not.toBeNull();
        expect(dispatched.detail).toStrictEqual(file);
    });

    it('on drag', async () => {
        startStimulus();

        // Simulate dragenter event
        const dragEnterEvent = new Event('dragenter');
        getByTestId(container, 'container').dispatchEvent(dragEnterEvent);

        // Check that the input and placeholder are visible, and preview hidden
        await waitFor(() => expect(getByTestId(container, 'input')).toHaveStyle({ display: 'block' }));
        await waitFor(() => expect(getByTestId(container, 'placeholder')).toHaveStyle({ display: 'block' }));
        await waitFor(() => expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' }));

        // Simulate dragleave event with relatedTarget set to outside the dropzone
        const dragLeaveEvent = new Event('dragleave', { bubbles: true });
        Object.defineProperty(dragLeaveEvent, 'relatedTarget', { value: document.body });
        getByTestId(container, 'container').dispatchEvent(dragLeaveEvent);

        // Check that the input and placeholder are hidden, and preview shown
        await waitFor(() => expect(getByTestId(container, 'input')).toHaveStyle({ display: 'none' }));
        await waitFor(() => expect(getByTestId(container, 'placeholder')).toHaveStyle({ display: 'none' }));
        await waitFor(() => expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'block' }));
    });
});
