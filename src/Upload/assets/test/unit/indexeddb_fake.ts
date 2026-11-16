type Store = { keyPath: string; records: Map<IDBValidKey, unknown> };

class FakeRequest {
    result: unknown;
    onsuccess: (() => void) | null = null;
    onerror: (() => void) | null = null;
    onupgradeneeded: (() => void) | null = null;
}

const settle = (request: FakeRequest, result: unknown): FakeRequest => {
    queueMicrotask(() => {
        request.result = result;
        request.onsuccess?.();
    });

    return request;
};

export class FakeIndexedDB {
    readonly databases = new Map<string, Map<string, Store>>();
    openConnections = 0;

    open(name: string): FakeRequest {
        const request = new FakeRequest();
        queueMicrotask(() => {
            const isNew = !this.databases.has(name);
            const stores = this.databases.get(name) ?? new Map<string, Store>();
            this.databases.set(name, stores);
            this.openConnections++;
            request.result = {
                createObjectStore: (store: string, options: { keyPath: string }) => {
                    stores.set(store, { keyPath: options.keyPath, records: new Map() });
                },
                transaction: (store: string) => ({ objectStore: () => this.objectStore(stores, store) }),
                close: () => {
                    this.openConnections--;
                },
            };
            if (isNew) {
                request.onupgradeneeded?.();
            }
            request.onsuccess?.();
        });

        return request;
    }

    records(database: string, store: string): unknown[] {
        return Array.from(this.databases.get(database)?.get(store)?.records.values() ?? []);
    }

    private objectStore(stores: Map<string, Store>, name: string) {
        const store = stores.get(name);
        if (!store) {
            throw new Error(`Object store "${name}" was never created`);
        }

        return {
            get: (key: IDBValidKey) => settle(new FakeRequest(), store.records.get(key)),
            put: (value: Record<string, IDBValidKey>) => {
                store.records.set(value[store.keyPath], structuredClone(value));

                return settle(new FakeRequest(), value[store.keyPath]);
            },
            delete: (key: IDBValidKey) => {
                store.records.delete(key);

                return settle(new FakeRequest(), undefined);
            },
        };
    }
}
