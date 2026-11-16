import { beforeEach, vi } from 'vitest';

globalThis.fetch = vi.fn();

beforeEach(() => {
    vi.resetAllMocks();
});

// jsdom has no DataTransfer nor ClipboardEvent
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

if (typeof globalThis.ClipboardEvent === 'undefined') {
    class MockClipboardEvent extends Event {
        readonly clipboardData: DataTransfer | null;

        constructor(type: string, init?: EventInit & { clipboardData?: DataTransfer | null }) {
            super(type, init);
            this.clipboardData = init?.clipboardData ?? null;
        }
    }

    globalThis.ClipboardEvent = MockClipboardEvent;
}
