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

        // access from stimulus_controller
        (component as any).pollingDirector = this.pollingDirector;

        component.on('connect', () => {
            this.pollingDirector.startAllPolling();
        });
        component.on('disconnect', () => {
            this.pollingDirector.stopAllPolling();
        });
        component.on('render:finished', () => {
            // re-start polling, in case polling changed
            this.initializePolling();
        });
    }

    addPoll(actionName: string, duration: number, limit: number): void {
        this.pollingDirector.addPoll(actionName, duration, limit);
    }

    clearPolling(): void {
        this.pollingDirector.clearAllPolling(true);
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

            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'delay':
                        if (modifier.value) {
                            const parsed = Number.parseInt(modifier.value);
                            duration = Number.isNaN(parsed) || parsed <= 0 ? 2000 : parsed;
                        }
                        break;
                    case 'limit':
                        if (modifier.value) {
                            const parsed = Number.parseInt(modifier.value);
                            limit = Number.isNaN(parsed) || parsed <= 0 ? 1 : parsed;
                        }
                        break;
                    default:
                        console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
                }
            });

            this.addPoll(directive.action, duration, limit);
        });
    }
}
