/**
 * Vitest test setup
 */

import { vi } from 'vitest';

// Mock fetch globally
global.fetch = vi.fn();

// Mock CompressionStream for tests (must implement TransformStream interface)
class MockCompressionStream {
    readable: ReadableStream;
    writable: WritableStream;

    constructor(_format: string) {
        let readableController: ReadableStreamDefaultController;
        this.readable = new ReadableStream({
            start(controller) {
                readableController = controller;
            },
        });
        this.writable = new WritableStream({
            write(chunk) {
                // Pass through data unchanged (mock doesn't actually compress)
                readableController.enqueue(chunk);
            },
            close() {
                readableController.close();
            },
        });
    }
}

// @ts-expect-error - Mock implementation
global.CompressionStream = MockCompressionStream;

// Polyfill Blob.prototype.arrayBuffer if missing (JSDOM issue)
if (!Blob.prototype.arrayBuffer) {
    Blob.prototype.arrayBuffer = function () {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result as ArrayBuffer);
            reader.onerror = () => reject(reader.error);
            reader.readAsArrayBuffer(this);
        });
    };
}

// Mock URL.createObjectURL / revokeObjectURL for JSDOM
if (typeof URL.createObjectURL !== 'function') {
    let blobCounter = 0;
    URL.createObjectURL = (_obj: Blob | MediaSource) => `blob:http://localhost/${++blobCounter}`;
}
if (typeof URL.revokeObjectURL !== 'function') {
    URL.revokeObjectURL = (_url: string) => {};
}

// Suppress known JSDOM quirk: HTMLBaseElement.href error when formatting
// DOM errors internally. This happens during AbortController-related operations
// in cancel tests and does not affect test correctness.
const originalOnError = globalThis.onerror;
globalThis.onerror = (message, _source, _lineno, _colno, error) => {
    if (error instanceof TypeError && String(error.message).includes('HTMLBaseElement')) {
        return true; // Suppress
    }
    if (typeof originalOnError === 'function') {
        return originalOnError(message as string, _source, _lineno, _colno, error);
    }
    return false;
};

// Polyfill DataTransfer for JSDOM (used by paste & drop tests)
if (typeof globalThis.DataTransfer === 'undefined') {
    class DataTransferItemList {
        private _items: { kind: string; type: string; file: File | null; data: string | null }[] = [];

        get length(): number {
            return this._items.length;
        }

        add(data: File): void;
        add(data: string, type: string): void;
        add(dataOrFile: File | string, type?: string): void {
            if (dataOrFile instanceof File) {
                this._items.push({ kind: 'file', type: dataOrFile.type, file: dataOrFile, data: null });
            } else {
                this._items.push({ kind: 'string', type: type ?? 'text/plain', file: null, data: dataOrFile });
            }
        }

        [Symbol.iterator](): Iterator<{ kind: string; type: string; getAsFile: () => File | null }> {
            let i = 0;
            const items = this._items;
            return {
                next() {
                    if (i >= items.length) return { done: true, value: undefined };
                    const item = items[i++];
                    return { done: false, value: { kind: item.kind, type: item.type, getAsFile: () => item.file } };
                },
            };
        }
    }

    class MockDataTransfer {
        items = new DataTransferItemList();

        get files(): FileList {
            const files: File[] = [];
            for (const item of this.items) {
                if (item.kind === 'file') {
                    const f = item.getAsFile();
                    if (f) files.push(f);
                }
            }
            const list = Object.assign(files, { item: (i: number) => files[i] ?? null });
            return list as unknown as FileList;
        }
    }

    // @ts-expect-error - JSDOM polyfill
    globalThis.DataTransfer = MockDataTransfer;
}

// Polyfill ClipboardEvent for JSDOM
if (typeof globalThis.ClipboardEvent === 'undefined') {
    class MockClipboardEvent extends Event {
        readonly clipboardData: DataTransfer | null;

        constructor(type: string, init?: EventInit & { clipboardData?: DataTransfer | null }) {
            super(type, init);
            this.clipboardData = init?.clipboardData ?? null;
        }
    }

    // @ts-expect-error - JSDOM polyfill
    globalThis.ClipboardEvent = MockClipboardEvent;
}

// Reset mocks before each test
beforeEach(() => {
    vi.clearAllMocks();
});
