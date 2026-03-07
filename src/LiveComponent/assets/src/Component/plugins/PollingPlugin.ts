import { parseDirectives } from '../../Directive/directives_parser';
import PollingDirector from '../../PollingDirector';
import type Component from '../index';
import type { PluginInterface } from './PluginInterface';

export default class implements PluginInterface {
    private element: Element;
    private pollingDirector: PollingDirector;

    attachToComponent(component: Component): void {
        this.element = component.element;
        this.pollingDirector = new PollingDirector(component);
        this.initializePolling();

        component.on('connect', () => {
            this.pollingDirector.startAllPolling();
        });
        component.on('disconnect', () => {
            // Clean up intervals, observers, and event listeners to prevent memory leaks
            this.pollingDirector.destroy();
        });
        component.on('render:finished', () => {
            // re-start polling, in case polling changed
            this.initializePolling();
        });

        // Listen for the stop-poll event dispatched from the server (PHP)
        this.element.addEventListener('live:stop-poll', ((event: CustomEvent) => {
            const actionToStop = event.detail?.action;
            if (actionToStop) {
                this.pollingDirector.stopPolling(actionToStop);
            } else {
                this.pollingDirector.stopAllPolling();
            }
        }) as EventListener);
    }

    addPoll(
        actionName: string,
        duration: number,
        limit: number,
        visibilityMode: 'component' | 'page' | false = false
    ): void {
        this.pollingDirector.addPoll(actionName, duration, limit, visibilityMode);
    }

    clearPolling(): void {
        this.pollingDirector.clearPolling();
    }

    private initializePolling(): void {
        this.clearPolling();

        if ((this.element as HTMLElement).dataset.poll === undefined) {
            return;
        }

        const rawPollConfig = (this.element as HTMLElement).dataset.poll;
        const directives = parseDirectives(rawPollConfig || '$render');

        directives.forEach((directive) => {
            let duration = 2000;
            let limit = 0;
            let visibilityMode: 'component' | 'page' | false = false; // Default: runs always in the background (BC)

            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'delay':
                        if (modifier.value) {
                            duration = Number.parseInt(modifier.value);
                        }
                        break;
                    case 'limit':
                        if (modifier.value) {
                            const parsed = Number.parseInt(modifier.value, 10);
                            // Fallback to 0 (infinite) if NaN or negative
                            limit = Number.isNaN(parsed) || parsed <= 0 ? 0 : parsed;
                        }
                        break;
                    case 'visible':
                        if (modifier.value === 'page') {
                            visibilityMode = 'page';
                        } else {
                            // Default to 'component' if only "visible" or "visible(component)" is provided
                            visibilityMode = 'component';
                        }
                        break;
                    default:
                        console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
                }
            });

            this.addPoll(directive.action, duration, limit, visibilityMode);
        });
    }
}
