import { FunctionComponent, ComponentClass } from 'react';

type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
interface ComponentCollection {
    [key: string]: Component;
}
declare const components: ComponentCollection;

export { type ComponentCollection, components };
