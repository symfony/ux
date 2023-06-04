import Component from './Component';
import { getElementAsTagText } from './dom_utils';

export default class {
    private componentMapByElement = new WeakMap<HTMLElement, Component>();
    /**
     * The value is the component's name.
     */
    private componentMapByComponent = new Map<Component, string>();

    public registerComponent(element: HTMLElement, component: Component) {
        this.componentMapByElement.set(element, component);
        this.componentMapByComponent.set(component, component.name);
    }

    public unregisterComponent(component: Component) {
        this.componentMapByElement.delete(component.element);
        this.componentMapByComponent.delete(component);
    }

    public getComponent(element: HTMLElement): Promise<Component> {
        return new Promise((resolve, reject) => {
            let count = 0;
            const maxCount = 10;
            const interval = setInterval(() => {
                const component = this.componentMapByElement.get(element);
                if (component) {
                    clearInterval(interval);
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

    /**
     * Returns a filtered list of all the currently-registered components
     */
    findComponents(currentComponent: Component, onlyParents: boolean, onlyMatchName: string | null): Component[] {
        const components: Component[] = [];
        this.componentMapByComponent.forEach((componentName: string, component: Component) => {
            if (
                onlyParents &&
                (currentComponent === component || !component.element.contains(currentComponent.element))
            ) {
                return;
            }

            if (onlyMatchName && componentName !== onlyMatchName) {
                return;
            }

            components.push(component);
        });

        return components;
    }
}
