import type Component from './Component';

export default class {
    component: Component;
    pollingIntervals: Map<string, number> = new Map(); // actionName → intervalId
    pollingCounts: Map<string, number> = new Map(); // actionName → current count
    pollingConfigs: Map<string, { duration: number; limit: number }> = new Map(); // All Polling's Map
    pollingStates: Map<string, 'active' | 'paused' | 'stopped'> = new Map(); // All Polling's States Map
    isPollingRunnig: Map<string, boolean> = new Map(); // Lock system for active polling

    constructor(component: Component) {
        this.component = component;
    }

    addPoll(actionName: string, duration: number, limit = 0) {
        this.pollingConfigs.set(actionName, { duration, limit });

        if (!this.pollingStates.has(actionName)) {
            this.pollingStates.set(actionName, 'active');
        }
        if (!this.pollingCounts.has(actionName)) {
            this.pollingCounts.set(actionName, 0);
        }
        this.initiatePoll(actionName, duration);
    }

    /**
     * Start All Pollings In PollingStates Map Entires
     */
    startAllPolling(): void {
        // this.clearAllPolling(true);
        for (const [actionName] of this.pollingConfigs.entries()) {
            this.start(actionName);
        }
    }

    /**
     * Stop All Pollings In PollingStates Map Entires
     */
    stopAllPolling(): void {
        for (const [actionName] of this.pollingConfigs.entries()) {
            this.stop(actionName);
        }
    }

    /**
     * Stop All Pollings and Clear All Pollings Data
     */
    clearAllPolling(soft = false): void {
        for (const intervalId of this.pollingIntervals.values()) {
            clearTimeout(intervalId);
        }

        this.pollingIntervals.clear();
        this.pollingConfigs.clear();
        if (!soft) {
            this.pollingStates.clear();
            this.pollingCounts.clear();
        }
    }

    private initiatePoll(actionName: string, duration: number): void {
        this.isPollingRunnig.set(actionName, false);
        const callback = async () => {
            if (this.isPollingRunnig.get(actionName)) return;
            this.isPollingRunnig.set(actionName, true);

            const limit = this.pollingConfigs.get(actionName)?.limit ?? 0;
            const currentCount = this.pollingCounts.get(actionName) ?? 0;

            if (currentCount === 0) {
                this.component.triggerPollHook('poll:started', { actionName, limit });
            }
            this.pollingCounts.set(actionName, currentCount + 1);

            if (limit > 0 && currentCount >= limit) {
                this.stop(actionName);
                return;
            }

            try {
                if (actionName === '$render') {
                    await this.component.render();
                } else {
                    const response = await this.component.action(actionName, {}, 0);

                    if (response?.response?.status === 500) {
                        this.stop(actionName);
                        throw new Error(this.decodeErrorMessage(await response.getBody()));
                    }
                }
                this.component.triggerPollHook('poll:running', {
                    actionName: actionName,
                    count: currentCount + 1,
                    limit: limit,
                });
            } catch (error) {
                this.component.triggerPollHook('poll:error', {
                    actionName: actionName,
                    finalCount: currentCount + 1,
                    limit: limit,
                    errorMessage: error instanceof Error ? error.message : String(error),
                });
            } finally {
                this.isPollingRunnig.set(actionName, false);
            }
        };

        const intervalId = window.setInterval(() => {
            if (this.pollingStates.get(actionName) !== 'active') {
                clearInterval(intervalId);
                this.pollingIntervals.delete(actionName);
                return;
            }

            callback().catch((e) => console.error(e));
        }, duration);

        this.pollingIntervals.set(actionName, intervalId);
    }

    /**
     * Pause Polling by action Name
     * Pause if polling's status is active only
     */
    pause(actionName = '$render'): void {
        if (this.pollingStates.get(actionName) !== 'active') return;

        const intervalId = this.pollingIntervals.get(actionName);
        if (intervalId !== undefined) {
            clearInterval(intervalId);
            this.pollingIntervals.delete(actionName);
        }
        this.pollingStates.set(actionName, 'paused');
        const count = this.pollingCounts.get(actionName) ?? 0;
        const limit = this.pollingConfigs.get(actionName)?.limit ?? 0;
        this.component.triggerPollHook('poll:paused', { actionName, count, limit });
    }

    /**
     * Resume Polling by action Name
     * Resume if polling's status is paused only
     */
    resume(actionName = '$render'): void {
        const config = this.pollingConfigs.get(actionName);
        if (this.pollingStates.get(actionName) !== 'paused' || !config) {
            return;
        }
        this.pollingStates.set(actionName, 'active');
        this.initiatePoll(actionName, config.duration);
    }

    /**
     * Stop Polling by action Name
     * Stop if polling's status is active or paused
     */
    stop(actionName = '$render'): void {
        const state = this.pollingStates.get(actionName);
        if (state !== 'active' && state !== 'paused') {
            return;
        }
        const intervalId = this.pollingIntervals.get(actionName);
        if (intervalId !== undefined) {
            clearInterval(intervalId);
            this.pollingIntervals.delete(actionName);
        }

        const currentCount = this.pollingCounts.get(actionName) ?? 1;
        const limit = this.pollingConfigs.get(actionName)?.limit ?? 0;
        this.component.triggerPollHook('poll:stopped', {
            actionName: actionName,
            finalCount: currentCount,
            limit: limit,
        });

        this.pollingCounts.delete(actionName);
        this.pollingStates.set(actionName, 'stopped');
    }

    /**
     * Start Polling by action Name
     * Start if polling's status is stopped only
     */
    start(actionName = '$render'): void {
        const config = this.pollingConfigs.get(actionName);
        if (!config || this.pollingStates.get(actionName) !== 'stopped') return;

        this.clearForAction(actionName);
        this.pollingCounts.set(actionName, 0);
        this.pollingStates.set(actionName, 'active');

        this.initiatePoll(actionName, config.duration);
    }

    /**
     * Clear Polling Count and Interval data by action Name
     */
    clearForAction(actionName = '$render') {
        this.pollingCounts.delete(actionName);
        const intervalId = this.pollingIntervals.get(actionName);
        if (intervalId !== undefined) {
            clearInterval(intervalId);
            this.pollingIntervals.delete(actionName);
        }
    }

    /**
     * Decode Error Message
     */
    decodeErrorMessage(errorMessage: string): string {
        errorMessage = errorMessage.split('<!--')[1]?.split('-->')[0]?.trim();
        if (errorMessage) {
            const decoded = errorMessage
                .replace(/&quot;/g, '"')
                .replace(/&#039;/g, "'")
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&amp;/g, '&');
            return `Poll·error:·${decoded}`;
        }
        return 'Poll error: 500 Internal Server Error';
    }
}
