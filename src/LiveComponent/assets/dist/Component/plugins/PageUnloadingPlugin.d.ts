import type Component from '../index';
import type { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    private isConnected;
    attachToComponent(component: Component): void;
}
