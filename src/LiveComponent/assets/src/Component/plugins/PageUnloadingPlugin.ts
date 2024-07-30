import type Component from '../index';
import type { PluginInterface } from './PluginInterface';

export default class implements PluginInterface {
    private isConnected = false;

    attachToComponent(component: Component): void {
        component.on('render:started', (html: string, response: Response, controls: { shouldRender: boolean }) => {
            if (!this.isConnected) {
                controls.shouldRender = false;
            }
        });

        component.on('connect', () => {
            this.isConnected = true;
        });

        component.on('disconnect', () => {
            this.isConnected = false;
        });
    }
}
