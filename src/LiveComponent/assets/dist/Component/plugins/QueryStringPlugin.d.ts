import type Component from '../index';
import type { PluginInterface } from './PluginInterface';
interface QueryMapping {
    name: string;
}
export default class implements PluginInterface {
    private readonly mapping;
    constructor(mapping: {
        [p: string]: QueryMapping;
    });
    attachToComponent(component: Component): void;
}
export {};
