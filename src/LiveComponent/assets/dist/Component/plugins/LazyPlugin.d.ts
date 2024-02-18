import { PluginInterface } from './PluginInterface';
import Component from '../index';
export default class implements PluginInterface {
    private intersectionObserver;
    attachToComponent(component: Component): void;
    private getObserver;
}
