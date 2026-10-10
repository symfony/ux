export interface ResumeRecord {
    fingerprint: string;
    token: string;
    expiresAt: number;
}

export class ResumeStore {
    private readonly database = 'symfony-ux-upload';
    private readonly store = 'resume';

    async get(fingerprint: string): Promise<ResumeRecord | null> {
        const db = await this.open();
        if (!db) return null;
        try {
            return await new Promise<ResumeRecord | null>((resolve) => {
                const request = db.transaction(this.store, 'readonly').objectStore(this.store).get(fingerprint);
                request.onsuccess = () => {
                    const record = request.result as ResumeRecord | undefined;
                    resolve(record && record.expiresAt > Date.now() ? record : null);
                };
                request.onerror = () => resolve(null);
            });
        } finally {
            db.close();
        }
    }

    async put(record: ResumeRecord): Promise<void> {
        const db = await this.open();
        if (!db) return;
        try {
            await new Promise<void>((resolve) => {
                const request = db.transaction(this.store, 'readwrite').objectStore(this.store).put(record);
                request.onsuccess = () => resolve();
                request.onerror = () => resolve();
            });
        } finally {
            db.close();
        }
    }

    async delete(fingerprint: string): Promise<void> {
        const db = await this.open();
        if (!db) return;
        try {
            await new Promise<void>((resolve) => {
                const request = db.transaction(this.store, 'readwrite').objectStore(this.store).delete(fingerprint);
                request.onsuccess = () => resolve();
                request.onerror = () => resolve();
            });
        } finally {
            db.close();
        }
    }

    private async open(): Promise<IDBDatabase | null> {
        if (!('indexedDB' in globalThis)) return null;
        return new Promise((resolve) => {
            const request = indexedDB.open(this.database, 1);
            request.onupgradeneeded = () => request.result.createObjectStore(this.store, { keyPath: 'fingerprint' });
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(null);
        });
    }
}
