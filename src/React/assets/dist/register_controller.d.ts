import { ComponentClass, FunctionComponent } from "react";

//#region src/register_controller.d.ts
type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
declare global {
  function resolveReactComponent(name: string): Component;
  interface Window {
    resolveReactComponent(name: string): Component;
  }
}
declare function registerReactControllerComponents(context: __WebpackModuleApi.RequireContext): void;
//#endregion
export { registerReactControllerComponents };