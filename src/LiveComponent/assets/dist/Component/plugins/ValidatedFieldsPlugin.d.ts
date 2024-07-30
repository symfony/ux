import type Component from '../index';
import type { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    attachToComponent(component: Component): void;
    private handleModelSet;
}
