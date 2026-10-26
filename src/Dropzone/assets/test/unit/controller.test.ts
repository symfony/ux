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
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { clearDOM, mountDOM } from '../../../../../test/stimulus-helpers';
import DropzoneController from '../../src/controller';

// jsdom does not implement DataTransfer so we make a stub
class DataTransferStub {
    private _files: File[] = [];
    readonly items = {
        add: (file: File) => void this._files.push(file),
        remove: (index: number) => void this._files.splice(index, 1),
    };
    get files(): File[] {
        return this._files;
    }
}
if (typeof globalThis.DataTransfer === 'undefined') {
    (globalThis as any).DataTransfer = DataTransferStub;
}

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('dropzone:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const applications: Application[] = [];

const startApplication = () => {
    const application = Application.start();
    applications.push(application);

    return application;
};

afterEach(() => {
    for (const application of applications.splice(0)) {
        application.stop();
    }
});

const startStimulus = () => {
    const application = startApplication();
    application.register('check', CheckController);
    application.register('dropzone', DropzoneController);
};

// Simulate a native file pick. A real <input> replaces its FileList on every pick.
// jsdom can't build a populated FileList, and user-event makes `files` read-only,
// so we install a writable `files` (which the controller writes back to as well) and dispatch the change event ourselves.
const selectFiles = (input: HTMLInputElement, files: File[]) => {
    Object.defineProperty(input, 'files', { configurable: true, writable: true, value: files });
    input.dispatchEvent(new Event('change'));
};

const startDropzone = (element: HTMLElement) =>
    new Promise<void>((resolve) => {
        element.addEventListener('dropzone:connect', () => resolve(), { once: true });
        startApplication().register('dropzone', DropzoneController);
    });

// Browsers list "Files" in the types of a drag that carries files
const fileDrag = (type: string, types: string[] = ['Files']) => {
    const event = new Event(type, { bubbles: true });
    Object.defineProperty(event, 'dataTransfer', { value: { types } });

    return event;
};

// A drop puts the files into the input and fires "change" right after the drop event
const dropFiles = (input: HTMLInputElement, files: File[]) => {
    input.dispatchEvent(new Event('drop', { bubbles: true }));
    Object.defineProperty(input, 'files', { configurable: true, writable: true, value: files });
    input.dispatchEvent(new Event('change'));
};

const pickFiles = (input: HTMLInputElement, files: File[]) => {
    Object.defineProperty(input, 'files', { configurable: true, writable: true, value: files });
    input.dispatchEvent(new Event('change'));
};

describe('DropzoneController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
            <div class="dropzone-container" data-controller="check dropzone" data-testid="container">
                <input type="file"
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
                     hidden>

                    <button type="button"
                            class="dropzone-preview-button"
                            data-dropzone-target="previewClearButton"
                            data-testid="button"></button>

                    <div class="dropzone-preview-image"
                         data-dropzone-target="previewImage"
                         data-testid="preview-image"
                         hidden></div>

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

    const start = async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
    };

    const state = () => getByTestId(container, 'container').getAttribute('data-state');

    const expectEmpty = () => {
        expect(state()).toBe('empty');
        expect(getByTestId(container, 'input')).not.toHaveAttribute('hidden');
        expect(getByTestId(container, 'placeholder')).not.toHaveAttribute('hidden');
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');
    };

    const expectFilled = () => {
        expect(state()).toBe('filled');
        expect(getByTestId(container, 'input')).toHaveAttribute('hidden');
        expect(getByTestId(container, 'placeholder')).toHaveAttribute('hidden');
        expect(getByTestId(container, 'preview')).not.toHaveAttribute('hidden');
    };

    it('connect', async () => {
        expect(getByTestId(container, 'container')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
    });

    it('ignores a drag without files', async () => {
        await start();

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter', ['text/plain']));

        expectEmpty();
    });

    it('keeps the file already in the input when it connects', async () => {
        const file = new File(['hello'], 'hello.txt');
        Object.defineProperty(getByTestId(container, 'input'), 'files', {
            configurable: true,
            writable: true,
            value: [file],
        });
        let cleared = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            cleared = true;
        });

        await start();

        expectFilled();
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('hello.txt');
        expect(cleared).toBe(false);
    });

    it('keeps the previous file when a pre-add listener prevents a selection', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const photo = new File(['png'], 'photo.png', { type: 'image/png' });
        pickFiles(input, [photo]);
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event) => event.preventDefault());

        pickFiles(input, [new File(['b'], 'b.txt')]);

        expectFilled();
        expect(input.files?.[0]).toBe(photo);
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('photo.png');
    });

    it('keeps the previous file in the input while a pre-add listener is pending', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const photo = new File(['png'], 'photo.png', { type: 'image/png' });
        pickFiles(input, [photo]);
        let resolve!: (files: File[]) => void;
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(new Promise<File[]>((r) => (resolve = r)));
        });

        const b = new File(['b'], 'b.txt');
        pickFiles(input, [b]);

        expect(input.files?.[0]).toBe(photo);
        resolve([b]);
        await waitFor(() => expect(getByTestId(container, 'preview-filename')).toHaveTextContent('b.txt'));
        expect(input.files?.[0]).toBe(b);
    });

    it('rejects the files over the first one', async () => {
        await start();
        const rejections: any[] = [];
        getByTestId(container, 'container').addEventListener('dropzone:reject', (event: any) =>
            rejections.push(event.detail)
        );

        const a = new File(['a'], 'a.txt');
        const b = new File(['b'], 'b.txt');
        pickFiles(getByTestId(container, 'input') as HTMLInputElement, [a, b]);

        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('a.txt');
        expect(rejections).toStrictEqual([{ files: [b], reason: 'max_files' }]);
    });

    it('does not show the image of a file replaced before it is loaded', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;

        pickFiles(input, [new File(['png'], 'a.png', { type: 'image/png' })]);
        pickFiles(input, [new File(['text'], 'b.txt', { type: 'text/plain' })]);
        await new Promise((resolve) => setTimeout(resolve, 50));

        expect(getByTestId(container, 'preview-image')).toHaveAttribute('hidden');
    });

    it('clears when the form is reset', async () => {
        const form = document.createElement('form');
        getByTestId(container, 'container').before(form);
        form.append(getByTestId(container, 'container'));
        await start();
        let cleared = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            cleared = true;
        });
        pickFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['hello'], 'hello.txt')]);

        form.reset();
        Object.defineProperty(getByTestId(container, 'input'), 'files', {
            configurable: true,
            writable: true,
            value: [],
        });

        await waitFor(() => expectEmpty());
        expect(cleared).toBe(true);
    });

    it('does not clear when the form reset is prevented', async () => {
        const form = document.createElement('form');
        getByTestId(container, 'container').before(form);
        form.append(getByTestId(container, 'container'));
        await start();
        let cleared = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            cleared = true;
        });
        form.addEventListener('reset', (event) => event.preventDefault());
        pickFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['hello'], 'hello.txt')]);

        form.reset();
        await new Promise((resolve) => setTimeout(resolve));

        expectFilled();
        expect(cleared).toBe(false);
    });

    it('does not clear after it disconnects during a form reset', async () => {
        const form = document.createElement('form');
        getByTestId(container, 'container').before(form);
        form.append(getByTestId(container, 'container'));
        await start();
        let cleared = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            cleared = true;
        });
        pickFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['hello'], 'hello.txt')]);

        form.reset();
        getByTestId(container, 'container').remove();
        await new Promise((resolve) => setTimeout(resolve));

        expect(cleared).toBe(false);
    });

    it('clears with the clear button when it failed to connect', async () => {
        const template = document.createElement('template');
        template.setAttribute('data-dropzone-target', 'previewItemTemplate');
        template.innerHTML = '<p></p><p></p>';
        getByTestId(container, 'container').append(template);
        Object.defineProperty(getByTestId(container, 'input'), 'files', {
            configurable: true,
            writable: true,
            value: [],
        });
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        const errors: unknown[] = [];
        const onError = (event: ErrorEvent) => errors.push(event.error);
        window.addEventListener('error', onError);

        startApplication().register('dropzone', DropzoneController);
        await waitFor(() => expect(consoleError).toHaveBeenCalled());
        getByTestId(container, 'button').click();

        window.removeEventListener('error', onError);
        consoleError.mockRestore();
        expect(errors).toStrictEqual([]);
    });

    it('renders the preview from the template', async () => {
        const template = document.createElement('template');
        template.setAttribute('data-dropzone-target', 'previewItemTemplate');
        template.innerHTML =
            '<p class="item"><span data-dropzone-slot="filename"></span><button type="button" data-dropzone-slot="remove"></button></p>';
        getByTestId(container, 'container').append(template);
        await start();
        let cleared = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            cleared = true;
        });

        pickFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['hello'], 'hello.txt')]);

        expectFilled();
        expect(getByTestId(container, 'preview').querySelector('.item span')?.textContent).toBe('hello.txt');

        (getByTestId(container, 'preview').querySelector('.item button') as HTMLButtonElement).click();

        expectEmpty();
        expect(cleared).toBe(true);
    });

    it('is empty on connect', async () => {
        await start();

        expectEmpty();
    });

    it('clear', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['hello'], 'hello.txt')]);
        expectFilled();

        // Attach a listener to ensure the event is dispatched
        let dispatched = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            dispatched = true;
        });

        // Click the clear button
        getByTestId(container, 'button').click();

        expectEmpty();
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('');

        // The event should have been dispatched
        expect(dispatched).toBe(true);
    });

    it('file chosen', async () => {
        await start();

        // Attach a listener to ensure the event is dispatched
        let dispatched = null;
        getByTestId(container, 'container').addEventListener('dropzone:change', (event) => {
            dispatched = event;
        });

        // Select the file
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const file = new File(['hello'], 'hello.png', { type: 'image/png' });

        pickFiles(input, [file]);
        expect(input.files?.[0]).toStrictEqual(file);

        expectFilled();
        expect(getByTestId(container, 'input')).not.toHaveAttribute('style');
        expect(getByTestId(container, 'placeholder')).not.toHaveAttribute('style');
        expect(getByTestId(container, 'preview')).not.toHaveAttribute('style');

        // The event should have been dispatched
        expect(dispatched).not.toBeNull();
        expect(dispatched.detail).toStrictEqual(file);
    });

    it('shows the image preview once it is loaded', async () => {
        await start();

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [
            new File(['png'], 'a.png', { type: 'image/png' }),
        ]);

        const image = getByTestId(container, 'preview-image');
        expect(image).toHaveAttribute('hidden');
        await waitFor(() => expect(image).not.toHaveAttribute('hidden'));
        expect(image.style.display).toBe('');
        expect(image.style.backgroundImage).toBe('url("data:image/png;base64,cG5n")');
    });

    it('on drag', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['hello'], 'hello.txt')]);

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));

        expect(state()).toBe('dragging');
        expect(getByTestId(container, 'input')).not.toHaveAttribute('hidden');
        expect(getByTestId(container, 'placeholder')).not.toHaveAttribute('hidden');
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');

        getByTestId(container, 'placeholder').dispatchEvent(fileDrag('dragenter'));
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragleave'));
        expect(state()).toBe('dragging');

        getByTestId(container, 'placeholder').dispatchEvent(fileDrag('dragleave'));
        expectFilled();
    });

    it('stays empty when a drag leaves an empty dropzone', async () => {
        await start();

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragleave'));

        expectEmpty();
    });

    it('restores the preview when the same file is dropped again', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['content'], 'a.txt')]);

        // Dragging over reveals the input and hides the preview
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');

        // Chrome does not fire "change" when the very same file is picked again
        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));

        await waitFor(() => expectFilled());
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('a.txt');
    });

    it('keeps the input shown while the drop is dispatched', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['content'], 'a.txt')]);
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));

        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));

        expect(getByTestId(container, 'input')).not.toHaveAttribute('hidden');
        await waitFor(() => expectFilled());
    });

    it('ends the drag when it reconnects', async () => {
        await start();
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        expect(state()).toBe('dragging');

        const reconnected = new Promise((resolve) =>
            getByTestId(container, 'container').addEventListener('dropzone:connect', resolve, { once: true })
        );
        const element = getByTestId(container, 'container');
        element.remove();
        await new Promise((resolve) => setTimeout(resolve));
        container.append(element);
        await reconnected;

        expectEmpty();
    });

    it('supports themes hiding the preview with an inline style', async () => {
        getByTestId(container, 'preview').removeAttribute('hidden');
        getByTestId(container, 'preview').style.display = 'none';
        getByTestId(container, 'preview-image').removeAttribute('hidden');
        getByTestId(container, 'preview-image').style.display = 'none';
        await start();

        expectEmpty();
        expect(getByTestId(container, 'preview-image')).toHaveAttribute('hidden');

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [
            new File(['png'], 'a.png', { type: 'image/png' }),
        ]);

        expectFilled();
        expect(getByTestId(container, 'preview').style.display).toBe('');
        await waitFor(() => expect(getByTestId(container, 'preview-image')).not.toHaveAttribute('hidden'));
        expect(getByTestId(container, 'preview-image').style.display).toBe('');
    });

    it('adds the files a pre-add listener resolves', async () => {
        await start();

        const original = new File(['hello'], 'hello.png', { type: 'image/png' });
        const transformed = new File(['resized'], 'hello.jpg', { type: 'image/jpeg' });
        let preAddFiles: File[] = [];
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            preAddFiles = event.detail.files;
            event.detail.waitUntil(Promise.resolve([transformed]));
        });
        let changed: File | null = null;
        getByTestId(container, 'container').addEventListener('dropzone:change', (event: any) => {
            changed = event.detail;
        });

        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [original]);

        expect(state()).toBe('processing');
        await waitFor(() => expect(getByTestId(container, 'preview-filename')).toHaveTextContent('hello.jpg'));
        expect(preAddFiles).toStrictEqual([original]);
        expect(changed).toBe(transformed);
        expect(input.files?.[0]).toBe(transformed);
        expectFilled();
    });

    it('adds nothing when a pre-add listener prevents it', async () => {
        await start();

        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event) => event.preventDefault());
        let changed = false;
        getByTestId(container, 'container').addEventListener('dropzone:change', () => {
            changed = true;
        });

        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [new File(['hello'], 'hello.png', { type: 'image/png' })]);

        expect(changed).toBe(false);
        expect(input.value).toBe('');
        expectEmpty();
    });

    const deferred = () => {
        let resolve!: (files: File[]) => void;
        const promise = new Promise<File[]>((r) => (resolve = r));

        return { promise, resolve };
    };

    it('submits the file it shows when selections resolve out of order', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        const a = new File(['a'], 'a.png', { type: 'image/png' });
        const resizedA = new File(['resized'], 'a.jpg', { type: 'image/jpeg' });
        const b = new File(['b'], 'b.pdf', { type: 'application/pdf' });
        const pendingA = deferred();
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(event.detail.files[0] === a ? pendingA.promise : Promise.resolve([b]));
        });

        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [a]);
        selectFiles(input, [b]);
        pendingA.resolve([resizedA]);

        await waitFor(() => expect(getByTestId(container, 'preview-filename')).toHaveTextContent('b.pdf'));
        expect(input.files?.[0]).toBe(b);
    });

    it('applies a prevented selection after the pending ones', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        const a = new File(['a'], 'a.png', { type: 'image/png' });
        const pendingA = deferred();
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            if (event.detail.files[0] === a) {
                event.detail.waitUntil(pendingA.promise);
            } else {
                event.preventDefault();
            }
        });

        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [a]);
        selectFiles(input, [new File(['b'], 'b.exe')]);
        pendingA.resolve([a]);

        await waitFor(() => expect(state()).toBe('filled'));
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('a.png');
        expect((getByTestId(container, 'input') as HTMLInputElement).files?.[0]).toBe(a);
    });

    it('drops the pending selections when the dropzone is cleared', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        const pending = deferred();
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(pending.promise);
        });

        const a = new File(['a'], 'a.png', { type: 'image/png' });
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [a]);
        getByTestId(container, 'button').click();
        pending.resolve([a]);

        await waitFor(() => expect(state()).toBe('empty'));
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('');
        expectEmpty();
    });

    it('replaces a processing state left in the markup', async () => {
        getByTestId(container, 'container').setAttribute('data-state', 'processing');

        await start();

        expectEmpty();
    });

    it('keeps the preview hidden after a drop while the dropped file is processed', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        const pending = deferred();
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(pending.promise);
        });

        const a = new File(['a'], 'a.png', { type: 'image/png' });
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [a]);
        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(state()).toBe('processing');
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');

        pending.resolve([a]);
        await waitFor(() => expectFilled());
    });

    it.each(['button', 'preview-filename', 'preview-image'])('works without the %s target', async (testId) => {
        getByTestId(container, testId).remove();
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        let changed: File | null = null;
        getByTestId(container, 'container').addEventListener('dropzone:change', (event: any) => {
            changed = event.detail;
        });

        const input = getByTestId(container, 'input') as HTMLInputElement;
        const file = new File(['png'], 'a.png', { type: 'image/png' });
        Object.defineProperty(input, 'files', { configurable: true, writable: true, value: [file] });
        input.dispatchEvent(new Event('change'));

        expect(changed).toBe(file);
        expect(getByTestId(container, 'preview')).not.toHaveAttribute('hidden');
        expect(getByTestId(container, 'placeholder')).toHaveAttribute('hidden');
    });

    const pick = (file: File) => {
        const input = getByTestId(container, 'input') as HTMLInputElement;
        Object.defineProperty(input, 'files', { configurable: true, writable: true, value: [file] });
        input.dispatchEvent(new Event('change'));
    };

    it('shows the file name without the image preview target', async () => {
        getByTestId(container, 'preview-image').remove();
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        pick(new File(['png'], 'a.png', { type: 'image/png' }));

        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('a.png');
    });

    it('shows the image preview without the file name target', async () => {
        getByTestId(container, 'preview-filename').remove();
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        pick(new File(['png'], 'a.png', { type: 'image/png' }));

        await waitFor(() =>
            expect(getByTestId(container, 'preview-image').style.backgroundImage).toBe(
                'url("data:image/png;base64,cG5n")'
            )
        );
    });

    it('disconnects without the clear button target', async () => {
        getByTestId(container, 'button').remove();
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

        for (const application of applications.splice(0)) {
            application.stop();
        }

        expect(consoleError).not.toHaveBeenCalled();
        consoleError.mockRestore();
    });

    it('clears with a clear button added after connect', async () => {
        getByTestId(container, 'button').remove();
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
        pick(new File(['hello'], 'hello.txt'));

        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('data-dropzone-target', 'previewClearButton');
        getByTestId(container, 'preview').append(button);
        await new Promise((resolve) => setTimeout(resolve));
        let cleared = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            cleared = true;
        });

        button.click();

        expect(cleared).toBe(true);
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');
    });

    const events = (...names: string[]) => {
        const log: Array<{ name: string; detail: any }> = [];
        for (const name of names) {
            getByTestId(container, 'container').addEventListener(name, (event: any) =>
                log.push({ name, detail: event.detail })
            );
        }

        return log;
    };

    const connect = async (accept: string) => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
        const input = getByTestId(container, 'input') as HTMLInputElement;
        input.accept = accept;

        return input;
    };

    it('rejects a dropped file that does not match the accept attribute', async () => {
        const input = await connect('image/*');
        const log = events('dropzone:reject', 'dropzone:change', 'dropzone:clear');

        const file = new File(['text'], 'a.txt', { type: 'text/plain' });
        dropFiles(input, [file]);

        expect(log).toStrictEqual([{ name: 'dropzone:reject', detail: { files: [file], reason: 'accept' } }]);
        expect(input.files).toHaveLength(0);
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');
    });

    it('keeps the previous file when a dropped file is rejected', async () => {
        const input = await connect('image/*');
        const photo = new File(['png'], 'photo.png', { type: 'image/png' });
        pickFiles(input, [photo]);
        const log = events('dropzone:reject', 'dropzone:change', 'dropzone:clear');

        const doc = new File(['pdf'], 'doc.pdf', { type: 'application/pdf' });
        dropFiles(input, [doc]);

        expect(log).toStrictEqual([{ name: 'dropzone:reject', detail: { files: [doc], reason: 'accept' } }]);
        expect(input.files?.[0]).toBe(photo);
        expect(input.files).toHaveLength(1);
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('photo.png');
    });

    it('keeps only the accepted file of a drop of several files', async () => {
        const input = await connect('image/*');
        const log = events('dropzone:reject', 'dropzone:change');

        const doc = new File(['pdf'], 'a.pdf', { type: 'application/pdf' });
        const photo = new File(['png'], 'b.png', { type: 'image/png' });
        dropFiles(input, [doc, photo]);

        expect(log).toStrictEqual([
            { name: 'dropzone:change', detail: photo },
            { name: 'dropzone:reject', detail: { files: [doc], reason: 'accept' } },
        ]);
        expect(Array.from(input.files ?? [])).toStrictEqual([photo]);
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('b.png');
    });

    it('accepts a dropped file matching an extension of the accept attribute', async () => {
        const input = await connect('.pdf, .PNG');
        const log = events('dropzone:reject');

        dropFiles(input, [new File(['png'], 'photo.png', { type: 'image/png' })]);

        expect(log).toStrictEqual([]);
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('photo.png');
    });

    it('does not filter a picked file by the accept attribute', async () => {
        const input = await connect('image/*');
        const log = events('dropzone:reject');

        pickFiles(input, [new File(['csv'], 'data.csv', { type: 'application/vnd.ms-excel' })]);

        expect(log).toStrictEqual([]);
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('data.csv');
    });

    it('does not filter a picked file after a drop without change', async () => {
        const input = await connect('image/*');
        const log = events('dropzone:reject');

        input.dispatchEvent(new Event('drop', { bubbles: true }));
        await new Promise((resolve) => setTimeout(resolve));
        pickFiles(input, [new File(['text'], 'a.txt', { type: 'text/plain' })]);

        expect(log).toStrictEqual([]);
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('a.txt');
    });
});

