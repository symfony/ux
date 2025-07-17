import type Component from '../index';
import type { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    private element;
    private pollingDirector;
    attachToComponent(component: Component): void;
    addPoll(actionName: string, duration: number): void;
    clearPolling(): void;
    private initializePolling;
}
