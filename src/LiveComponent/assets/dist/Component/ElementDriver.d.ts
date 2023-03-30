export interface ElementDriver {
    getModelName(element: HTMLElement): string | null;
    getComponentProps(rootElement: HTMLElement): any;
    findChildComponentElement(id: string, element: HTMLElement): HTMLElement | null;
    getKeyFromElement(element: HTMLElement): string | null;
    getEventsToEmit(element: HTMLElement): Array<{
        event: string;
        data: any;
        target: string | null;
        componentName: string | null;
    }>;
}
export declare class StandardElementDriver implements ElementDriver {
    getModelName(element: HTMLElement): string | null;
    getComponentProps(rootElement: HTMLElement): any;
    findChildComponentElement(id: string, element: HTMLElement): HTMLElement | null;
    getKeyFromElement(element: HTMLElement): string | null;
    getEventsToEmit(element: HTMLElement): Array<{
        event: string;
        data: any;
        target: string | null;
        componentName: string | null;
    }>;
}
