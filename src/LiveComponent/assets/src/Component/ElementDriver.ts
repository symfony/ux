import {getModelDirectiveFromElement} from '../dom_utils';
import type LiveControllerDefault from '../live_controller';

export interface ElementDriver {
    getModelName(element: HTMLElement): string|null;

    getComponentProps(): any;

    /**
     * Given an element from a response, find all the events that should be emitted.
     */
    getEventsToEmit(): Array<{event: string, data: any, target: string|null, componentName: string|null }>;

    /**
     * Given an element from a response, find all the events that should be dispatched.
     */
    getBrowserEventsToDispatch(): Array<{event: string, payload: any }>;
}

export class StimulusElementDriver implements ElementDriver {
    private readonly controller: LiveControllerDefault;

    constructor(controller: LiveControllerDefault) {
        this.controller = controller;
    }

    getModelName(element: HTMLElement): string|null {
        const modelDirective = getModelDirectiveFromElement(element, false);

        if (!modelDirective) {
            return null;
        }

        return modelDirective.action;
    }

    getComponentProps(): any {
        return this.controller.propsValue;
    }

    getEventsToEmit(): Array<{event: string, data: any, target: string|null, componentName: string|null }> {
        return this.controller.eventsToEmitValue;
    }

    getBrowserEventsToDispatch(): Array<{event: string, payload: any }> {
        return this.controller.eventsToDispatchValue;
    }
}
