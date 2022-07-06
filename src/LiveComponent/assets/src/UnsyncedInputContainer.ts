export default class UnsyncedInputContainer {
    #mappedFields: Map<string, HTMLElement>;
    #unmappedFields: Array<HTMLElement>= [];

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

    clone(): UnsyncedInputContainer {
        const container = new UnsyncedInputContainer();
        container.#mappedFields = new Map(this.#mappedFields);
        container.#unmappedFields = [...this.#unmappedFields];

        return container;
    }

    allMappedFields(): Map<string, HTMLElement> {
        return this.#mappedFields;
    }

    remove(modelName: string) {
        this.#mappedFields.delete(modelName);
    }
}
