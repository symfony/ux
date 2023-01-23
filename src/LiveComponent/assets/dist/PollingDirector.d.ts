/// <reference types="node" />
import Component from './Component';
export default class {
    component: Component;
    isPollingActive: boolean;
    polls: Array<{
        actionName: string;
        duration: number;
    }>;
    pollingIntervals: NodeJS.Timer[];
    constructor(component: Component);
    addPoll(actionName: string, duration: number): void;
    startAllPolling(): void;
    stopAllPolling(): void;
    clearPolling(): void;
    private initiatePoll;
}
