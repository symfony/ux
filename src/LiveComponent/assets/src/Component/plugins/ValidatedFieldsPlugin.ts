import type Component from '../index';
import type ValueStore from '../ValueStore';
import type { PluginInterface } from './PluginInterface';

export default class implements PluginInterface {
    attachToComponent(component: Component): void {
        component.on('model:set', (modelName: string) => {
            this.handleModelSet(modelName, component.valueStore);
        });
    }

    private handleModelSet(modelName: string, valueStore: ValueStore): void {
        if (valueStore.has('validatedFields')) {
            const validatedFields = [...valueStore.get('validatedFields')];
            if (!validatedFields.includes(modelName)) {
                validatedFields.push(modelName);
            }
            valueStore.set('validatedFields', validatedFields);
        }
    }
}