describe('DropzoneController (multiple)', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
            <div class="dropzone-container dropzone-container-multiple"
                 data-controller="dropzone"
                 data-dropzone-multiple-value="true"
                 data-testid="container">
                <input type="file"
                       multiple
                       data-dropzone-target="input"
                       data-testid="input" />

                <div class="dropzone-placeholder" data-dropzone-target="placeholder">Placeholder</div>

                <ul class="dropzone-preview-list" data-dropzone-target="previewList" data-testid="list"></ul>
            </div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    const start = () => startDropzone(getByTestId(container, 'container'));

    const make = (name: string, lastModified = 1) => new File(['content'], name, { type: 'text/plain', lastModified });

    const filenames = () =>
        Array.from(getByTestId(container, 'list').querySelectorAll('.dropzone-preview-filename')).map(
            (element) => element.textContent
        );

    it('accumulates files across selections', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;

        selectFiles(input, [make('a.txt')]);
        await waitFor(() => expect(getByTestId(container, 'list').children).toHaveLength(1));

        selectFiles(input, [make('b.txt')]);
        await waitFor(() => expect(getByTestId(container, 'list').children).toHaveLength(2));
    });

    it('dedupes identical files', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;

        selectFiles(input, [make('a.txt')]);
        selectFiles(input, [make('a.txt')]);

        await waitFor(() => expect(getByTestId(container, 'list').children).toHaveLength(1));
    });

    it('removes a single file', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;

        selectFiles(input, [make('a.txt')]);
        selectFiles(input, [make('b.txt')]);
        await waitFor(() => expect(getByTestId(container, 'list').children).toHaveLength(2));

        const firstRemove = getByTestId(container, 'list').querySelector(
            '.dropzone-preview-list-remove'
        ) as HTMLButtonElement;
        firstRemove.click();

        await waitFor(() => expect(getByTestId(container, 'list').children).toHaveLength(1));
        await waitFor(() =>
            expect(getByTestId(container, 'list').querySelector('.dropzone-preview-filename')?.textContent).toBe(
                'b.txt'
            )
        );
    });

    it('labels each remove button with the file name', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;

        selectFiles(input, [make('a.txt')]);

        await waitFor(() =>
            expect(
                getByTestId(container, 'list')
                    .querySelector('.dropzone-preview-list-remove')
                    ?.getAttribute('aria-label')
            ).toBe('Remove a.txt')
        );
    });

    it('sets the state from the files added', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'empty');

        selectFiles(input, [make('a.txt')]);
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');

        (getByTestId(container, 'list').querySelector('.dropzone-preview-list-remove') as HTMLButtonElement).click();
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'empty');
    });

    it('sets the dragging state while files are dragged over', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [make('a.txt')]);

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'dragging');

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragleave'));
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled'));
    });

    it('shows the image preview of each file once it is loaded', async () => {
        await start();

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [
            new File(['png'], 'a.png', { type: 'image/png' }),
        ]);

        const image = getByTestId(container, 'list').querySelector('.dropzone-preview-image') as HTMLElement;
        expect(image).toHaveAttribute('hidden');
        await waitFor(() => expect(image).not.toHaveAttribute('hidden'));
        expect(image.style.display).toBe('');
    });

    it('adds the files a pre-add listener resolves', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(
                Promise.resolve(event.detail.files.map((file: File) => make(`resized-${file.name}`)))
            );
        });

        selectFiles(input, [make('a.txt'), make('b.txt')]);

        await waitFor(() => expect(filenames()).toStrictEqual(['resized-a.txt', 'resized-b.txt']));
        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual([
            'resized-a.txt',
            'resized-b.txt',
        ]);
    });

    it('keeps the previous files when a pre-add listener prevents the selection', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [make('a.txt')]);
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event) => event.preventDefault());

        selectFiles(input, [make('b.txt')]);

        expect(filenames()).toStrictEqual(['a.txt']);
        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual(['a.txt']);
    });

    it('keeps the previous files in the input while a pre-add listener is pending', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [make('a.txt')]);
        let resolve!: (files: File[]) => void;
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(new Promise<File[]>((r) => (resolve = r)));
        });

        selectFiles(input, [make('b.txt')]);

        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'processing');
        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual(['a.txt']);

        resolve([make('b.txt')]);

        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt', 'b.txt']));
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');
    });

    it('adds the original files when a pre-add listener rejects', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(Promise.reject(new Error('Cannot decode the image')));
        });

        selectFiles(input, [make('a.txt')]);

        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt']));
        expect(consoleError).toHaveBeenCalledWith(new Error('Cannot decode the image'));
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');
        consoleError.mockRestore();
    });

    it('adds the original files when a pre-add listener resolves to something else than files', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(Promise.resolve(undefined));
        });

        selectFiles(input, [make('a.txt')]);

        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt']));
        expect(consoleError).toHaveBeenCalledWith(
            new TypeError('The promise passed to waitUntil() must resolve to an array of files.')
        );
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');
        consoleError.mockRestore();
    });

    it('adds the files in selection order when an earlier pre-add listener resolves later', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const resolvers: Array<() => void> = [];
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            const files = event.detail.files;
            event.detail.waitUntil(new Promise<File[]>((r) => resolvers.push(() => r(files))));
        });

        selectFiles(input, [make('a.txt')]);
        selectFiles(input, [make('b.txt')]);
        resolvers[1]();
        resolvers[0]();

        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt', 'b.txt']));
    });

    it('adds a selection without pre-add promise after the pending ones', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        let resolve!: () => void;
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            const files = event.detail.files;
            if (files[0].name === 'a.txt') {
                event.detail.waitUntil(new Promise<File[]>((r) => (resolve = () => r(files))));
            }
        });

        selectFiles(input, [make('a.txt')]);
        selectFiles(input, [make('b.txt')]);

        expect(filenames()).toStrictEqual([]);
        resolve();
        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt', 'b.txt']));
    });

    it('throws when waitUntil is called twice', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        let error: unknown = null;
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(Promise.resolve(event.detail.files));
            try {
                event.detail.waitUntil(Promise.resolve([]));
            } catch (e) {
                error = e;
            }
        });

        selectFiles(input, [make('a.txt')]);

        expect(error).toStrictEqual(
            new Error('waitUntil() can only be called once, from a "dropzone:pre-add" listener.')
        );
        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt']));
    });

    it('throws when waitUntil is called after the pre-add event', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        let waitUntil!: (promise: Promise<File[]>) => void;
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            waitUntil = event.detail.waitUntil;
        });

        selectFiles(input, [make('a.txt')]);

        expect(() => waitUntil(Promise.resolve([]))).toThrow(
            'waitUntil() can only be called once, from a "dropzone:pre-add" listener.'
        );
        expect(filenames()).toStrictEqual(['a.txt']);
    });

    it('adds the original files when a pre-add listener resolves to something else than files in an array', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        const listener = (event: any) => event.detail.waitUntil(Promise.resolve([new Blob(['resized'])]));
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', listener);

        selectFiles(input, [make('a.txt')]);

        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt']));
        expect(consoleError).toHaveBeenCalledWith(
            new TypeError('The promise passed to waitUntil() must resolve to an array of files.')
        );

        getByTestId(container, 'container').removeEventListener('dropzone:pre-add', listener);
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(Promise.resolve(event.detail.files));
        });
        selectFiles(input, [make('b.txt')]);

        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt', 'b.txt']));
        consoleError.mockRestore();
    });

    it('accepts files passed to waitUntil without a promise', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(event.detail.files.filter((file: File) => file.name !== 'b.txt'));
        });

        selectFiles(input, [make('a.txt'), make('b.txt')]);

        await waitFor(() => expect(filenames()).toStrictEqual(['a.txt']));
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');
    });

    it('handles the promise of a prevented selection', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(Promise.reject(new Error('Cannot decode the image')));
            event.preventDefault();
        });

        selectFiles(input, [make('a.txt')]);
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(filenames()).toStrictEqual([]);
        expect(consoleError).toHaveBeenCalledWith(new Error('Cannot decode the image'));
        consoleError.mockRestore();
    });

    it('does not clear the files with a clear button', async () => {
        // The theme also renders the preview of the single-file mode in multiple mode
        const preview = document.createElement('div');
        preview.setAttribute('data-dropzone-target', 'preview');
        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('data-dropzone-target', 'previewClearButton');
        preview.append(button);
        getByTestId(container, 'container').append(preview);
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [make('a.txt')]);
        let cleared = false;
        getByTestId(container, 'container').addEventListener('dropzone:clear', () => {
            cleared = true;
        });

        button.click();

        expect(cleared).toBe(false);
        expect(getByTestId(container, 'list').children).toHaveLength(1);
    });

    const listen = (name: string) => {
        const details: any[] = [];
        getByTestId(container, 'container').addEventListener(name, (event: any) => details.push(event.detail));

        return details;
    };

    const names = () =>
        Array.from(getByTestId(container, 'list').querySelectorAll('.dropzone-preview-filename')).map(
            (element) => element.textContent
        );

    it('adds only the files matching the accept attribute', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        input.accept = 'image/png,.txt';
        const rejections = listen('dropzone:reject');

        const png = new File(['png'], 'a.png', { type: 'image/png' });
        const jpeg = new File(['jpeg'], 'b.jpg', { type: 'image/jpeg' });
        dropFiles(input, [png, jpeg, make('d.txt')]);

        expect(names()).toStrictEqual(['a.png', 'd.txt']);
        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual(['a.png', 'd.txt']);
        expect(rejections).toStrictEqual([{ files: [jpeg], reason: 'accept' }]);
    });

    it('accepts a file of unknown type when the accept attribute has a MIME type', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        input.accept = 'image/*,.pdf';
        const rejections = listen('dropzone:reject');

        const heic = new File(['heic'], 'a.heic', { type: '' });
        const doc = new File(['doc'], 'b.doc', { type: 'application/msword' });
        dropFiles(input, [heic, doc]);

        expect(names()).toStrictEqual(['a.heic']);
        expect(rejections).toStrictEqual([{ files: [doc], reason: 'accept' }]);
    });

    it('matches the accept attribute with a MIME type wildcard', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        input.accept = 'image/*';
        const rejections = listen('dropzone:reject');

        dropFiles(input, [new File(['png'], 'a.png', { type: 'image/png' }), make('b.txt')]);

        expect(names()).toStrictEqual(['a.png']);
        expect(rejections).toHaveLength(1);
        expect(rejections[0].files.map((file: File) => file.name)).toStrictEqual(['b.txt']);
    });

    it('does not filter the picked files by the accept attribute', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        input.accept = 'text/csv';
        const rejections = listen('dropzone:reject');

        selectFiles(input, [new File(['csv'], 'data.csv', { type: 'application/vnd.ms-excel' })]);

        expect(names()).toStrictEqual(['data.csv']);
        expect(rejections).toStrictEqual([]);
    });

    it.each(['*', '*/*'])('accepts every dropped file with the "%s" accept attribute', async (accept) => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        input.accept = accept;
        const rejections = listen('dropzone:reject');

        dropFiles(input, [new File(['png'], 'a.png', { type: 'image/png' }), make('b.txt')]);

        expect(names()).toStrictEqual(['a.png', 'b.txt']);
        expect(rejections).toStrictEqual([]);
    });

    it('accepts every file without accept attribute', async () => {
        await start();
        const rejections = listen('dropzone:reject');

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [make('a.txt'), make('b.bin')]);

        expect(names()).toStrictEqual(['a.txt', 'b.bin']);
        expect(rejections).toStrictEqual([]);
    });

    it('rejects the files over max files', async () => {
        getByTestId(container, 'container').setAttribute('data-dropzone-max-files-value', '2');
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const rejections = listen('dropzone:reject');

        const c = make('c.txt');
        selectFiles(input, [make('a.txt'), make('b.txt'), c]);

        expect(names()).toStrictEqual(['a.txt', 'b.txt']);
        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual(['a.txt', 'b.txt']);
        expect(rejections).toStrictEqual([{ files: [c], reason: 'max_files' }]);

        (getByTestId(container, 'list').querySelector('.dropzone-preview-list-remove') as HTMLButtonElement).click();
        selectFiles(input, [make('d.txt')]);

        expect(names()).toStrictEqual(['b.txt', 'd.txt']);
        expect(rejections).toHaveLength(1);
    });

    it('does not count duplicates against max files', async () => {
        getByTestId(container, 'container').setAttribute('data-dropzone-max-files-value', '2');
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const rejections = listen('dropzone:reject');
        selectFiles(input, [make('a.txt')]);

        selectFiles(input, [make('a.txt'), make('b.txt')]);

        expect(names()).toStrictEqual(['a.txt', 'b.txt']);
        expect(rejections).toStrictEqual([]);
    });

    it('rejects for each reason', async () => {
        getByTestId(container, 'container').setAttribute('data-dropzone-max-files-value', '1');
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        input.accept = '.txt';
        const log: string[] = [];
        const rejections = listen('dropzone:reject');
        for (const name of ['dropzone:change', 'dropzone:reject']) {
            getByTestId(container, 'container').addEventListener(name, () => log.push(name));
        }

        const png = new File(['png'], 'a.png', { type: 'image/png' });
        const c = make('c.txt');
        dropFiles(input, [png, make('b.txt'), c]);

        expect(names()).toStrictEqual(['b.txt']);
        expect(rejections).toStrictEqual([
            { files: [png], reason: 'accept' },
            { files: [c], reason: 'max_files' },
        ]);
        expect(log).toStrictEqual(['dropzone:change', 'dropzone:reject', 'dropzone:reject']);
    });

    it('clears when the last file is removed', async () => {
        await start();
        const events: string[] = [];
        for (const name of ['dropzone:change', 'dropzone:remove', 'dropzone:clear']) {
            getByTestId(container, 'container').addEventListener(name, () => events.push(name));
        }
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [make('a.txt')]);

        (getByTestId(container, 'list').querySelector('.dropzone-preview-list-remove') as HTMLButtonElement).click();

        expect(events).toStrictEqual(['dropzone:change', 'dropzone:change', 'dropzone:remove', 'dropzone:clear']);
    });

    it('reads each image once', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const readAsDataURL = vi.spyOn(FileReader.prototype, 'readAsDataURL');

        selectFiles(input, [new File(['a'], 'a.png', { type: 'image/png' })]);
        selectFiles(input, [new File(['b'], 'b.png', { type: 'image/png' })]);
        selectFiles(input, [new File(['c'], 'c.png', { type: 'image/png' })]);

        expect(readAsDataURL).toHaveBeenCalledTimes(3);
        readAsDataURL.mockRestore();
    });

    it('keeps dragging while the drag moves over a child, without relatedTarget', async () => {
        await start();

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        getByTestId(container, 'input').dispatchEvent(fileDrag('dragenter'));
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragleave'));

        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'dragging');
    });

    it('does not dispatch change when no file is added', async () => {
        getByTestId(container, 'container').setAttribute('data-dropzone-max-files-value', '2');
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [make('a.txt'), make('b.txt')]);
        const changes = listen('dropzone:change');

        selectFiles(input, [make('a.txt')]);
        selectFiles(input, [make('c.txt')]);

        expect(names()).toStrictEqual(['a.txt', 'b.txt']);
        expect(changes).toStrictEqual([]);
    });

    it('reads an image again after a failed read', async () => {
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        const failOnce = vi.spyOn(FileReader.prototype, 'readAsDataURL').mockImplementationOnce(function (
            this: FileReader
        ) {
            setTimeout(() => this.dispatchEvent(new ProgressEvent('error')));
        });
        const png = new File(['png'], 'a.png', { type: 'image/png' });

        selectFiles(input, [png]);
        await new Promise((resolve) => setTimeout(resolve, 10));
        failOnce.mockRestore();
        selectFiles(input, [make('b.txt')]);

        await waitFor(() =>
            expect(getByTestId(container, 'list').querySelector('.dropzone-preview-image')).not.toHaveAttribute(
                'hidden'
            )
        );
    });

    it('ends the drag when the element under the cursor is removed', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [make('a.txt')]);
        const item = getByTestId(container, 'list').firstElementChild as HTMLElement;

        getByTestId(container, 'container').dispatchEvent(fileDrag('dragenter'));
        item.dispatchEvent(fileDrag('dragenter'));
        item.remove();
        getByTestId(container, 'container').dispatchEvent(fileDrag('dragleave'));

        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');
    });
});

