export interface ElementDriver {
    getModelName(element: HTMLElement): string | null;
    getComponentData(rootElement: HTMLElement): any;
    getComponentProps(rootElement: HTMLElement): any;
    findChildComponentElement(id: string, element: HTMLElement): HTMLElement | null;
    getKeyFromElement(element: HTMLElement): string | null;
}
export declare class StandardElementDriver implements ElementDriver {
    getModelName(element: HTMLElement): string | null;
    getComponentData(rootElement: HTMLElement): any;
    getComponentProps(rootElement: HTMLElement): any;
    findChildComponentElement(id: string, element: HTMLElement): HTMLElement | null;
    getKeyFromElement(element: HTMLElement): string | null;
}
