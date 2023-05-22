import { ComponentClass, FunctionComponent } from 'react';
type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
export interface ComponentCollection {
    [key: string]: Component;
}
export declare const components: ComponentCollection;
export {};
