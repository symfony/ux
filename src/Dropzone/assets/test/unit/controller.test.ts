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

        await user.upload(input, file);
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

    it('restores the preview when the same file is dropped again', async () => {
        startStimulus();

        const input = getByTestId(container, 'input') as HTMLInputElement;
        const file = new File(['content'], 'a.txt', { type: 'text/plain' });
        Object.defineProperty(input, 'files', { configurable: true, writable: true, value: [file] });

        // Dragging over reveals the input and hides the preview
        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
        await waitFor(() => expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' }));

        // Chrome does not fire "change" when the very same file is picked again
        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));

        await waitFor(() => expect(getByTestId(container, 'input')).toHaveStyle({ display: 'none' }));
        await waitFor(() => expect(getByTestId(container, 'placeholder')).toHaveStyle({ display: 'none' }));
        await waitFor(() => expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'flex' }));
    });

    it('adds the files a pre-add listener resolves', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

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

        await waitFor(() => expect(getByTestId(container, 'preview-filename')).toHaveTextContent('hello.jpg'));
        expect(preAddFiles).toStrictEqual([original]);
        expect(changed).toBe(transformed);
        expect(input.files?.[0]).toBe(transformed);
        expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'flex' });
    });

    it('adds nothing when a pre-add listener prevents it', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event) => event.preventDefault());
        let changed = false;
        getByTestId(container, 'container').addEventListener('dropzone:change', () => {
            changed = true;
        });

        const input = getByTestId(container, 'input') as HTMLInputElement;
        selectFiles(input, [new File(['hello'], 'hello.png', { type: 'image/png' })]);

        expect(changed).toBe(false);
        expect(input.value).toBe('');
        expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' });
        expect(getByTestId(container, 'placeholder')).toHaveStyle({ display: 'block' });
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

        await waitFor(() => expect(getByTestId(container, 'container')).not.toHaveAttribute('data-state'));
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('');
        expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' });
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

        await waitFor(() => expect(getByTestId(container, 'container')).not.toHaveAttribute('data-state'));
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('');
        expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' });
    });

    it('removes a processing state left in the markup', async () => {
        getByTestId(container, 'container').setAttribute('data-state', 'processing');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        expect(getByTestId(container, 'container')).not.toHaveAttribute('data-state');
    });

    it('keeps the preview hidden after a drop while the dropped file is processed', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));

        const pending = deferred();
        getByTestId(container, 'container').addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(pending.promise);
        });

        const a = new File(['a'], 'a.png', { type: 'image/png' });
        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [a]);
        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' });

        pending.resolve([a]);
        await waitFor(() => expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'flex' }));
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

    const start = () =>
        new Promise<void>((resolve) => {
            getByTestId(container, 'container').addEventListener('dropzone:connect', () => resolve(), { once: true });
            const application = startApplication();
            application.register('dropzone', DropzoneController);
        });

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
        expect(getByTestId(container, 'container')).not.toHaveAttribute('data-state');
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
        expect(getByTestId(container, 'container')).not.toHaveAttribute('data-state');
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
        expect(getByTestId(container, 'container')).not.toHaveAttribute('data-state');
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
        expect(getByTestId(container, 'container')).not.toHaveAttribute('data-state');
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
});
