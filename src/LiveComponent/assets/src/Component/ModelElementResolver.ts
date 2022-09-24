import {getModelDirectiveFromElement} from "../dom_utils";

export interface ModelElementResolver {
    getModelName(element: HTMLElement): string|null;
}

export class DataModelElementResolver implements ModelElementResolver {
    getModelName(element: HTMLElement): string|null {
        const modelDirective = getModelDirectiveFromElement(element, false);

        if (!modelDirective) {
            return null;
        }

        return modelDirective.action;
    }
}
