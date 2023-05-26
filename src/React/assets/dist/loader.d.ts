import { ComponentCollection } from './components.js';
import { ComponentClass, FunctionComponent } from 'react';
type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
declare global {
    function resolveReactComponent(name: string): Component;
    interface Window {
        resolveReactComponent(name: string): Component;
    }
}
export declare function registerReactControllerComponents(reactComponents?: ComponentCollection): void;
export {};
