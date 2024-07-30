import type { ElementDriver } from './ElementDriver';
import type Component from './index';
export default class {
    private readonly component;
    private readonly modelElementResolver;
    private readonly unsyncedInputs;
    private elementEventListeners;
    constructor(component: Component, modelElementResolver: ElementDriver);
    activate(): void;
    deactivate(): void;
    markModelAsSynced(modelName: string): void;
    private handleInputEvent;
    private updateModelFromElement;
    getUnsyncedInputs(): HTMLElement[];
    getUnsyncedModels(): string[];
    resetUnsyncedFields(): void;
}
export declare class UnsyncedInputContainer {
    private unsyncedModelFields;
    private unsyncedNonModelFields;
    private unsyncedModelNames;
    constructor();
    add(element: HTMLElement, modelName?: string | null): void;
    resetUnsyncedFields(): void;
    allUnsyncedInputs(): HTMLElement[];
    markModelAsSynced(modelName: string): void;
    getUnsyncedModelNames(): string[];
}
