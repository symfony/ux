import { ComponentCollection } from "./components.js";
import { ComponentClass, FunctionComponent } from "react";

//#region src/loader.d.ts
type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
declare global {
  function resolveReactComponent(name: string): Component;
  interface Window {
    resolveReactComponent(name: string): Component;
  }
}
declare function registerReactControllerComponents(reactComponents?: ComponentCollection): void;
//#endregion
export { registerReactControllerComponents };