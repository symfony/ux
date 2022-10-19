import Component from '../index';
import { parseDirectives } from '../../Directive/directives_parser';
import PollingDirector from '../../PollingDirector';
import { PluginInterface } from './PluginInterface';

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
            this.pollingDirector.stopAllPolling();
        });
        component.on('render:finished', () => {
            // re-start polling, in case polling changed
            this.initializePolling();
        });
    }

    addPoll(actionName: string, duration: number): void {
        this.pollingDirector.addPoll(actionName, duration);
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

            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'delay':
                        if (modifier.value) {
                            duration = parseInt(modifier.value);
                        }

                         break;
                    default:
                        console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
                }
            });

            this.addPoll(directive.action, duration);
        });
    }
}

