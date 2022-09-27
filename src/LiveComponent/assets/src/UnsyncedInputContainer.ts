/**
 * Tracks field & models whose values are "unsynced".
 *
 * Unsynced means that the value has been updated inside of
 * a field (e.g. an input), but that this new value hasn't
 * yet been set onto the actual model data. It is "unsynced"
 * from the underlying model data.
 */
export default class UnsyncedInputContainer {
    #mappedFields: Map<string, HTMLElement>;
    #unmappedFields: Array<HTMLElement> = [];

    constructor() {
        this.#mappedFields = new Map();
    }

    add(element: HTMLElement, modelName: string|null = null) {
        if (modelName) {
            this.#mappedFields.set(modelName, element);

            return;
        }

        this.#unmappedFields.push(element);
    }

    all() {
        return [...this.#unmappedFields, ...this.#mappedFields.values()]
    }

    markModelAsSynced(modelName: string): void {
        this.#mappedFields.delete(modelName);
    }

    getModifiedModels(): string[] {
        return Array.from(this.#mappedFields.keys());
    }
}
