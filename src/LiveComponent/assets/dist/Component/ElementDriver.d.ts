import type LiveControllerDefault from '../live_controller';
export interface ElementDriver {
    getModelName(element: HTMLElement): string | null;
    getComponentProps(): any;
    getEventsToEmit(): Array<{
        event: string;
        data: any;
        target: string | null;
        componentName: string | null;
    }>;
    getBrowserEventsToDispatch(): Array<{
        event: string;
        payload: any;
    }>;
}
export declare class StimulusElementDriver implements ElementDriver {
    private readonly controller;
    constructor(controller: LiveControllerDefault);
    getModelName(element: HTMLElement): string | null;
    getComponentProps(): any;
    getEventsToEmit(): Array<{
        event: string;
        data: any;
        target: string | null;
        componentName: string | null;
    }>;
    getBrowserEventsToDispatch(): Array<{
        event: string;
        payload: any;
    }>;
}
