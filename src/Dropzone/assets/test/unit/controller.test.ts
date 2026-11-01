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
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
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
        expect(getByTestId(container, 'preview')).toHaveStyle({ display: 'none' });
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

    const start = () =>
        new Promise<void>((resolve) => {
            getByTestId(container, 'container').addEventListener('dropzone:connect', () => resolve(), { once: true });
            const application = startApplication();
            application.register('dropzone', DropzoneController);
        });

    // Simulate a native file pick. A real <input multiple> replaces its FileList
    // on every pick; jsdom can't build a populated FileList, and user-event makes
    // `files` read-only, so we install create a writable `files` (which the
    // controller writes back to as well) and dispatch the change event ourselves.
    const selectFiles = (input: HTMLInputElement, files: File[]) => {
        Object.defineProperty(input, 'files', { configurable: true, writable: true, value: files });
        input.dispatchEvent(new Event('change'));
    };

    const make = (name: string, lastModified = 1) => new File(['content'], name, { type: 'text/plain', lastModified });

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
});
