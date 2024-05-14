import type Component from './Component';
import getElementAsTagText from './Util/getElementAsTagText';

let componentMapByElement = new WeakMap<HTMLElement, Component>();
/**
 * The value is the component's name.
 */
let componentMapByComponent = new Map<Component, string>();

export const resetRegistry = () => {
    componentMapByElement = new WeakMap<HTMLElement, Component>();
    componentMapByComponent = new Map<Component, string>();
};

export const registerComponent = (component: Component) => {
    componentMapByElement.set(component.element, component);
    componentMapByComponent.set(component, component.name);
};

export const unregisterComponent = (component: Component) => {
    componentMapByElement.delete(component.element);
    componentMapByComponent.delete(component);
};

export const getComponent = (element: HTMLElement): Promise<Component> =>
    new Promise((resolve, reject) => {
        let count = 0;
        const maxCount = 10;
        const interval = setInterval(() => {
            const component = componentMapByElement.get(element);
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

/**
 * Returns a filtered list of all the currently-registered components
 */
export const findComponents = (
    currentComponent: Component,
    onlyParents: boolean,
    onlyMatchName: string | null
): Component[] => {
    const components: Component[] = [];
    componentMapByComponent.forEach((componentName: string, component: Component) => {
        if (onlyParents && (currentComponent === component || !component.element.contains(currentComponent.element))) {
            return;
        }

        if (onlyMatchName && componentName !== onlyMatchName) {
            return;
        }

        components.push(component);
    });

    return components;
};

/**
 * Returns an array of components that are direct children of the given component.
 */
export const findChildren = (currentComponent: Component): Component[] => {
    const children: Component[] = [];
    componentMapByComponent.forEach((componentName: string, component: Component) => {
        if (currentComponent === component) {
            return;
        }

        if (!currentComponent.element.contains(component.element)) {
            return;
        }

        // check if there are any other components between the two
        let foundChildComponent = false;
        componentMapByComponent.forEach((childComponentName: string, childComponent: Component) => {
            if (foundChildComponent) {
                // return early
                return;
            }

            if (childComponent === component) {
                return;
            }

            if (childComponent.element.contains(component.element)) {
                foundChildComponent = true;
            }
        });

        children.push(component);
    });

    return children;
};

export const findParent = (currentComponent: Component): Component | null => {
    // recursively traverse the node tree up to find a parent
    let parentElement = currentComponent.element.parentElement;
    while (parentElement) {
        const component = componentMapByElement.get(parentElement);
        if (component) {
            return component;
        }

        parentElement = parentElement.parentElement;
    }

    return null;
};
