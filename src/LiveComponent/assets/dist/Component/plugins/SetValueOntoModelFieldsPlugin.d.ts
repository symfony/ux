import Component from '../index';
import { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    attachToComponent(component: Component): void;
    private synchronizeValueOfModelFields;
}
