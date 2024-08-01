import type Component from '../../Component';
import type { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    private readonly component;
    private parentModelBindings;
    constructor(component: Component);
    attachToComponent(component: Component): void;
    private getChildrenFingerprints;
    private notifyParentModelChange;
    private getChildren;
}
