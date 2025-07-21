import { ComponentClass, FunctionComponent } from "react";

//#region src/components.d.ts
type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
interface ComponentCollection {
  [key: string]: Component;
}
declare const components: ComponentCollection;
//#endregion
export { ComponentCollection, components };