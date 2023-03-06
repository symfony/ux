import {getModelDirectiveFromElement} from '../dom_utils';

export interface ElementDriver {
    getModelName(element: HTMLElement): string|null;

    getComponentProps(rootElement: HTMLElement): { props: any, nestedProps: any };

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

    getComponentProps(rootElement: HTMLElement): any {
        const propsJson = rootElement.dataset.livePropsValue ?? '{}';
        const nestedPropsJson = rootElement.dataset.liveNestedPropsValue ?? '{}';

        return {
            props: JSON.parse(propsJson),
            nestedProps: JSON.parse(nestedPropsJson),
        }
    }

    findChildComponentElement(id: string, element: HTMLElement): HTMLElement|null {
        return element.querySelector(`[data-live-id=${id}]`);
    }

    getKeyFromElement(element: HTMLElement): string|null {
        return element.dataset.liveId || null;
    }
}
