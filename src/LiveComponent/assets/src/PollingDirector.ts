import type Component from './Component';

export interface PollConfig {
    actionName: string;
    duration: number;
    limit: number;
    count: number;
    visibilityMode: 'component' | 'page' | false;
}

export default class {
    component: Component;
    isPollingActive = true;
    polls: PollConfig[] = [];
    pollingIntervals: Array<{ actionName: string; timer: number }> = [];
    isPageVisible = true;
    isComponentIntersecting = true;
    observer?: IntersectionObserver;

    // Memory dictionary to keep track of poll counts across component re-renders
    private memoryCounts: Record<string, number> = {};
    private readonly visibilityChangeListener: () => void;

    constructor(component: Component) {
        this.component = component;
        this.isPageVisible = !document.hidden;

        // 1. Listen for page visibility changes (tab switching
        this.visibilityChangeListener = () => {
            this.isPageVisible = !document.hidden;
            this.evaluatePollingStates();
        };

        document.addEventListener('visibilitychange', this.visibilityChangeListener);

        // 2. Observe component visibility in the viewport
        if (typeof IntersectionObserver !== 'undefined') {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    this.isComponentIntersecting = entry.isIntersecting;
                    this.evaluatePollingStates();
                });
            });
            this.observer.observe(this.component.element);
        }
    }

    addPoll(
        actionName: string,
        duration: number,
        limit: number = 0,
        visibilityMode: 'component' | 'page' | false = false
    ) {
        // Retrieve count from memory if it exists, otherwise start at 0
        const count = this.memoryCounts[actionName] || 0;
        const pollConfig: PollConfig = { actionName, duration, limit, count, visibilityMode };
        this.polls.push(pollConfig);

        if (this.isPollingActive) {
            this.evaluatePollingStates();
        }
    }

    startAllPolling(): void {
        if (this.isPollingActive) return;
        this.isPollingActive = true;
        // Note: memoryCounts is NOT reset here to preserve limits across re-renders
        this.evaluatePollingStates();
    }

    private evaluatePollingStates(): void {
        if (!this.isPollingActive) return;

        this.polls.forEach((poll) => {
            // Skip if the limit has already been reached
            if (poll.limit > 0 && poll.count >= poll.limit) {
                return;
            }

            let shouldRun = true;

            if (poll.visibilityMode === 'page') {
                shouldRun = this.isPageVisible;
            } else if (poll.visibilityMode === 'component') {
                shouldRun = this.isPageVisible && this.isComponentIntersecting;
            }

            const isRunning = this.pollingIntervals.some((i) => i.actionName === poll.actionName);

            // Start polling if conditions are met and it's not currently running
            if (shouldRun && !isRunning) {
                this.initiatePoll(poll);
            } else if (!shouldRun && isRunning) {
                // Stop polling if conditions are no longer met
                this.stopPolling(poll.actionName);
            }
        });
    }

    stopAllPolling(): void {
        this.isPollingActive = false;
        this.pollingIntervals.forEach(({ timer }) => {
            window.clearInterval(timer);
        });
        this.pollingIntervals = [];
    }

    stopPolling(actionName: string): void {
        this.pollingIntervals = this.pollingIntervals.filter(({ actionName: intervalAction, timer }) => {
            if (intervalAction === actionName) {
                window.clearInterval(timer);
                return false;
            }
            return true;
        });
    }

    clearPolling(): void {
        this.stopAllPolling();
        this.polls = [];
        this.startAllPolling();
    }

    private initiatePoll(poll: PollConfig): void {
        let callback: () => void =
            poll.actionName === '$render'
                ? () => this.component.render()
                : () => this.component.action(poll.actionName, {}, 0);

        const timer = window.setInterval(() => {
            callback();

            // Check and increment the limit inside the interval
            if (poll.limit > 0) {
                poll.count++;
                this.memoryCounts[poll.actionName] = poll.count;

                // Target limit reached, stop this specific poll
                if (poll.count >= poll.limit) {
                    this.stopPolling(poll.actionName);
                }
            }
        }, poll.duration);

        this.pollingIntervals.push({ actionName: poll.actionName, timer });
    }

    /**
     * Cleans up event listeners and observers when the component is disconnected.
     * Prevents memory leaks in SPA or Turbo Drive environments.
     */
    destroy(): void {
        document.removeEventListener('visibilitychange', this.visibilityChangeListener);
        if (this.observer) {
            this.observer.disconnect();
        }
        this.stopAllPolling();
    }
}
