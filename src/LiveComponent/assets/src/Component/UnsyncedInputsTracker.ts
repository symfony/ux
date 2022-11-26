import {ElementDriver} from './ElementDriver';
import {elementBelongsToThisComponent} from '../dom_utils';
import Component from './index';

export default class {
    private readonly component: Component;
    private readonly modelElementResolver: ElementDriver;
    /** Fields that have changed, but whose value is not set back onto the value store */
    private readonly unsyncedInputs: UnsyncedInputContainer;

    private elementEventListeners: Array<{ event: string, callback: (event: any) => void }> = [
        { event: 'input', callback: (event) => this.handleInputEvent(event) },
    ];

    constructor(component: Component, modelElementResolver: ElementDriver) {
        this.component = component;
        this.modelElementResolver = modelElementResolver;
        this.unsyncedInputs = new UnsyncedInputContainer();
    }

    activate(): void {
        this.elementEventListeners.forEach(({event, callback}) => {
            this.component.element.addEventListener(event, callback);
        });
    }

    deactivate(): void {
        this.elementEventListeners.forEach(({event, callback}) => {
            this.component.element.removeEventListener(event, callback);
        });
    }

    markModelAsSynced(modelName: string): void {
        this.unsyncedInputs.markModelAsSynced(modelName);
    }

    private handleInputEvent(event: Event) {
        const target = event.target as Element;
        if (!target) {
            return;
        }

        this.updateModelFromElement(target)
    }

    private updateModelFromElement(element: Element) {
        if (!elementBelongsToThisComponent(element, this.component)) {
            return;
        }

        if (!(element instanceof HTMLElement)) {
            throw new Error('Could not update model for non HTMLElement');
        }

        const modelName = this.modelElementResolver.getModelName(element);
        // track any inputs that are "unsynced"
        this.unsyncedInputs.add(element, modelName);
    }

    getUnsyncedInputs(): HTMLElement[] {
        return this.unsyncedInputs.allUnsyncedInputs();
    }

    getUnsyncedModels(): string[] {
        return Array.from(this.unsyncedInputs.getUnsyncedModelNames());
    }

    resetUnsyncedFields(): void {
        this.unsyncedInputs.resetUnsyncedFields();
    }
}

/**
 * Tracks field & models whose values are "unsynced".
 *
 * For a model, unsynced means that the value has been updated inside of
 * a field (e.g. an input), but that this new value hasn't
 * yet been set onto the actual model data. It is "unsynced"
 * from the underlying model data.
 *
 * For a field, unsynced means that it is "modified on the client side". In
 * other words, the field's value in the browser would be different than the
 * one returned from the server. This can happen because a field has no model
 * (and so it is permanently unsynced once changed) or the field has been changed
 * and the corresponding model has not yet been sent to the server.
 *
 * Note: a "model" can become synced when that value is set back
 * onto the data store. But the corresponding field will
 * remain unsynced until the next Ajax call starts.
 */
export class UnsyncedInputContainer {
    private unsyncedModelFields: Map<string, HTMLElement>;
    private unsyncedNonModelFields: Array<HTMLElement> = [];
    private unsyncedModelNames: Array<string> = [];

    constructor() {
        this.unsyncedModelFields = new Map();
    }

    add(element: HTMLElement, modelName: string|null = null) {
        if (modelName) {
            this.unsyncedModelFields.set(modelName, element);
            if (!this.unsyncedModelNames.includes(modelName)) {
                this.unsyncedModelNames.push(modelName);
            }

            return;
        }

        this.unsyncedNonModelFields.push(element);
    }

    /**
     * Mark all fields as synced, except for those not bound to a model or whose
     * values are still dirty.
     */
    resetUnsyncedFields(): void {
        // clear out all unsynced fields, except those where the value is still unsynced
        this.unsyncedModelFields.forEach((value, key) => {
            if (!this.unsyncedModelNames.includes(key)) {
                this.unsyncedModelFields.delete(key);
            }
        });
    }

    allUnsyncedInputs(): HTMLElement[] {
        return [...this.unsyncedNonModelFields, ...this.unsyncedModelFields.values()]
    }

    markModelAsSynced(modelName: string): void {
        const index = this.unsyncedModelNames.indexOf(modelName);
        if (index !== -1) {
            this.unsyncedModelNames.splice(index, 1);
        }
    }

    /**
     * Returns a list of models whose fields have been modified, but whose values
     * have not yet been set onto the data store.
     */
    getUnsyncedModelNames(): string[] {
        return this.unsyncedModelNames;
    }
}