describe('DropzoneController (multiple, preview item template)', () => {
    let container: HTMLElement;

    const mount = (template: string) => {
        container = mountDOM(`
            <div data-controller="dropzone" data-dropzone-multiple-value="true" data-testid="container">
                <input type="file" multiple data-dropzone-target="input" data-testid="input" />
                <ul data-dropzone-target="previewList" data-testid="list"></ul>
                <template data-dropzone-target="previewItemTemplate">${template}</template>
            </div>
        `);
    };

    const start = () => startDropzone(getByTestId(container, 'container'));

    afterEach(() => {
        clearDOM();
    });

    it('renders each file from the template', async () => {
        mount(`
            <li class="item">
                <div data-dropzone-slot="image" class="thumbnail"></div>
                <span data-dropzone-slot="filename" class="name"></span>
                <button type="button" data-dropzone-slot="remove" class="remove"><svg></svg></button>
            </li>
        `);
        await start();

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [
            new File(['a'], 'a.txt', { type: 'text/plain' }),
            new File(['b'], '<b>.txt', { type: 'text/plain' }),
        ]);

        const items = getByTestId(container, 'list').querySelectorAll('li.item');
        expect(items).toHaveLength(2);
        expect(items[0].querySelector('.name')?.textContent).toBe('a.txt');
        expect(items[1].querySelector('.name')?.textContent).toBe('<b>.txt');
        expect(items[1].querySelector('.name')?.children).toHaveLength(0);
        expect(items[0].querySelector('.remove')).toHaveAttribute('aria-label', 'Remove a.txt');
        expect(items[0].querySelector('.remove svg')).not.toBeNull();
        expect(items[0].querySelector('.thumbnail')).toHaveAttribute('hidden');
        expect(items[0].querySelector('.thumbnail')).not.toHaveAttribute('style');
    });

    it('removes the file of the clicked remove slot', async () => {
        mount(`
            <li>
                <span data-dropzone-slot="filename"></span>
                <button type="button" data-dropzone-slot="remove"></button>
            </li>
        `);
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [new File(['a'], 'a.txt'), new File(['b'], 'b.txt')]);

        (getByTestId(container, 'list').querySelector('[data-dropzone-slot="remove"]') as HTMLButtonElement).click();

        const names = Array.from(
            getByTestId(container, 'list').querySelectorAll('[data-dropzone-slot="filename"]')
        ).map((element) => element.textContent);
        expect(names).toStrictEqual(['b.txt']);
        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual(['b.txt']);
    });

    it('shows the image preview as the background of the image slot', async () => {
        mount('<li><div data-dropzone-slot="image"></div></li>');
        await start();

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [
            new File(['png'], 'a.png', { type: 'image/png' }),
        ]);

        const image = getByTestId(container, 'list').querySelector('[data-dropzone-slot="image"]') as HTMLElement;
        await waitFor(() => expect(image).not.toHaveAttribute('hidden'));
        expect(image.style.backgroundImage).toBe('url("data:image/png;base64,cG5n")');
    });

    it('shows the image preview as the source of an img image slot', async () => {
        mount('<li><img data-dropzone-slot="image" alt=""></li>');
        await start();

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [
            new File(['png'], 'a.png', { type: 'image/png' }),
        ]);

        const image = getByTestId(container, 'list').querySelector('[data-dropzone-slot="image"]') as HTMLImageElement;
        await waitFor(() => expect(image).not.toHaveAttribute('hidden'));
        expect(image).toHaveAttribute('src', 'data:image/png;base64,cG5n');
        expect(image).not.toHaveAttribute('style');
    });

    it.each([
        ['no element', 'Text only'],
        ['several root elements', '<li></li><li></li>'],
    ])('fails to connect when the template has %s', async (_, template) => {
        mount(template);
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        let connected = false;
        getByTestId(container, 'container').addEventListener('dropzone:connect', () => {
            connected = true;
        });

        startApplication().register('dropzone', DropzoneController);

        await waitFor(() =>
            expect(consoleError).toHaveBeenCalledWith(
                expect.any(String),
                'Error connecting controller',
                new Error('The "previewItemTemplate" target must contain one root element.'),
                expect.anything()
            )
        );
        expect(connected).toBe(false);
        consoleError.mockRestore();
    });

    it('fills a filename slot on the root element of the template', async () => {
        mount('<li data-dropzone-slot="filename"></li>');
        await start();

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['a'], 'a.txt')]);

        expect(getByTestId(container, 'list').querySelector('li')?.textContent).toBe('a.txt');
    });

    it('binds a remove slot on the root element of the template', async () => {
        mount('<button type="button" data-dropzone-slot="remove"></button>');
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [new File(['a'], 'a.txt'), new File(['b'], 'b.txt')]);

        const button = getByTestId(container, 'list').querySelector('button') as HTMLButtonElement;
        expect(button).toHaveAttribute('aria-label', 'Remove a.txt');
        button.click();

        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual(['b.txt']);
    });

    it('fills every element of a slot', async () => {
        mount(`
            <li>
                <span data-dropzone-slot="filename" class="name"></span>
                <span data-dropzone-slot="filename" class="sr-only"></span>
                <button type="button" data-dropzone-slot="remove" class="icon"></button>
                <button type="button" data-dropzone-slot="remove" class="text">Remove</button>
            </li>
        `);
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [new File(['a'], 'a.txt'), new File(['b'], 'b.txt')]);

        const items = getByTestId(container, 'list').querySelectorAll('li');
        expect(items[0].querySelector('.name')?.textContent).toBe('a.txt');
        expect(items[0].querySelector('.sr-only')?.textContent).toBe('a.txt');
        expect(items[0].querySelector('.text')).toHaveAttribute('aria-label', 'Remove a.txt');

        (items[0].querySelector('.text') as HTMLButtonElement).click();

        expect(Array.from(input.files ?? []).map((file) => file.name)).toStrictEqual(['b.txt']);
    });

    it('does not submit the form with a remove slot without type', async () => {
        mount('<li><button data-dropzone-slot="remove">Remove</button></li>');
        const form = document.createElement('form');
        getByTestId(container, 'container').before(form);
        form.append(getByTestId(container, 'container'));
        let submitted = false;
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitted = true;
        });
        await start();
        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [new File(['a'], 'a.txt')]);

        (getByTestId(container, 'list').querySelector('button') as HTMLButtonElement).click();

        expect(submitted).toBe(false);
        expect(input.files).toHaveLength(0);
    });

    it('renders a template without slots', async () => {
        mount('<li class="item">File</li>');
        await start();

        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['a'], 'a.txt')]);

        expect(getByTestId(container, 'list').querySelectorAll('li.item')).toHaveLength(1);
    });
});
