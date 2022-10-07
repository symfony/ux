import {getModelDirectiveFromElement} from '../dom_utils';

export interface ElementDriver {
    getModelName(element: HTMLElement): string|null;

    /**
     * Given the root element of a component, returns its "data".
     *
     * This is used during a re-render to get the fresh data from the server.
     */
    getComponentData(rootElement: HTMLElement): any;

    getComponentProps(rootElement: HTMLElement): any;

    /**
     * Given an HtmlElement and a child id, find the root element for that child.
     */
    findChildComponentElement(id: string, element: HTMLElement): HTMLElement|null;

    /**
     * Given an element, find the "key" that should be used to identify it;
     */
    getKeyFromElement(element: HTMLElement): string|null;
}

export class StandardElementDriver implements ElementDriver {
    getModelName(element: HTMLElement): string|null {
        const modelDirective = getModelDirectiveFromElement(element, false);

        if (!modelDirective) {
            return null;
        }

        return modelDirective.action;
    }

    getComponentData(rootElement: HTMLElement): any {
        if (!rootElement.dataset.liveDataValue) {
            return null;
        }

        return JSON.parse(rootElement.dataset.liveDataValue as string);
    }

    getComponentProps(rootElement: HTMLElement): any {
        if (!rootElement.dataset.livePropsValue) {
            return null;
        }

        return JSON.parse(rootElement.dataset.livePropsValue as string);
    }

    findChildComponentElement(id: string, element: HTMLElement): HTMLElement|null {
        return element.querySelector(`[data-live-id=${id}]`);
    }

    getKeyFromElement(element: HTMLElement): string|null {
        return element.dataset.liveId || null;
    }
}
