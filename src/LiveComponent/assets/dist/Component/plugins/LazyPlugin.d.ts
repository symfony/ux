import type Component from '../index';
import type { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    private intersectionObserver;
    attachToComponent(component: Component): void;
    private getObserver;
}
