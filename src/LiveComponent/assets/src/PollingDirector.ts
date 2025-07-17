import type Component from './Component';

export default class {
    component: Component;
    isPollingActive = true;
    polls: Array<{ actionName: string; duration: number }>;
    pollingIntervals: number[] = [];

    constructor(component: Component) {
        this.component = component;
    }

    addPoll(actionName: string, duration: number) {
        this.polls.push({ actionName, duration });

        if (this.isPollingActive) {
            this.initiatePoll(actionName, duration);
        }
    }

    startAllPolling(): void {
        if (this.isPollingActive) {
            return; // already active!
        }

        this.isPollingActive = true;
        this.polls.forEach(({ actionName, duration }) => {
            this.initiatePoll(actionName, duration);
        });
    }

    stopAllPolling(): void {
        this.isPollingActive = false;
        this.pollingIntervals.forEach((interval) => {
            clearInterval(interval);
        });
    }

    clearPolling(): void {
        this.stopAllPolling();
        this.polls = [];
        // set back to "is polling" status
        this.startAllPolling();
    }

    private initiatePoll(actionName: string, duration: number): void {
        let callback: () => void;
        if (actionName === '$render') {
            callback = () => {
                this.component.render();
            };
        } else {
            callback = () => {
                this.component.action(actionName, {}, 0);
            };
        }

        const timer = window.setInterval(() => {
            callback();
        }, duration);
        this.pollingIntervals.push(timer);
    }
}
