import {getModelDirectiveFromElement} from '../dom_utils';

export interface ElementDriver {
    getModelName(element: HTMLElement): string|null;

    getComponentProps(rootElement: HTMLElement): any;

    /**
     * Given an HtmlElement and a child id, find the root element for that child.
     */
    findChildComponentElement(id: string, element: HTMLElement): HTMLElement|null;

    /**
     * Given an element, find the "key" that should be used to identify it;
     */
    getKeyFromElement(element: HTMLElement): string|null;

    /**
     * Given an element from a response, find all the events that should be emitted.
     */
    getEventsToEmit(element: HTMLElement): Array<{event: string, data: any, target: string|null, componentName: string|null }>;

    /**
     * Given an element from a response, find all the events that should be dispatched.
     */
    getBrowserEventsToDispatch(element: HTMLElement): Array<{event: string, payload: any }>;
}

export class StandardElementDriver implements ElementDriver {
    getModelName(element: HTMLElement): string|null {
        const modelDirective = getModelDirectiveFromElement(element, false);

        if (!modelDirective) {
            return null;
        }

        return modelDirective.action;
    }

    getComponentProps(rootElement: HTMLElement): any {
        const propsJson = rootElement.dataset.livePropsValue ?? '{}';

        return JSON.parse(propsJson);
    }

    findChildComponentElement(id: string, element: HTMLElement): HTMLElement|null {
        return element.querySelector(`[data-live-id=${id}]`);
    }

    getKeyFromElement(element: HTMLElement): string|null {
        return element.dataset.liveId || null;
    }

    getEventsToEmit(element: HTMLElement): Array<{event: string, data: any, target: string|null, componentName: string|null }> {
        const eventsJson = element.dataset.liveEmit ?? '[]';

        return JSON.parse(eventsJson);
    }

    getBrowserEventsToDispatch(element: HTMLElement): Array<{event: string, payload: any }> {
        const eventsJson = element.dataset.liveBrowserDispatch ?? '[]';

        return JSON.parse(eventsJson);
    }
}
