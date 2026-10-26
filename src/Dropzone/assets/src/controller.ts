/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly inputTarget: HTMLInputElement;
    declare readonly placeholderTarget: HTMLDivElement;
    declare readonly previewTarget: HTMLDivElement;
    declare readonly hasPreviewTarget: boolean;
    declare readonly previewClearButtonTarget: HTMLButtonElement;
    declare readonly previewFilenameTarget: HTMLDivElement;
    declare readonly hasPreviewFilenameTarget: boolean;
    declare readonly previewImageTarget: HTMLDivElement;
    declare readonly hasPreviewImageTarget: boolean;
    declare readonly previewListTarget: HTMLUListElement;
    declare readonly hasPreviewListTarget: boolean;
    declare readonly previewItemTemplateTarget: HTMLTemplateElement;
    declare readonly hasPreviewItemTemplateTarget: boolean;

    declare readonly multipleValue: boolean;
    declare readonly maxFilesValue: number;
    declare readonly removeLabelValue: string;

    static targets = [
        'input',
        'placeholder',
        'preview',
        'previewClearButton',
        'previewFilename',
        'previewImage',
        'previewList',
        'previewItemTemplate',
    ];

    static values = {
        multiple: Boolean,
        maxFiles: Number,
        removeLabel: { type: String, default: 'Remove' },
    };

    // The files added, in both modes: in single-file mode, a new selection replaces the file
    private dataTransfer!: DataTransfer;
    private previewItem: Element | null = null;
    private processingCount = 0;
    // The elements a file drag has entered and not left yet
    private readonly dragTargets = new Set<EventTarget>();
    private queue: Promise<void> = Promise.resolve();
    private generation = 0;
    private dropping = false;
    private form: HTMLFormElement | null = null;
    private resetTimeout: ReturnType<typeof setTimeout> | undefined;
    private readonly dataUrls = new WeakMap<Blob, Promise<string>>();
    private readonly previewedFiles = new WeakMap<HTMLElement, Blob>();

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
        this.onDrop = this.onDrop.bind(this);
        this.onFormReset = this.onFormReset.bind(this);
    }

    connect() {
        // A drag can end while the controller is disconnected
        this.dragTargets.clear();

        // Keep the files already in the input, when the controller reconnects for example
        this.dataTransfer = new DataTransfer();
        for (const file of Array.from(this.inputTarget.files ?? []).slice(0, this.multipleValue ? undefined : 1)) {
            this.dataTransfer.items.add(file);
        }

        this.previewItem = null;
        if (this.hasPreviewItemTemplateTarget) {
            // Checked before any file is added, so an invalid template cannot leave the input and the preview apart
            const elements = this.previewItemTemplateTarget.content.children;
            if (elements.length !== 1) {
                throw new Error('The "previewItemTemplate" target must contain one root element.');
            }
            this.previewItem = elements[0];
        }

        // Themes copied from older versions hide these parts with an inline style, which would win over the hidden attribute
        for (const target of [
            ...(this.hasPreviewTarget ? [this.previewTarget] : []),
            ...(this.hasPreviewImageTarget ? [this.previewImageTarget] : []),
        ]) {
            if (target.style.display === 'none') {
                target.style.removeProperty('display');
            }
        }

        this.element.addEventListener('dragenter', this.onDragEnter);
        this.element.addEventListener('dragleave', this.onDragLeave);
        this.element.addEventListener('drop', this.onDrop);
        this.inputTarget.addEventListener('change', this.onInputChange);
        this.form = this.inputTarget.form;
        this.form?.addEventListener('reset', this.onFormReset);

        this.render();
        this.dispatchEvent('connect');
    }

    disconnect() {
        this.generation++;
        clearTimeout(this.resetTimeout);
        this.element.removeEventListener('dragenter', this.onDragEnter);
        this.element.removeEventListener('dragleave', this.onDragLeave);
        this.element.removeEventListener('drop', this.onDrop);
        this.inputTarget.removeEventListener('change', this.onInputChange);
        this.form?.removeEventListener('reset', this.onFormReset);
        this.form = null;
    }

    // Bound when the button appears, as it is optional and can be rendered after the controller connects
    previewClearButtonTargetConnected(button: HTMLButtonElement) {
        // In multiple mode, files are removed one by one, never cleared together
        if (!this.multipleValue) {
            button.addEventListener('click', this.clear);
        }
    }

    previewClearButtonTargetDisconnected(button: HTMLButtonElement) {
        button.removeEventListener('click', this.clear);
    }

    clear() {
        // The selections still processed must not bring a file back
        this.generation++;

        const cleared = this.dataTransfer.files.length > 0;
        this.dataTransfer = new DataTransfer();
        this.inputTarget.files = this.dataTransfer.files;
        this.render();

        if (cleared) {
            this.dispatchEvent('clear');
        }
    }

    onInputChange() {
        const selected = Array.from(this.inputTarget.files ?? []);
        const [accepted, rejected] = this.checkAccept(selected);

        // The browser replaced the files of the input with the new selection: put back the files added before, until the new ones are
        this.inputTarget.files = this.dataTransfer.files;

        if (selected.length === 0) {
            return;
        }

        if (accepted.length === 0) {
            this.reject(rejected, 'accept');
            return;
        }

        this.preAdd(accepted, (added) => this.add(added, rejected));
    }

    _populateImagePreview(target: HTMLElement, file: Blob) {
        this.previewedFiles.set(target, file);
        this.readAsDataUrl(file, (url) => {
            // The target shows another file since, or none
            if (this.previewedFiles.get(target) !== file) {
                return;
            }

            target.hidden = false;
            target.style.backgroundImage = `url("${url}")`;
        });
    }

    onDragEnter(event: Event) {
        if (!this.isFileDrag(event)) {
            return;
        }

        this.dragTargets.add(event.target!);
        this.updateState();
    }

    onDragLeave(event: Event) {
        event.preventDefault();

        if (!this.isFileDrag(event)) {
            return;
        }

        // Each element the drag moves over fires its own "dragenter" and "dragleave", and not every browser sets their relatedTarget.
        // An element removed during the drag, by a render for example, never fires its "dragleave" on the container.
        this.dragTargets.delete(event.target!);
        for (const target of this.dragTargets) {
            if (!(target instanceof Node) || !this.element.contains(target)) {
                this.dragTargets.delete(target);
            }
        }
        this.updateState();
    }

    onDrop() {
        // The browser puts the dropped files into the input and fires "change" right after the drop event
        this.dropping = true;
        // The drop ends the drag even when no "change" follows, as in Chrome when the very same file is dropped again.
        // Deferred so the input stays shown until the browser has put the dropped files into it.
        setTimeout(() => {
            this.dropping = false;
            this.dragTargets.clear();
            this.updateState();
        });
    }

    private onFormReset(event: Event) {
        // The form empties the input after the "reset" event, unless a listener prevents it
        clearTimeout(this.resetTimeout);
        this.resetTimeout = setTimeout(() => {
            if (!event.defaultPrevented) {
                this.clear();
            }
        });
    }

    private preAdd(files: File[], add: (files: File[]) => void) {
        let promise = null as Promise<File[]> | null;
        let dispatching = true;
        const event = this.dispatchEvent(
            'pre-add',
            {
                files,
                waitUntil: (filesPromise: File[] | Promise<File[]>) => {
                    if (!dispatching || promise) {
                        throw new Error('waitUntil() can only be called once, from a "dropzone:pre-add" listener.');
                    }
                    promise = Promise.resolve(filesPromise);
                },
            },
            true
        );
        dispatching = false;

        if (event.defaultPrevented && promise) {
            // The selection is dropped, but a rejection of its promise must not go unhandled
            promise.catch((error) => console.error(error));
            promise = null;
        }
        const selected = event.defaultPrevented ? [] : files;

        if (!promise && this.processingCount === 0) {
            add(selected);
            return;
        }

        const generation = this.generation;
        this.processingCount++;
        this.updateState();

        const result = (promise ?? Promise.resolve(selected))
            .then((resolved) => {
                if (!Array.isArray(resolved) || !resolved.every((file) => file instanceof File)) {
                    throw new TypeError('The promise passed to waitUntil() must resolve to an array of files.');
                }

                return resolved;
            })
            .catch((error) => {
                console.error(error);

                return files;
            });

        this.queue = this.queue
            .then(() => result)
            .then((resolved) => {
                // The dropzone was cleared or disconnected since this selection
                if (generation === this.generation) {
                    add(resolved);
                }
            })
            .finally(() => {
                this.processingCount--;
                this.updateState();
            });
    }

    private add(added: File[], rejected: File[]) {
        const overflow: File[] = [];
        let changed = false;
        if (this.multipleValue) {
            for (const file of added) {
                if (this.containsFile(file)) {
                    continue;
                }

                if (this.maxFilesValue > 0 && this.dataTransfer.files.length >= this.maxFilesValue) {
                    overflow.push(file);
                } else {
                    this.dataTransfer.items.add(file);
                    changed = true;
                }
            }
        } else if (added.length > 0) {
            this.dataTransfer = new DataTransfer();
            this.dataTransfer.items.add(added[0]);
            overflow.push(...added.slice(1));
            changed = true;
        }

        if (changed) {
            this.inputTarget.files = this.dataTransfer.files;
            this.render();
            this.dispatchEvent('change', this.multipleValue ? this.inputTarget.files : this.dataTransfer.files[0]);
        }

        this.reject(rejected, 'accept');
        this.reject(overflow, 'max_files');
    }

    private removeFileAt(index: number, file: File) {
        if (!this.multipleValue) {
            this.clear();
            return;
        }

        this.dataTransfer.items.remove(index);
        this.inputTarget.files = this.dataTransfer.files;
        this.render();
        this.dispatchEvent('change', this.inputTarget.files);
        this.dispatchEvent('remove', file);

        if (this.dataTransfer.files.length === 0) {
            this.dispatchEvent('clear');
        }
    }

    private checkAccept(files: File[]): [File[], File[]] {
        // Only drops ignore the accept attribute: the file picker filters with the file types of the operating system
        if (!this.dropping) {
            return [files, []];
        }

        const tokens = this.inputTarget.accept
            .split(',')
            .map((token) => token.trim().toLowerCase())
            .filter((token) => token !== '');
        // "*" and "*/*" are not valid tokens, and browsers do not restrict the files with them either
        if (tokens.length === 0 || tokens.some((token) => token === '*' || token === '*/*')) {
            return [files, []];
        }

        const accepted: File[] = [];
        const rejected: File[] = [];
        for (const file of files) {
            const name = file.name.toLowerCase();
            const type = file.type.toLowerCase();
            const matches = tokens.some((token) => {
                if (token.startsWith('.')) {
                    return name.endsWith(token);
                }
                // Browsers report no type for some files (HEIC photos, for example), leave them to the server
                if (type === '') {
                    return true;
                }
                if (token.endsWith('/*')) {
                    return type.startsWith(token.slice(0, -1));
                }

                return type === token;
            });

            (matches ? accepted : rejected).push(file);
        }

        return [accepted, rejected];
    }

    private reject(files: File[], reason: 'accept' | 'max_files') {
        if (files.length > 0) {
            this.dispatchEvent('reject', { files, reason });
        }
    }

    private containsFile(file: File): boolean {
        return Array.from(this.dataTransfer.files).some(
            (existing) =>
                existing.name === file.name &&
                existing.size === file.size &&
                existing.lastModified === file.lastModified
        );
    }

    private render() {
        const files = Array.from(this.dataTransfer.files);

        if (this.multipleValue) {
            if (this.hasPreviewListTarget) {
                this.previewListTarget.replaceChildren(...files.map((file, index) => this.buildListItem(file, index)));
            }
        } else if (this.previewItem) {
            this.previewTarget.replaceChildren(...files.map((file, index) => this.buildListItem(file, index)));
        } else {
            const file = files[0];
            if (this.hasPreviewFilenameTarget) {
                this.previewFilenameTarget.textContent = file?.name ?? '';
            }
            if (this.hasPreviewImageTarget) {
                this.previewedFiles.delete(this.previewImageTarget);
                this.previewImageTarget.hidden = true;
                this.previewImageTarget.style.removeProperty('background-image');
                if (file && this.isImage(file)) {
                    this._populateImagePreview(this.previewImageTarget, file);
                }
            }
        }

        this.updateState();
    }

    private buildListItem(file: File, index: number): Element {
        if (this.previewItem) {
            return this.buildListItemFromTemplate(this.previewItem, file, index);
        }

        const item = document.createElement('li');
        item.className = 'dropzone-preview-list-item';

        const image = document.createElement('div');
        image.className = 'dropzone-preview-image';
        image.hidden = true;
        if (this.isImage(file)) {
            this._populateImagePreview(image, file);
        }

        const filename = document.createElement('span');
        filename.className = 'dropzone-preview-filename';
        // Always use textContent: file names are user input and must never be treated as HTML
        filename.textContent = file.name;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'dropzone-preview-list-remove';
        this.bindRemove(remove, file, index);

        item.append(image, filename, remove);

        return item;
    }

    private buildListItemFromTemplate(template: Element, file: File, index: number): Element {
        const item = template.cloneNode(true) as Element;
        const slots = (name: string): Element[] => {
            const selector = `[data-dropzone-slot="${name}"]`;

            return [...(item.matches(selector) ? [item] : []), ...Array.from(item.querySelectorAll(selector))];
        };

        const images = slots('image') as HTMLElement[];
        for (const image of images) {
            image.hidden = true;
        }
        if (images.length > 0 && this.isImage(file)) {
            this.readAsDataUrl(file, (url) => {
                for (const image of images) {
                    if (image instanceof HTMLImageElement) {
                        image.src = url;
                    } else {
                        image.style.backgroundImage = `url("${url}")`;
                    }
                    image.hidden = false;
                }
            });
        }

        for (const filename of slots('filename')) {
            // Always use textContent: file names are user input and must never be treated as HTML
            filename.textContent = file.name;
        }

        for (const remove of slots('remove')) {
            this.bindRemove(remove, file, index);
        }

        return item;
    }

    private isImage(file: File): boolean {
        return !!file.type && file.type.indexOf('image') !== -1;
    }

    private readAsDataUrl(file: Blob, onLoad: (url: string) => void) {
        if (typeof FileReader === 'undefined') {
            // FileReader API not available, skip
            return;
        }

        // Each file is read once, however many times the preview is rendered
        let url = this.dataUrls.get(file);
        if (!url) {
            url = new Promise<string>((resolve, reject) => {
                const reader = new FileReader();
                reader.addEventListener('load', () => resolve(reader.result as string));
                reader.addEventListener('error', () => reject(reader.error));
                reader.readAsDataURL(file);
            });
            this.dataUrls.set(file, url);
            // A failed read is tried again on the next render
            url.catch(() => this.dataUrls.delete(file));
        }

        url.then(onLoad, () => {});
    }

    private bindRemove(element: Element, file: File, index: number) {
        element.setAttribute('aria-label', `${this.removeLabelValue} ${file.name}`);
        element.addEventListener('click', () => this.removeFileAt(index, file));
    }

    private isFileDrag(event: Event): boolean {
        return Array.from((event as DragEvent).dataTransfer?.types ?? []).includes('Files');
    }

    private updateState() {
        const filled = this.dataTransfer.files.length > 0;
        const dragging = this.dragTargets.size > 0;

        let state = filled ? 'filled' : 'empty';
        if (dragging) {
            state = 'dragging';
        }
        if (this.processingCount > 0) {
            state = 'processing';
        }
        this.element.setAttribute('data-state', state);

        if (this.multipleValue) {
            return;
        }

        // Show the input and placeholder to receive a drop, and the preview of the file otherwise
        const showPreview = filled && !dragging;
        this.inputTarget.hidden = showPreview;
        this.placeholderTarget.hidden = showPreview;
        this.previewTarget.hidden = !showPreview;
    }

    private dispatchEvent(name: string, payload: any = {}, cancelable = false) {
        return this.dispatch(name, { detail: payload, prefix: 'dropzone', cancelable });
    }
}
