import Component from './Component';
export default class {
    private componentMapByElement;
    private componentMapByComponent;
    registerComponent(element: HTMLElement, component: Component): void;
    unregisterComponent(component: Component): void;
    getComponent(element: HTMLElement): Promise<Component>;
    findComponents(currentComponent: Component, onlyParents: boolean, onlyMatchName: string | null): Component[];
}
