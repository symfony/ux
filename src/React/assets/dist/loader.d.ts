import { FunctionComponent, ComponentClass } from 'react';
import { ComponentCollection } from './components.js';

type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
declare global {
    function resolveReactComponent(name: string): Component;
    interface Window {
        resolveReactComponent(name: string): Component;
    }
}
declare function registerReactControllerComponents(reactComponents?: ComponentCollection): void;

export { registerReactControllerComponents };
