import Component from '../index';
import { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    private isConnected;
    attachToComponent(component: Component): void;
}
