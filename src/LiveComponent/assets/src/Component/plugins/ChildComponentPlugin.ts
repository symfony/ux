import Component from '../../Component';
import { PluginInterface } from './PluginInterface';
import { ChildrenFingerprints } from '../../Backend/Backend';
import getModelBinding, { ModelBinding } from '../../Directive/get_model_binding';
import { getAllModelDirectiveFromElements } from '../../dom_utils';
import { findChildren, findParent } from '../../ComponentRegistry';

/**
 * Handles all interactions for child components of a component.
 *
 *      A) This parent component handling its children:
 *      * Sending children fingerprints to the server.
 *
 *      B) This child component handling its parent:
 *      * Notifying the parent of a model change.
 */
export default class implements PluginInterface {
    private readonly component: Component;
    private parentModelBindings: ModelBinding[] = [];

    constructor(component: Component) {
        this.component  = component;

        const modelDirectives = getAllModelDirectiveFromElements(this.component.element);
        this.parentModelBindings = modelDirectives.map(getModelBinding);
    }

    attachToComponent(component: Component): void {
        component.on('request:started', (requestData: any) => {
            requestData.children = this.getChildrenFingerprints();
        });

        component.on('model:set', (model: string, value: any) => {
            this.notifyParentModelChange(model, value);
        });
    }

    private getChildrenFingerprints(): ChildrenFingerprints {
        const fingerprints: ChildrenFingerprints = {};

        this.getChildren().forEach((child) => {
            if (!child.id) {
                throw new Error('missing id');
            }

            fingerprints[child.id] = {
                fingerprint: child.fingerprint as string,
                tag: child.element.tagName.toLowerCase(),
            };
        });

        return fingerprints;
    }

    /**
     * Notifies parent of a model change if desired.
     *
     * This makes the child "behave" like it's a normal `<input>` element,
     * where, when its value changes, the parent is notified.
     */
    private notifyParentModelChange(modelName: string, value: any): void {
        const parentComponent = findParent(this.component);

        if (!parentComponent) {
            return;
        }

        this.parentModelBindings.forEach((modelBinding) => {
            const childModelName = modelBinding.innerModelName || 'value';

            // skip, unless childModelName matches the model that just changed
            if (childModelName !== modelName) {
                return;
            }

            parentComponent.set(
                modelBinding.modelName,
                value,
                modelBinding.shouldRender,
                modelBinding.debounce
            );
        });
    }

    private getChildren(): Component[] {
        return findChildren(this.component);
    }
}
