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

    allMappedFields(): Map<string, HTMLElement> {
        return this.#mappedFields;
    }

    remove(modelName: string) {
        this.#mappedFields.delete(modelName);
    }
}
