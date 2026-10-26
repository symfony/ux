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

const startDropzone = (element: HTMLElement) =>
    new Promise<void>((resolve) => {
        element.addEventListener('dropzone:connect', () => resolve(), { once: true });
        startApplication().register('dropzone', DropzoneController);
    });

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

    const dragLeave = (relatedTarget: Node) => {
        const event = new Event('dragleave', { bubbles: true });
        Object.defineProperty(event, 'relatedTarget', { value: relatedTarget });
        getByTestId(container, 'container').dispatchEvent(event);
    };

    it('connect', async () => {
        expect(getByTestId(container, 'container')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'container')).toHaveClass('connected'));
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
        const input = getByTestId(container, 'input');
        const file = new File(['hello'], 'hello.png', { type: 'image/png' });

        await user.upload(input, file);
        expect(input.files[0]).toStrictEqual(file);

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

        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));

        expect(state()).toBe('dragging');
        expect(getByTestId(container, 'input')).not.toHaveAttribute('hidden');
        expect(getByTestId(container, 'placeholder')).not.toHaveAttribute('hidden');
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');

        dragLeave(getByTestId(container, 'placeholder'));
        expect(state()).toBe('dragging');

        dragLeave(document.body);
        expectFilled();
    });

    it('stays empty when a drag leaves an empty dropzone', async () => {
        await start();

        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
        dragLeave(document.body);

        expectEmpty();
    });

    it('restores the preview when the same file is dropped again', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['content'], 'a.txt')]);

        // Dragging over reveals the input and hides the preview
        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');

        // Chrome does not fire "change" when the very same file is picked again
        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));

        await waitFor(() => expectFilled());
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('a.txt');
    });

    it('keeps the input shown while the drop is dispatched', async () => {
        await start();
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [new File(['content'], 'a.txt')]);
        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));

        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));

        expect(getByTestId(container, 'input')).not.toHaveAttribute('hidden');
        await waitFor(() => expectFilled());
    });

    it('ends the drag when it reconnects', async () => {
        await start();
        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
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

        await waitFor(() => expect(state()).toBe('empty'));
        expect(getByTestId(container, 'preview-filename')).toHaveTextContent('');
        expectEmpty();
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
        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
        selectFiles(getByTestId(container, 'input') as HTMLInputElement, [a]);
        getByTestId(container, 'container').dispatchEvent(new Event('drop', { bubbles: true }));
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(state()).toBe('processing');
        expect(getByTestId(container, 'preview')).toHaveAttribute('hidden');

        pending.resolve([a]);
        await waitFor(() => expectFilled());
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

        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'dragging');

        const dragLeave = new Event('dragleave', { bubbles: true });
        Object.defineProperty(dragLeave, 'relatedTarget', { value: document.body });
        getByTestId(container, 'container').dispatchEvent(dragLeave);
        expect(getByTestId(container, 'container')).toHaveAttribute('data-state', 'filled');

        getByTestId(container, 'container').dispatchEvent(new Event('dragenter'));
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
