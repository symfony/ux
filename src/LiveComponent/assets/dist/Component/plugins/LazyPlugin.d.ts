import type { PluginInterface } from './PluginInterface';
import type Component from '../index';
export default class implements PluginInterface {
    private intersectionObserver;
    attachToComponent(component: Component): void;
    private getObserver;
}
