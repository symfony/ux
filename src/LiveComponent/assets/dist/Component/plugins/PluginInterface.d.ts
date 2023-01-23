import Component from '../index';
export interface PluginInterface {
    attachToComponent(component: Component): void;
}
