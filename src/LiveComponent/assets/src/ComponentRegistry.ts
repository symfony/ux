import Component from './Component';
import { getElementAsTagText } from './dom_utils';

class ComponentRegistry {
    private components = new WeakMap<HTMLElement, Component>();

    public registerComponent(element: HTMLElement, definition: Component) {
        this.components.set(element, definition);
    }

    public unregisterComponent(element: HTMLElement) {
        this.components.delete(element);
    }

    public getComponent(element: HTMLElement): Promise<Component> {
        return new Promise((resolve, reject) => {
            let count = 0;
            const maxCount = 10;
            const interval = setInterval(() => {
                const component = this.components.get(element);
                if (component) {
                    resolve(component);
                }
                count++;

                if (count > maxCount) {
                    clearInterval(interval);
                    reject(new Error(`Component not found for element ${getElementAsTagText(element)}`));
                }
            }, 5);
        });
    }
}

export default new ComponentRegistry();
