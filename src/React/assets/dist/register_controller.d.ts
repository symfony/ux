import { FunctionComponent, ComponentClass } from 'react';

type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
declare global {
    function resolveReactComponent(name: string): Component;
    interface Window {
        resolveReactComponent(name: string): Component;
    }
}
declare function registerReactControllerComponents(context: __WebpackModuleApi.RequireContext): void;

export { registerReactControllerComponents };
