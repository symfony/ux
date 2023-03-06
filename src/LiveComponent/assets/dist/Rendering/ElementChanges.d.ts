export default class ElementChanges {
    private addedClasses;
    private removedClasses;
    private styleChanges;
    private attributeChanges;
    addClass(className: string): void;
    removeClass(className: string): void;
    addStyle(styleName: string, newValue: string, originalValue: string | null): void;
    removeStyle(styleName: string, originalValue: string): void;
    addAttribute(attributeName: string, newValue: string, originalValue: string | null): void;
    removeAttribute(attributeName: string, originalValue: string | null): void;
    getAddedClasses(): string[];
    getRemovedClasses(): string[];
    getChangedStyles(): {
        name: string;
        value: string;
    }[];
    getRemovedStyles(): string[];
    getChangedAttributes(): {
        name: string;
        value: string;
    }[];
    getRemovedAttributes(): string[];
    applyToElement(element: HTMLElement): void;
    isEmpty(): boolean;
}
