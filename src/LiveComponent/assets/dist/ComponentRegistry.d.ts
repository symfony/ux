import type Component from './Component';
export declare const resetRegistry: () => void;
export declare const registerComponent: (component: Component) => void;
export declare const unregisterComponent: (component: Component) => void;
export declare const getComponent: (element: HTMLElement) => Promise<Component>;
export declare const findComponents: (currentComponent: Component, onlyParents: boolean, onlyMatchName: string | null) => Component[];
export declare const findChildren: (currentComponent: Component) => Component[];
export declare const findParent: (currentComponent: Component) => Component | null;
