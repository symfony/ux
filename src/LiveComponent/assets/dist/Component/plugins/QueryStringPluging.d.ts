import Component from '../index';
import { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    private element;
    private mapping;
    attachToComponent(component: Component): void;
    private registerBindings;
    private updateUrl;
}
