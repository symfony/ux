import Component from './Component';
declare class ComponentRegistry {
    private components;
    registerComponent(element: HTMLElement, definition: Component): void;
    unregisterComponent(element: HTMLElement): void;
    getComponent(element: HTMLElement): Promise<Component>;
}
declare const _default: ComponentRegistry;
export default _default;
