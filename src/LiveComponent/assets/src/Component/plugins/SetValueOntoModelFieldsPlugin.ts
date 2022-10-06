import Component from '../index';
import {
    getModelDirectiveFromElement,
    getValueFromElement,
    setValueOnElement
} from '../../dom_utils';
import { PluginInterface } from './PluginInterface';

/**
 * Handles setting the "value" onto data-model fields automatically from the data store.
 */
export default class implements PluginInterface {
    attachToComponent(component: Component): void {
        this.synchronizeValueOfModelFields(component);
        component.on('render:finished', () => {
            this.synchronizeValueOfModelFields(component);
        });
    }

    /**
     * Sets the "value" of all model fields to the component data.
     *
     * This is called when the component initializes and after re-render.
     * Take the following element:
     *
     *      <input data-model="firstName">
     *
     * This method will set the "value" of that element to the value of
     * the "firstName" model.
     */
    private synchronizeValueOfModelFields(component: Component): void {
        component.element.querySelectorAll('[data-model]').forEach((element: Element) => {
            if (!(element instanceof HTMLElement)) {
                throw new Error('Invalid element using data-model.');
            }

            if (element instanceof HTMLFormElement) {
                return;
            }

            const modelDirective = getModelDirectiveFromElement(element);
            if (!modelDirective) {
                return;
            }

            const modelName = modelDirective.action;

            // skip any elements whose model name is currently in an unsynced state
            if (component.getUnsyncedModels().includes(modelName)) {
                return;
            }

            if (component.valueStore.has(modelName)) {
                setValueOnElement(element, component.valueStore.get(modelName))
            }

            // for select elements without a blank value, one might be selected automatically
            // https://github.com/symfony/ux/issues/469
            if (element instanceof HTMLSelectElement && !element.multiple) {
                component.valueStore.set(modelName, getValueFromElement(element, component.valueStore));
            }
        })
    }
}
